<?php

namespace App\Support;

/**
 * Le catalogue des rôles, en un seul endroit.
 *
 * Les rôles étaient éparpillés : une liste dans le modèle `User`, une autre
 * dans le menu, une troisième dans la validation de chaque formulaire, une
 * quatrième dans la contrainte de la base. Ajouter un rôle demandait de les
 * retrouver toutes, et l'une d'elles finissait toujours par être oubliée.
 *
 * Trois titres de direction s'ajoutent ici aux rôles d'origine : le
 * **directeur** dirige une école primaire ou préprimaire, le **proviseur** un
 * collège ou un lycée, le **censeur** le seconde. Les deux premiers sont chefs
 * d'établissement et en ont tous les pouvoirs ; le censeur suit la scolarité
 * et la discipline, sans toucher à la caisse.
 */
class Roles
{
    /**
     * Tous les rôles, avec ce qu'ils recouvrent.
     *
     * `personnel` : le rôle se gère depuis « Comptes utilisateurs ». Les
     * enseignants, les parents et les élèves n'y figurent pas — leur compte
     * naît de leur fiche, dans leur propre module, avec ce qu'elle porte de
     * classes, d'enfants ou d'inscription.
     */
    public const CATALOGUE = [
        'superadmin' => [
            'acces' => [
                'Tous les établissements',
                'Personnalisation de la plateforme',
                'Maintenance et corbeille',
            ],
            'libelle' => 'Super administrateur',
            'detail' => 'Surplombe tous les établissements',
            'personnel' => true,
            'direction' => true,
        ],
        'admin' => [
            'acces' => [
                'Élèves, classes et personnel',
                'Notes, bulletins et emplois du temps',
                'Frais et encaissements',
                'Comptes du personnel',
            ],
            'libelle' => 'Administrateur',
            'detail' => 'Gère son établissement de bout en bout',
            'personnel' => true,
            'direction' => true,
        ],
        'directeur' => [
            'acces' => [
                'Toute la scolarité de l’école',
                'Frais et encaissements',
                'Comptes du personnel',
            ],
            'libelle' => 'Directeur',
            'detail' => 'Chef d’une école primaire ou préprimaire',
            'personnel' => true,
            'direction' => true,
        ],
        'proviseur' => [
            'acces' => [
                'Toute la scolarité de l’établissement',
                'Frais et encaissements',
                'Comptes du personnel',
            ],
            'libelle' => 'Proviseur',
            'detail' => 'Chef d’un collège ou d’un lycée',
            'personnel' => true,
            'direction' => true,
        ],
        'censeur' => [
            'acces' => [
                'Scolarité, notes et bulletins',
                'Assiduité et discipline',
                'Aucun accès aux finances',
            ],
            'libelle' => 'Censeur',
            'detail' => 'Scolarité, évaluations et discipline — hors finances',
            'personnel' => true,
            'direction' => true,
        ],
        'secretary' => [
            'acces' => [
                'Inscriptions et dossiers élèves',
                'Frais et encaissements',
                'Reçus et attestations',
            ],
            'libelle' => 'Secrétariat',
            'detail' => 'Inscriptions, élèves et encaissements',
            'personnel' => true,
            'direction' => false,
        ],
        'teacher' => [
            'acces' => [
                'Ses classes et ses élèves',
                'Ses notes et ses appels',
            ],
            'libelle' => 'Enseignant',
            'detail' => 'Ses classes, ses notes, ses appels',
            'personnel' => false,
            'direction' => false,
        ],
        'parent' => [
            'acces' => [
                'Le dossier de ses enfants',
                'Les frais de scolarité à régler',
            ],
            'libelle' => 'Parent d’élève',
            'detail' => 'Le dossier de ses enfants',
            'personnel' => false,
            'direction' => false,
        ],
        'student' => [
            'acces' => [
                'Ses notes et son bulletin',
                'Ses absences et son emploi du temps',
            ],
            'libelle' => 'Élève',
            'detail' => 'Ses notes, ses absences, sa fiche',
            'personnel' => false,
            'direction' => false,
        ],
    ];

    /** Ceux qui commandent l'établissement, ou la plateforme. */
    public static function direction(): array
    {
        return array_keys(array_filter(self::CATALOGUE, fn ($r) => $r['direction']));
    }

    /** Ceux qui se créent depuis « Comptes utilisateurs ». */
    public static function personnel(): array
    {
        return array_keys(array_filter(self::CATALOGUE, fn ($r) => $r['personnel']));
    }

    /**
     * Ceux qu'un compte donné a le droit d'attribuer.
     *
     * Le rôle de super administrateur ne se transmet qu'entre super
     * administrateurs : le proposer à un chef d'établissement ne mènerait
     * qu'à un refus au moment d'enregistrer.
     */
    public static function attribuablesPar(?string $role): array
    {
        $roles = self::personnel();

        if ($role !== 'superadmin') {
            $roles = array_values(array_diff($roles, ['superadmin']));
        }

        return $roles;
    }

    public static function libelle(?string $role): string
    {
        return self::CATALOGUE[$role]['libelle'] ?? ucfirst((string) $role);
    }

    public static function detail(?string $role): string
    {
        return self::CATALOGUE[$role]['detail'] ?? '';
    }

    /**
     * Ce que le rôle ouvre, en clair.
     *
     * Nommer un rôle ne dit pas ce qu'il permet : « censeur » ne se lit pas
     * tout seul. La liste s'affiche là où l'on choisit un rôle et là où on le
     * consulte, pour que l'écran réponde sans qu'on aille chercher ailleurs.
     *
     * @return array<int, string>
     */
    public static function acces(?string $role): array
    {
        return self::CATALOGUE[$role]['acces'] ?? [];
    }

    public static function estDeDirection(?string $role): bool
    {
        return in_array($role, self::direction(), true);
    }

    /**
     * La caisse de l'établissement lui est-elle ouverte ?
     *
     * Le censeur seconde le chef d'établissement sur la scolarité et la
     * discipline ; les frais et les encaissements ne relèvent pas de lui.
     */
    public static function voitLesFinances(?string $role): bool
    {
        return $role !== 'censeur'
            && in_array($role, ['superadmin', 'admin', 'directeur', 'proviseur', 'secretary'], true);
    }
}
