<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Routes pour l'API REST (application mobile, intégrations tierces)
| Préfixe: /api/v1
*/

require __DIR__.'/api/webhooks.php';
require __DIR__.'/api/compat.php';

Route::prefix('v1')->group(function () {
    require __DIR__.'/api/v1/public.php';
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/authenticated.php';
    require __DIR__.'/api/v1/owner.php';
    require __DIR__.'/api/v1/admin.php';
    require __DIR__.'/api/v1/support.php';
});
