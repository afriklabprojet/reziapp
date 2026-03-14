# REZI - Nouvelles Fonctionnalités (v2.0)

Ce document décrit les nouvelles fonctionnalités implémentées dans REZI.

## 🔐 Authentification Sociale

### Google & Facebook OAuth

Les utilisateurs peuvent maintenant se connecter/s'inscrire via leurs comptes Google ou Facebook.

**Configuration requise** (dans `.env`):

```env
# Google OAuth
GOOGLE_CLIENT_ID=votre_client_id
GOOGLE_CLIENT_SECRET=votre_client_secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# Facebook OAuth
FACEBOOK_CLIENT_ID=votre_app_id
FACEBOOK_CLIENT_SECRET=votre_app_secret
FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"
```

**Routes**:
- `GET /auth/google` - Redirection vers Google
- `GET /auth/google/callback` - Callback Google
- `GET /auth/facebook` - Redirection vers Facebook
- `GET /auth/facebook/callback` - Callback Facebook

---

## 💬 Système de Messagerie

Communication directe entre utilisateurs et propriétaires.

### Modèles
- `Conversation` - Conversations liées à une résidence
- `Message` - Messages individuels

### Routes
- `GET /conversations` - Liste des conversations
- `POST /conversations/start` - Démarrer une conversation
- `GET /conversations/{id}` - Voir une conversation
- `POST /conversations/{id}/messages` - Envoyer un message
- `DELETE /conversations/{id}` - Supprimer une conversation

### Fonctionnalités
- Indicateur de messages non lus
- Marquage automatique comme lu
- Limite de rate (30 messages/minute)

---

## ⭐ Système d'Avis

Les utilisateurs peuvent laisser des avis sur les résidences.

### Notes (sur 5)
- Propreté (`rating_cleanliness`)
- Emplacement (`rating_location`)
- Rapport qualité/prix (`rating_value`)
- Communication (`rating_communication`)

### Routes
- `GET /reviews` - Liste des avis
- `GET /reviews/create/{residence}` - Formulaire d'avis
- `POST /reviews/{residence}` - Soumettre un avis
- `POST /reviews/{review}/respond` - Réponse du propriétaire

### Modération
- Les avis sont en statut `pending` par défaut
- Admin peut approuver/rejeter

---

## 🔔 Système de Notifications

Notifications en temps réel pour tous les utilisateurs.

### Types de notifications
- `new_message` - Nouveau message reçu
- `new_review` - Nouvel avis sur une résidence
- `review_approved` - Avis approuvé
- `contact_received` - Demande de contact
- `residence_approved` - Résidence approuvée
- `residence_rejected` - Résidence rejetée
- `new_favorite` - Résidence ajoutée en favori

### Routes
- `GET /notifications` - Liste des notifications
- `POST /notifications/{id}/read` - Marquer comme lue
- `POST /notifications/read-all` - Tout marquer comme lu
- `DELETE /notifications/{id}` - Supprimer

### API (AJAX)
- `GET /api/notifications/unread-count` - Nombre de non lues
- `GET /api/notifications/latest` - 5 dernières notifications

---

## ❤️ Système de Favoris

Sauvegarde des résidences préférées avec notes personnelles.

### Routes
- `GET /favorites` - Liste des favoris
- `POST /favorites/{residence}/toggle` - Ajouter/Retirer
- `PATCH /favorites/{id}/note` - Ajouter une note
- `DELETE /favorites/{id}` - Supprimer

### Fonctionnalités
- Synchronisation serveur (utilisateurs connectés)
- Fallback localStorage (visiteurs)
- Notes personnelles par favori

---

## 📱 Progressive Web App (PWA)

REZI est maintenant installable sur mobile comme une application native.

### Fichiers
- `/manifest.json` - Manifest PWA
- `/sw.js` - Service Worker
- `/offline.html` - Page hors ligne

### Fonctionnalités
- Installation sur écran d'accueil
- Fonctionne hors ligne (pages cachées)
- Notifications push (préparé)
- Raccourcis app (Rechercher, Carte, Favoris)

### Cache Strategy
- **Network First** pour les pages HTML
- **Cache First** pour les images
- **Aucun cache** pour les API

---

## 🗄️ Nouvelles Tables de Base de Données

### `conversations`
```sql
- id (BIGINT)
- residence_id (BIGINT, nullable)
- user_id (BIGINT) - Initiateur
- owner_id (BIGINT) - Propriétaire
- last_message_at (TIMESTAMP)
- timestamps
```

### `messages`
```sql
- id (BIGINT)
- conversation_id (BIGINT)
- sender_id (BIGINT)
- content (TEXT)
- read_at (TIMESTAMP, nullable)
- timestamps
```

### `reviews`
```sql
- id (BIGINT)
- residence_id (BIGINT)
- user_id (BIGINT)
- rating_cleanliness (1-5)
- rating_location (1-5)
- rating_value (1-5)
- rating_communication (1-5)
- comment (TEXT, nullable)
- owner_response (TEXT, nullable)
- owner_responded_at (TIMESTAMP)
- status (pending/approved/rejected)
- timestamps
```

