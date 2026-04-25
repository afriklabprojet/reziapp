# 🔍 AUDIT COMPLET — PAGE D'ACCUEIL REZI
> Date : 21 février 2026  
> Scope : Route `/` → `HomeController@index` → `home.blade.php` + layout + composants  
> Fichier : 1 271 lignes Blade · 189 lignes Controller · ~40 composants Alpine.js

---

## 📊 RÉSUMÉ EXÉCUTIF

| Catégorie | Critique | Moyen | Faible | Total |
|---|:---:|:---:|:---:|:---:|
| 🔧 Performance Backend | 1 | 3 | 2 | **6** |
| ⚡ Performance Frontend | 1 | 2 | 1 | **4** |
| 🔎 SEO | 1 | 2 | 0 | **3** |
| 📱 PWA | 0 | 2 | 1 | **3** |
| ♿ Accessibilité | 1 | 4 | 0 | **5** |
| 🔒 Sécurité | 0 | 2 | 0 | **2** |
| 🎨 UX / UI | 0 | 3 | 1 | **4** |
| **TOTAL** | **4** | **18** | **5** | **27** |

**Score global : 62/100** — Bonne base fonctionnelle, mais des problèmes critiques à corriger avant mise en production.

---

## 🏗️ ARCHITECTURE DE LA PAGE

```
GET / → HomeController@index
         ├── 7 requêtes/caches : residences, featured, zones, stats, testimonials, categories
         └── home.blade.php (1 271 lignes)
              ├── <x-app-layout> → layouts/app.blade.php
              │    ├── <x-seo-meta>         → SEO / OpenGraph / JSON-LD
              │    ├── layouts/navigation    → Navbar desktop (sticky, frosted-glass)
              │    ├── <x-mobile-header>     → Header mobile fixe
              │    ├── <x-mobile-nav>        → Bottom tab bar (5 onglets)
              │    ├── <x-footer>            → Footer complet (newsletter, liens, social)
              │    └── <x-push-notifications>
              │
              └── Sections page d'accueil :
                   1. Hero immersif (géoloc / recherche par quartier)
                   2. Bottom sheet résultats (scroll horizontal)
                   3. Bande de micro-rassurance (4 badges)
                   3.5 Section catégories (grille dynamique)
                   4. Résidences vedettes (grille 3 colonnes)
                   5. Zones populaires (grille 4 colonnes + stats)
                   6. CTA sticky mobile (fixe en bas)
```

### Données injectées dans la vue :
| Variable | Source | Cache TTL |
|---|---|---|
| `$residences` | Recherche géoloc/commune (conditionnel) | ❌ Pas de cache |
| `$featuredResidences` | 6 plus récentes approuvées avec photos | 1h |
| `$popularZones` | Top 6 communes par nombre de résidences | 1h |
| `$stats` | Compteurs (résidences, propriétaires, communes, contacts) | 1h |
| `$testimonials` | Top 3 avis ≥4★ approuvés | 1h |
| `$categories` | Catégories actives avec compteur | 1h |

---

## 🔴 PROBLÈMES CRITIQUES (4)

### C1. N+1 Query dans `popularZones` 
**Fichier** : `app/Http/Controllers/HomeController.php` (lignes 77-96)

Le `->map()` exécute **1 requête par commune** pour récupérer une photo de résidence :
```php
->map(function ($zone) {
    $photo = Residence::approved()
        ->where('commune', $zone->commune)
        ->whereHas('photos')   // → sous-requête EXISTS
        ->with('photos')
        ->first();             // → 1 query par zone
```
Avec 6 zones → **~12 requêtes supplémentaires** sur cache-miss.

**Fix** : Réécrire avec un `JOIN` ou une sous-requête unique qui récupère une photo par commune en une seule requête.

---

### C2. Image Hero en `loading="lazy"` — Tue le LCP
**Fichier** : `resources/views/home.blade.php` (ligne ~108)

