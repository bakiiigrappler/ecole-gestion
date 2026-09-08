#!/bin/sh
set -e

# Demarrage de l'application, a chaque lancement du conteneur.
#
# Ce qui a besoin de la base ne peut pas se faire a la construction de l'image :
# elle n'existe pas encore. Migrations et mises en cache se font donc ici.

echo "→ Preparation de l'application"

# Dire, des la premiere ligne, sur quoi ce conteneur-ci travaille.
#
# Sans cela, une variable modifiee dans le tableau de bord et un conteneur qui
# n'a pas redemarre donnent exactement la meme trace qu'une variable mal
# renseignee : on cherche l'erreur dans la configuration alors qu'elle est dans
# le cycle de vie du service. La ligne ci-dessous tranche.
if [ "${DB_CONNECTION}" = "sqlite" ]; then
    echo "   base : sqlite → ${DB_DATABASE:-<non renseignee>}"
else
    echo "   base : ${DB_CONNECTION:-<non renseignee>} → ${DB_HOST:-<hote absent>}:${DB_PORT:-<port absent>}${DB_URL:+ (via DB_URL)}"
fi

echo "   environnement : ${APP_ENV:-<non renseigne>} · debug ${APP_DEBUG:-<non renseigne>}"

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

# SQLite : le fichier doit exister avant que Laravel n'essaie de l'ouvrir.
if [ "${DB_CONNECTION}" = "sqlite" ]; then
    FICHIER="${DB_DATABASE:-/var/www/database/database.sqlite}"

    if [ ! -f "${FICHIER}" ]; then
        echo "→ Creation de la base SQLite : ${FICHIER}"
        mkdir -p "$(dirname "${FICHIER}")"
        touch "${FICHIER}"
    fi

    chown www-data:www-data "${FICHIER}" 2>/dev/null || true
    chmod 664 "${FICHIER}" 2>/dev/null || true
fi

# Les migrations, seulement si une base est configuree — par ses cinq variables,
# par une URL unique ou par un fichier SQLite. Tester DB_HOST seul laissait
# passer les deploiements configures autrement, et la base restait vide sans
# que rien ne le signale.
#
# `--force` parce qu'en production Laravel demande confirmation, et personne
# n'est la pour la donner.
if [ -n "${DB_HOST}" ] || [ -n "${DB_URL}" ] || [ "${DB_CONNECTION}" = "sqlite" ]; then
    echo "→ Migrations"
    php artisan migrate --force --no-interaction || echo "⚠ Migrations en echec — voir les journaux"

    # Une base sans le moindre administrateur n'est utilisable par personne :
    # nul ne peut s'y connecter, donc nul ne peut rien y creer. On la peuple au
    # premier demarrage ; une installation qui a deja son administrateur n'est
    # jamais retouchee.
    #
    # Le critere porte sur les administrateurs, et non sur le nombre total de
    # comptes : certaines migrations posent elles-memes des utilisateurs et des
    # classes de demonstration. Une base fraiche n'est donc jamais vide, et
    # compter les lignes ne dirait rien.
    #
    # `php -r` plutot que `artisan tinker` : tinker ecrit ses erreurs sur la
    # sortie standard, et un message d'exception contient des chiffres —
    # numeros de ligne, codes SQL — qu'on prendrait pour un decompte.
    #
    # `require vendor/autoload.php` : `bootstrap/app.php` ne le charge pas,
    # artisan et public/index.php s'en chargent eux-memes. Sans lui, aucune
    # classe n'est trouvee et le comptage echoue en silence.
    #
    # `|| echo -1` : le script tourne sous `set -e`, un php qui sort en erreur
    # l'arreterait net et le conteneur s'eteindrait sans servir une page.
    ADMINS=$(php -r '
        require "/var/www/vendor/autoload.php";
        $app = require "/var/www/bootstrap/app.php";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        try {
            echo App\Models\User::whereIn("role", ["admin", "superadmin"])->count();
        } catch (Throwable $e) {
            echo "-1";
        }
    ' 2>/dev/null || echo "-1")

    if [ "${ADMINS}" = "0" ]; then
        echo "→ Aucun administrateur : peuplement initial"
        php artisan db:seed --force --no-interaction || echo "⚠ Peuplement en echec — voir les journaux"
    elif [ "${ADMINS}" = "-1" ]; then
        echo "⚠ Base injoignable : ni comptage, ni peuplement"
    else
        echo "→ ${ADMINS} administrateur(s) en place, peuplement ignore"
    fi
fi

# Rendre a www-data ce que root vient d'ecrire.
#
# Tout ce qui precede tourne en root : `storage:link`, les mises en cache, les
# migrations, le peuplement. Chaque commande qui journalise cree
# `storage/logs/laravel.log` appartenant a root, et PHP-FPM — qui tourne en
# www-data — ne peut plus y ajouter une ligne :
#
#   The stream or file "/var/www/storage/logs/laravel.log" could not be opened
#   in append mode: Failed to open stream: Permission denied
#
# L'application se retrouvait alors incapable de journaliser sa propre panne,
# et l'erreur de journalisation masquait la panne d'origine.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Nginx lit ${PORT} : Render le fixe a l'execution, 10000 par defaut.
export PORT="${PORT:-10000}"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

echo "→ En ecoute sur le port ${PORT}"

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
