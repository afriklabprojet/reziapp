<?php

/*
|--------------------------------------------------------------------------
| Routes Web — Point d'entrée
|--------------------------------------------------------------------------
| Ce fichier orchestre le chargement des sous-fichiers de routes.
| Chaque fichier est responsable d'un domaine fonctionnel précis.
|
| web/public.php       → Routes publiques (accueil, résidences, pages)
| web/auth_common.php  → Routes pour tous les utilisateurs connectés
| web/client.php       → Espace client (dashboard, réservations, favoris)
| web/owner.php        → Espace propriétaire (gestion résidences, analytics)
| web/misc.php         → Reviews, chat, paiements, notifications, booking
|--------------------------------------------------------------------------
*/

require __DIR__.'/web/public.php';
require __DIR__.'/web/auth_common.php';
require __DIR__.'/web/client.php';
require __DIR__.'/web/owner.php';
require __DIR__.'/web/misc.php';

/*
|--------------------------------------------------------------------------
| Routes d'authentification Laravel Breeze
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Route admin pour servir les fichiers privés (KYC, documents identité)
|--------------------------------------------------------------------------
*/
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/admin/files/private/{path}', function (string $path) {
    // Admin uniquement (vérifié via Filament auth)
    /** @var \Illuminate\Auth\AuthManager $auth */
    $auth = auth();
    abort_unless(
        $auth->check() && $auth->user()->isAdmin(),
        403
    );

    $path = ltrim(str_replace('\\', '/', $path), '/');

    abort_if($path === '' || str_contains($path, "\0"), 404);

    foreach (explode('/', $path) as $segment) {
        if ($segment === '' || $segment === '.' || $segment === '..') {
            abort(404);
        }
    }

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = Storage::disk('private');

    if (! $disk->exists($path)) {
        abort(404);
    }

    $mime = $disk->mimeType($path);
    $stream = $disk->readStream($path);

    return response()->stream(
        function () use ($stream) {
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        },
        200,
        [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline',
            'Cache-Control'       => 'no-store, private',
            'X-Robots-Tag'        => 'noindex',
        ]
    );
})->where('path', '.*')->name('admin.private-file')->middleware(['web', 'auth']);
