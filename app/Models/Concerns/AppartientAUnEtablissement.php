<?php

namespace App\Models\Concerns;

use App\Models\School;
use App\Models\Scopes\EtablissementScope;
use App\Support\EcoleCourante;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rattache un modèle à son établissement, et l'y enferme.
 *
 * Deux garanties, sans que l'appelant ait rien à écrire :
 *   — toute lecture est filtrée sur l'établissement courant ;
 *   — toute création reçoit l'établissement courant.
 *
 * Le super administrateur qui ne s'est placé dans aucun établissement voit
 * l'ensemble : c'est le seul cas où le filtre ne s'applique pas.
 */
trait AppartientAUnEtablissement
{
    public static function bootAppartientAUnEtablissement(): void
    {
        static::addGlobalScope(new EtablissementScope());

        static::creating(function ($modele) {
            if ($modele->school_id === null) {
                $modele->school_id = EcoleCourante::id();
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Sortir du filtre, en connaissance de cause : consolidation d'un rapport
     * inter-établissements, tâche planifiée, migration de données.
     */
    public function scopeTousEtablissements($query)
    {
        return $query->withoutGlobalScope(EtablissementScope::class);
    }
}
