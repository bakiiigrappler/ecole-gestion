<?php

namespace App\Support\Orientation;

/**
 * Ce qui s'ouvre après le baccalauréat, au Gabon.
 *
 * La série du bac commande l'entrée dans le supérieur : un bachelier A2 ne
 * s'inscrit pas en médecine, un bachelier D y est attendu. Chaque série porte
 * donc ses filières, les établissements qui les enseignent, et les métiers
 * auxquels elles mènent — c'est ce que l'élève vient chercher, et ce qu'un
 * conseil oral de fin d'année n'a jamais le temps de dérouler.
 *
 * Les séries sont reconnues à partir du code de la série suivie dans
 * l'établissement (TERM-C, TERM-G2…) : l'élève n'a rien à ressaisir.
 */
class VoiesApresTerminale
{
    public const SERIES = [
        'A1' => [
            'libelle' => 'Série A1 — Lettres et langues anciennes',
            'filieres' => ['Lettres modernes', 'Langues étrangères appliquées', 'Traduction et interprétariat', 'Droit'],
            'metiers' => 'Enseignement, traduction, diplomatie, journalisme, édition',
            'couleur' => 'violet',
        ],
        'A2' => [
            'libelle' => 'Série A2 — Lettres et langues vivantes',
            'filieres' => ['Sociologie', 'Psychologie', 'Histoire', 'Géographie', 'Anthropologie', 'Tourisme', 'Droit'],
            'metiers' => 'Travail social, tourisme, ressources humaines, organisations internationales',
            'couleur' => 'violet',
        ],
        'B' => [
            'libelle' => 'Série B — Sciences économiques et sociales',
            'filieres' => ['Économie', 'Gestion', 'Comptabilité', 'Marketing', 'Banque et finance', 'Commerce international'],
            'metiers' => 'Comptable, banquier, contrôleur de gestion, commercial, entrepreneur',
            'couleur' => 'soleil',
        ],
        'C' => [
            'libelle' => 'Série C — Mathématiques et sciences physiques',
            'filieres' => ['Mathématiques', 'Physique', 'Informatique', 'Génie logiciel', 'Ingénierie', 'Architecture'],
            'metiers' => 'Ingénieur, développeur, chercheur, enseignant scientifique',
            'couleur' => 'ogar',
        ],
        'D' => [
            'libelle' => 'Série D — Sciences de la nature',
            'filieres' => ['Médecine', 'Pharmacie', 'Maïeutique', 'Biologie', 'Géologie', 'Agronomie', 'Environnement'],
            'metiers' => 'Médecin, pharmacien, sage-femme, ingénieur agronome ou environnement',
            'couleur' => 'emerald',
        ],
        'E' => [
            'libelle' => 'Série E — Techniques industrielles',
            'filieres' => ['Génie civil', 'Génie mécanique', 'Génie électrique', 'Maintenance industrielle'],
            'metiers' => 'Technicien supérieur, chef de chantier, conducteur de travaux',
            'couleur' => 'soleil',
        ],
        'F' => [
            'libelle' => 'Séries F — Industrielles spécialisées',
            'filieres' => ['Génie civil', 'Électrotechnique', 'Électronique', 'Mécanique générale', 'Ingénierie pétrolière'],
            'metiers' => 'Technicien de maintenance, électrotechnicien, secteur pétrolier et minier',
            'couleur' => 'soleil',
        ],
        'G' => [
            'libelle' => 'Séries G — Techniques de gestion',
            'filieres' => ['Comptabilité', 'Gestion des ressources humaines', 'Commerce international', 'Banque', 'Assurance'],
            'metiers' => 'Comptable, gestionnaire, assistant de direction, chargé de clientèle',
            'couleur' => 'violet',
        ],
    ];

    /**
     * La série d'un élève, lue sur le code porté par sa classe.
     *
     * `TERM-G2` devient `G`, `TERM-F3` devient `F` : les spécialités d'une même
     * famille ouvrent les mêmes portes dans le supérieur.
     */
    public static function depuisLeCode(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        $fin = strtoupper(preg_replace('/^.*-/', '', $code));

        if ($fin === '' || $fin === 'S') {
            // « Scientifique » sans précision : la série C est le cas général.
            return $fin === 'S' ? 'C' : null;
        }

        foreach (['A1', 'A2'] as $serie) {
            if ($fin === $serie) {
                return $serie;
            }
        }

        $premiere = substr($fin, 0, 1);

        return isset(self::SERIES[$premiere]) ? $premiere : null;
    }

    public static function libelle(?string $serie): string
    {
        return self::SERIES[$serie]['libelle'] ?? 'Série non précisée';
    }

    /** @return array<int, string> */
    public static function filieres(?string $serie): array
    {
        return self::SERIES[$serie]['filieres'] ?? [];
    }

    public static function metiers(?string $serie): string
    {
        return self::SERIES[$serie]['metiers'] ?? '';
    }

    public static function couleur(?string $serie): string
    {
        return self::SERIES[$serie]['couleur'] ?? 'slate';
    }

    /**
     * Ce que la moyenne du bac ouvre, honnêtement.
     *
     * Les filières sélectives — médecine, écoles d'ingénieurs — recrutent sur
     * dossier ou concours. Le dire avant le vœu évite la déception de mars.
     */
    public static function mentionAttendue(float $moyenne): string
    {
        return match (true) {
            $moyenne >= 14 => 'Mention bien ou très bien : les filières sélectives vous sont ouvertes, concours compris.',
            $moyenne >= 12 => 'Mention assez bien : les filières sur dossier vous sont accessibles ; préparez les concours des plus demandées.',
            $moyenne >= 10 => 'Baccalauréat obtenu : l’université publique vous accueille ; les filières sélectives demanderont un concours.',
            default => 'Moyenne en deçà de 10 : le baccalauréat n’est pas acquis. Ces vœux ne vaudront qu’une fois l’examen réussi.',
        };
    }
}
