# 🔍 Audit complet Frontend — ReziApp
> **Date** : 17 février 2026  
> **Scope** : 212 vues Blade, 42 modules JS, CSS, PWA, Vite build

---

## 📊 Résumé exécutif

| Catégorie | 🔴 Critique | 🟠 Important | 🟡 Mineur |
|-----------|:-----------:|:------------:|:---------:|
| Layouts & Blade | 2 | 1 | 1 |
| Alpine.js | 2 | 0 | 0 |
| Tailwind CSS v4 | 0 | 1 | 0 |
| PWA & Assets | 2 | 1 | 1 |
| Accessibilité | 1 | 0 | 0 |
| Performance | 0 | 1 | 1 |
| Vite Build | 0 | 0 | 1 |
| Dead Code | 2 | 0 | 0 |
| **TOTAL** | **9** | **4** | **4** |

---

## 🔴 CRITIQUE (9 problèmes)

### 1. Layouts `layouts.owner` et `layouts.admin` inexistants
**Impact** : 22 vues cassées (pages blanches / erreur 500)

**20 vues étendant `layouts.owner`** (aucun fichier `resources/views/layouts/owner.blade.php`) :
- `owner/marketing/campaigns/{index,create,show,edit}.blade.php`
- `owner/marketing/sponsored/{create,show,payment,index}.blade.php`
- `owner/marketing/promotions/{create,index,edit}.blade.php`
- `owner/marketing/coupons/{create,index,show,edit}.blade.php`
- `owner/marketing/referrals/{index,leaderboard}.blade.php`
- `owner/analytics/{index,fiscal}.blade.php`
- `owner/pricing/suggestions.blade.php`

**2 vues étendant `layouts.admin`** (aucun fichier `resources/views/layouts/admin.blade.php`) :
- `admin/verification/dashboard.blade.php`
- `admin/verification/identity/index.blade.php`

**Action** : Créer `layouts/owner.blade.php` (wrapper de `layouts.app` + sidebar owner) et `layouts/admin.blade.php` (wrapper + sidebar admin), ou migrer les vues vers `@extends('layouts.app')`.

---

### 2. Composants Alpine non définis — `interactiveMap`, `calendarApp`
**Impact** : 2 pages cassées (erreur Alpine.js au runtime)

| Vue | Composant Alpine manquant | Module JS |
|-----|---------------------------|-----------|
| `residences/map-interactive.blade.php` | `interactiveMap()` | N'existe nulle part |
| `owner/bookings/calendar.blade.php` | `calendarApp()` | N'existe nulle part |

Ces composants sont appelés via `x-data="interactiveMap(...)"` et `x-data="calendarApp(...)"` mais ne sont ni dans `app.js`, ni inline, ni dans un fichier JS.

**Action** : Créer les modules JS manquants ou supprimer les vues orphelines.

---

### 3. Vues orphelines (0 référence, 0 route)
**Impact** : Code mort, confusion, maintenance inutile

| Fichier | Raison |
|---------|--------|
| `conversations/show-new.blade.php` (306 lignes) | Aucune route, aucun contrôleur ne la référence |
| `residences/map-interactive.blade.php` (466 lignes) | Aucune route, composant Alpine manquant |

**Action** : Supprimer ces 2 fichiers.

---

### 4. Theme-color incohérent — `#4f46e5` (indigo) vs `#F7931E` (orange ReziApp)
**Impact** : Barre d'adresse mobile avec couleur incorrecte

| Fichier | Valeur | Attendu |
|---------|--------|---------|
| `layouts/app.blade.php` ligne 16 | `#4f46e5` (indigo) ❌ | `#F7931E` (orange) |
| `manifest.json` | `#F7931E` ✅ | ✅ |
| `offline.html` background gradient | `#4f46e5` (indigo) ❌ | `#F7931E` (orange) |

**Action** : Corriger `layouts/app.blade.php` et `offline.html`.

---

### 5. 5 fichiers PWA manquants référencés dans `manifest.json`
**Impact** : Erreurs console, installation PWA échouée

