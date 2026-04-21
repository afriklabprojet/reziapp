<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Vérifier quotidiennement les promotions et mises en avant expirant bientôt
Schedule::command('rezi:check-expiring-promotions')->dailyAt('09:00');

// Recalculer les prix du marché chaque semaine (dimanche à 4h)
Schedule::command('rezi:calculate-market-prices --country=CI')->weeklyOn(0, '04:00');

// Recalculer les badges de confiance 2x par semaine (lundi et jeudi à 5h)
Schedule::command('rezi:calculate-owner-badges')->weeklyOn(1, '05:00'); // Lundi
Schedule::command('rezi:calculate-owner-badges')->weeklyOn(4, '05:00'); // Jeudi

// Alertes quotidiennes : loyers en retard de paiement (08h00)
Schedule::command('rezi:check-unpaid-bookings --days=3')->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground();

// Alertes relance : loyers en retard critique (15h00)
Schedule::command('rezi:check-unpaid-bookings --days=7')->dailyAt('15:00')
    ->withoutOverlapping()
    ->runInBackground();

// Auto-compléter les mises en avant expirées et vérifier les budgets épuisés
// → Planifié dans bootstrap/app.php toutes les 15 minutes
// Schedule::command('rezi:manage-sponsored-listings')->hourly();

// Régénérer le sitemap chaque nuit — planifié dans bootstrap/app.php à 03:00
// Schedule::command('rezi:generate-sitemap')->dailyAt('02:00');

// Envoyer les relances de loyer automatiques (chaque jour à 08h30)
Schedule::command('rezi:send-rent-reminders')->dailyAt('08:30')
    ->withoutOverlapping()
    ->runInBackground();

// Vérifier les documents expirant bientôt (chaque jour à 09h30)
Schedule::command('rezi:check-document-expiry')->dailyAt('09:30')
    ->withoutOverlapping()
    ->runInBackground();

// Gérer les modes vacances (activation/désactivation automatique, chaque jour à 00h05)
Schedule::command('rezi:manage-vacation-modes')->dailyAt('00:05')
    ->withoutOverlapping()
    ->runInBackground();

// ============================================
// Nouvelles commandes d'automatisation
// ============================================

// Traiter les séquences de messages automatiques (toutes les 15 minutes)
Schedule::command('rezi:process-sequences')->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Planifier les ménages automatiques après checkouts (chaque jour à 06h00)
Schedule::command('rezi:schedule-cleanings')->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground();

// Appliquer le yield management (tarification dynamique, 2x par jour)
Schedule::command('rezi:apply-yield')->dailyAt('00:15')
    ->withoutOverlapping()
    ->runInBackground();
Schedule::command('rezi:apply-yield')->dailyAt('12:15')
    ->withoutOverlapping()
    ->runInBackground();

// Synchroniser les flux iCal (toutes les heures)
Schedule::command('rezi:sync-ical')->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Recalculer les scores des voyageurs (chaque nuit à 03h30)
Schedule::command('rezi:recalculate-guest-scores')->dailyAt('03:30')
    ->withoutOverlapping()
    ->runInBackground();

// Vérifier les alertes propriétaires (toutes les 30 minutes)
Schedule::command('rezi:check-owner-alerts')->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Expirer les codes de serrures connectées (toutes les heures)
Schedule::command('rezi:expire-lock-codes')->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Mettre à jour les statistiques de rareté (viewers 24h, réservations du mois) — toutes les heures
Schedule::command('rezi:update-scarcity-stats')->hourly()
    ->withoutOverlapping()
    ->runInBackground();
