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
