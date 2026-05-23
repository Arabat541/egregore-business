<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Repair;
use App\Models\Shop;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Détecte les réparations en attente sans activité depuis N jours par boutique
 * et envoie une notification aux admins de la boutique concernée.
 */
class CheckStaleRepairsCommand extends Command
{
    protected $signature = 'app:check-stale-repairs
                            {--days=7 : Nombre de jours sans activité}
                            {--force : Ignorer la déduplication}';
    protected $description = 'Notifie les admins des réparations inactives depuis trop longtemps (par boutique)';

    public function handle(NotificationService $notifications): int
    {
        $days       = (int) $this->option('days');
        $force      = $this->option('force');
        $totalCount = 0;

        Shop::where('is_active', true)->each(function ($shop) use ($days, $force, &$totalCount) {
            $shopAdmins = User::role('admin')
                ->where('shop_id', $shop->id)
                ->where('is_active', true)
                ->get();

            if ($shopAdmins->isEmpty()) {
                return;
            }

            $stale = Repair::pending()
                ->where('shop_id', $shop->id)
                ->where('updated_at', '<', now()->subDays($days))
                ->with('customer')
                ->get();

            foreach ($stale as $repair) {
                $cacheKey = "notif_stale_repair_{$repair->id}";

                if (!$force && Cache::has($cacheKey)) {
                    continue;
                }

                $daysSince = (int) $repair->updated_at->diffInDays(now());

                foreach ($shopAdmins as $admin) {
                    Notification::create([
                        'user_id'         => $admin->id,
                        'type'            => Notification::TYPE_SYSTEM,
                        'title'           => "Réparation inactive ({$daysSince}j)",
                        'message'         => "#{$repair->repair_number} — {$repair->customer->name} — {$repair->device_brand} {$repair->device_model}",
                        'icon'            => 'bi-hourglass-split',
                        'color'           => 'warning',
                        'link'            => route('admin.reports.repairs'),
                        'notifiable_type' => Repair::class,
                        'notifiable_id'   => $repair->id,
                        'is_important'    => $daysSince >= 14,
                        'play_sound'      => false,
                    ]);
                }

                Cache::put($cacheKey, true, now()->addHours(24));
            }

            $totalCount += $stale->count();
        });

        $this->info($totalCount > 0
            ? "{$totalCount} réparation(s) inactive(s) signalée(s)."
            : "Aucune réparation inactive depuis {$days} jours."
        );

        return Command::SUCCESS;
    }
}
