<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

/**
 * Commande pour lister et gérer les sauvegardes existantes
 */
class BackupListCommand extends Command
{
    protected $signature = 'app:backup:list 
                            {--delete= : ID de la sauvegarde à supprimer}
                            {--restore= : ID de la sauvegarde à restaurer (base de données uniquement)}';

    protected $description = 'Liste les sauvegardes disponibles et permet de les gérer';

    public function handle(): int
    {
        $backupPath = config('maintenance.backup.path', storage_path('backups'));

        if (!File::exists($backupPath)) {
            $this->warn('Aucun dossier de sauvegarde trouvé.');
            return 0;
        }

        // Suppression si demandée
        if ($deleteId = $this->option('delete')) {
            return $this->deleteBackup($backupPath, $deleteId);
        }

        // Restauration si demandée
        if ($restoreId = $this->option('restore')) {
            return $this->restoreBackup($backupPath, $restoreId);
        }

        // Lister les sauvegardes
        return $this->listBackups($backupPath);
    }

    /**
     * Liste les sauvegardes
     */
    private function listBackups(string $backupPath): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║           LISTE DES SAUVEGARDES                              ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        $backups = collect(File::files($backupPath))
            ->filter(function ($file) {
                return preg_match('/^(backup_full_|database_|files_)/', $file->getFilename());
            })
            ->sortByDesc(function ($file) {
                return $file->getMTime();
            })
            ->values();

        if ($backups->isEmpty()) {
            $this->warn('Aucune sauvegarde trouvée.');
            $this->info("Exécutez 'php artisan app:backup' pour créer une sauvegarde.");
            return 0;
        }

        $rows = [];
        foreach ($backups as $index => $file) {
            $size = $this->formatBytes($file->getSize());
            $date = Carbon::createFromTimestamp($file->getMTime())->format('d/m/Y H:i:s');
            $type = $this->getBackupType($file->getFilename());

            $rows[] = [
                $index + 1,
                $file->getFilename(),
                $type,
                $size,
                $date,
            ];
        }

        $this->table(
            ['#', 'Fichier', 'Type', 'Taille', 'Date'],
            $rows
        );

        $totalSize = $backups->sum(fn($f) => $f->getSize());
        $this->info('');
        $this->info("Total : {$backups->count()} sauvegarde(s), " . $this->formatBytes($totalSize));
        $this->info("Emplacement : {$backupPath}");
        $this->info('');
        $this->info('💡 Commandes utiles :');
        $this->info("   php artisan app:backup:list --delete=1     # Supprimer la sauvegarde #1");
        $this->info("   php artisan app:backup:list --restore=1    # Restaurer la BDD depuis #1");

