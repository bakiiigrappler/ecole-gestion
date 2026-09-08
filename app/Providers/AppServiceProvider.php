<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use App\Models\SchoolSettings;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pagination maison, aux couleurs de la charte. Les vues livrees par
        // Laravel vivent sous vendor/, que Tailwind ne parcourt pas : leurs
        // classes ne seraient jamais generees a la compilation des assets.
        Paginator::defaultView('vendor.pagination.ogar');
        Paginator::defaultSimpleView('vendor.pagination.ogar');

        /*
         * L'identite de la plateforme est partagee avec toutes les vues, y
         * compris la page de connexion : celle-ci s'affiche avant qu'aucun
         * etablissement ne soit resolu, elle ne peut donc rien lire en base.
         */
        \Illuminate\Support\Facades\View::share('marque', \App\Support\Marque::tous());
        \Illuminate\Support\Facades\View::share('marqueLogo', \App\Support\Marque::logoUrl());
        \Illuminate\Support\Facades\View::share('plateforme', \App\Support\ParametresPlateforme::tous());

        /*
         * Les paramètres de l'établissement, partagés avec toutes les vues.
         *
         * Le `try` n'est pas de la prudence de principe : ce composeur tourne
         * sur *toutes* les vues, y compris celle qui affiche les erreurs. Base
         * injoignable, et le rendu de l'erreur échouait à son tour — on
         * n'obtenait plus qu'un 500 muet, au moment précis où l'on avait le
         * plus besoin de lire ce qui n'allait pas.
         *
         * Les gabarits savent se passer de ces paramètres : ils retombent sur
         * le nom de la plateforme.
         */
        View::composer('*', function ($view) {
            try {
                $view->with('schoolSettings', SchoolSettings::getSettings());
            } catch (\Throwable $e) {
                $view->with('schoolSettings', null);
            }
        });
    }
}
