<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * Faire confiance au proxy de l'hebergeur.
         *
         * Render, comme tout hebergeur de ce type, termine le TLS sur son
         * proxy et transmet la requete en clair au conteneur, avec l'en-tete
         * `X-Forwarded-Proto: https`. Sans cette confiance, Laravel croit
         * repondre a une requete `http` et fabrique toutes ses URL avec ce
         * schema : le navigateur, lui, est sur une page `https` et refuse de
         * charger des feuilles de style annoncees en clair. Le site s'affichait
         * sans le moindre style, et les redirections de connexion repartaient
         * en `http`.
         *
         * `at: '*'` : l'adresse du proxy n'est pas connue d'avance et change
         * d'un deploiement a l'autre. C'est sans risque ici, le conteneur
         * n'etant joignable que par ce proxy.
         */
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'auth' => \App\Http\Middleware\RedirectIfNotAuthenticated::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'db.connection.errors' => \App\Http\Middleware\HandleDatabaseConnectionErrors::class,
        ]);
        
        // Ajouter le middleware globalement pour capturer les erreurs de connexion
        $middleware->append(\App\Http\Middleware\HandleDatabaseConnectionErrors::class);

        // Un compte eleve ne doit pas atteindre les ecrans de gestion : les
        // routes ne portent qu'un garde « auth », ce filtre complete. Il est
        // pose dans le groupe « web » et non en global : le nom de la route
        // n'est connu qu'une fois le routage fait.
        $middleware->web(append: [\App\Http\Middleware\RestreindreLesEleves::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
