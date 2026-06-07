<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ResidenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:admin', 'throttle:api'])
    ->prefix('admin')
    ->name('api.admin.')
    ->group(function () {
        $pendingResidencesRoute = '/residences/pending';
        $adminResidenceRoute = '/residences/{residence}';

        Route::get('/statistics', [ResidenceController::class, 'adminStatistics'])
            ->name('statistics');

        Route::get($pendingResidencesRoute, [ResidenceController::class, 'pendingResidences'])
            ->name('residences.pending');
        Route::post("{$adminResidenceRoute}/approve", [ResidenceController::class, 'approve'])
            ->name('residences.approve');
        Route::post("{$adminResidenceRoute}/reject", [ResidenceController::class, 'reject'])
            ->name('residences.reject');

        Route::get('/users', [AuthController::class, 'users'])
            ->name('users.index');
        Route::patch('/users/{user}/role', [AuthController::class, 'updateRole'])
            ->name('users.role');
    });
