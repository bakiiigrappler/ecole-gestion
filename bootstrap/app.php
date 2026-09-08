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
