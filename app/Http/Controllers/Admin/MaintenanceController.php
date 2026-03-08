<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
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

/**
 * Contrôleur pour la gestion de la maintenance système
 * Sauvegarde et nettoyage de l'application
 */
class MaintenanceController extends Controller
{
    /**
     * Affiche la page de maintenance
     */
    public function index()
    {
        // Statistiques des données à nettoyer
        $cleanupStats = $this->getCleanupStats();

        // Liste des sauvegardes
        $backups = $this->getBackupsList();

        // Configuration actuelle
        $config = [
            'retention' => config('maintenance.retention'),
            'backup' => config('maintenance.backup'),
            'schedule' => config('maintenance.schedule'),
        ];

        // Espace disque
        $diskInfo = $this->getDiskInfo();

        // Dernières opérations
        $recentOperations = ActivityLog::whereIn('action', ['system_backup', 'system_cleanup'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.maintenance.index', compact(
            'cleanupStats',
            'backups',
            'config',
            'diskInfo',
            'recentOperations'
        ));
    }

    /**
     * Lance une sauvegarde
     */
    public function backup(Request $request)
    {
        $request->validate([
            'type' => 'required|in:full,database,files',
        ]);

        try {
            $exitCode = Artisan::call('app:backup', [
                '--type' => $request->type,
            ]);

            $output = Artisan::output();

            if ($exitCode === 0) {
                return redirect()->route('admin.maintenance.index')
                    ->with('success', 'Sauvegarde effectuée avec succès !');
            } else {
                return redirect()->route('admin.maintenance.index')
                    ->with('error', 'Erreur lors de la sauvegarde : ' . $output);
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.maintenance.index')
                ->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Lance un nettoyage
     */
    public function cleanup(Request $request)
    {
        $request->validate([
            'type' => 'required|in:all,logs,sessions,soft-deleted',
            'dry_run' => 'nullable|boolean',
        ]);

        try {
            $options = [
                '--type' => $request->type,
                '--force' => true,
            ];

            if ($request->boolean('dry_run')) {
                $options['--dry-run'] = true;
            }

            $exitCode = Artisan::call('app:cleanup', $options);
            $output = Artisan::output();

            if ($exitCode === 0) {
                $message = $request->boolean('dry_run') 
                    ? 'Simulation terminée. Consultez les détails ci-dessous.'
                    : 'Nettoyage effectué avec succès !';
                
                return redirect()->route('admin.maintenance.index')
                    ->with('success', $message)
                    ->with('cleanup_output', $output);
            } else {
                return redirect()->route('admin.maintenance.index')
                    ->with('error', 'Erreur lors du nettoyage');
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.maintenance.index')
                ->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Télécharge une sauvegarde
     */
    public function downloadBackup($filename)
    {
        $backupPath = config('maintenance.backup.path', storage_path('backups'));
        $filepath = $backupPath . '/' . $filename;

        // Sécurité : vérifier que le fichier est bien dans le dossier de sauvegarde
        $realPath = realpath($filepath);
        $realBackupPath = realpath($backupPath);

        if (!$realPath || !str_starts_with($realPath, $realBackupPath)) {
            abort(404, 'Fichier non trouvé');
        }

        if (!File::exists($filepath)) {
            abort(404, 'Fichier non trouvé');
        }

        return response()->download($filepath);
    }

    /**
     * Supprime une sauvegarde
     */
    public function deleteBackup($filename)
    {
        $backupPath = config('maintenance.backup.path', storage_path('backups'));
        $filepath = $backupPath . '/' . $filename;

        // Sécurité
        $realPath = realpath($filepath);
        $realBackupPath = realpath($backupPath);

        if (!$realPath || !str_starts_with($realPath, $realBackupPath)) {
            return redirect()->route('admin.maintenance.index')
                ->with('error', 'Fichier non trouvé');
        }

        if (File::exists($filepath)) {
            unlink($filepath);
            return redirect()->route('admin.maintenance.index')
                ->with('success', 'Sauvegarde supprimée');
        }

        return redirect()->route('admin.maintenance.index')
            ->with('error', 'Fichier non trouvé');
    }

    /**
     * Calcule les statistiques de nettoyage
     */
    private function getCleanupStats(): array
    {
        $stats = [];
        $config = config('maintenance.retention');

        // Activity Logs
        $days = $config['activity_logs'] ?? 365;
        $cutoff = Carbon::now()->subDays($days);
        $stats['activity_logs'] = [
            'count' => ActivityLog::where('created_at', '<', $cutoff)->count(),
            'retention' => $days,
            'label' => 'Logs d\'activité',
        ];

        // Login Attempts
        $days = $config['login_attempts'] ?? 90;
        $cutoff = Carbon::now()->subDays($days);
        $stats['login_attempts'] = [
            'count' => LoginAttempt::where('created_at', '<', $cutoff)->count(),
            'retention' => $days,
            'label' => 'Tentatives de connexion',
        ];

        // User Sessions
        $days = $config['user_sessions'] ?? 30;
        $cutoff = Carbon::now()->subDays($days);
        $stats['user_sessions'] = [
            'count' => UserSession::where('last_activity_at', '<', $cutoff)->count(),
            'retention' => $days,
            'label' => 'Sessions inactives',
        ];

        // Security Alerts
        $days = $config['security_alerts_resolved'] ?? 365;
        $cutoff = Carbon::now()->subDays($days);
        $stats['security_alerts'] = [
            'count' => SecurityAlert::where('is_resolved', true)->where('resolved_at', '<', $cutoff)->count(),
            'retention' => $days,
            'label' => 'Alertes résolues',
        ];

        // Soft Deleted
        $softDeletedConfig = $config['soft_deleted'] ?? [];
        $stats['soft_deleted'] = [
            'customers' => Customer::onlyTrashed()
                ->where('deleted_at', '<', Carbon::now()->subDays($softDeletedConfig['customers'] ?? 1095))
                ->count(),
            'resellers' => Reseller::onlyTrashed()
                ->where('deleted_at', '<', Carbon::now()->subDays($softDeletedConfig['resellers'] ?? 1095))
                ->count(),
            'products' => Product::onlyTrashed()
                ->where('deleted_at', '<', Carbon::now()->subDays($softDeletedConfig['products'] ?? 730))
                ->count(),
            'sales' => Sale::onlyTrashed()
                ->where('deleted_at', '<', Carbon::now()->subDays($softDeletedConfig['sales'] ?? 3650))
                ->count(),
            'repairs' => Repair::onlyTrashed()
                ->where('deleted_at', '<', Carbon::now()->subDays($softDeletedConfig['repairs'] ?? 1095))
                ->count(),
        ];

        return $stats;
    }

    /**
     * Récupère la liste des sauvegardes
     */
    private function getBackupsList(): array
    {
        $backupPath = config('maintenance.backup.path', storage_path('backups'));

        if (!File::exists($backupPath)) {
            return [];
        }

        return collect(File::files($backupPath))
            ->filter(function ($file) {
                return preg_match('/^(backup_full_|database_|files_)/', $file->getFilename());
            })
            ->sortByDesc(function ($file) {
                return $file->getMTime();
            })
            ->map(function ($file) {
                return [
                    'filename' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'size_formatted' => $this->formatBytes($file->getSize()),
                    'date' => Carbon::createFromTimestamp($file->getMTime()),
                    'type' => $this->getBackupType($file->getFilename()),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Informations sur l'espace disque
     */
    private function getDiskInfo(): array
    {
        $path = storage_path();
        
        return [
            'total' => disk_total_space($path),
            'free' => disk_free_space($path),
            'used' => disk_total_space($path) - disk_free_space($path),
            'total_formatted' => $this->formatBytes(disk_total_space($path)),
            'free_formatted' => $this->formatBytes(disk_free_space($path)),
            'used_formatted' => $this->formatBytes(disk_total_space($path) - disk_free_space($path)),
            'percent_used' => round(((disk_total_space($path) - disk_free_space($path)) / disk_total_space($path)) * 100, 1),
        ];
    }

    /**
     * Détermine le type de sauvegarde
     */
    private function getBackupType(string $filename): string
    {
        if (str_starts_with($filename, 'backup_full_')) {
            return 'full';
        } elseif (str_starts_with($filename, 'database_')) {
            return 'database';
        } elseif (str_starts_with($filename, 'files_')) {
            return 'files';
        }
        return 'unknown';
    }

    /**
     * Formate la taille en bytes
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