| Fichier manquant | Référencé dans |
|------------------|----------------|
| `/images/screenshots/home.png` | `manifest.json` screenshots |
| `/images/screenshots/mobile.png` | `manifest.json` screenshots |
| `/images/icons/search-icon.png` | `manifest.json` shortcuts |
| `/images/icons/map-icon.png` | `manifest.json` shortcuts |
| `/images/icons/heart-icon.png` | `manifest.json` shortcuts |

**Action** : Créer les images ou supprimer les entrées du manifest.

---

### 6. 66 images sans attribut `alt`
**Impact** : Accessibilité (WCAG A non conforme), SEO pénalisé

Fichiers les plus touchés :
- `payments/` (4 fichiers)
- `cancellations/` (4 fichiers)
- `owner/bookings/` (2 fichiers)
- `bookings/` (2 fichiers)
- Divers (résidences, profils, etc.)

**Action** : Ajouter `alt=""` décoratif ou `alt="description"` selon le contexte.

---

## 🟠 IMPORTANT (4 problèmes)

### 7. Deux systèmes de layout en parallèle
**Impact** : Incohérence UX, maintenance double

| Système | Fichier | Vues l'utilisant |
|---------|---------|:----------------:|
| `@extends('layouts.app')` | `resources/views/layouts/app.blade.php` | ~60 vues |
| `<x-app-layout>` | `resources/views/components/layouts/app.blade.php` | ~25 vues |

**Différences notables** :
- `layouts/app.blade.php` : header mobile dédié (`<x-mobile-header>`), footer caché sur mobile, PWA meta tags, Service Worker
- `components/layouts/app.blade.php` : navigation inline, Google Maps conditionnel, pas de PWA meta, pas de Service Worker

**Action** : Unifier vers un seul système à terme. Priorité basse mais source de bugs subtils.

---

### 8. Classes Tailwind v4 obsolètes — `bg-opacity-*`
**Impact** : Warnings de compilation, rendu potentiellement différent

5 fichiers utilisent encore `bg-opacity-*` (syntaxe Tailwind v3) :
- `verification/dashboard.blade.php` → `bg-gray-500 bg-opacity-75`
- `verification/emergency/contacts.blade.php` → `bg-gray-500 bg-opacity-75`
- `payments/checkout.blade.php` → `bg-gray-500 bg-opacity-75`
- `bookings/show.blade.php` → `bg-black bg-opacity-50`
- `conversations/show-new.blade.php` → `bg-black bg-opacity-90` (orpheline)

**Migration Tailwind v4** : `bg-gray-500 bg-opacity-75` → `bg-gray-500/75`

---

### 9. Double chargement de polices
**Impact** : Requêtes réseau inutiles, ralentissement du First Paint

| Source | CDN | Fichier |
|--------|-----|---------|
| Figtree 400,500,600 | `fonts.bunny.net` | `layouts/app.blade.php`, `components/layouts/app.blade.php`, `layouts/guest.blade.php` |
| Figtree (via preconnect) | `fonts.googleapis.com` | `components/seo-meta.blade.php` |

**Action** : Supprimer les preconnect Google Fonts dans `seo-meta.blade.php` (lignes 190-195).

---

### 10. `<style>` dans `@push('scripts')` au lieu de `@push('styles')`
**Impact** : CSS chargé après le DOM, potentiel FOUC

Fichier : `residences/map-interactive.blade.php` ligne 457 — `<style>` placé dans `@push('scripts')`.

**Action** : Fichier orphelin, sera supprimé (voir point 3).

---

## 🟡 MINEUR (4 problèmes)

### 11. Package `@tailwindcss/vite` installé mais non utilisé
Le `vite.config.js` n'importe pas `@tailwindcss/vite`. Le projet utilise `@tailwindcss/postcss` via `postcss.config.js` — ça fonctionne, mais le package NPM est inutile.

**Action** : `npm uninstall @tailwindcss/vite` ou migrer vers le plugin Vite (plus performant).

---

### 12. Figtree font weights différents entre layouts
- `layouts/app.blade.php` : `figtree:400,500,600`
- `components/layouts/app.blade.php` : `figtree:400,500,600,700` (inclut bold)
- `layouts/guest.blade.php` : `figtree:400,500,600`

**Action** : Harmoniser à `400,500,600,700` partout si `font-bold` est utilisé.

