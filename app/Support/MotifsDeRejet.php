<?php

namespace App\Support;

/**
 * Pourquoi un versement déclaré est refusé.
 *
 * Un refus sans motif laisse le parent devant un écran qui dit « annulé » et
 * rien d'autre : il ne sait ni s'il a mal recopié un chiffre, ni si son argent
 * est perdu, ni ce qu'il doit faire. Il rappelle le secrétariat, qui doit
 * rouvrir le dossier pour se souvenir. Le motif est donc obligatoire, et il
 * accompagne le versement jusque sur l'écran du parent.
 *
 * Les motifs courants sont proposés d'avance — ce sont ceux qui reviennent au
 * guichet — et chacun porte la conduite à tenir, écrite pour le parent et non
 * pour l'administration. Le motif libre reste possible : la vie scolaire
 * déborde toujours d'une liste.
 */
class MotifsDeRejet
{
    /**
     * `consigne` est ce que le parent lira : ce qu'il doit faire maintenant.
     */
    public const CATALOGUE = [
        'introuvable' => [
            'libelle' => 'Aucune opération retrouvée chez l’opérateur',
            'consigne' => 'Vérifiez l’identifiant figurant sur le SMS de confirmation, puis déclarez de nouveau le versement. Si le SMS n’existe pas, l’opération n’a pas abouti et rien n’a été débité.',
        ],
        'montant' => [
            'libelle' => 'Le montant reçu ne correspond pas au montant déclaré',
            'consigne' => 'Déclarez de nouveau le versement avec le montant exact figurant sur le SMS de l’opérateur.',
        ],
        'identifiant' => [
            'libelle' => 'Identifiant de transaction incorrect ou illisible',
            'consigne' => 'Recopiez l’identifiant tel qu’il figure sur le SMS, sans espace ni caractère ajouté, puis déclarez de nouveau.',
        ],
        'doublon' => [
            'libelle' => 'Versement déjà enregistré',
            'consigne' => 'Ce versement figure déjà à votre historique : il n’y a rien à refaire, et rien de plus à payer de ce fait.',
        ],
        'autre_eleve' => [
            'libelle' => 'Versement porté au dossier d’un autre élève',
            'consigne' => 'Le versement a été rattaché au bon dossier par le secrétariat. Aucun nouveau paiement n’est nécessaire.',
        ],
        'autre' => [
            'libelle' => 'Autre motif',
            'consigne' => 'Rapprochez-vous du secrétariat pour régulariser.',
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

    /**
     * Le motif exige-t-il une précision écrite ?
     *
     * « Autre motif » ne dit rien à lui seul : le refuser sans un mot laisserait
     * le parent aussi démuni qu'avant.
     */
    public static function exigeUnePrecision(?string $motif): bool
    {
        return $motif === 'autre';
    }
}
