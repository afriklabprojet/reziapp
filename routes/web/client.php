<?php

use App\Http\Controllers\Client\ClientController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('client')
    ->name('client.')
    ->group(function () {

        // Dashboard client
        Route::get('/dashboard', [ClientController::class, 'dashboard'])->name('dashboard');

        // Historique de recherche
        Route::get('/search-history', [ClientController::class, 'searchHistory'])->name('search-history');
        Route::delete('/search-history/clear', [ClientController::class, 'clearSearchHistory'])->name('search-history.clear');
        Route::delete('/search-history/{search}', [ClientController::class, 'deleteSearch'])->name('search-history.delete');

        // Historique des visites
        Route::get('/view-history', [ClientController::class, 'viewHistory'])->name('view-history');
        Route::delete('/view-history/clear', [ClientController::class, 'clearViewHistory'])->name('view-history.clear');

        // Comparateur
        Route::get('/compare', [ClientController::class, 'compare'])->name('compare');

        // Alertes
        Route::get('/alerts', [ClientController::class, 'alerts'])->name('alerts');

        // Mes contacts envoyés
        Route::get('/contacts', [ClientController::class, 'contacts'])->name('contacts');

        // Mes avis
        Route::get('/reviews', [ClientController::class, 'reviews'])->name('reviews');

        // Statistiques personnelles
        Route::get('/statistics', [ClientController::class, 'statistics'])->name('statistics');

        // Contrats / Baux
        Route::get('/contracts', [ClientController::class, 'contracts'])->name('contracts');
        Route::get('/contracts/{leaseContract}', [ClientController::class, 'showContract'])->name('contracts.show');
        Route::post('/contracts/{leaseContract}/sign', [ClientController::class, 'signContract'])->name('contracts.sign');
        Route::get('/contracts/{leaseContract}/download', [ClientController::class, 'downloadContract'])->name('contracts.download');

        // Sauvegarder une recherche comme alerte
        Route::post('/search-history/{search}/save-alert', [ClientController::class, 'saveSearchAsAlert'])->name('search-history.save-alert');

        // Supprimer une alerte sauvegardée
        Route::delete('/alerts/{savedSearch}', [ClientController::class, 'deleteAlert'])->name('alerts.delete');
    });
