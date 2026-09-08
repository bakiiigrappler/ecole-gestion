<?php

namespace App\Models\Scopes;

use App\Support\EcoleCourante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Enferme les lectures dans l'établissement courant.
 *
 * Classe nommée plutôt qu'anonyme : c'est ce qui permet de la retirer
 * explicitement, via `withoutGlobalScope(EtablissementScope::class)`, pour les
 * rares traitements qui doivent voir tous les établissements.
 */
class EtablissementScope implements Scope
{
    public function apply(Builder $requete, Model $modele): void
    {
        $ecole = EcoleCourante::id();

        // Vue d'ensemble du super administrateur : aucun filtre.
        if ($ecole === null) {
            return;
        }

        $requete->where($modele->qualifyColumn('school_id'), $ecole);
    }
}
