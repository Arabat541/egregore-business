<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\Notification;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Génère un rapport hebdomadaire de CA par boutique
 * et le diffuse en notification interne aux admins de chaque boutique.
 */
class WeeklyReportCommand extends Command
{
    protected $signature = 'app:weekly-report
                            {--weeks=1 : Nombre de semaines à couvrir}';
    protected $description = 'Envoie un rapport hebdomadaire de CA aux admins (par boutique)';

    public function handle(): int
    {
        $weeks = (int) $this->option('weeks');
        $from  = now()->subWeeks($weeks)->startOfWeek();
        $to    = now()->endOfWeek();
        $fmt   = fn(float $v) => number_format($v, 0, ',', ' ');

        Shop::where('is_active', true)->each(function ($shop) use ($from, $to, $fmt) {
            $shopAdmins = User::role('admin')
                ->where('shop_id', $shop->id)
                ->where('is_active', true)
                ->get();

            if ($shopAdmins->isEmpty()) {
                return;
            }

            // ── Ventes ──────────────────────────────────────────────
            $salesRevenue = Sale::where('shop_id', $shop->id)
                ->whereBetween('created_at', [$from, $to])
                ->where('payment_status', '!=', 'cancelled')
                ->sum('total_amount');

            $salesCount = Sale::where('shop_id', $shop->id)
                ->whereBetween('created_at', [$from, $to])
                ->where('payment_status', '!=', 'cancelled')
                ->count();

            // ── Réparations livrées — labor_cost = main d'œuvre totale (cohérent avec FinancialReportService)
            $repairRevenue = Repair::where('shop_id', $shop->id)
                ->where('status', Repair::STATUS_DELIVERED)
                ->whereBetween('delivered_at', [$from, $to])
                ->sum('labor_cost');

            $repairCount = Repair::where('shop_id', $shop->id)
                ->where('status', Repair::STATUS_DELIVERED)
                ->whereBetween('delivered_at', [$from, $to])
                ->count();

            // ── Dépenses approuvées sur la date effective ────────────
            $expenses = Expense::where('shop_id', $shop->id)
                ->approved()
                ->whereBetween('expense_date', [$from, $to])
                ->sum('amount');

            $totalRevenue = $salesRevenue + $repairRevenue;
            $profit       = $totalRevenue - $expenses;

            $period  = $from->format('d/m') . ' – ' . $to->format('d/m/Y');
            $title   = "[{$shop->name}] Rapport semaine du {$period}";
            $message = implode(' | ', [
                "CA: {$fmt($totalRevenue)} F",
                "Ventes: {$fmt($salesRevenue)} F ({$salesCount})",
                "Répar.: {$fmt($repairRevenue)} F ({$repairCount})",
                "Dépenses: {$fmt($expenses)} F",
                "Bénéfice: {$fmt($profit)} F",
            ]);

            foreach ($shopAdmins as $admin) {
                Notification::create([
                    'user_id'      => $admin->id,
                    'type'         => Notification::TYPE_SYSTEM,
                    'title'        => $title,
                    'message'      => $message,
                    'icon'         => 'bi-bar-chart-line',
                    'color'        => 'primary',
                    'link'         => route('admin.reports.index'),
                    'is_important' => false,
                    'play_sound'   => false,
                ]);
            }

            $this->info("{$shop->name}: {$title}");
            $this->line($message);
        });

        return Command::SUCCESS;
    }
}
