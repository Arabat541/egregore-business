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
