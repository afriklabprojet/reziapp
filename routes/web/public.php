<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ResidenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Publiques
|--------------------------------------------------------------------------
| Accessibles sans authentification
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// Sitemap dynamique (toujours utilise APP_URL)
Route::get('/sitemap.xml', \App\Http\Controllers\SitemapController::class)->name('sitemap');

// Pages statiques (légales, info, support)
Route::get('/conditions-utilisation', [PageController::class, 'cgu'])->name('pages.cgu');
Route::get('/confidentialite', [PageController::class, 'confidentialite'])->name('pages.confidentialite');
Route::get('/mentions-legales', [PageController::class, 'mentionsLegales'])->name('pages.mentions-legales');
Route::get('/faq', [PageController::class, 'faq'])->name('pages.faq');
Route::get('/a-propos', [PageController::class, 'about'])->name('pages.about');
Route::get('/guide-proprietaire', [PageController::class, 'guideProprietaire'])->name('pages.guide-proprietaire');
Route::get('/nous-contacter', [PageController::class, 'contact'])->name('pages.contact');
Route::redirect('/contact', '/nous-contacter', 301);

// Newsletter
Route::post('/newsletter/subscribe', [App\Http\Controllers\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [App\Http\Controllers\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::post('/newsletter/resubscribe', [App\Http\Controllers\NewsletterController::class, 'resubscribe'])->name('newsletter.resubscribe');

// URLs propres pour les types de location (SEO-friendly)
Route::get('/residences-meublees', [ResidenceController::class, 'index'])
    ->defaults('type_location', 'residence_meublee')
    ->name('residences.meublees');

// Résidences publiques
Route::prefix('residences')->name('residences.')->group(function () {
    Route::get('/', [ResidenceController::class, 'index'])->name('index');
    Route::get('/search', [ResidenceController::class, 'search'])->name('search');
    Route::get('/map', [ResidenceController::class, 'map'])->name('map');
    Route::get('/compare', fn () => view('residences.compare'))->name('compare');
    Route::post('/{residence}/report', [ResidenceController::class, 'report'])
        ->middleware('throttle:5,10')
        ->name('report');
    Route::get('/{residence}', [ResidenceController::class, 'show'])->name('show');
});

/*
|--------------------------------------------------------------------------
| Routes publiques iCal & Guidebook
|--------------------------------------------------------------------------
*/

// Export iCal public (pour synchronisation externe)
Route::get('/ical/{token}.ics', [\App\Http\Controllers\Owner\IcalController::class, 'export'])->name('ical.export');

// Guidebook public (partagé aux voyageurs)
Route::get('/guidebook/{token}', [\App\Http\Controllers\Owner\GuidebookController::class, 'publicShow'])->name('guidebook.public');

/*
|--------------------------------------------------------------------------
| Callbacks Paiement Jeko (authentifié, sans préfixe owner)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('payment/jeko')->name('payment.jeko.')->group(function () {
    Route::get('/success', [\App\Http\Controllers\Payment\JekoCallbackController::class, 'success'])->name('success');
    Route::get('/error', [\App\Http\Controllers\Payment\JekoCallbackController::class, 'error'])->name('error');
    Route::get('/check/{sponsored}', [\App\Http\Controllers\Payment\JekoCallbackController::class, 'checkStatus'])->name('check');
});

// Callbacks paiement assurance
Route::middleware(['auth'])->prefix('insurance/payment')->name('insurance.payment.')->group(function () {
    Route::get('/success', [\App\Http\Controllers\Payment\InsuranceCallbackController::class, 'success'])->name('success');
    Route::get('/error', [\App\Http\Controllers\Payment\InsuranceCallbackController::class, 'error'])->name('error');
});