---

### 13. `conversations/show-new.blade.php` — `border-opacity-20`
Syntaxe invalide : `border-opacity-20` n'existe pas en Tailwind (v3 ou v4). La classe est ignorée silencieusement.

**Action** : Fichier orphelin, sera supprimé.

---

### 14. `searchPage` exposé comme `window.searchPage` mais pas comme `Alpine.data`
Dans `app.js`, `searchPage` est importé et exposé via `window.searchPage` au lieu d'être enregistré via `Alpine.data('searchPage', ...)`. Le fichier `residence-search.blade.php` l'utilise via `x-data="searchPage()"` — cela fonctionne car Alpine v3 permet les fonctions globales, mais c'est incohérent avec le pattern du reste de l'app.

---

## ✅ Points positifs

| Aspect | Statut |
|--------|--------|
| **0 CDN externe** dans les vues (sauf Mapbox + Google Maps conditionnels) | ✅ |
| **Vite build propre** — 0 warning, 0 erreur | ✅ |
| **Bundle raisonnable** — app.js 220 KB, app.css 124 KB (gzip ~87 KB total) | ✅ |
| **42 modules Alpine bien structurés** — 1 fichier = 1 composant | ✅ |
| **Tailwind v4 @theme correctement configuré** — palette primary orange | ✅ |
| **CSS utilitaires centralisés** — `.btn-primary`, `.card`, `.badge-*` dans app.css | ✅ |
| **PWA fonctionnelle** — Service Worker, manifest, icônes (sauf screenshots) | ✅ |
| **`@stack('styles')` + `@stack('scripts')`** dans les 2 layouts | ✅ |
| **Pas de loading="lazy" dupliqué** | ✅ |
| **Pas de CDN jQuery, Bootstrap, ou autres dépendances lourdes** | ✅ |
| **Alpine.js via NPM + Vite** (pas de CDN) | ✅ |
| **Leaflet + Chart.js bundlés via Vite** (lazy-loaded) | ✅ |
| **Echo/Pusher configurés conditionnellement** dans `bootstrap.js` | ✅ |

---

## 📈 Métriques

| Métrique | Valeur |
|----------|--------|
| Total vues Blade | **212** |
| Vues `@extends('layouts.app')` | ~60 |
| Vues `<x-app-layout>` | ~25 |
| Vues `@extends('layouts.owner')` | **20** (layout manquant) |
| Vues `@extends('layouts.admin')` | **2** (layout manquant) |
| Modules JS (components/) | 9 |
| Modules JS (pages/) | 33 |
| Total lignes JS | **3 808** |
| CSS personnalisé (hors Tailwind) | ~100 lignes |
| Bundle JS (app.js) | 220 KB (69 KB gzip) |
| Bundle CSS (app.css) | 124 KB (18 KB gzip) |
| Bundle Leaflet | 150 KB (43 KB gzip) |
| Bundle Chart.js | 207 KB (71 KB gzip) |
| Icônes PWA | 8 fichiers ✅ |
| Images manquantes | 5 fichiers ❌ |
| Images sans `alt` | **66** |

---

## 🎯 Plan d'action prioritaire

| Priorité | Action | Effort |
|:--------:|--------|:------:|
| **P0** | Créer `layouts/owner.blade.php` et `layouts/admin.blade.php` | 30 min |
| **P0** | Supprimer 2 vues orphelines (`show-new`, `map-interactive`) | 5 min |
| **P0** | Corriger theme-color → `#F7931E` dans `layouts/app.blade.php` + `offline.html` | 5 min |
| **P0** | Créer module Alpine `bookingCalendar` pour `calendarApp` ou le définir inline | 20 min |
| **P1** | Supprimer screenshots/shortcuts du manifest ou créer les images | 10 min |
| **P1** | Migrer 5 fichiers `bg-opacity-*` → syntaxe Tailwind v4 | 10 min |
| **P1** | Supprimer preconnect Google Fonts dans `seo-meta.blade.php` | 5 min |
| **P2** | Ajouter `alt` aux 66 images | 45 min |
| **P2** | Harmoniser font weights | 5 min |
| **P2** | Nettoyer `@tailwindcss/vite` du package.json | 2 min |
