<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Une couleur par matière, la même partout.
 *
 * La composition de l'emploi du temps colorait déjà ses matières pour rendre
 * une semaine lisible d'un coup d'œil. La grille de l'enseignant, celle de
 * l'élève et celle du parent doivent teinter les mêmes matières de la même
 * façon : sans quoi la même semaine change de couleurs selon qui la regarde.
 *
 * La teinte se déduit de l'identifiant de la matière, pas de son rang dans une
 * liste : une matière garde sa couleur même quand la sélection change.
 */
class CouleursDesMatieres
{
    /** Dix teintes franches, distinguables les unes des autres. */
    public const NUANCIER = [
        ['fond' => '#e0f2fe', 'bord' => '#7dd3fc', 'encre' => '#075985', 'vif' => '#0ea5e9'],
        ['fond' => '#dcfce7', 'bord' => '#86efac', 'encre' => '#166534', 'vif' => '#22c55e'],
        ['fond' => '#fef3c7', 'bord' => '#fcd34d', 'encre' => '#92400e', 'vif' => '#f59e0b'],
        ['fond' => '#ede9fe', 'bord' => '#c4b5fd', 'encre' => '#5b21b6', 'vif' => '#8b5cf6'],
        ['fond' => '#ffe4e6', 'bord' => '#fda4af', 'encre' => '#9f1239', 'vif' => '#f43f5e'],
        ['fond' => '#cffafe', 'bord' => '#67e8f9', 'encre' => '#155e75', 'vif' => '#06b6d4'],
        ['fond' => '#fae8ff', 'bord' => '#f0abfc', 'encre' => '#86198f', 'vif' => '#d946ef'],
        ['fond' => '#ffedd5', 'bord' => '#fdba74', 'encre' => '#9a3412', 'vif' => '#f97316'],
        ['fond' => '#e0e7ff', 'bord' => '#a5b4fc', 'encre' => '#3730a3', 'vif' => '#6366f1'],
        ['fond' => '#d1fae5', 'bord' => '#6ee7b7', 'encre' => '#065f46', 'vif' => '#10b981'],
    ];

    /** Teinte neutre : une case sans matière, ou une matière inconnue. */
    public const NEUTRE = ['fond' => '#f4f5f7', 'bord' => '#d8dce2', 'encre' => '#4b5563', 'vif' => '#9ca3af'];

    /**
     * La teinte d'une matière.
     */
    public static function pour(?int $matiereId): array
    {
        if (! $matiereId) {
            return self::NEUTRE;
        }

        return self::NUANCIER[$matiereId % count(self::NUANCIER)];
    }

    /**
     * Les teintes d'un ensemble de matières, indexées par identifiant.
     *
     * @param  iterable<int>  $matiereIds
     */
    public static function table(iterable $matiereIds): array
    {
        return Collection::make($matiereIds)
            ->filter()
            ->unique()
            ->mapWithKeys(fn ($id) => [(int) $id => self::pour((int) $id)])
            ->all();
    }
}