```html
<img loading="lazy" src="https://images.unsplash.com/photo-15...?w=2000"
     alt="Image" class="..." alt="Carte Abidjan">
```

**Problèmes multiples** :
- `loading="lazy"` sur l'image above-the-fold → **retarde le Largest Contentful Paint**
- Image externe (unsplash.com) sans `<link rel="preconnect">`
- Pas de `srcset` / `sizes` → 2000px servis à tous les écrans
- Double attribut `alt` (seul le premier `alt="Image"` est lu)
- Image décorative mais pas `alt=""`

**Fix** : Héberger localement l'image hero, ajouter `loading="eager"` + `fetchpriority="high"`, fournir des variantes WebP responsives.

---

### C3. Sitemap pointe vers `localhost:8000`
**Fichier** : `public/sitemap.xml`

Toutes les URLs du sitemap sont en `http://localhost:8000/` → les moteurs de recherche ne peuvent indexer **aucune URL valide**.

**Fix** : Générer le sitemap dynamiquement avec `spatie/laravel-sitemap` ou remplacer par `APP_URL` de production.

---

### C4. Double `alt` sur les images → Accessibilité cassée
**Fichier** : `resources/views/home.blade.php` (lignes ~108, ~1220)

Plusieurs balises `<img>` ont 2 attributs `alt` — le navigateur ne lit que le premier :
```html
<!-- Hero -->
<img ... alt="Image" class="..." alt="Carte Abidjan">

<!-- Zones populaires -->
<img ... alt="Image" class="..." alt="{{ $zone['name'] }}">
```
Le premier `alt="Image"` est générique et inutile pour les lecteurs d'écran.

**Fix** : Supprimer le premier `alt="Image"` et ne garder que le second significatif. Pour l'image hero (décorative), utiliser `alt=""`.

---

## 🟡 PROBLÈMES MOYENS (18)

### Performance Backend

#### M1. Pas d'invalidation de cache événementielle
Les 5 caches homepage (`featured_residences`, `popular_zones`, `home_stats`, `home_testimonials`, `home_categories`) utilisent un TTL flat de 3600s sans invalidation sur événement. Quand une résidence est approuvée/supprimée → données potentiellement périmées pendant 1h.

**Fix** : Ajouter un Observer sur `Residence` qui `Cache::forget()` les clés pertinentes sur `created`/`updated`/`deleted`.

---

#### M2. `radiusCounts` API — 5 requêtes Haversine sans cache
Le contrôleur `GeoSearchController@radiusCounts` exécute 1 requête `withinRadius()->count()` **par rayon** (100, 200, 300, 400, 500m) sans mise en cache, alors que le service `GeolocationService::getRadiusCounts()` le fait déjà avec cache.

**Fix** : Remplacer la logique inline par `$this->geoService->getRadiusCounts($lat, $lng)`.

---

#### M3. Requête `.get()` non bornée sur la recherche
Dans `HomeController@index`, la recherche par commune/quartier fait un `->get()` sans `->limit()` ou `->paginate()`. Une commune populaire pourrait retourner des centaines de résidences.

**Fix** : Ajouter `->paginate(20)` ou au minimum `->limit(50)`.

---

### Performance Frontend

#### M4. Aucun pipeline d'optimisation d'images
`package.json` ne contient aucun plugin d'optimisation d'image (pas de WebP/AVIF, pas de `sharp`, pas de `vite-plugin-imagemin`). Les photos de résidences sont servies à leur taille originale.

**Fix** : Intégrer `intervention/image` côté Laravel pour générer des thumbnails WebP au moment de l'upload + utiliser `srcset` dans les templates.

---

#### M5. Aucune image avec `srcset` / responsive
Aucune balise `<img>` du template n'utilise `srcset` ou `sizes`. Les cartes de résidences servent la même résolution quelle que soit la taille d'écran.

**Fix** : Générer 3 tailles (400w, 800w, 1200w) et utiliser `srcset` + `sizes` sur toutes les images de résidences.

---

### SEO

