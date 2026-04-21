<?php

use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Authentifiées (Tous les utilisateurs connectés)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard (redirige selon le rôle)
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return redirect('/admin'); // Filament admin panel
        }

        if ($user->isOwner()) {
            return redirect()->route('owner.dashboard');
        }

        // Rediriger les utilisateurs standard vers le dashboard client
        return redirect()->route('client.dashboard');
    })->name('dashboard');

    // Profil utilisateur
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // Contact propriétaire (rate limited)
    Route::middleware('throttle:contact')->group(function () {
        Route::post('/residences/{residence}/contact', [ContactController::class, 'store'])
            ->name('residences.contact');
    });

    // Mes contacts envoyés
    Route::get('/my-contacts', [ContactController::class, 'myContacts'])->name('contacts.mine');

    // Double authentification (2FA)
    Route::prefix('two-factor')->name('two-factor.')->group(function () {
        Route::get('/setup', [\App\Http\Controllers\TwoFactorController::class, 'setup'])->name('setup');
        Route::post('/generate', [\App\Http\Controllers\TwoFactorController::class, 'generate'])->name('generate');
        Route::post('/enable', [\App\Http\Controllers\TwoFactorController::class, 'enable'])->name('enable');
        Route::delete('/disable', [\App\Http\Controllers\TwoFactorController::class, 'disable'])->name('disable');
        Route::get('/challenge', [\App\Http\Controllers\TwoFactorController::class, 'challenge'])->name('challenge');
        Route::post('/verify', [\App\Http\Controllers\TwoFactorController::class, 'verify'])->name('verify');
        Route::post('/verify-recovery', [\App\Http\Controllers\TwoFactorController::class, 'verifyRecovery'])->name('verify-recovery');
        Route::get('/recovery-codes', [\App\Http\Controllers\TwoFactorController::class, 'showRecoveryCodes'])->name('recovery-codes');
        Route::post('/recovery-codes/confirm', [\App\Http\Controllers\TwoFactorController::class, 'confirmRecoveryCodes'])->name('recovery-codes.confirm');
        Route::post('/recovery-codes/regenerate', [\App\Http\Controllers\TwoFactorController::class, 'regenerateRecoveryCodes'])->name('recovery-codes.regenerate');
    });

    // Vérification utilisateur
    Route::prefix('verification')->name('verification.')->group(function () {
        // Dashboard vérification
        Route::get('/', [VerificationController::class, 'dashboard'])->name('dashboard');

        // Vérification identité
        Route::prefix('identity')->name('identity.')->group(function () {
            Route::get('/start', [VerificationController::class, 'identityStart'])->name('start');
            Route::post('/upload', [VerificationController::class, 'identityUpload'])->name('upload');
            Route::get('/selfie', [VerificationController::class, 'identitySelfieForm'])->name('selfie.form');
            Route::post('/selfie', [VerificationController::class, 'identitySelfie'])->name('selfie');
        });

        // Vérification téléphone
        Route::prefix('phone')->name('phone.')->group(function () {
            Route::post('/send', [VerificationController::class, 'phoneSend'])->name('send')->middleware('throttle:3,1');
            Route::get('/verify', [VerificationController::class, 'phoneVerifyForm'])->name('verify.form');
            Route::post('/verify', [VerificationController::class, 'phoneVerify'])->name('verify')->middleware('throttle:10,1');
        });

        // Contacts d'urgence
        Route::prefix('emergency')->name('emergency.')->group(function () {
            Route::get('/contacts', [VerificationController::class, 'emergencyContacts'])->name('contacts');
            Route::post('/contacts', [VerificationController::class, 'emergencyStore'])->name('store');
            Route::delete('/contacts/{contact}', [VerificationController::class, 'emergencyDestroy'])->name('destroy');
            Route::patch('/contacts/{contact}/primary', [VerificationController::class, 'emergencySetPrimary'])->name('set-primary');
            Route::post('/trigger', [VerificationController::class, 'emergencyTrigger'])->name('trigger')->middleware('throttle:5,1');
        });

        // Signalement fraude
        Route::post('/fraud/report', [VerificationController::class, 'reportFraud'])->name('fraud.report')->middleware('throttle:5,1');
    });
});

/*
|--------------------------------------------------------------------------
| Routes Client (Utilisateurs standard)
|--------------------------------------------------------------------------
*/
