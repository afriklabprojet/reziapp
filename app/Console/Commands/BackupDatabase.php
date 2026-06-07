<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rezi:backup-database
                            {--compress : Compress the backup file}
                            {--keep=7 : Number of days to keep backups}';

    /**
     * The console command description.
     */
    protected $description = 'Create a backup of the Rezi App database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting database backup...');

        try {
            // Get database configuration
            $connection = config('database.default');
            $database = config("database.connections.{$connection}.database");
            $host = config("database.connections.{$connection}.host");
            $port = config("database.connections.{$connection}.port");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");

            // Generate backup filename
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$database}_{$timestamp}.sql";
            $backupPath = storage_path("app/backups/{$filename}");

            // Ensure backup directory exists
            $backupDir = storage_path('app/backups');
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Create backup based on database type
            if ($connection === 'mysql' || $connection === 'mariadb') {
                $this->backupMysql($host, $port, $username, $password, $database, $backupPath);
            } elseif ($connection === 'sqlite') {
                $this->backupSqlite($database, $backupPath);
            } else {
                $this->error("Unsupported database connection: {$connection}");

                return Command::FAILURE;
            }

            // Compress if requested
            if ($this->option('compress') && file_exists($backupPath)) {
                $compressedPath = $backupPath.'.gz';
                $this->compressFile($backupPath, $compressedPath);
                unlink($backupPath);
                $filename = $filename.'.gz';
                $backupPath = $compressedPath;
            }

            // Get file size
            $fileSize = $this->formatBytes(filesize($backupPath));

            $this->info('Backup created successfully!');
            $this->info("Location: {$backupPath}");
            $this->info("Size: {$fileSize}");

            // Clean up old backups
            $keepDays = (int) $this->option('keep');
            $this->cleanOldBackups($keepDays);

            // Log the backup
            $this->logBackup($filename, filesize($backupPath));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Backup MySQL/MariaDB database
     */
    protected function backupMysql(string $host, string $port, string $username, string $password, string $database, string $outputPath): void
    {
        $mysqldump = $this->findMysqldump();

        if (!$mysqldump) {
            throw new \RuntimeException('mysqldump command not found');
        }

        // Write credentials to a temp file so the password never appears in ps/proc args
        $configFile = tempnam(sys_get_temp_dir(), 'rezi_bk_');
        file_put_contents($configFile, "[mysqldump]\npassword=".str_replace('"', '\\"', $password)."\n");
        chmod($configFile, 0600);

        try {
            $command = sprintf(
                '%s --defaults-extra-file=%s --host=%s --port=%s --user=%s --single-transaction --routines --triggers --events %s > %s',
                escapeshellarg($mysqldump),
                escapeshellarg($configFile),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($database),
                escapeshellarg($outputPath),
            );

            $this->line('Executing mysqldump...');
            exec($command.' 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \RuntimeException('mysqldump failed: '.implode("\n", $output));
            }
        } finally {
            unlink($configFile);
        }
    }

    /**
     * Backup SQLite database
     */
    protected function backupSqlite(string $databasePath, string $outputPath): void
    {
        if (!file_exists($databasePath)) {
            throw new \RuntimeException("SQLite database not found: {$databasePath}");
        }

        copy($databasePath, str_replace('.sql', '.sqlite', $outputPath));
    }

    /**
     * Find mysqldump binary
     */
    protected function findMysqldump(): ?string
    {
        $possiblePaths = [
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
            '/opt/cpanel/ea-php84/root/usr/bin/mysqldump',
            'mysqldump', // Use PATH
        ];

        foreach ($possiblePaths as $path) {
            if (is_executable($path)) {
                return $path;
            }
            $found = shell_exec('which '.escapeshellarg($path).' 2>/dev/null');
            if ($found !== null && trim($found) !== '') {
                return trim($found);
            }
        }

        return null;
    }

    /**
     * Compress file with gzip
     */
    protected function compressFile(string $sourcePath, string $destPath): void
    {
        $this->line('Compressing backup...');

        $input = fopen($sourcePath, 'rb');
        $output = gzopen($destPath, 'wb9');

        while (!feof($input)) {
            gzwrite($output, fread($input, 524288)); // 512KB chunks
        }

        fclose($input);
        gzclose($output);
    }

    /**
     * Clean up old backups
     */
    protected function cleanOldBackups(int $keepDays): void
    {
        $backupDir = storage_path('app/backups');
        $threshold = Carbon::now()->subDays($keepDays);
        $deletedCount = 0;

        foreach (glob("{$backupDir}/backup_*") as $file) {
            $fileTime = Carbon::createFromTimestamp(filemtime($file));
            if ($fileTime->lt($threshold)) {
                unlink($file);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("Cleaned up {$deletedCount} old backup(s)");
        }
    }

    /**
     * Log backup to database
     */
    protected function logBackup(string $filename, int $filesize): void
    {
        try {
            DB::table('activity_log')->insert([
                'log_name' => 'system',
                'description' => 'Database backup created',
                'subject_type' => 'backup',
                'subject_id' => 0,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => json_encode([
                    'filename' => $filename,
                    'size' => $filesize,
                    'created_at' => now()->toISOString(),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail if activity_log table doesn't exist
        }
    }

    /**
     * Format bytes to human readable string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
