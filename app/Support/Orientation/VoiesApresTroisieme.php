<?php

namespace App\Support\Orientation;

/**
 * Ce qui s'ouvre après la 3ème, au Gabon.
 *
 * Trois voies, et le redoublement. La voie générale mène aux séries A, B, C et
 * D ; la voie technique aux séries industrielles (E, F) et de gestion (G) ; la
 * voie professionnelle au CAP, c'est-à-dire à un métier. Chaque filière indique
 * la moyenne qui ouvre l'inscription directe — en deçà, le concours reste
 * ouvert, et c'est une précision qui compte : un élève à 9,5 n'est pas écarté,
 * il passe par le concours.
 *
 * Les seuils et les débouchés sont écrits ici, en clair, plutôt que dans une
 * table : ils relèvent de la réglementation et non des données d'un
 * établissement. Un chef d'établissement ne les modifie pas depuis un écran.
 */
class VoiesApresTroisieme
{
    public const VOIES = [
        'generale' => [
            'libelle' => '2nde Générale',
            'detail' => 'Vers les séries A, B, C et D, puis l’université',
            'couleur' => 'ogar',
        ],
        'technique_industrielle' => [
            'libelle' => '2nde Technique Industrielle',
            'detail' => 'Vers les séries E et F — génie civil, mécanique, électrotechnique',
            'couleur' => 'soleil',
        ],
        'technique_gestion' => [
            'libelle' => '2nde Technique Administrative et de Gestion',
            'detail' => 'Vers la série G — comptabilité, secrétariat, commerce',
            'couleur' => 'violet',
        ],
        'professionnelle' => [
            'libelle' => 'Enseignement Professionnel (CAP)',
            'detail' => 'Un métier qualifié, et l’entrée rapide dans la vie active',
            'couleur' => 'emerald',
        ],
    ];

    /**
     * Les filières de chaque voie, avec la moyenne d'inscription directe.
     *
     * `moyenne_min` à 0 : la filière n'exige aucune moyenne — elle se choisit
     * sur la motivation et le concours.
     */
    public const FILIERES = [
        'generale' => [
            ['nom' => 'Série C — Mathématiques et Sciences physiques', 'moyenne_min' => 12, 'profils' => ['scientifique'],
             'debouches' => 'Ingénierie, informatique, architecture, mathématiques'],
            ['nom' => 'Série D — Sciences de la nature', 'moyenne_min' => 11, 'profils' => ['scientifique'],
             'debouches' => 'Médecine, pharmacie, biologie, agronomie, environnement'],
            ['nom' => 'Série A1 — Lettres et langues anciennes', 'moyenne_min' => 10, 'profils' => ['litteraire'],
             'debouches' => 'Lettres, traduction, enseignement, diplomatie'],
            ['nom' => 'Série A2 — Lettres et langues vivantes', 'moyenne_min' => 10, 'profils' => ['litteraire'],
             'debouches' => 'Droit, sciences humaines, journalisme, tourisme'],
            ['nom' => 'Série B — Sciences économiques et sociales', 'moyenne_min' => 11, 'profils' => ['equilibre', 'litteraire'],
             'debouches' => 'Économie, gestion, banque, commerce'],
        ],
        'technique_industrielle' => [
            ['nom' => 'Génie civil', 'moyenne_min' => 12, 'debouches' => 'Bâtiment, travaux publics, conduite de chantier'],
            ['nom' => 'Génie mécanique', 'moyenne_min' => 12, 'debouches' => 'Maintenance industrielle, construction mécanique'],
            ['nom' => 'Électrotechnique', 'moyenne_min' => 12, 'debouches' => 'Électricité industrielle, réseaux, énergie'],
            ['nom' => 'Génie industriel', 'moyenne_min' => 13, 'debouches' => 'Production, qualité, méthodes'],
        ],
        'technique_gestion' => [
            ['nom' => 'Comptabilité', 'moyenne_min' => 11, 'debouches' => 'Comptabilité, audit, finance'],
            ['nom' => 'Secrétariat', 'moyenne_min' => 10, 'debouches' => 'Secrétariat de direction, assistanat'],
            ['nom' => 'Commerce', 'moyenne_min' => 10, 'debouches' => 'Vente, distribution, négoce'],
            ['nom' => 'Action commerciale', 'moyenne_min' => 11, 'debouches' => 'Marketing, force de vente'],
        ],
        'professionnelle' => [
            ['nom' => 'Électricité bâtiment', 'moyenne_min' => 10, 'debouches' => 'Installation électrique, dépannage'],
            ['nom' => 'Mécanique automobile', 'moyenne_min' => 10, 'debouches' => 'Garage, entretien, diagnostic'],
            ['nom' => 'Menuiserie', 'moyenne_min' => 0, 'debouches' => 'Ameublement, agencement, charpente'],
            ['nom' => 'Couture et mode', 'moyenne_min' => 0, 'debouches' => 'Confection, stylisme, retouche'],
            ['nom' => 'Hôtellerie-restauration', 'moyenne_min' => 11, 'debouches' => 'Cuisine, salle, hébergement'],
            ['nom' => 'Coiffure et esthétique', 'moyenne_min' => 0, 'debouches' => 'Salon, soins, cosmétique'],
        ],
    ];

    /** @return array<int, array<string, mixed>> */
    public static function filieres(?string $voie): array
    {
        return self::FILIERES[$voie] ?? [];
    }

    public static function filiere(?string $voie, ?string $nom): ?array
    {
        foreach (self::filieres($voie) as $filiere) {
            if ($filiere['nom'] === $nom) {
                return $filiere;
            }
        }

        return null;
    }

    public static function libelle(?string $voie): string
    {
        return self::VOIES[$voie]['libelle'] ?? 'Voie non précisée';
    }

    public static function detail(?string $voie): string
    {
        return self::VOIES[$voie]['detail'] ?? '';
    }

    public static function couleur(?string $voie): string
    {
        return self::VOIES[$voie]['couleur'] ?? 'slate';
    }

    /**
     * Les types d'établissement qui accueillent cette voie.
     *
     * Le CAP ne se prépare pas dans un lycée général, mais il se prépare dans
     * un lycée technique autant que dans un centre de formation : ne proposer
     * que les centres laissait l'écran vide là où dix lycées techniques
     * existent.
     *
     * @return array<int, string>
     */
    public static function typesEtablissement(?string $voie): array
    {
        return $voie === 'professionnelle'
            ? ['centre_professionnel', 'lycee']
            : ['lycee'];
    }

    /**
     * Les établissements d'une voie se reconnaissent aussi à leurs filières :
     * un lycée qui n'annonce que le général ne prépare pas un CAP.
     */
    public static function motsDesFilieres(?string $voie): array
    {
        return match ($voie) {
            'professionnelle' => ['techn', 'profession'],
            'technique_industrielle' => ['techn', 'industr', 'génie', 'genie'],
            'technique_gestion' => ['techn', 'gestion', 'commerc', 'compta'],
            default => [],
        };
    }
}
