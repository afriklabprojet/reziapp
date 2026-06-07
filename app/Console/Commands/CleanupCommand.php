<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CleanupCommand extends Command
{
    protected $signature = 'rezi:cleanup
                            {--dry-run : Afficher ce qui serait supprimé sans supprimer}';

    protected $description = 'Nettoyage hebdomadaire : logs anciens, fichiers temporaires, caches obsolètes';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $totalFreed = 0;

        $this->info('🧹 Rezi App — Nettoyage '.($dryRun ? '(simulation)' : 'en cours').'...');
        $this->newLine();

        // 1. Logs Laravel > 7 jours
        $totalFreed += $this->cleanOldLogs($dryRun);

        // 2. Fichiers temporaires de storage
        $totalFreed += $this->cleanTempFiles($dryRun);

        // 3. Cache des vues compilées obsolètes
        if (! $dryRun) {
            $this->call('view:clear');
            $this->call('view:cache');
        }
        $this->info('  ✓ Cache des vues rafraîchi');

        // 4. Purge des sessions expirées (si file driver)
        $totalFreed += $this->cleanExpiredSessions($dryRun);

        $this->newLine();
        $this->info(sprintf(
            '✅ Nettoyage terminé — %s libéré(s)',
            $this->formatBytes($totalFreed),
        ));

        Log::info('Rezi App cleanup terminé', [
            'freed_bytes' => $totalFreed,
            'dry_run' => $dryRun,
        ]);

        return self::SUCCESS;
    }

    private function cleanOldLogs(bool $dryRun): int
    {
        $freed = 0;
        $logPath = storage_path('logs');

        if (! File::isDirectory($logPath)) {
            return 0;
        }

        $files = File::glob($logPath.'/laravel-*.log');
        $cutoff = now()->subDays(7)->timestamp;

        foreach ($files as $file) {
            if (File::lastModified($file) < $cutoff) {
                $size = File::size($file);
                $freed += $size;

                if (! $dryRun) {
                    File::delete($file);
                }

                $this->line(sprintf(
                    '  %s %s (%s)',
                    $dryRun ? '⏳' : '🗑️',
                    basename($file),
                    $this->formatBytes($size),
                ));
            }
        }

        $this->info(sprintf('  ✓ Logs anciens : %s', $this->formatBytes($freed)));

        return $freed;
    }

    private function cleanTempFiles(bool $dryRun): int
    {
        $freed = 0;
        $tempPaths = [
            storage_path('app/temp'),
            storage_path('app/public/temp'),
        ];

        foreach ($tempPaths as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            $files = File::allFiles($path);
            $cutoff = now()->subHours(24)->timestamp;

            foreach ($files as $file) {
                if ($file->getMTime() < $cutoff) {
                    $size = $file->getSize();
                    $freed += $size;

                    if (! $dryRun) {
                        File::delete($file->getPathname());
                    }
                }
            }
        }

        $this->info(sprintf('  ✓ Fichiers temporaires : %s', $this->formatBytes($freed)));

        return $freed;
    }

    private function cleanExpiredSessions(bool $dryRun): int
    {
        $freed = 0;

        if (config('session.driver') !== 'file') {
            return 0;
        }

        $sessionPath = storage_path('framework/sessions');
        if (! File::isDirectory($sessionPath)) {
            return 0;
        }

        $lifetime = config('session.lifetime', 120);
        $cutoff = now()->subMinutes($lifetime * 2)->timestamp;

        foreach (File::files($sessionPath) as $file) {
            if ($file->getMTime() < $cutoff) {
                $size = $file->getSize();
                $freed += $size;

                if (! $dryRun) {
                    File::delete($file->getPathname());
                }
            }
        }

        $this->info(sprintf('  ✓ Sessions expirées : %s', $this->formatBytes($freed)));

        return $freed;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $i = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 1).' '.$units[$i];
    }
}
