<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Planification des tâches de maintenance
|--------------------------------------------------------------------------
|
| Ces tâches s'exécutent automatiquement selon la configuration.
| Pour activer, ajoutez cette entrée cron sur le serveur :
| * * * * * cd /chemin/vers/projet && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Sauvegarde quotidienne (par défaut à 2h du matin)
if (config('maintenance.schedule.backup_daily', true)) {
    $backupTime = config('maintenance.schedule.backup_time', '02:00');
    Schedule::command('app:backup --type=full')
        ->dailyAt($backupTime)
        ->withoutOverlapping()
        ->onOneServer()
        ->appendOutputTo(storage_path('logs/backup.log'));
}

// Vérification stock bas (toutes les 2h en heures ouvrées)
Schedule::command('app:check-low-stock')
    ->everyTwoHours()
    ->between('08:00', '20:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/check-low-stock.log'));

// Réparations inactives depuis 7+ jours (chaque jour à 8h)
Schedule::command('app:check-stale-repairs')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/check-stale-repairs.log'));

// Nettoyage notifications lues de +30 jours (chaque nuit à 3h30)
Schedule::call(function () {
    app(\App\Services\NotificationService::class)->cleanup(30);
})->dailyAt('03:30');

// Rapport hebdomadaire CA (lundi matin à 7h)
Schedule::command('app:weekly-report')
    ->weeklyOn(1, '07:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/weekly-report.log'));

// Nettoyage hebdomadaire (par défaut le dimanche à 3h du matin)
if (config('maintenance.schedule.cleanup_weekly', true)) {
    $cleanupDay = config('maintenance.schedule.cleanup_day', 'sunday');
    $cleanupTime = config('maintenance.schedule.cleanup_time', '03:00');
    
    Schedule::command('app:cleanup --force')
        ->weeklyOn(
            match($cleanupDay) {
                'monday' => 1,
                'tuesday' => 2,
                'wednesday' => 3,
                'thursday' => 4,
                'friday' => 5,
                'saturday' => 6,
                default => 0, // sunday
            },
            $cleanupTime
        )
        ->withoutOverlapping()
        ->onOneServer()
        ->appendOutputTo(storage_path('logs/cleanup.log'));
}