### `notifications`
```sql
- id (UUID)
- user_id (BIGINT)
- type (VARCHAR)
- title (VARCHAR)
- body (TEXT, nullable)
- icon (VARCHAR, nullable)
- action_url (VARCHAR, nullable)
- data (JSON, nullable)
- read_at (TIMESTAMP, nullable)
- timestamps
```

### `favorites`
```sql
- id (BIGINT)
- user_id (BIGINT)
- residence_id (BIGINT)
- notes (TEXT, nullable)
- timestamps
```

---

## 🔧 Mise à jour du modèle User

### Nouveaux champs
- `provider` - google/facebook/null
- `provider_id` - ID externe OAuth
- `avatar` - URL de l'avatar

### Nouvelles relations
- `conversations()`
- `messages()`
- `reviews()`
- `notifications()`
- `unreadNotifications()`
- `favorites()`
- `favoriteResidences()`
- `searchHistories()` - Historique de recherche
- `residenceViews()` - Visites de résidences

### Nouvelles méthodes
- `hasFavorited($residenceId)` - Vérifie si en favori
- `getAvatarUrl()` - URL de l'avatar ou placeholder
- `unreadMessagesCount()` - Nombre de messages non lus
- `recentlyViewedResidences($limit)` - Résidences récemment visitées

---

## 👤 Dashboard Client Complet

Un espace personnel complet pour les utilisateurs avec statistiques et outils.

### Routes Client
- `GET /client/dashboard` - Dashboard principal
- `GET /client/search-history` - Historique de recherche
- `DELETE /client/search-history/clear` - Effacer historique
- `DELETE /client/search-history/{id}` - Supprimer une recherche
- `GET /client/view-history` - Historique des visites
- `DELETE /client/view-history/clear` - Effacer visites
- `GET /client/compare` - Comparateur de résidences
- `GET /client/contacts` - Mes demandes de contact
- `GET /client/reviews` - Mes avis publiés
- `GET /client/statistics` - Statistiques personnelles
- `GET /client/alerts` - Centre d'alertes

### Fonctionnalités Dashboard
- **Statistiques rapides** : Favoris, messages, visites, contacts, avis, alertes
- **Recommandations personnalisées** : Basées sur les favoris et recherches
- **Résidences récemment visitées** : Avec source (recherche, carte, recommandation)
- **Nouvelles résidences** : Dans les zones favorites
- **Actions rapides** : Accès direct aux fonctionnalités

### Comparateur de Résidences
- Comparer jusqu'à 4 résidences côte à côte
- Critères : Prix, localisation, type, chambres, superficie, équipements, note, disponibilité
- Ajout depuis les favoris

### Statistiques Personnelles
- **Activité mensuelle** : Graphique des 6 derniers mois (visites, recherches, contacts)
- **Communes explorées** : Top des communes visitées
- **Types de logement** : Préférences par type (graphique donut)
- **Budget moyen** : Analyse des critères de recherche
- **Conseils personnalisés** : Suggestions basées sur l'activité

### Centre d'Alertes
- Nouvelles résidences dans les zones favorites
- Disponibilité des favoris
- Support des notifications push (navigateur)

### Nouvelles Tables

#### `search_histories`
```sql
- id (BIGINT)
- user_id (BIGINT)
- commune (VARCHAR, nullable)
- min_price (DECIMAL, nullable)
- max_price (DECIMAL, nullable)
- bedrooms (INT, nullable)
- type (VARCHAR, nullable)
- amenities (JSON, nullable)
- latitude (DECIMAL, nullable)
- longitude (DECIMAL, nullable)
- radius (INT, nullable)
- results_count (INT)
- search_query (VARCHAR, nullable)
- timestamps
```

#### `residence_views`
```sql
- id (BIGINT)
- user_id (BIGINT, nullable)
- residence_id (BIGINT)
- ip_address (VARCHAR)
- user_agent (VARCHAR, nullable)
- referer (VARCHAR, nullable)
- source (VARCHAR) - search/map/recommendation/direct
- duration_seconds (INT, nullable)
- contacted (BOOLEAN)
- favorited (BOOLEAN)
- shared (BOOLEAN)
- timestamps
```

---

## 📦 Dépendances Ajoutées

```bash
composer require laravel/socialite
```

---

## 🚀 Migration

```bash
php artisan migrate
```

---

## ✅ Tests

À ajouter pour les nouvelles fonctionnalités :
- Tests d'authentification sociale
- Tests de messagerie
- Tests d'avis
- Tests de notifications
- Tests de favoris

---

## 📝 Configuration Google OAuth

1. Aller sur https://console.developers.google.com/
2. Créer un projet
3. Activer "Google+ API"
4. Créer des identifiants OAuth 2.0
5. Configurer les URIs de redirection autorisées
6. Copier Client ID et Secret dans `.env`

## 📝 Configuration Facebook OAuth

1. Aller sur https://developers.facebook.com/
2. Créer une application
3. Ajouter le produit "Facebook Login"
4. Configurer les URIs de redirection autorisées
5. Copier App ID et Secret dans `.env`
