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

        $cancelledPayment = ResellerPayment::where('reseller_id', $reseller->id)
            ->whereNotNull('cancelled_at')
            ->latest('cancelled_at')
            ->first();

        if (!$cancelledPayment) {
            $this->warn('Aucun paiement annulé trouvé pour ce revendeur.');
            return;
        }

        $correctOldDebt = (float) $cancelledPayment->debt_before;
        $paymentDate    = $cancelledPayment->created_at;

        $this->table(['Métrique', 'Valeur'], [
            ['Paiement annulé', 'PAY-' . str_pad((string) $cancelledPayment->id, 5, '0', STR_PAD_LEFT)],
            ['Montant du paiement', number_format((float) $cancelledPayment->amount, 0, ',', ' ') . ' F'],
            ['Date du paiement', $paymentDate->format('d/m/Y H:i')],
            ['Date annulation', $cancelledPayment->cancelled_at->format('d/m/Y H:i')],
            ['Dette correcte anciennes ventes (debt_before)', number_format($correctOldDebt, 0, ',', ' ') . ' F'],
            ['current_debt actuel', number_format((float) $reseller->current_debt, 0, ',', ' ') . ' F'],
        ]);

        if (!$this->confirm('Voulez-vous réparer ? (reset complet + replay + redistribution acomptes)')) {
            return;
        }

        DB::transaction(function () use ($reseller, $correctOldDebt, $paymentDate) {
            // 1. Reset TOUTES les ventes non-annulées
            $allSales = Sale::withoutGlobalScope('shop')
                ->where('reseller_id', $reseller->id)
                ->where('payment_status', '!=', 'cancelled')
                ->get();

            foreach ($allSales as $sale) {
                $sale->update([
                    'amount_paid'    => 0,
                    'amount_due'     => (float) $sale->total_amount,
                    'payment_status' => 'credit',
                ]);
            }

            $this->line("  Reset : {$allSales->count()} vente(s) remises à zéro");

            // 2. Rejouer TOUS les paiements actifs (chronologiquement)
            $activePayments = ResellerPayment::where('reseller_id', $reseller->id)
                ->whereNull('cancelled_at')
                ->orderBy('created_at')
                ->get();

            $replayedTotal = 0;
            foreach ($activePayments as $p) {
                if ($p->sale_id) {
                    $sale = Sale::withoutGlobalScope('shop')->find($p->sale_id);
                    if ($sale && $sale->payment_status !== 'cancelled') {
                        $newPaid = (float) $sale->amount_paid + (float) $p->amount;
                        $newDue  = max(0.0, (float) $sale->amount_due - (float) $p->amount);
                        $sale->update([
                            'amount_paid'    => $newPaid,
                            'amount_due'     => $newDue,
                            'payment_status' => $newDue <= 0 ? 'paid' : 'credit',
                        ]);
                    }
                } else {
                    ResellerPayment::distributePaymentToSales($reseller, (float) $p->amount, $p->shop_id);
                }
                $replayedTotal += (float) $p->amount;
            }

            $this->line("  Replay : {$activePayments->count()} paiement(s) rejoués (" . number_format($replayedTotal, 0, ',', ' ') . " F)");

            // 3. Calculer les acomptes perdus pour les ANCIENNES ventes uniquement
            $oldSalesDebt = (float) Sale::withoutGlobalScope('shop')
                ->where('reseller_id', $reseller->id)
                ->where('payment_status', '!=', 'cancelled')
                ->where('created_at', '<', $paymentDate)
                ->sum('amount_due');

            $newSalesDebt = (float) Sale::withoutGlobalScope('shop')
                ->where('reseller_id', $reseller->id)
                ->where('payment_status', '!=', 'cancelled')
                ->where('created_at', '>=', $paymentDate)
                ->sum('amount_due');

            $this->line("  Après replay : anciennes ventes dû=" . number_format($oldSalesDebt, 0, ',', ' ') . " F, nouvelles ventes dû=" . number_format($newSalesDebt, 0, ',', ' ') . " F");

            $lostDeposits = $oldSalesDebt - $correctOldDebt;

            if ($lostDeposits > 0) {
                $this->warn("  Acomptes perdus (anciennes ventes) : " . number_format($lostDeposits, 0, ',', ' ') . " F");

                // 4. Redistribuer les acomptes perdus aux ANCIENNES ventes uniquement (FIFO)
                $remaining = $lostDeposits;
                $oldSales = Sale::withoutGlobalScope('shop')
                    ->where('reseller_id', $reseller->id)
                    ->where('payment_status', '!=', 'cancelled')
                    ->where('created_at', '<', $paymentDate)
                    ->where('amount_due', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();

                $repaired = 0;
                foreach ($oldSales as $sale) {
                    if ($remaining <= 0) break;
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
                    $this->line("    Facture {$sale->invoice_number} : +{$canApply} F → payé={$newPaid}, dû=" . max(0, $newDue));
                }

                $this->line("  {$repaired} ancienne(s) facture(s) corrigée(s)");
            } else {
                $this->info('  Aucun acompte perdu détecté sur les anciennes ventes.');
            }

            // 5. Recalculer current_debt depuis la vraie somme des amount_due
            $finalDebt = (float) Sale::withoutGlobalScope('shop')
                ->where('reseller_id', $reseller->id)
                ->where('payment_status', '!=', 'cancelled')
                ->sum('amount_due');

            $reseller->update(['current_debt' => $finalDebt]);

            $this->newLine();
            $this->info("Réparation terminée :");
            $this->info("  - Anciennes ventes (avant paiement) : dû = " . number_format($correctOldDebt, 0, ',', ' ') . " F");
            $this->info("  - Nouvelles ventes (après paiement) : dû = " . number_format($newSalesDebt, 0, ',', ' ') . " F");
            $this->info("  - current_debt = " . number_format($finalDebt, 0, ',', ' ') . " F");
        });
    }
}
