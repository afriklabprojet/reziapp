#!/bin/bash

# 🚀 REZI - Script de démarrage rapide

echo "🏠 REZI - Démarrage de l'environnement de développement"
echo "=================================================="
echo ""

# Vérifier si .env existe
if [ ! -f .env ]; then
    echo "⚠️  Fichier .env manquant. Création à partir de .env.example..."
    cp .env.example .env
    echo "✅ Fichier .env créé"
    echo ""
    echo "⚠️  N'oubliez pas de configurer:"
    echo "   - DB_DATABASE, DB_USERNAME, DB_PASSWORD"
    echo "   - GOOGLE_MAPS_API_KEY ou MAPBOX_API_KEY"
    echo ""
fi

# Vérifier si la clé d'application existe
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Génération de la clé d'application..."
    php artisan key:generate
    echo "✅ Clé générée"
    echo ""
fi

# Vérifier si node_modules existe
if [ ! -d "node_modules" ]; then
    echo "📦 Installation des dépendances NPM..."
    npm install
    echo "✅ Dépendances NPM installées"
    echo ""
fi

# Vérifier si vendor existe
if [ ! -d "vendor" ]; then
    echo "📦 Installation des dépendances Composer..."
    composer install
    echo "✅ Dépendances Composer installées"
    echo ""
fi

# Vérifier la connexion base de données
echo "🔍 Vérification de la base de données..."
php artisan db:show 2>/dev/null
if [ $? -ne 0 ]; then
    echo "⚠️  Impossible de se connecter à la base de données"
    echo "   Assurez-vous que MySQL est lancé et que .env est bien configuré"
    echo ""
else
    echo "✅ Connexion base de données OK"
    echo ""

    # Vérifier si les tables existent
    TABLE_COUNT=$(php artisan db:table --json 2>/dev/null | grep -c "\"table\"" || echo "0")
    if [ "$TABLE_COUNT" -lt 5 ]; then
        echo "📊 Les migrations n'ont pas été exécutées."
        read -p "   Voulez-vous exécuter les migrations maintenant? (o/N) " -n 1 -r
        echo ""
        if [[ $REPLY =~ ^[Oo]$ ]]; then
            php artisan migrate
            echo "✅ Migrations exécutées"
            echo ""

            read -p "   Voulez-vous créer les équipements par défaut? (o/N) " -n 1 -r
            echo ""
            if [[ $REPLY =~ ^[Oo]$ ]]; then
                php artisan db:seed --class=AmenitySeeder 2>/dev/null || echo "Seeder non créé encore"
                echo ""
            fi
        fi
    else
        echo "✅ Migrations déjà exécutées"
        echo ""
    fi
fi

# Vérifier storage link
if [ ! -L "public/storage" ]; then
    echo "🔗 Création du lien symbolique storage..."
    php artisan storage:link
    echo "✅ Lien créé"
    echo ""
fi

# Compiler les assets
echo "🎨 Compilation des assets..."
npm run build
echo "✅ Assets compilés"
echo ""

echo "=================================================="
echo "✅ Environnement prêt!"
echo ""
echo "📍 Pour démarrer le serveur de développement:"
echo "   php artisan serve"
echo ""
echo "📍 Pour compiler les assets en mode watch:"
echo "   npm run dev"
echo ""
echo "📍 Pour démarrer le serveur WebSocket (Reverb):"
echo "   php artisan reverb:start --debug"
echo ""
echo "📍 Pour démarrer le worker de files d'attente:"
echo "   php artisan queue:work --queue=high,default --tries=3 --timeout=90"
echo ""
echo "🌐 Puis visitez: http://localhost:8000"
echo "=================================================="