#### M6. Titre de page trop générique
`REZI - Trouvez une résidence autour de vous` ne contient pas le mot-clé géo principal "Abidjan".

**Fix** : `REZI — Résidences meublées à Abidjan | Recherche géolocalisée`

---

#### M7. Pas de JSON-LD `Organization` sur la homepage
Le composant `<x-seo-meta>` supporte le JSON-LD `Organization` uniquement sur la route `home`, **mais seulement si certaines conditions sont remplies**. Vérifier que le JSON-LD s'affiche bien avec les données `sameAs` (réseaux sociaux) et `SearchAction`.

**Fix** : S'assurer que la config `rezi.social.*` est renseignée en production pour que le JSON-LD soit complet.

---

### PWA

#### M8. `purpose: "any maskable"` sur toutes les icônes
Dans `manifest.json`, chaque icône a `"purpose": "any maskable"`. Les icônes maskable nécessitent un padding supplémentaire (safe zone) — utiliser la même image pour les deux contextes peut donner un résultat rogné.

**Fix** : Fournir des icônes séparées pour `"any"` et `"maskable"`.

---

#### M9. Double `fetch` event listener dans le Service Worker
`sw.js` contient 2 listeners `addEventListener('fetch', ...)`. Le deuxième (cache-first pour images) est du **code mort** — un seul `respondWith()` peut être appelé par événement `fetch`.

**Fix** : Fusionner la logique en un seul listener avec un routing par URL pattern.

---

### Accessibilité

#### M10. Pas de lien "Aller au contenu" (Skip Nav)
Aucun lien d'accessibilité pour les utilisateurs clavier/lecteurs d'écran pour passer directement au contenu.

**Fix** : Ajouter `<a href="#main-content" class="sr-only focus:not-sr-only ...">Aller au contenu</a>` en haut du body.

---

#### M11. SVG inline sans `aria-hidden="true"`
La majorité des icônes SVG dans la page ne sont pas marquées `aria-hidden="true"`. Les lecteurs d'écran tentent de lire leurs chemins `<path>`.

**Fix** : Ajouter `aria-hidden="true"` sur tous les SVG décoratifs.

---

#### M12. Bouton favori non fonctionnel
La section résidences vedettes contient un `<button>` cœur (favori) avec `aria-label="Ajouter aux favoris"` mais sans `@click` handler.

**Fix** : Connecter au système de favoris existant ou retirer le bouton de la homepage.

---

#### M13. Contraste insuffisant sur certains textes
Des textes en `text-gray-400` et `text-gray-500` sur fond blanc ou clair peuvent ne pas respecter le ratio WCAG AA (4.5:1) pour le texte de petite taille.

**Fix** : Utiliser minimum `text-gray-600` pour les textes informatifs.

---

### Sécurité

#### M14. `mapData()` n'a pas de validation géographique
`HomeController@mapData()` valide uniquement `numeric` pour lat/lng, sans bornes géographiques. Contrairement à `GeoSearchController` qui restreint à la zone Abidjan (5.20–5.50, -4.15 à -3.85).

**Fix** : Ajouter les mêmes contraintes `between:` que le GeoSearchController.

---

#### M15. Caractères wildcard LIKE non échappés
La recherche par commune/quartier utilise `LIKE '%input%'` sans échapper `%` et `_` — un utilisateur peut envoyer `%` seul et forcer un full table scan.

**Fix** : Sanitiser l'input avec `str_replace(['%', '_'], ['\%', '\_'], $input)`.

---

### UX / UI

#### M16. Timeout géolocalisation trop court (8s)
Le `navigator.geolocation.getCurrentPosition()` a un `timeout: 8000`. Sur les réseaux mobiles en Côte d'Ivoire (3G/EDGE), le GPS peut prendre plus de temps.

**Fix** : Augmenter à 12–15 secondes.

---

#### M17. `maximumAge: 300000` (5 min) — Position potentiellement périmée
Pour une recherche dans un rayon de 100–500m, une position GPS vieille de 5 minutes (en déplacement) peut être significativement fausse.

