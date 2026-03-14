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
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware aliases pour les routes
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'ensure.owner' => \App\Http\Middleware\EnsureOwnership::class,
            'identity.verified' => \App\Http\Middleware\EnsureIdentityVerified::class,
            '2fa' => \App\Http\Middleware\EnsureTwoFactor::class,
        ]);

        // Ajouter les headers de sécurité à toutes les réponses web
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\DetectLocation::class,
        ]);

        // Exclure les routes de la vérification CSRF en environnement de test
        // Utiliser la variable d'environnement APP_ENV car app() n'est pas disponible ici
        if (env('APP_ENV') === 'testing') {
            $middleware->validateCsrfTokens(except: ['*']);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
