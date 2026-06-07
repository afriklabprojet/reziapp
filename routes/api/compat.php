<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Redirects rétrocompatibilité (routes sans /v1 → /api/v1/*)
| Source : appels anciens, app mobile ou crawlers
|--------------------------------------------------------------------------
*/

Route::get('/communes', fn () => redirect('/api/v1/communes', 301))
    ->name('api.compat.communes');
Route::get('/cities', fn () => redirect('/api/v1/locations/countries/ci/cities', 301))
    ->name('api.compat.cities');
Route::get('/amenities', fn () => redirect('/api/v1/amenities', 301))
    ->name('api.compat.amenities');
Route::get('/residence-types', fn () => redirect('/api/v1/residences', 301))
    ->name('api.compat.residence-types');