**Fix** : Réduire à `120000` (2 min) ou `60000` (1 min).

---

#### M18. Pas de skeleton loading pour les cartes résidences
Le bottom sheet affiche les `featuredResidences` (server-rendered) mais les résultats dynamiques par rayon n'ont aucun placeholder de chargement.

**Fix** : Ajouter des skeletons animés pendant le chargement des résultats.

---

## 🟢 PROBLÈMES MINEURS (5)

| # | Description | Fichier |
|---|---|---|
| L1 | Index manquant sur `(status, is_available)` dans la table `residences` | migrations |
| L2 | Alpine.js inline massif (~90 lignes) dans le HTML — non cacheable séparément | home.blade.php L3-92 |
| L3 | Pas de limite de taille du cache Service Worker (images) | sw.js |
| L4 | Newsletter dans le footer non connectée (action `#`) | footer component |
| L5 | Polices Google Fonts sans `font-display: swap` explicite | app.blade.php L36 |

---

## ✅ POINTS POSITIFS

| # | Description |
|---|---|
| ✅ | **Fallback géoloc → recherche par quartier** — excellente UX pour le marché cible |
| ✅ | **PWA complète** — manifest, service worker, push notifications, mode offline |
| ✅ | **SEO meta bien structuré** — OpenGraph, Twitter Cards, canonical, JSON-LD conditionnel |
| ✅ | **Rate limiting complet** — API, géoloc, contact, login, register, upload |
| ✅ | **Cache Eloquent** — toutes les données lourdes sont cachées 1h |
| ✅ | **Design responsive** — layout adapté mobile/desktop avec composants dédiés |
| ✅ | **Animations CSS sans JS** — `x-intersect` + reveal CSS = performant |
| ✅ | **Charte graphique cohérente** — palette orange (#F7931E) + Tailwind v4 theme |
| ✅ | **Navigation mobile native-like** — bottom tab bar, header fixe, safe-area |
| ✅ | **Haversine avec bounding box** — pré-filtre géographique performant |

---

## 🎯 PLAN D'ACTION PRIORITAIRE

### Sprint 1 — Critiques (1–2 jours)
1. **C2** : Fixer l'image hero (`loading="eager"`, héberger localement, `alt=""`)
2. **C4** : Corriger les doubles `alt` sur toutes les images
3. **C3** : Générer le sitemap avec l'URL de production
4. **C1** : Réécrire la requête `popularZones` sans N+1

### Sprint 2 — Performance (2–3 jours)
5. **M4/M5** : Pipeline d'optimisation images (WebP + srcset)
6. **M2** : Utiliser `GeolocationService::getRadiusCounts()` dans le contrôleur
7. **M1** : Ajouter un Observer pour invalider le cache homepage
8. **L1** : Migration pour index `(status, is_available)` + `(status, is_available, latitude, longitude)`

### Sprint 3 — SEO & Accessibilité (1–2 jours)
9. **M6** : Optimiser le titre de la page avec "Abidjan"
10. **M10** : Ajouter le lien "Aller au contenu"
11. **M11** : `aria-hidden="true"` sur tous les SVG décoratifs
12. **M13** : Vérifier les contrastes WCAG AA

### Sprint 4 — Sécurité & UX (1 jour)
13. **M14** : Valider les bornes géographiques dans `mapData()`
14. **M15** : Échapper les wildcards LIKE
15. **M16/M17** : Ajuster timeout et maximumAge géoloc
16. **M18** : Ajouter des skeletons de chargement

### Sprint 5 — PWA & Finitions (1 jour)
17. **M8** : Séparer les icônes maskable
18. **M9** : Fusionner les listeners fetch du SW
19. **M12** : Connecter ou retirer le bouton favori
20. **L4** : Connecter le formulaire newsletter

---

> **Prochain audit recommandé** : Après le déploiement en production, faire un audit Lighthouse + Core Web Vitals réels depuis Abidjan (réseau mobile).
