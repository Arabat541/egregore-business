<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Reseller;
use App\Models\ResellerPayment;
use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairResellerDebtCommand extends Command
{
    protected $signature = 'repair:reseller-debt {reseller_id?}';
    protected $description = 'Répare la dette d\'un revendeur corrompue par l\'ancien bug d\'annulation (reset all)';

    public function handle(): int
    {
        $resellerId = $this->argument('reseller_id');

        if (!$resellerId) {
            $this->info('Recherche des revendeurs potentiellement corrompus...');
            $this->detectCorrupted();
            return 0;
        }

        $reseller = Reseller::find($resellerId);
        if (!$reseller) {
            $this->error("Revendeur #{$resellerId} introuvable.");
            return 1;
        }

        $this->repair($reseller);
        return 0;
    }

    private function detectCorrupted(): void
    {
        $cancelled = ResellerPayment::whereNotNull('cancelled_at')
            ->with('reseller')
            ->get();

        if ($cancelled->isEmpty()) {
            $this->info('Aucun paiement annulé trouvé.');
            return;
        }

        $this->table(
            ['ID', 'Revendeur', 'Montant', 'debt_before', 'current_debt', 'Écart', 'Suspect ?'],
            $cancelled->map(function ($p) {
                $reseller = $p->reseller;
                $realDebt = (float) Sale::withoutGlobalScope('shop')
                    ->where('reseller_id', $reseller->id)
                    ->where('payment_status', '!=', 'cancelled')
                    ->sum('amount_due');
                $ecart = abs($realDebt - (float) $p->debt_before);
                return [
                    $reseller->id,
                    $reseller->company_name,
                    number_format((float) $p->amount, 0, ',', ' '),
                    number_format((float) $p->debt_before, 0, ',', ' '),
                    number_format((float) $reseller->current_debt, 0, ',', ' '),
                    number_format($ecart, 0, ',', ' '),
                    $ecart > (float) $p->amount * 2 ? 'OUI' : 'non',
                ];
            })
        );

        $this->newLine();
        $this->info('Utilisez : php artisan repair:reseller-debt {ID} pour réparer un revendeur suspect.');
    }

    private function repair(Reseller $reseller): void
    {
        $this->info("=== Réparation : {$reseller->company_name} (ID #{$reseller->id}) ===");

        // Trouver le paiement annulé le plus récent pour ce revendeur
        $cancelledPayment = ResellerPayment::where('reseller_id', $reseller->id)
            ->whereNotNull('cancelled_at')
            ->latest('cancelled_at')
            ->first();

        if (!$cancelledPayment) {
            $this->warn('Aucun paiement annulé trouvé pour ce revendeur.');
            return;
        }

        // debt_before = dette juste avant le paiement qui a été annulé
        // Après annulation, la dette devrait revenir à cette valeur
        $correctDebt = (float) $cancelledPayment->debt_before;

        // État actuel corrompu
        $currentDebt = (float) Sale::withoutGlobalScope('shop')
            ->where('reseller_id', $reseller->id)
            ->where('payment_status', '!=', 'cancelled')
            ->sum('amount_due');

        $this->table(['Métrique', 'Valeur'], [
            ['Dette actuelle (corrompue)', number_format($currentDebt, 0, ',', ' ') . ' F'],
            ['Dette correcte (debt_before)', number_format($correctDebt, 0, ',', ' ') . ' F'],
            ['Paiement annulé', 'PAY-' . str_pad((string) $cancelledPayment->id, 5, '0', STR_PAD_LEFT)],
            ['Montant du paiement', number_format((float) $cancelledPayment->amount, 0, ',', ' ') . ' F'],
            ['Date annulation', $cancelledPayment->cancelled_at->format('d/m/Y H:i')],
        ]);

        $lostDeposits = $currentDebt - $correctDebt;

        if ($lostDeposits <= 0) {
            $this->info('La dette semble déjà correcte. Aucune réparation nécessaire.');
            return;
        }

        $this->warn("Acomptes initiaux perdus : " . number_format($lostDeposits, 0, ',', ' ') . " F");
        $this->warn("Ces acomptes (payés au moment des ventes) ont été effacés par le bug.");

        if (!$this->confirm('Voulez-vous réparer en redistribuant les acomptes perdus sur les factures (FIFO) ?')) {
            return;
        }

        DB::transaction(function () use ($reseller, $correctDebt, $lostDeposits) {
            // Redistribuer les acomptes perdus sur les factures (les plus anciennes d'abord)
            $remaining = $lostDeposits;
            $sales = Sale::withoutGlobalScope('shop')
                ->where('reseller_id', $reseller->id)
                ->where('payment_status', '!=', 'cancelled')
                ->where('amount_due', '>', 0)
                ->orderBy('created_at', 'asc')
                ->get();

            $repaired = 0;
            foreach ($sales as $sale) {
                if ($remaining <= 0) {
                    break;
                }

                $canApply = min($remaining, (float) $sale->amount_due);
                $newPaid  = (float) $sale->amount_paid + $canApply;
                $newDue   = (float) $sale->amount_due - $canApply;

                $sale->update([
                    'amount_paid'    => $newPaid,
                    'amount_due'     => max(0.0, $newDue),
                    'payment_status' => $newDue <= 0 ? 'paid' : 'credit',
                ]);

                $remaining -= $canApply;
                $repaired++;

                $this->line("  Facture {$sale->invoice_number} : +{$canApply} F → payé={$newPaid}, dû=" . max(0, $newDue));
            }

            // Mettre à jour current_debt
            $reseller->update(['current_debt' => $correctDebt]);

            $this->newLine();
            $this->info("Réparation terminée :");
            $this->info("  - {$repaired} facture(s) corrigée(s)");
            $this->info("  - current_debt = " . number_format($correctDebt, 0, ',', ' ') . " F");
        });
    }
}
