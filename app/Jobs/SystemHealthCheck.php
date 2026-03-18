<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * System health check — probes all critical dependencies.
 * Results cached for dashboards/monitoring endpoints.
 */
class SystemHealthCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        $checks = [];

        // 1. Database
        $checks['database'] = $this->checkDatabase();

        // 2. Cache
        $checks['cache'] = $this->checkCache();

        // 3. Storage
        $checks['storage'] = $this->checkStorage();

        // 4. Queue (self-check — if this job runs, queue works)
        $checks['queue'] = ['status' => 'ok', 'response_ms' => 0, 'details' => 'Job executed successfully'];

        // 5. Jeko API
        $checks['jeko'] = $this->checkJekoApi();

        // Store in cache for quick access
        Cache::put('system:health', $checks, now()->addMinutes(10));

        // Persist to DB for history (silently)
        try {
            foreach ($checks as $component => $result) {
                DB::table('system_health_checks')->insert([
                    'component' => $component,
                    'status' => $result['status'],
                    'response_ms' => $result['response_ms'],
                    'details' => $result['details'] ?? null,
                    'checked_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // Don't fail the job if DB insert fails
        }

        // Alert on degraded components
        $degraded = array_filter($checks, fn ($c) => $c['status'] !== 'ok');
        if (! empty($degraded)) {
            Log::channel('critical')->warning('SystemHealthCheck: Degraded components', $degraded);
        }
    }

    private function checkDatabase(): array
    {
        $start = microtime(true);
        try {
            DB::select('SELECT 1');
            $ms = round((microtime(true) - $start) * 1000);
            return ['status' => $ms > 1000 ? 'degraded' : 'ok', 'response_ms' => $ms];
        } catch (\Throwable $e) {
            return ['status' => 'down', 'response_ms' => 0, 'details' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        $start = microtime(true);
        try {
            $key = 'health:probe:' . uniqid();
            Cache::put($key, 'ok', 10);
            $value = Cache::get($key);
            Cache::forget($key);
            $ms = round((microtime(true) - $start) * 1000);
            return ['status' => $value === 'ok' ? ($ms > 500 ? 'degraded' : 'ok') : 'down', 'response_ms' => $ms];
        } catch (\Throwable $e) {
            return ['status' => 'down', 'response_ms' => 0, 'details' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        $start = microtime(true);
        try {
            $file = 'health-check-probe.tmp';
            Storage::put($file, 'ok');
            $value = Storage::get($file);
            Storage::delete($file);
            $ms = round((microtime(true) - $start) * 1000);
            return ['status' => $value === 'ok' ? 'ok' : 'degraded', 'response_ms' => $ms];
        } catch (\Throwable $e) {
            return ['status' => 'down', 'response_ms' => 0, 'details' => $e->getMessage()];
        }
    }

    private function checkJekoApi(): array
    {
        if (! config('services.jeko.api_key')) {
            return ['status' => 'ok', 'response_ms' => 0, 'details' => 'Not configured'];
        }

        $start = microtime(true);
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.jeko.api_key'),
                    'X-Api-Key-Id' => config('services.jeko.api_key_id'),
                ])
                ->get(config('services.jeko.base_url', 'https://api.jeko.africa') . '/health');
            $ms = round((microtime(true) - $start) * 1000);
            return [
                'status' => $response->successful() ? ($ms > 3000 ? 'degraded' : 'ok') : 'degraded',
                'response_ms' => $ms,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'degraded', 'response_ms' => 0, 'details' => 'API unreachable'];
        }
    }
}
