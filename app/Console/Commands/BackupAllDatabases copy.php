<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupAllDatabases extends Command
{
    protected $signature = 'backup:all-databases
                            {--output= : Directory for backup files (default: storage/app/backups)}
                            {--no-zip : Keep individual SQL files instead of creating a zip}
                            {--include-system : Include MySQL system databases (mysql, sys, etc.)}';

    protected $description = 'Backup all MySQL databases the configured user can access';

    /** @var list<string> */
    protected array $systemDatabases = [
        'information_schema',
        'performance_schema',
        'mysql',
        'sys',
    ];
    /** @var list<string> Additional database names (exact match) included in the goquickqore folder */
    /*local*/
    // protected array $qqDatabases = ['pj'];
    protected array $qqDatabases = ['patel123_erpquickqore','quickqore_reporting'];

    /*local*/
    // protected array $projectMapping = [
    //     'laravelvite' => 'tdpus',
    //     'kyn-task' => 'kyn',
    //     'production_quickob' => 'quickob',
    // ];
    protected array $projectMapping = [
        'devquickqore_quickob' => 'tdpus',
        'kyn_task_system' => 'kyn-task',
    ];
    public function handle(): int
    {
        $connection = $this->backupConnectionName();
        $driver = config("database.connections.{$connection}.driver");

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->error("Connection '{$connection}' uses '{$driver}'. Only mysql/mariadb are supported.");

            return 1;
        }

        try {
            $databases = $this->listAccessibleDatabases();
        } catch (\Throwable $e) {
            $this->error('Could not list databases: '.$e->getMessage());

            return 1;
        }

        if ($databases === []) {
            $this->warn('No databases found to backup.');

            return 0;
        }

        $date = now()->format('Y-m-d');
        $timestamp = now()->format('Y-m-d_His');
        $backupDir = $this->option('output') ?: storage_path('app/backups');
        $workDir = $backupDir.'/all_databases_'.$timestamp;

        if (! File::exists($workDir)) {
            File::makeDirectory($workDir, 0755, true);
        }

        $this->info('Backing up '.count($databases).' database(s)...');
        $this->newLine();

        $dumped = [];
        $failed = [];

        foreach ($databases as $database) {
            $sqlFileName = "{$database}_{$timestamp}.sql";
            $sqlFilePath = $workDir.'/'.$sqlFileName;

            $this->line("  → {$database}");

            if ($this->dumpDatabase($database, $sqlFilePath)) {
                $dumped[] = ['database' => $database, 'path' => $sqlFilePath, 'name' => $sqlFileName];
                $this->info("    ✓ {$this->formatBytes(filesize($sqlFilePath))}");
            } else {
                $failed[] = $database;
                $this->error('    ✗ dump failed');
            }
        }

        $this->newLine();

        if ($dumped === []) {
            File::deleteDirectory($workDir);
            $this->error('All database dumps failed.');

            return 1;
        }

        if ($this->option('no-zip')) {
            $this->info('Backup completed (SQL files only).');
            $this->info("Location: {$workDir}");
        } else {
            $zipDir = $backupDir.'/'.$date;
            if (! File::exists($zipDir)) {
                File::makeDirectory($zipDir, 0755, true);
            }

            $backupGroups = $this->groupDumpedForBackup($dumped, $timestamp);
            $createdArtifacts = [];

            foreach ($backupGroups as $group) {
                if ($group['type'] === 'folder_zips') {
                    $folderPath = $zipDir.'/'.$group['name'];

                    $this->line("Creating {$group['name']}/ (".count($group['files']).' database(s))...');

                    if (! File::exists($folderPath)) {
                        File::makeDirectory($folderPath, 0755, true);
                    }

                    $folderOk = true;

                    foreach ($group['files'] as $file) {
                        $zipFilePath = $folderPath.'/'.$file['database'].'_'.$timestamp.'.zip';

                        if ($this->createZip($zipFilePath, [$file])) {
                            $this->info("  ✓ {$file['database']} ".$this->formatBytes(filesize($zipFilePath)));
                        } else {
                            $this->error("  ✗ failed to create {$file['database']}.zip");
                            $folderOk = false;
                        }
                    }

                    if ($folderOk && count(File::files($folderPath)) > 0) {
                        $createdArtifacts[] = [
                            'type' => 'folder',
                            'path' => $folderPath,
                            'name' => $group['name'],
                        ];
                    } else {
                        $this->error("  ✗ failed to create {$group['name']}/");
                    }

                    continue;
                }

                $zipFilePath = $zipDir.'/'.$group['name'];

                $this->line("Creating {$group['name']} (".count($group['files']).' database(s))...');

                if ($this->createZip($zipFilePath, $group['files'])) {
                    $createdArtifacts[] = [
                        'type' => 'zip',
                        'path' => $zipFilePath,
                        'name' => $group['name'],
                    ];
                    $this->info('  ✓ '.$this->formatBytes(filesize($zipFilePath)));
                } else {
                    $this->error("  ✗ failed to create {$group['name']}");
                }
            }

            File::deleteDirectory($workDir);

            if ($createdArtifacts === []) {
                $this->error('No backup files were created.');

                return 1;
            }

            $this->newLine();
            $this->info('Backup completed successfully!');
            $this->info('Date folder: '.$date);
            $this->info('Backup items: '.count($createdArtifacts));
            $this->info("Location: {$zipDir}");

            $this->syncBackupsToS3($createdArtifacts, $date, $zipDir);
            $this->cleanOldBackups($backupDir);
        }

        if ($failed !== []) {
            $this->warn('Failed databases: '.implode(', ', $failed));

            return 1;
        }

        return 0;
    }

    /**
     * @return list<string>
     */
    protected function listAccessibleDatabases(): array
    {
        $connection = $this->backupConnectionName();
        $db = DB::connection($connection);
        $includeSystem = (bool) $this->option('include-system');

        $explicitDatabases = $this->listDatabasesFromGrants($db);
        $candidates = $explicitDatabases ?? $this->listDatabasesFromShowDatabases($db);

        $databases = [];
        foreach ($candidates as $name) {
            if ($name === '') {
                continue;
            }

            if (! $includeSystem && in_array($name, $this->systemDatabases, true)) {
                continue;
            }

            if (! $this->canAccessDatabase($name)) {
                continue;
            }

            $databases[] = $name;
        }

        sort($databases);

        return $databases;
    }

    /**
     * @return list<string>
     */
    protected function listDatabasesFromShowDatabases(\Illuminate\Database\Connection $db): array
    {
        $databases = [];

        foreach ($db->select('SHOW DATABASES') as $row) {
            $values = (array) $row;
            $name = (string) ($row->Database ?? array_values($values)[0] ?? '');

            if ($name !== '') {
                $databases[] = $name;
            }
        }

        return $databases;
    }

    /**
     * Prefer explicit per-database grants over global *.* when both exist.
     *
     * @return list<string>|null Explicit database names, or null to fall back to SHOW DATABASES.
     */
    protected function listDatabasesFromGrants(\Illuminate\Database\Connection $db): ?array
    {
        $databases = [];
        $hasGlobalBackupPrivilege = false;

        foreach ($db->select('SHOW GRANTS FOR CURRENT_USER()') as $grant) {
            $line = (string) array_values((array) $grant)[0];

            if (preg_match('/\sON\s+`?\*`?\.`?\*`?\s+/i', $line)) {
                if (preg_match('/\b(SELECT|LOCK TABLES|ALL(?:\s+PRIVILEGES)?)\b/i', $line)) {
                    $hasGlobalBackupPrivilege = true;
                }

                continue;
            }

            if (preg_match('/\sON\s+`([^`]+)`\.\*`?\s+/i', $line, $matches)) {
                $databases[] = $matches[1];
            }
        }

        $databases = array_values(array_unique($databases));

        if ($databases !== []) {
            return $databases;
        }

        return $hasGlobalBackupPrivilege ? null : [];
    }

    protected function backupConnectionName(): string
    {
        return (string) config('database.all_db');
    }

    protected function canAccessDatabase(string $database): bool
    {
        try {
            DB::connection($this->backupConnectionName())
                ->select('SHOW TABLES FROM '.$this->quoteDatabaseIdentifier($database));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function quoteDatabaseIdentifier(string $database): string
    {
        return '`'.str_replace('`', '``', $database).'`';
    }

    protected function dumpDatabase(string $database, string $outputPath): bool
    {
        $connection = $this->backupConnectionName();
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");
        $host = config("database.connections.{$connection}.host");
        $port = config("database.connections.{$connection}.port", 3306);

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

        if (! empty($password)) {
            $command[] = "--password={$password}";
        }

        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('    '.trim($process->getErrorOutput()) ?: trim($process->getOutput()) ?: 'Unknown mysqldump error');

            return false;
        }

        return File::exists($outputPath) && filesize($outputPath) > 0;
    }

    /**
     * @param  list<array{database: string, path: string, name: string}>  $dumped
     */
    protected function createZip(string $zipFilePath, array $dumped): bool
    {
        $zip = new ZipArchive();

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('Failed to create zip file.');

            return false;
        }

        foreach ($dumped as $file) {
            $zip->addFile($file['path'], 'databases/'.$file['database'].'/'.$file['name']);
        }

        try {
            $zip->close();
        } catch (\Throwable $e) {
            $this->error('Zip close failed: '.$e->getMessage());
            @unlink($zipFilePath);

            return false;
        }

        return File::exists($zipFilePath) && filesize($zipFilePath) > 0;
    }

    /**
     * @param  list<array{database: string, path: string, name: string}>  $dumped
     * @return list<array{type: 'folder_zips'|'zip', name: string, files: list<array{database: string, path: string, name: string}>}>
     */
    protected function groupDumpedForBackup(array $dumped, string $timestamp): array
    {
        $goQuickqoreGroup = [];
        $others = [];

        foreach ($dumped as $file) {
            if ($this->belongsToGoQuickqoreBackup($file['database'])) {
                $goQuickqoreGroup[] = $file;
            } else {
                $others[] = $file;
            }
        }

        $groups = [];

        if ($goQuickqoreGroup !== []) {
            $groups[] = [
                'type' => 'folder_zips',
                'name' => "goquickqore_{$timestamp}",
                'files' => $goQuickqoreGroup,
            ];
        }

        foreach ($others as $file) {
            $backupName = $this->resolveBackupName($file['database']);
            $groups[] = [
                'type' => 'zip',
                'name' => "{$backupName}_{$timestamp}.zip",
                'files' => [$file],
            ];
        }

        return $groups;
    }

    protected function resolveBackupName(string $database): string
    {
        return $this->projectMapping[$database] ?? $database;
    }

    protected function belongsToGoQuickqoreBackup(string $database): bool
    {
        if (str_starts_with($database, 'qq_') || str_starts_with($database, 'pj_')) {
            return true;
        }

        return in_array($database, $this->qqDatabases, true);
    }

    /**
     * @param  list<array{type: 'folder'|'zip', path: string, name: string}>  $artifacts
     */
    protected function syncBackupsToS3(array $artifacts, string $date, string $localDateFolder): void
    {
        $enabled = filter_var(env('BACKUP_S3_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
        if (! $enabled) {
            $this->info('S3 backup skipped (BACKUP_S3_ENABLED is false).');

            return;
        }

        $bucket = config('filesystems.disks.s3-backup.bucket');
        if (empty($bucket)) {
            $this->warn('S3 backup skipped: AWS_BACKUP_BUCKET / AWS_BUCKET is not set.');

            return;
        }

        $prefix = $this->buildS3DatePrefix($date);
        $allSucceeded = true;

        foreach ($artifacts as $artifact) {
            if ($artifact['type'] === 'folder') {
                if (! $this->uploadFolderToS3($artifact['path'], $prefix.'/'.$artifact['name'], $bucket)) {
                    $allSucceeded = false;
                }
            } elseif (! $this->uploadFileToS3($artifact['path'], $prefix.'/'.$artifact['name'], $bucket)) {
                $allSucceeded = false;
            }
        }

        if ($allSucceeded) {
            $keepDays = max(1, (int) env('BACKUP_S3_KEEP_DAYS', 30));
            // $this->pruneS3DateFolders($this->buildS3BasePrefix(), $keepDays);

            if (File::isDirectory($localDateFolder)) {
                File::deleteDirectory($localDateFolder);
                $this->info("Local backup folder removed after S3 upload: {$localDateFolder}");
            }
        } else {
            $this->warn('Local backup kept because one or more S3 uploads failed.');
        }
    }

    protected function buildS3BasePrefix(): string
    {
        // $root = trim((string) env('BACKUP_S3_PREFIX', 'backups'), '/');
        $root = '';
        // $root = trim((string) env('BACKUP_S3_PREFIX', 'backups'), '/');

        return $root;
    }

    protected function buildS3DatePrefix(string $date): string
    {
        return $this->buildS3BasePrefix().'/'.$date;
    }

    protected function uploadFolderToS3(string $localFolder, string $s3Prefix, string $bucket): bool
    {
        $files = File::files($localFolder);

        if ($files === []) {
            $this->warn("S3 backup skipped: no files found in {$localFolder}.");

            return false;
        }

        foreach ($files as $file) {
            $key = $s3Prefix.'/'.basename($file->getPathname());
            if (! $this->uploadFileToS3($file->getPathname(), $key, $bucket)) {
                return false;
            }
        }

        return true;
    }

    protected function uploadFileToS3(string $filePath, string $key, string $bucket): bool
    {
        $stream = fopen($filePath, 'r');
        if ($stream === false) {
            $this->warn("S3 backup skipped: could not read {$filePath}.");

            return false;
        }

        try {
            $ok = Storage::disk('s3-backup')->put($key, $stream);

            if ($ok) {
                $this->info("Uploaded to S3 ({$bucket}): {$key}");
            } else {
                $this->warn("S3 upload failed for key: {$key}");
            }

            return (bool) $ok;
        } catch (\Throwable $e) {
            $this->warn('S3 upload failed: '.$e->getMessage());

            return false;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * Delete S3 date folders older than $keepDays under $basePrefix.
     */
    protected function pruneS3DateFolders(string $basePrefix, int $keepDays): void
    {
        try {
            $disk = Storage::disk('s3-backup');
            $directories = $disk->directories($basePrefix);
            $threshold = now()->subDays($keepDays)->startOfDay();
            $removed = 0;

            foreach ($directories as $directory) {
                $date = basename($directory);
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    continue;
                }

                try {
                    $folderDate = Carbon::parse($date)->startOfDay();
                } catch (\Throwable) {
                    continue;
                }

                if ($folderDate->gte($threshold)) {
                    continue;
                }

                try {
                    $disk->deleteDirectory($directory);
                    $removed++;
                } catch (\Throwable $e) {
                    $this->warn("Could not delete old S3 folder {$directory}: {$e->getMessage()}");
                }
            }

            if ($removed > 0) {
                $this->info("S3 retention: removed {$removed} date folder(s) older than {$keepDays} day(s).");
            }
        } catch (\Throwable $e) {
            $this->warn('S3 retention cleanup failed: '.$e->getMessage());
        }
    }

    protected function cleanOldBackups(string $backupDir): void
    {
        $keepDays = max(1, (int) env('BACKUP_S3_KEEP_DAYS', 30));
        $threshold = now()->subDays($keepDays)->startOfDay();
        $deletedCount = 0;

        foreach (File::directories($backupDir) as $directory) {
            $date = basename($directory);
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }

            try {
                $folderDate = Carbon::parse($date)->startOfDay();
            } catch (\Throwable) {
                continue;
            }

            if ($folderDate->lt($threshold)) {
                File::deleteDirectory($directory);
                $deletedCount++;
            }
        }

        foreach (File::glob($backupDir.'/all_databases_backup_*.zip') as $file) {
            File::delete($file);
            $deletedCount++;
        }

        if ($deletedCount > 0) {
            $this->info("Cleaned up {$deletedCount} old local backup item(s).");
        }
    }

    protected function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