        return 0;
    }

    /**
     * Supprime une sauvegarde
     */
    private function deleteBackup(string $backupPath, string $id): int
    {
        $backups = $this->getBackupsList($backupPath);
        $index = (int) $id - 1;

        if (!isset($backups[$index])) {
            $this->error("Sauvegarde #{$id} non trouvée.");
            return 1;
        }

        $file = $backups[$index];
        
        if (!$this->confirm("Supprimer la sauvegarde '{$file->getFilename()}' ?")) {
            $this->info('Opération annulée.');
            return 0;
        }

        unlink($file->getRealPath());
        $this->info("✓ Sauvegarde supprimée : {$file->getFilename()}");

        return 0;
    }

    /**
     * Restaure une sauvegarde de base de données
     */
    private function restoreBackup(string $backupPath, string $id): int
    {
        $backups = $this->getBackupsList($backupPath);
        $index = (int) $id - 1;

        if (!isset($backups[$index])) {
            $this->error("Sauvegarde #{$id} non trouvée.");
            return 1;
        }

        $file = $backups[$index];
        $filename = $file->getFilename();

        // Vérifier que c'est une sauvegarde de BDD
        if (!preg_match('/^database_/', $filename) && !preg_match('/^backup_full_/', $filename)) {
            $this->error("Ce fichier n'est pas une sauvegarde de base de données.");
            return 1;
        }

        $this->warn('⚠️  ATTENTION : Cette opération va REMPLACER toutes les données actuelles !');
        
        if (!$this->confirm('Êtes-vous sûr de vouloir restaurer cette sauvegarde ?')) {
            $this->info('Opération annulée.');
            return 0;
        }

        $this->info('🔄 Restauration en cours...');

        try {
            $filepath = $file->getRealPath();

            // Si c'est une archive complète, extraire d'abord
            if (str_ends_with($filename, '.zip')) {
                $filepath = $this->extractDatabaseFromZip($filepath);
                if (!$filepath) {
                    return 1;
                }
            }

            // Décompresser si gzippé
            if (str_ends_with($filepath, '.gz')) {
                $filepath = $this->decompressGzip($filepath);
            }

            // Restaurer selon le driver
            $driver = config('database.connections.' . config('database.default') . '.driver');

            if ($driver === 'mysql') {
                $this->restoreMySQL($filepath);
            } elseif ($driver === 'sqlite') {
                $this->restoreSQLite($filepath);
            }

            $this->info('✅ Restauration terminée avec succès !');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la restauration : ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Restaure MySQL
     */
    private function restoreMySQL(string $filepath): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");
        $host = config("database.connections.{$connection}.host");
        $port = config("database.connections.{$connection}.port", 3306);

        $command = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s %s < %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Erreur MySQL : ' . implode("\n", $output));
        }
    }

    /**
     * Restaure SQLite
     */
    private function restoreSQLite(string $filepath): void
    {
        $database = config('database.connections.' . config('database.default') . '.database');
        
        // Sauvegarder la BDD actuelle
        $backupCurrent = $database . '.bak';
        copy($database, $backupCurrent);

        try {
            copy($filepath, $database);
        } catch (\Exception $e) {
            // Restaurer en cas d'erreur
            copy($backupCurrent, $database);
            throw $e;
        }

        unlink($backupCurrent);
    }

    /**
     * Extrait le fichier de BDD d'une archive ZIP
     */
    private function extractDatabaseFromZip(string $zipPath): ?string
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->error('Impossible d\'ouvrir l\'archive ZIP');
            return null;
        }

        $tempDir = sys_get_temp_dir() . '/crm_restore_' . time();
        mkdir($tempDir);
        $zip->extractTo($tempDir);
        $zip->close();

        // Chercher le fichier de BDD
        $files = glob($tempDir . '/database_*');
        if (empty($files)) {
            $this->error('Aucun fichier de base de données trouvé dans l\'archive');
            return null;
        }

        return $files[0];
    }

    /**
     * Décompresse un fichier gzip
     */
    private function decompressGzip(string $filepath): string
    {
        $outputPath = preg_replace('/\.gz$/', '', $filepath);
        
        if ($filepath === $outputPath) {
            $outputPath = sys_get_temp_dir() . '/' . basename($filepath) . '.sql';
        }

        $data = gzdecode(file_get_contents($filepath));
        file_put_contents($outputPath, $data);

        return $outputPath;
    }

    /**
     * Récupère la liste des sauvegardes triées
     */
    private function getBackupsList(string $backupPath): array
    {
        return collect(File::files($backupPath))
            ->filter(function ($file) {
                return preg_match('/^(backup_full_|database_|files_)/', $file->getFilename());
            })
            ->sortByDesc(function ($file) {
                return $file->getMTime();
            })
            ->values()
            ->toArray();
    }

    /**
     * Détermine le type de sauvegarde
     */
    private function getBackupType(string $filename): string
    {
        if (str_starts_with($filename, 'backup_full_')) {
            return '📦 Complète';
        } elseif (str_starts_with($filename, 'database_')) {
            return '💾 BDD';
        } elseif (str_starts_with($filename, 'files_')) {
            return '📂 Fichiers';
        }
        return '❓ Inconnu';
    }

    /**
     * Formate la taille en bytes
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
