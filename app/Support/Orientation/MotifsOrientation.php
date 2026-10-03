<?php

namespace App\Support\Orientation;

/**
 * Pourquoi un vœu d'orientation est refusé.
 *
 * Un refus sans motif laisse l'élève devant une porte close et une année
 * perdue : il ne sait ni quoi corriger, ni s'il doit refaire un vœu. Le motif
 * est donc obligatoire, choisi parmi les cas que connaît tout conseil de
 * classe, et accompagné de ce que l'élève doit faire maintenant.
 */
class MotifsOrientation
{
    public const CATALOGUE = [
        'moyenne_insuffisante' => [
            'libelle' => 'Résultats insuffisants pour cette filière',
            'consigne' => 'Reprends un vœu vers une filière dont la moyenne d’entrée correspond à tes résultats, ou présente le concours, qui reste ouvert.',
        ],
        'profil_inadapte' => [
            'libelle' => 'Profil scolaire éloigné de la filière demandée',
            'consigne' => 'Tes points forts se situent ailleurs : regarde les filières conseillées sur ton écran d’orientation avant de refaire un vœu.',
        ],
        'etablissement_complet' => [
            'libelle' => 'Établissement demandé complet',
            'consigne' => 'La filière te reste ouverte : refais ton vœu vers un autre établissement qui la propose.',
        ],
        'dossier_incomplet' => [
            'libelle' => 'Dossier incomplet',
            'consigne' => 'Complète ton dossier auprès du secrétariat, puis transmets-le de nouveau.',
        ],
        'redoublement' => [
            'libelle' => 'Redoublement proposé par le conseil de classe',
            'consigne' => 'Le conseil estime qu’une année de consolidation vaut mieux qu’un lycée subi. Rapproche-toi de ton professeur principal.',
        ],
        'autre' => [
            'libelle' => 'Autre motif',
            'consigne' => 'Rapproche-toi du service d’orientation de l’établissement.',
        ],
    ];

    /** @return array<int, string> */
    public static function cles(): array
    {
        return array_keys(self::CATALOGUE);
    }

    public static function libelle(?string $motif): string
    {
        return self::CATALOGUE[$motif]['libelle'] ?? 'Motif non précisé';
    }

    public static function consigne(?string $motif): string
    {
        return self::CATALOGUE[$motif]['consigne'] ?? self::CATALOGUE['autre']['consigne'];
    }

    public static function exigeUnePrecision(?string $motif): bool
    {
        return $motif === 'autre';
    }
}
