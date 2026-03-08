<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\ActivityLog;
use App\Models\LoginAttempt;
use App\Models\UserSession;
use App\Models\SecurityAlert;
use App\Models\Customer;
use App\Models\Reseller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Repair;
use App\Models\SavTicket;
use App\Models\User;

/**
 * Commande de nettoyage périodique des données
 * Supprime les anciennes données selon les périodes de rétention configurées
 */
class CleanupCommand extends Command
{
    protected $signature = 'app:cleanup 
                            {--dry-run : Affiche ce qui serait supprimé sans effectuer de suppression}
                            {--force : Force l\'exécution sans confirmation}
                            {--type= : Type spécifique à nettoyer (logs, sessions, soft-deleted, all)}';

    protected $description = 'Nettoie les anciennes données selon les périodes de rétention configurées';

    private array $stats = [];

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║         NETTOYAGE PÉRIODIQUE - EGREGORE BUSINESS             ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        $isDryRun = $this->option('dry-run');
        $type = $this->option('type') ?? 'all';

        if ($isDryRun) {
            $this->warn('⚠️  Mode simulation (dry-run) - Aucune donnée ne sera supprimée');
            $this->info('');
        }

        // Confirmation si pas en mode force
        if (!$isDryRun && !$this->option('force')) {
            if (!$this->confirm('Voulez-vous vraiment lancer le nettoyage des données ?')) {
                $this->info('Opération annulée.');
                return 0;
            }
        }

        $startTime = microtime(true);

        try {
            DB::beginTransaction();

            if (in_array($type, ['all', 'logs'])) {
                $this->cleanActivityLogs($isDryRun);
                $this->cleanLoginAttempts($isDryRun);
                $this->cleanSecurityAlerts($isDryRun);
            }

            if (in_array($type, ['all', 'sessions'])) {
                $this->cleanUserSessions($isDryRun);
            }

            if (in_array($type, ['all', 'soft-deleted'])) {
                $this->cleanSoftDeleted($isDryRun);
            }

            if (!$isDryRun) {
                DB::commit();
                $this->logCleanup();
            } else {
                DB::rollBack();
            }

            $this->displaySummary($startTime, $isDryRun);

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Erreur lors du nettoyage : ' . $e->getMessage());
            Log::error('Cleanup failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return 1;
        }
    }

    /**
     * Nettoie les logs d'activité
     */
    private function cleanActivityLogs(bool $isDryRun): void
    {
        $days = config('maintenance.retention.activity_logs', 365);
        $cutoffDate = Carbon::now()->subDays($days);

        $count = ActivityLog::where('created_at', '<', $cutoffDate)->count();
        $this->stats['activity_logs'] = $count;

        $this->info("📋 Logs d'activité > {$days} jours : {$count} enregistrements");

        if (!$isDryRun && $count > 0) {
            ActivityLog::where('created_at', '<', $cutoffDate)->delete();
            $this->info("   ✓ Supprimés");
        }
    }

    /**
     * Nettoie les tentatives de connexion
     */
    private function cleanLoginAttempts(bool $isDryRun): void
    {
        $days = config('maintenance.retention.login_attempts', 90);
        $cutoffDate = Carbon::now()->subDays($days);

        $count = LoginAttempt::where('created_at', '<', $cutoffDate)->count();
        $this->stats['login_attempts'] = $count;

        $this->info("🔐 Tentatives de connexion > {$days} jours : {$count} enregistrements");

        if (!$isDryRun && $count > 0) {
            LoginAttempt::where('created_at', '<', $cutoffDate)->delete();
            $this->info("   ✓ Supprimés");
        }
    }

    /**
     * Nettoie les alertes de sécurité résolues
     */
    private function cleanSecurityAlerts(bool $isDryRun): void
    {
        $days = config('maintenance.retention.security_alerts_resolved', 365);
        $cutoffDate = Carbon::now()->subDays($days);

        $count = SecurityAlert::where('is_resolved', true)
            ->where('resolved_at', '<', $cutoffDate)
            ->count();
        $this->stats['security_alerts'] = $count;

        $this->info("🚨 Alertes sécurité résolues > {$days} jours : {$count} enregistrements");

        if (!$isDryRun && $count > 0) {
            SecurityAlert::where('is_resolved', true)
                ->where('resolved_at', '<', $cutoffDate)
                ->delete();
            $this->info("   ✓ Supprimés");
        }
    }

    /**
     * Nettoie les sessions utilisateur inactives
     */
    private function cleanUserSessions(bool $isDryRun): void
    {
        $days = config('maintenance.retention.user_sessions', 30);
        $cutoffDate = Carbon::now()->subDays($days);

        $count = UserSession::where('last_activity_at', '<', $cutoffDate)->count();
        $this->stats['user_sessions'] = $count;

        $this->info("👤 Sessions inactives > {$days} jours : {$count} enregistrements");

        if (!$isDryRun && $count > 0) {
            UserSession::where('last_activity_at', '<', $cutoffDate)->delete();
            $this->info("   ✓ Supprimés");
        }
    }

    /**
     * Nettoie les données soft-deleted au-delà de leur période de rétention
     */
    private function cleanSoftDeleted(bool $isDryRun): void
    {
        $this->info('');
        $this->info('🗑️  Nettoyage des données supprimées (soft-deleted) :');

        $models = [
            'customers' => Customer::class,
            'resellers' => Reseller::class,
            'products' => Product::class,
            'sales' => Sale::class,
            'repairs' => Repair::class,
            'sav_tickets' => SavTicket::class,
            'users' => User::class,
        ];

        foreach ($models as $key => $modelClass) {
            $days = config("maintenance.retention.soft_deleted.{$key}", 730);
            $cutoffDate = Carbon::now()->subDays($days);

            $count = $modelClass::onlyTrashed()
                ->where('deleted_at', '<', $cutoffDate)
                ->count();

            $this->stats["soft_deleted_{$key}"] = $count;

            $label = ucfirst(str_replace('_', ' ', $key));
            $this->info("   {$label} > {$days} jours : {$count} enregistrements");

            if (!$isDryRun && $count > 0) {
                $modelClass::onlyTrashed()
                    ->where('deleted_at', '<', $cutoffDate)
                    ->forceDelete();
                $this->info("      ✓ Supprimés définitivement");
            }
        }
    }

    /**
     * Enregistre le nettoyage dans les logs
     */
    private function logCleanup(): void
    {
        $totalDeleted = array_sum($this->stats);

        ActivityLog::create([
            'user_id' => null,
            'action' => 'system_cleanup',
            'description' => "Nettoyage système : {$totalDeleted} enregistrements supprimés",
            'new_values' => $this->stats,
            'ip_address' => '127.0.0.1',
        ]);

        Log::info('System cleanup completed', $this->stats);
    }

    /**
     * Affiche le résumé du nettoyage
     */
    private function displaySummary(float $startTime, bool $isDryRun): void
    {
        $duration = round(microtime(true) - $startTime, 2);
        $totalDeleted = array_sum($this->stats);

        $this->info('');
        $this->info('══════════════════════════════════════════════════════════════');
        $this->info('                         RÉSUMÉ                               ');
        $this->info('══════════════════════════════════════════════════════════════');
        $this->info("Total : {$totalDeleted} enregistrements " . ($isDryRun ? 'à supprimer' : 'supprimés'));
        $this->info("Durée : {$duration} secondes");
        $this->info('');

        if ($isDryRun) {
            $this->warn('💡 Exécutez sans --dry-run pour effectuer le nettoyage réel');
        } else {
            $this->info('✅ Nettoyage terminé avec succès !');
        }
    }
}
