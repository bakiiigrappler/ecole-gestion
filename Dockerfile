# Image applicative d'Egesco : nginx + PHP-FPM dans un seul conteneur.
#
# Le docker-compose local separe le serveur web de l'applicatif ; un hebergeur
# comme Render n'expose qu'un port par service et ne lance qu'un conteneur.
# L'image doit donc se suffire a elle-meme.
#
# Deux etapes : Node construit les feuilles de style et les scripts, PHP recoit
# le resultat. Le dossier `public/build` n'est pas versionne — sans cette
# premiere etape, l'application se servirait sans aucun style.

# ---------------------------------------------------------------- 1. Les assets
FROM node:22-alpine AS assets

WORKDIR /app

# Les dependances d'abord : cette couche ne se reconstruit que si package.json
# bouge, ce qui epargne une minute a chaque deploiement.
COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# ---------------------------------------------------------------- 2. L'application
FROM php:8.2-fpm-alpine

# nginx et supervisor servent l'application ; postgresql-dev et les
# bibliotheques d'images servent a compiler les extensions PHP ; gettext donne
# `envsubst`, qui injecte le port dans la configuration nginx au demarrage.
RUN apk add --no-cache \
        nginx \
        supervisor \
        gettext \
        postgresql-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
        git \
        unzip

# `pdo_pgsql`, et non `pdo_mysql` : l'application tourne sur PostgreSQL.
# L'image installait la bibliotheque cliente sans l'extension PHP — elle se
# construisait, et se refusait a la base des le premier acces.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        gd \
        mbstring \
        exif \
        pcntl \
        bcmath \
        zip \
        intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Les dependances PHP avant le code, pour la meme raison que ci-dessus.
# `--no-scripts` : les scripts de Laravel touchent a l'application, qui n'est
# pas encore copiee.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

COPY . /var/www
COPY --from=assets /app/public/build /var/www/public/build

# Les dossiers de travail avant l'autoloader : `dump-autoload` declenche
# `package:discover`, qui refuse de s'executer sans `bootstrap/cache`. Ces
# dossiers ne viennent pas du depot, le .dockerignore les ecarte.
RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/app/public \
        bootstrap/cache

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Configuration du conteneur
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# La configuration nginx est un gabarit : le port n'est connu qu'a l'execution.
#
# Alpine inclut `conf.d/*.conf` au premier niveau — c'est la place des modules —
# et `http.d/*.conf` a l'interieur du bloc `http`. Un bloc `server` depose dans
# conf.d est donc refuse net ; c'est http.d que l'entrypoint vise.
RUN mkdir -p /etc/nginx/templates /run/nginx \
    && rm -f /etc/nginx/http.d/default.conf
COPY docker/nginx/default.conf /etc/nginx/templates/default.conf.template

# Le dossier `database` rejoint storage : SQLite y ecrit son fichier, et son
# journal de transactions a cote. Sans le dossier accessible en ecriture,
# SQLite echoue meme quand le fichier, lui, existe.
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p database \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

# Journaux sur la sortie d'erreur : c'est la convention des conteneurs, et
# c'est la que l'hebergeur les lit. Sans cela, Laravel ecrit dans un fichier
# que personne n'ira jamais consulter — et qui posait un probleme de droits.
# Une variable definie par l'hebergeur reste prioritaire sur celle-ci.
ENV LOG_CHANNEL=stderr

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
