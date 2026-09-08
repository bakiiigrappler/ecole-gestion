<?php

/*
 * Environnement de l'instance : production ou test.
 *
 * Une plateforme scolaire se forme et se demontre ; il faut pouvoir le faire
 * sans toucher aux vraies donnees. Les deux instances sont physiquement
 * separees — base et serveur — et cette configuration ne fait que dire laquelle
 * on regarde, et ou trouver l'autre.
 *
 * Rien n'est devine : sans EGESCO_ENV dans le .env, l'instance se presente
 * comme la production, et aucun lien de bascule ne s'affiche.
 */
return [
    'nom' => env('EGESCO_ENV', 'prod'),                     // 'prod' | 'test'
    'url_prod' => rtrim((string) env('EGESCO_URL_PROD', ''), '/'),
    'url_test' => rtrim((string) env('EGESCO_URL_TEST', ''), '/'),
];
