#!/bin/sh
set -e

# Demarrage de l'application, a chaque lancement du conteneur.
#
# Ce qui a besoin de la base ne peut pas se faire a la construction de l'image :
# elle n'existe pas encore. Migrations et mises en cache se font donc ici.

echo "→ Preparation de l'application"

# Le lien public/storage n'existe pas dans l'image : le dossier est monte ou
# recree a chaque demarrage.
php artisan storage:link --force 2>/dev/null || true

# Les caches sont reconstruits a chaque deploiement : un cache herite d'une
# version precedente sert des routes qui n'existent plus.
php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan view:cache

# Les migrations, seulement si une base est configuree. `--force` parce qu'en
# production Laravel demande confirmation, et personne n'est la pour la donner.
if [ -n "${DB_HOST}" ]; then
    echo "→ Migrations"
    php artisan migrate --force --no-interaction || echo "⚠ Migrations en echec — voir les journaux"
fi

# Nginx lit ${PORT} : Render le fixe a l'execution, 10000 par defaut.
export PORT="${PORT:-10000}"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

echo "→ En ecoute sur le port ${PORT}"

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
