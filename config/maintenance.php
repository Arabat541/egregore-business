<?php

/**
 * Configuration de la maintenance de l'application
 * Sauvegarde et nettoyage périodique
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Périodes de rétention des données (en jours)
    |--------------------------------------------------------------------------
    */
    'retention' => [
        // Logs et audit
        'activity_logs' => env('RETENTION_ACTIVITY_LOGS', 365),
        'login_attempts' => env('RETENTION_LOGIN_ATTEMPTS', 90),
        'user_sessions' => env('RETENTION_USER_SESSIONS', 30),
        'security_alerts_resolved' => env('RETENTION_SECURITY_ALERTS', 365),

        // Données soft-deleted
        'soft_deleted' => [
            'customers' => env('RETENTION_SOFT_DELETED_CUSTOMERS', 1095), // 3 ans
            'resellers' => env('RETENTION_SOFT_DELETED_RESELLERS', 1095),
            'products' => env('RETENTION_SOFT_DELETED_PRODUCTS', 730), // 2 ans
            'sales' => env('RETENTION_SOFT_DELETED_SALES', 3650), // 10 ans (fiscal)
            'repairs' => env('RETENTION_SOFT_DELETED_REPAIRS', 1095), // 3 ans
            'sav_tickets' => env('RETENTION_SOFT_DELETED_SAV', 730), // 2 ans
            'users' => env('RETENTION_SOFT_DELETED_USERS', 1825), // 5 ans
        ],

        // Transactions (ne pas supprimer, archiver)
        'archive_transactions_after' => env('ARCHIVE_TRANSACTIONS_AFTER', 1825), // 5 ans
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des sauvegardes
    |--------------------------------------------------------------------------
    */
    'backup' => [
        // Chemin de stockage des sauvegardes
        'path' => env('BACKUP_PATH', storage_path('backups')),

        // Nombre de sauvegardes à conserver
        'keep_last' => env('BACKUP_KEEP_LAST', 7),

        // Sauvegardes hebdomadaires à conserver
        'keep_weekly' => env('BACKUP_KEEP_WEEKLY', 4),

        // Sauvegardes mensuelles à conserver
        'keep_monthly' => env('BACKUP_KEEP_MONTHLY', 6),

        // Compresser les sauvegardes
        'compress' => env('BACKUP_COMPRESS', true),

        // Inclure les fichiers uploadés
        'include_uploads' => env('BACKUP_INCLUDE_UPLOADS', true),

        // Chemin mysqldump (laisser vide pour auto-détection)
        'mysqldump_path' => env('MYSQLDUMP_PATH', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        // Activer les notifications par email
        'enabled' => env('MAINTENANCE_NOTIFICATIONS', true),

        // Email de notification
        'email' => env('MAINTENANCE_NOTIFICATION_EMAIL', null),

        // Notifier en cas de succès
        'notify_on_success' => env('MAINTENANCE_NOTIFY_SUCCESS', false),

        // Notifier en cas d'échec
        'notify_on_failure' => env('MAINTENANCE_NOTIFY_FAILURE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Planification automatique
    |--------------------------------------------------------------------------
    */
    'schedule' => [
        // Sauvegarde quotidienne
        'backup_daily' => env('SCHEDULE_BACKUP_DAILY', true),
        'backup_time' => env('SCHEDULE_BACKUP_TIME', '02:00'),

        // Nettoyage hebdomadaire
        'cleanup_weekly' => env('SCHEDULE_CLEANUP_WEEKLY', true),
        'cleanup_day' => env('SCHEDULE_CLEANUP_DAY', 'sunday'),
        'cleanup_time' => env('SCHEDULE_CLEANUP_TIME', '03:00'),
    ],

];
