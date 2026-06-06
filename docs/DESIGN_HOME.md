# ReziApp — Refonte de la page d’accueil

## 🎯 Objectifs UX
- Compréhension du produit en moins de 5 secondes.
- Mise en avant claire de la géolocalisation automatique (rayon 100/300/500 m).
- Confiance, simplicité, rapidité (pattern marketplace + mobilité).
- Incitation à la recherche + inscription propriétaires.

---

## 🧩 Wireframe basse fidélité (structure)

```
[HEADER]
- Logo ReziApp
- Accès Résidences / Carte / Connexion

[HERO]
- Titre principal (500 m)
- Sous-titre (géoloc + Abidjan)
- CTA primaire: Rechercher autour de moi
- CTA secondaire: Je suis propriétaire
- Chips rayon (100/300/500)
- Visuel carte + pins

[COMMENT ÇA MARCHE]
- Étape 1: Localisation
- Étape 2: Rayon
- Étape 3: Contact

[CARTE PREVIEW]
- Mini map
- Cartes “à 280 m”
- CTA: Explorer

[AVANTAGES]
- Ultra local
- Vérifié
- WhatsApp/Appel
- Spécial Abidjan

[PROPRIÉTAIRES]
- Message business
- CTA: Ajouter ma résidence gratuitement
- Mock dashboard

[ZONES COUVERTES]
- Chips communes
- Visuels zones populaires

[PREUVE SOCIALE]
- Témoignages
- Badges confiance

[FOOTER]
- Légal / Contact / Réseaux
```

---

## 🖼️ Maquette haute fidélité (description)
- **Hero premium** avec gradient vert → bleu, carte simulée avec pins animés.
- **CTA primaires visibles** : bouton principal blanc + bouton secondaire contour.
- **Sections alternées** clair / gris clair pour scannabilité.
- **Cards** arrondies 2xl, ombres légères, typographie lisible.
- **Badges de confiance** sous forme de cartes compactes.
- **CTA sticky mobile** (barre fixe bas d’écran).

---

## 🎨 Design system

### Couleurs
- **Vert confiance**: `#10B981` (emerald-600)
- **Vert clair**: `#D1FAE5` (emerald-100)
- **Bleu tech**: `#2563EB` (blue-600)
- **Gris texte**: `#6B7280` (gray-500)
- **Noir texte**: `#111827` (gray-900)
- **Fond clair**: `#F9FAFB` (gray-50)

### Typographies
- **Titres**: `text-3xl` / `text-4xl`, `font-bold`
- **Sous-titres**: `text-sm` / `text-base`, `text-gray-600`
- **CTA**: `text-sm`, `font-semibold`, `rounded-xl`

### Boutons
- **Primary**: `bg-emerald-600 text-white` (hover emerald-700)
- **Secondary**: `border border-white/40 text-white`
- **Outline**: `border border-gray-300 text-gray-700`

### Spacing & radius
- **Cards**: `rounded-2xl`, `shadow-sm`, `p-6`
- **Sections**: `py-14`, `max-w-7xl`

---

## 🧱 Composants réutilisables
- `HeroMapCard` (carte + pins + badge distance)
- `HowItWorksCard` (étape + description)
- `BenefitCard` (icône + bénéfice)
- `OwnerDashboardCard` (stats + quote)
- `TestimonialCard` (avatar + quote)
- `ZoneChip` (commune)
- `CTAStickyMobile`

---

## ✅ Prêt pour intégration Blade
Le design est déjà intégré dans `resources/views/home.blade.php` avec Tailwind (notation canonique v4) et des sections modulaires faciles à découper en composants.
