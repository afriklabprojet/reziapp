<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Log les actions critiques de l'API (mutations) pour audit trail.
 * Appliqué uniquement aux routes sensibles (POST/PUT/PATCH/DELETE).
 */
class AuditApiActions
{
    /**
     * Routes sensibles à logger (préfixe match).
     */
    private const AUDITED_PREFIXES = [
        'api/v1/auth',
        'api/v1/owner',
        'api/v1/admin',
        'api/v1/push',
        'api/webhooks',
    ];

    /**
     * Routes de paiement (log vers channel dédié).
     */
    private const PAYMENT_PREFIXES = [
        'api/webhooks',
        'payments/',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $response = $next($request);

        // Ne logger que les mutations (pas les GET)
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $response;
        }

        // Vérifier si la route est dans les préfixes audités
        $path = $request->path();
        $shouldAudit = false;
        $isPayment = false;

        foreach (self::AUDITED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $shouldAudit = true;
                break;
            }
        }

        foreach (self::PAYMENT_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $isPayment = true;
                $shouldAudit = true;
                break;
            }
        }

        if (! $shouldAudit) {
            return $response;
        }

        $statusCode = $response->getStatusCode();
        $level = $statusCode >= 400 ? 'warning' : 'info';
        $durationMs = round((microtime(true) - $startTime) * 1000);

        $logData = [
            'method' => $request->method(),
            'path' => $path,
            'status' => $statusCode,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 200),
            'duration_ms' => $durationMs,
        ];

        // Route to appropriate channel
        $channel = $isPayment ? 'payments' : 'audit';
        Log::channel($channel)->$level('API Audit', $logData);

        // Alert on slow requests (>3s)
        if ($durationMs > 3000) {
            Log::channel('critical')->warning('Slow API request', $logData);
        }

        return $response;
    }
}
