<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        foreach ($this->userAwareRateLimits() as [$name, $method, $maxAttempts]) {
            RateLimiter::for($name, function (Request $request) use ($method, $maxAttempts) {
                return Limit::{$method}($maxAttempts)->by(
                    $request->user()?->id ?: $request->ip()
                );
            });
        }

        foreach ($this->ipRateLimits() as [$name, $method, $maxAttempts]) {
            RateLimiter::for($name, function (Request $request) use ($method, $maxAttempts) {
                return Limit::{$method}($maxAttempts)->by($request->ip());
            });
        }

        RateLimiter::for('otp', function (Request $request) {
            return [
                Limit::perMinute(1)->by($request->ip()),
                Limit::perHour(5)->by($request->user()?->id ?: $request->ip()),
            ];
        });
    }

    /** @return array<int, array{string, string, int}> */
    private function userAwareRateLimits(): array
    {
        return [
            ['api', 'perMinute', 60],
            ['geo-search', 'perMinute', 120],
            ['contact', 'perHour', 10],
            ['upload', 'perMinute', 20],
            ['booking', 'perHour', 10],
            ['review', 'perDay', 5],
            ['report', 'perHour', 5],
            ['export', 'perHour', 10],
            ['push', 'perMinute', 30],
            ['chat', 'perMinute', 60],
            ['residence-create', 'perDay', 5],
            ['payment', 'perHour', 20],
            ['admin', 'perMinute', 30],
        ];
    }

    /** @return array<int, array{string, string, int}> */
    private function ipRateLimits(): array
    {
        return [
            ['login', 'perMinute', 5],
            ['register', 'perHour', 3],
            ['webhook', 'perMinute', 20],
            ['password-reset', 'perHour', 3],
        ];
    }
}
