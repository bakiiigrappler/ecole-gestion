#!/bin/bash

# Script de déploiement automatique pour Egesco
# Usage: ./deploy.sh [environment]

set -e

# Configuration
PROJECT_NAME="egestco"
PROJECT_PATH="/var/www/$PROJECT_NAME"
BACKUP_PATH="/var/backups/$PROJECT_NAME"
ENVIRONMENT=${1:-production}

echo "🚀 Déploiement de Egesco - Environnement: $ENVIRONMENT"

# Fonction de sauvegarde
backup_database() {
    echo "📦 Sauvegarde de la base de données..."
    BACKUP_FILE="$BACKUP_PATH/backup_$(date +%Y%m%d_%H%M%S).sql"
    mkdir -p $BACKUP_PATH
    
    # Récupérer les informations de la base de données depuis .env
    DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
    DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
    DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
    
    mysqldump -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > $BACKUP_FILE
    echo "✅ Sauvegarde créée: $BACKUP_FILE"
}

# Fonction de mise à jour du code
update_code() {
    echo "📥 Mise à jour du code source..."
    git fetch origin
    git reset --hard origin/main
    echo "✅ Code mis à jour"
}

# Fonction d'installation des dépendances
install_dependencies() {
    echo "📦 Installation des dépendances..."
    
    # Dépendances PHP
    composer install --optimize-autoloader --no-dev --no-interaction
    
    # Dépendances Node.js
    npm ci --production
    npm run build
    
    echo "✅ Dépendances installées"
}

# Fonction de migration
run_migrations() {
    echo "🔄 Exécution des migrations..."
    php artisan migrate --force
    echo "✅ Migrations exécutées"
}

# Fonction d'optimisation
optimize_application() {
    echo "⚡ Optimisation de l'application..."
    
    # Cache de configuration
    php artisan config:cache
    
    # Cache des routes
    php artisan route:cache
    
    # Cache des vues
    php artisan view:cache
    
    # Optimisation générale
    php artisan optimize
    
    echo "✅ Application optimisée"
}

# Fonction de redémarrage des services
restart_services() {
    echo "🔄 Redémarrage des services..."
    
    # Redémarrer PHP-FPM
    sudo systemctl restart php8.2-fpm
    
    # Redémarrer Apache/Nginx
    if systemctl is-active --quiet apache2; then
        sudo systemctl restart apache2
    elif systemctl is-active --quiet nginx; then
        sudo systemctl restart nginx
    fi
    
    echo "✅ Services redémarrés"
}

# Fonction de vérification
verify_deployment() {
    echo "🔍 Vérification du déploiement..."
    
    # Vérifier que l'application répond
    if curl -f -s http://localhost > /dev/null; then
        echo "✅ Application accessible"
    else
        echo "❌ Application non accessible"
        exit 1
    fi
    
    # Vérifier les permissions
    if [ -w storage ] && [ -w bootstrap/cache ]; then
        echo "✅ Permissions correctes"
    else
        echo "❌ Permissions incorrectes"
        exit 1
    fi
}

# Fonction de nettoyage
cleanup() {
    echo "🧹 Nettoyage..."
    
    # Supprimer les anciennes sauvegardes (garder les 7 dernières)
    find $BACKUP_PATH -name "backup_*.sql" -type f -mtime +7 -delete
    
    # Nettoyer les logs anciens
    find storage/logs -name "*.log" -type f -mtime +30 -delete
    
    echo "✅ Nettoyage terminé"
}

# Fonction principale
main() {
    echo "🎯 Début du déploiement..."
    
    # Vérifier que nous sommes dans le bon répertoire
    if [ ! -f "artisan" ]; then
        echo "❌ Ce script doit être exécuté depuis la racine du projet Laravel"
        exit 1
    fi
    
    # Vérifier que .env existe
    if [ ! -f ".env" ]; then
        echo "❌ Fichier .env manquant"
        exit 1
    fi
    
    # Exécuter les étapes de déploiement
    backup_database
    update_code
    install_dependencies
    run_migrations
    optimize_application
    restart_services
    verify_deployment
    cleanup
    
    echo "🎉 Déploiement terminé avec succès!"
    echo "📊 Application disponible sur: $(grep APP_URL .env | cut -d '=' -f2)"
}

# Gestion des erreurs
trap 'echo "❌ Erreur lors du déploiement. Vérifiez les logs."; exit 1' ERR

# Exécution
main "$@"
