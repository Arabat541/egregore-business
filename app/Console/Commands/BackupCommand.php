<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use App\Models\ActivityLog;
use ZipArchive;

/**
 * Commande de sauvegarde de la base de données et des fichiers
 */
class BackupCommand extends Command
{
    protected $signature = 'app:backup 
                            {--type=full : Type de sauvegarde (full, database, files)}
                            {--keep= : Nombre de sauvegardes à conserver}
                            {--no-compress : Ne pas compresser la sauvegarde}';

    protected $description = 'Sauvegarde la base de données et les fichiers uploadés';

    private string $backupPath;
    private string $timestamp;

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║           SAUVEGARDE - EGREGORE BUSINESS                     ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        $this->timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $this->backupPath = config('maintenance.backup.path', storage_path('backups'));

        // Créer le dossier de sauvegarde s'il n'existe pas
        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
            $this->info("📁 Dossier de sauvegarde créé : {$this->backupPath}");
        }

        $type = $this->option('type');
        $startTime = microtime(true);
        $backupFiles = [];

        try {
            if (in_array($type, ['full', 'database'])) {
                $dbBackup = $this->backupDatabase();
                if ($dbBackup) {
                    $backupFiles['database'] = $dbBackup;
                }
            }

            if (in_array($type, ['full', 'files'])) {
                $filesBackup = $this->backupFiles();
                if ($filesBackup) {
                    $backupFiles['files'] = $filesBackup;
                }
            }

            // Compresser si plusieurs fichiers ou si demandé
            if ($type === 'full' && !$this->option('no-compress') && count($backupFiles) > 0) {
                $this->createFullBackupArchive($backupFiles);
            }

            // Rotation des anciennes sauvegardes
            $this->rotateBackups();

            // Logger la sauvegarde
            $this->logBackup($backupFiles);

            $this->displaySummary($startTime, $backupFiles);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la sauvegarde : ' . $e->getMessage());
            Log::error('Backup failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return 1;
        }
    }

    /**
     * Sauvegarde la base de données
     */
    private function backupDatabase(): ?string
    {
        $this->info('💾 Sauvegarde de la base de données...');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'mysql') {
            return $this->backupMySQL();
        } elseif ($driver === 'sqlite') {
            return $this->backupSQLite();
        } else {
            $this->warn("   ⚠️ Driver {$driver} non supporté pour la sauvegarde automatique");
            return null;
        }
    }

    /**
     * Sauvegarde MySQL avec mysqldump
     */
    private function backupMySQL(): ?string
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");
        $host = config("database.connections.{$connection}.host");
        $port = config("database.connections.{$connection}.port", 3306);

        $filename = "database_{$this->timestamp}.sql";
        $filepath = "{$this->backupPath}/{$filename}";

        // Détecter mysqldump
        $mysqldump = config('maintenance.backup.mysqldump_path') ?: $this->findMysqldump();

        if (!$mysqldump) {
            $this->error('   ❌ mysqldump non trouvé. Installez mysql-client ou configurez MYSQLDUMP_PATH');
            return null;
        }

        // Construire la commande
        $command = sprintf(
            '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s 2>&1',
            escapeshellarg($mysqldump),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('   ❌ Erreur mysqldump : ' . implode("\n", $output));
            return null;
        }

        // Compresser le fichier SQL
        if (config('maintenance.backup.compress', true) && !$this->option('no-compress')) {
            $gzFilepath = $filepath . '.gz';
            $this->compressFile($filepath, $gzFilepath);
            unlink($filepath);
            $filepath = $gzFilepath;
            $filename .= '.gz';
        }

        $size = $this->formatBytes(filesize($filepath));
        $this->info("   ✓ Base de données sauvegardée : {$filename} ({$size})");

        return $filepath;
    }

    /**
     * Sauvegarde SQLite
     */
    private function backupSQLite(): ?string
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if (!File::exists($database)) {
            $this->error("   ❌ Fichier SQLite non trouvé : {$database}");
            return null;
        }

        $filename = "database_{$this->timestamp}.sqlite";
        $filepath = "{$this->backupPath}/{$filename}";

        // Copier le fichier SQLite
        File::copy($database, $filepath);

        // Compresser
        if (config('maintenance.backup.compress', true) && !$this->option('no-compress')) {
            $gzFilepath = $filepath . '.gz';
            $this->compressFile($filepath, $gzFilepath);
            unlink($filepath);
            $filepath = $gzFilepath;
            $filename .= '.gz';
        }

        $size = $this->formatBytes(filesize($filepath));
        $this->info("   ✓ Base de données sauvegardée : {$filename} ({$size})");

        return $filepath;
    }

    /**
     * Sauvegarde les fichiers uploadés
     */
    private function backupFiles(): ?string
    {
        if (!config('maintenance.backup.include_uploads', true)) {
            $this->info('📂 Sauvegarde des fichiers désactivée');
            return null;
        }

        $this->info('📂 Sauvegarde des fichiers uploadés...');

        $sourcePath = storage_path('app/public');
        
        if (!File::exists($sourcePath) || count(File::allFiles($sourcePath)) === 0) {
            $this->info('   ℹ️ Aucun fichier à sauvegarder');
            return null;
        }

        $filename = "files_{$this->timestamp}.zip";
        $filepath = "{$this->backupPath}/{$filename}";

        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('   ❌ Impossible de créer l\'archive ZIP');
            return null;
        }

        $files = File::allFiles($sourcePath);
        $count = 0;

        foreach ($files as $file) {
            $relativePath = str_replace($sourcePath . '/', '', $file->getRealPath());
            $zip->addFile($file->getRealPath(), $relativePath);
            $count++;
        }

        $zip->close();

        $size = $this->formatBytes(filesize($filepath));
        $this->info("   ✓ {$count} fichiers sauvegardés : {$filename} ({$size})");

        return $filepath;
    }

    /**
     * Crée une archive complète
     */
    private function createFullBackupArchive(array $backupFiles): void
    {
        $this->info('📦 Création de l\'archive complète...');

        $filename = "backup_full_{$this->timestamp}.zip";
        $filepath = "{$this->backupPath}/{$filename}";

        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('   ❌ Impossible de créer l\'archive ZIP complète');
            return;
        }

        foreach ($backupFiles as $type => $file) {
            if ($file && File::exists($file)) {
                $zip->addFile($file, basename($file));
            }
        }

        // Ajouter un fichier d'info
        $info = [
            'created_at' => Carbon::now()->toIso8601String(),
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'files' => array_map('basename', array_filter($backupFiles)),
        ];
        $zip->addFromString('backup_info.json', json_encode($info, JSON_PRETTY_PRINT));

        $zip->close();

        // Supprimer les fichiers individuels
        foreach ($backupFiles as $file) {
            if ($file && File::exists($file)) {
                unlink($file);
            }
        }

        $size = $this->formatBytes(filesize($filepath));
        $this->info("   ✓ Archive complète créée : {$filename} ({$size})");
    }

    /**
     * Rotation des anciennes sauvegardes
     */
    private function rotateBackups(): void
    {
        $this->info('🔄 Rotation des anciennes sauvegardes...');

        $keepLast = $this->option('keep') ?? config('maintenance.backup.keep_last', 7);
        
        // Récupérer toutes les sauvegardes triées par date
        $backups = collect(File::files($this->backupPath))
            ->filter(function ($file) {
                return preg_match('/^(backup_full_|database_|files_)/', $file->getFilename());
            })
            ->sortByDesc(function ($file) {
                return $file->getMTime();
            })
            ->values();

        $toDelete = $backups->slice($keepLast);
        $deletedCount = 0;

        foreach ($toDelete as $file) {
            unlink($file->getRealPath());
            $deletedCount++;
        }

        if ($deletedCount > 0) {
            $this->info("   ✓ {$deletedCount} ancienne(s) sauvegarde(s) supprimée(s)");
        } else {
            $this->info("   ℹ️ Aucune sauvegarde à supprimer");
        }
    }

    /**
     * Trouve le chemin de mysqldump
     */
    private function findMysqldump(): ?string
    {
        $paths = [
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe',
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }

        // Essayer via which
        $result = shell_exec('which mysqldump 2>/dev/null');
        if ($result) {
            return trim($result);
        }

        return null;
    }

    /**
     * Compresse un fichier avec gzip
     */
    private function compressFile(string $source, string $destination): void
    {
        $data = file_get_contents($source);
        $gzdata = gzencode($data, 9);
        file_put_contents($destination, $gzdata);
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

    /**
     * Logger la sauvegarde
     */
    private function logBackup(array $backupFiles): void
    {
        ActivityLog::create([
            'user_id' => null,
            'action' => 'system_backup',
            'description' => 'Sauvegarde système effectuée',
            'new_values' => [
                'files' => array_map('basename', array_filter($backupFiles)),
                'path' => $this->backupPath,
            ],
            'ip_address' => '127.0.0.1',
        ]);

        Log::info('System backup completed', ['files' => $backupFiles]);
    }

    /**
     * Affiche le résumé
     */
    private function displaySummary(float $startTime, array $backupFiles): void
    {
        $duration = round(microtime(true) - $startTime, 2);

        $this->info('');
        $this->info('══════════════════════════════════════════════════════════════');
        $this->info('                         RÉSUMÉ                               ');
        $this->info('══════════════════════════════════════════════════════════════');
        $this->info("Emplacement : {$this->backupPath}");
        $this->info("Durée : {$duration} secondes");
        $this->info('');
        $this->info('✅ Sauvegarde terminée avec succès !');
    }
}
