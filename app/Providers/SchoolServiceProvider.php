<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Helpers\SchoolHelper;

class SchoolServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        /*
         * Le partage de `schoolSettings` avec toutes les vues a lieu dans
         * AppServiceProvider, et là seulement.
         *
         * Les deux fournisseurs déclaraient le même composeur sur la même
         * variable : le second écrasait le premier, et la version d'ici — non
         * protégée — reprenait le dessus. Base injoignable, et le rendu de la
         * page d'erreur échouait à son tour ; on n'obtenait plus qu'un 500
         * muet, au moment précis où il fallait lire la cause.
         *
         * `SchoolHelper` reste utilisé ailleurs : seul le doublon disparaît.
         */
    }
}
