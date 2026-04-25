<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // ── Nettoyage quotidien ──
        $schedule->command('sanctum:prune-expired --hours=24')
            ->daily()
            ->at('02:00')
            ->description('Purge des tokens Sanctum expirés');

        $schedule->command('queue:prune-batches --hours=48')
            ->daily()
            ->at('02:15')
            ->description('Purge des anciens batches de queue');

        // ── SEO ──
        $schedule->command('rezi:generate-sitemap')
            ->daily()
            ->at('03:00')
            ->description('Régénération du sitemap XML');

        // ── Maintenance ──
        $schedule->command('view:cache')
            ->daily()
            ->at('04:00')
            ->description('Cache des vues Blade');

        $schedule->command('rezi:cleanup')
            ->weekly()
            ->sundays()
            ->at('05:00')
            ->description('Nettoyage hebdomadaire (logs, temp)');

        // ── Sponsoring ──
        $schedule->command('rezi:manage-sponsored-listings')
            ->everyFifteenMinutes()
            ->description('Gestion auto des mises en avant (expirées / budget épuisé)');

        // ── Backup Database ──
        $schedule->command('rezi:backup-database --compress')
            ->dailyAt('01:00')
            ->description('Backup quotidien de la base de données');

        // ── Process Price Alerts ──
        $schedule->command('rezi:process-price-alerts')
            ->hourly()
            ->description('Traitement des alertes de prix');

        // ── Bulletproof : Expire stale bookings & orphaned payments ──
        $schedule->job(new \App\Jobs\ExpireStaleBookings())
            ->everyFiveMinutes()
            ->description('Expire les réservations en attente de paiement (>30min)');

        // ── Bulletproof : System health check ──
        $schedule->job(new \App\Jobs\SystemHealthCheck())
            ->everyFiveMinutes()
            ->description('Vérification santé système (DB, cache, stockage, Jeko)');

        // ── Bulletproof : Prune old webhook events (90 jours) ──
        $schedule->call(fn () => \App\Models\WebhookEvent::prune(90))
            ->weekly()
            ->sundays()
            ->at('04:00')
            ->description('Nettoyage anciensévénements webhook');

        // ── Newsletter hebdomadaire (Option C) ──
        $schedule->command('newsletter:weekly')
            ->weeklyOn(1, '09:00')
            ->description('Newsletter hebdomadaire — meilleures résidences récentes');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware aliases pour les routes
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'ensure.owner' => \App\Http\Middleware\EnsureOwnership::class,
            'identity.verified' => \App\Http\Middleware\EnsureIdentityVerified::class,
            '2fa' => \App\Http\Middleware\EnsureTwoFactor::class,
            'audit' => \App\Http\Middleware\AuditApiActions::class,
        ]);

        // Ajouter les headers de sécurité à toutes les réponses web
        $middleware->web(append: [
            \App\Http\Middleware\CheckBlacklist::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\DetectLocation::class,
        ]);

        // Audit trail pour les mutations API
        $middleware->api(append: [
            \App\Http\Middleware\AuditApiActions::class,
        ]);

        // Exclure les routes de la vérification CSRF en environnement de test
        // Utiliser la variable d'environnement APP_ENV car app() n'est pas disponible ici
        if (env('APP_ENV') === 'testing') {
            $middleware->validateCsrfTokens(except: ['*']);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sentry — capture toutes les exceptions non gérées (no-op si SENTRY_LARAVEL_DSN vide)
        \Sentry\Laravel\Integration::handles($exceptions);

        // API : retourner JSON structuré pour toutes les erreurs
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null; // Laisser le handler web standard
            }

            $status = match (true) {
                $e instanceof \Illuminate\Auth\AuthenticationException => 401,
                $e instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
                $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException => 404,
                $e instanceof \Illuminate\Validation\ValidationException => 422,
                $e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException => 429,
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 404,
                $e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException => 405,
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException => $e->getStatusCode(),
                default => 500,
            };

            $response = [
                'success' => false,
                'message' => match ($status) {
                    401 => 'Non authentifié.',
                    403 => 'Accès refusé.',
                    404 => 'Ressource introuvable.',
                    405 => 'Méthode non autorisée.',
                    422 => 'Données invalides.',
                    429 => 'Trop de requêtes. Réessayez plus tard.',
                    500 => 'Erreur serveur.',
                    default => 'Erreur.',
                },
                'error_code' => $status,
            ];

            // Ajouter les erreurs de validation
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $response['errors'] = $e->errors();
            }

            // En debug : ajouter le détail (jamais en production)
            if (config('app.debug') && $status === 500) {
                $response['debug'] = [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile().':'.$e->getLine(),
                ];
            }

            // Log les erreurs 500
            if ($status === 500) {
                \Illuminate\Support\Facades\Log::channel('critical')->error('API Error 500', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_id' => $request->user()?->id,
                    'ip' => $request->ip(),
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile().':'.$e->getLine(),
                ]);
            }

            // Log les erreurs 4xx de sécurité (401, 403)
            if (in_array($status, [401, 403])) {
                \Illuminate\Support\Facades\Log::channel('security')->warning('API Auth Error', [
                    'status' => $status,
                    'url' => $request->fullUrl(),
                    'user_id' => $request->user()?->id,
                    'ip' => $request->ip(),
                ]);
            }

            // Retry-After header pour 429
            $headers = [];
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
                $headers['Retry-After'] = $retryAfter;
                $response['retry_after'] = (int) $retryAfter;
            }

            return response()->json($response, $status, $headers);
        });
    })->create();
