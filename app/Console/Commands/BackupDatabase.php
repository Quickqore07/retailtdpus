<?php

namespace App\Console\Commands;

use App\Services\MailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:create {--database-only : Backup only the database} {--code-only : Backup only the code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a backup of the database and/or application code';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $databaseOnly = $this->option('database-only');
        $codeOnly = $this->option('code-only');

        // Determine what to backup
        $backupDatabase = !$codeOnly;
        $backupCode = !$databaseOnly;

        $timestamp = now()->format('Y-m-d_His');
        $backupDir = storage_path('app/backups');
        
        // Ensure backup directory exists
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
            $this->info('Created backup directory: ' . $backupDir);
        }

        try {
            $zipFileName = "backup_{$timestamp}.zip";
            $zipFilePath = $backupDir . '/' . $zipFileName;
            
            $zip = new ZipArchive();
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $this->error('Failed to create zip file');
                return 1;
            }

            // Backup database
            if ($backupDatabase) {
                $this->info('Starting database backup...');
                $sqlFileName = "database_{$timestamp}.sql";
                $sqlFilePath = $backupDir . '/' . $sqlFileName;
                
                if ($this->dumpDatabase($sqlFilePath)) {
                    $zip->addFile($sqlFilePath, 'database/' . $sqlFileName);
                    $this->info('Database backup added to zip');
                } else {
                    $this->error('Database backup failed');
                    $zip->close();
                    @unlink($zipFilePath);
                    return 1;
                }
            }

            // Backup code
            if ($backupCode) {
                $this->info('Starting code backup...');
                $this->addDirectoryToZip($zip, base_path(), 'code');
                $this->info('Code backup added to zip');
            }

            try {
                $zip->close();
            } catch (\Throwable $e) {
                throw new \Exception("Zip close failed: " . $e->getMessage());
            }

            // Clean up temporary SQL file
            if ($backupDatabase && isset($sqlFilePath) && File::exists($sqlFilePath)) {
                @unlink($sqlFilePath);
            }

            $fileSize = $this->formatBytes(filesize($zipFilePath));
            $this->info("Backup completed successfully!");
            $this->info("File: {$zipFileName}");
            $this->info("Size: {$fileSize}");
            $this->info("Location: {$backupDir}");

            $this->syncBackupToS3($zipFilePath, $zipFileName);

            $this->sendBackupSuccessEmail($zipFileName, $fileSize);

            // Clean old backups (keep last 30 days)
            $this->cleanOldBackups($backupDir);

            return 0;
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Dump database to SQL file
     */
    protected function dumpDatabase($outputPath)
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");
        $host = config("database.connections.{$connection}.host");
        $port = config("database.connections.{$connection}.port", 3306);

        if ($driver === 'mysql') {
            $mysqldumpBinary = env('MYSQLDUMP_BINARY', 'mysqldump');
            $command = [
                $mysqldumpBinary,
                "--user={$username}",
                "--host={$host}",
                "--port={$port}",
                '--single-transaction',
                '--quick',
                '--routines',
                '--triggers',
                '--events',
                '--default-character-set=utf8mb4',
                '--set-gtid-purged=OFF',
                "--result-file={$outputPath}",
                $database,
            ];

            if (!empty($password)) {
                $command[] = "--password={$password}";
            }

            $process = new Process($command);
            $process->setTimeout(1800);
            $process->run();

            if (!$process->isSuccessful()) {
                $this->error('mysqldump command failed. Error output:');
                $this->error(trim($process->getErrorOutput()) ?: trim($process->getOutput()) ?: 'Unknown mysqldump error');
                return false;
            }

            return File::exists($outputPath) && filesize($outputPath) > 0;
        } else {
            $this->error("Database connection type '{$connection}' is not supported for backup");
            return false;
        }
    }

    /**
     * Add directory to zip archive recursively
     */
    protected function addDirectoryToZip(ZipArchive $zip, $path, $zipPath = '')
    {
        $basePath = base_path();
        
        // Directories to exclude
        $excludeDirs = [
            'node_modules',
            'vendor',
            'storage',
            // 'storage/logs',
            // 'storage/framework/cache',
            // 'storage/app/backups',
            // 'storage/framework/sessions',
            // 'storage/framework/views',
            // 'storage/backups',
            'tdpus',
            '.git',
            '.idea',
            'public/build',
            'bootstrap/cache',

        ];

        // Files to exclude
        $excludeFiles = [
            '.env',
            'composer.lock',
            'package-lock.json',
        ];

        if (is_dir($path)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $filePath);
                $relativePath = str_replace('\\', '/', $relativePath);

                // Check if path should be excluded
                $shouldExclude = false;
                foreach ($excludeDirs as $excludeDir) {
                    $excludeDir = str_replace('/', DIRECTORY_SEPARATOR, $excludeDir);
                    if (str_contains($relativePath, $excludeDir)) {
                        $shouldExclude = true;
                        break;
                    }
                }

                // Check if file should be excluded
                $fileName = basename($filePath);
                if (in_array($fileName, $excludeFiles)) {
                    $shouldExclude = true;
                }

                if ($shouldExclude) {
                    continue;
                }

                $zipFilePath = $zipPath . '/' . $relativePath;

                if ($file->isDir()) {
                    $zip->addEmptyDir($zipFilePath);
                } else {
                   // Skip unreadable files
                    if (!is_readable($filePath)) {
                        continue;
                    }

                    // Skip large/locked files silently
                    try {
                        $zip->addFile($filePath, $zipFilePath);
                    } catch (\Throwable $e) {
                        // Skip locked file (Windows fix)
                        continue;
                    }
                }
            }
        }
    }

    /**
     * Copy the backup zip to S3 (in addition to storage/app/backups) when configured.
     */
    protected function syncBackupToS3(string $zipFilePath, string $zipFileName): void
    {
        $enabled = filter_var(env('BACKUP_S3_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
        if (!$enabled) {
            $this->info('S3 backup skipped (BACKUP_S3_ENABLED is false).');

            return;
        }

        $bucket = config('filesystems.disks.s3-backup.bucket');
        if (empty($bucket)) {
            $this->warn('S3 backup skipped: AWS_BACKUP_BUCKET / AWS_BUCKET is not set.');

            return;
        }

        $prefix = trim((string) env('BACKUP_S3_PREFIX', 'backups'), '/');
        $key = $prefix === '' ? $zipFileName : $prefix.'/'.$zipFileName;

        $stream = fopen($zipFilePath, 'r');
        if ($stream === false) {
            $this->warn('S3 backup skipped: could not read local backup file.');

            return;
        }

        try {
            $ok = Storage::disk('s3-backup')->put($key, $stream);

            if ($ok) {
                $this->info("Uploaded backup to S3 ({$bucket}): {$key}");
                $keep = max(1, (int) env('BACKUP_S3_KEEP', 15));
                $this->pruneS3Backups($prefix, $keep);
            } else {
                $this->warn("S3 upload failed for key: {$key}");
            }
        } catch (\Throwable $e) {
            $this->warn('S3 upload failed: '.$e->getMessage());
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * Delete older backup zips on S3, keeping only the newest $keep files under $prefix.
     */
    protected function pruneS3Backups(string $prefix, int $keep): void
    {
        try {
            $disk = Storage::disk('s3-backup');
            $files = $disk->files($prefix);
            $backupFiles = array_values(array_filter($files, function (string $path): bool {
                $name = basename($path);

                return str_starts_with($name, 'backup_') && str_ends_with($name, '.zip');
            }));

            if (count($backupFiles) <= $keep) {
                return;
            }

            $withTime = [];
            foreach ($backupFiles as $path) {
                try {
                    $withTime[] = ['path' => $path, 'time' => $disk->lastModified($path)];
                } catch (\Throwable) {
                    continue;
                }
            }

            usort($withTime, fn (array $a, array $b): int => $b['time'] <=> $a['time']);
            $toDelete = array_slice(array_column($withTime, 'path'), $keep);

            $removed = 0;
            foreach ($toDelete as $path) {
                try {
                    $disk->delete($path);
                    $removed++;
                } catch (\Throwable $e) {
                    $this->warn("Could not delete old S3 backup {$path}: {$e->getMessage()}");
                }
            }

            if ($removed > 0) {
                $this->info("S3 retention: removed {$removed} old backup(s), keeping newest {$keep}.");
            }
        } catch (\Throwable $e) {
            $this->warn('S3 retention cleanup failed: '.$e->getMessage());
        }
    }

    /**
     * Clean old backup files (keep last 30 days)
     */
    protected function cleanOldBackups($backupDir)
    {
        $files = File::glob($backupDir . '/backup_*.zip');
        $threshold = now()->subDays(30);

        $deletedCount = 0;
        foreach ($files as $file) {
            $fileTime = File::lastModified($file);
            if ($fileTime < $threshold->timestamp) {
                File::delete($file);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("Cleaned up {$deletedCount} old backup file(s)");
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Send backup success email (information only)
     */
    protected function sendBackupSuccessEmail(string $zipFileName, string $fileSize): void
    {
        $recipient = env('BACKUP_NOTIFICATION_EMAIL') ?? 'quickqore7@gmail.com';

        if (empty($recipient)) {
            $this->warn('BACKUP_NOTIFICATION_EMAIL is not set. Skipping backup email notification.');
            return;
        }

        try {
            $subject = 'Backup Completed Successfully - ' . 'Quick OB';
            $content = '<p>Hello,</p>'
                . '<p>The backup has completed successfully.</p>'
                . '<p>'
                . "Backup file: {$zipFileName}<br>"
                . "Backup size: {$fileSize}<br>"
                . 'Generated at: ' . now()->toDateTimeString()
                . '</p>'
                . '<p>Regards,<br>' . 'Quick OB' . '</p>';

            MailService::sendMail(
                $recipient,
                $subject,
                $content,
                []
            );

            $this->info("Backup success email sent to {$recipient}");
        } catch (\Throwable $e) {
            // Do not fail backup if only email notification fails.
            $this->warn('Backup email notification failed: ' . $e->getMessage());
        }
    }
}
