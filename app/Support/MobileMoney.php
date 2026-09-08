<?php

namespace App\Support;

use App\Models\SchoolSettings;

/**
 * Le paiement marchand par téléphone, tel qu'il se pratique au Gabon.
 *
 * Deux opérateurs, un même geste : le parent compose un code USSD, choisit
 * « paiement marchand », entre le code de l'établissement, le montant, puis son
 * code secret. Il reçoit un SMS portant un identifiant de transaction — c'est
 * cet identifiant qu'il rapporte, et que le secrétariat vérifie avant de
 * valider le versement.
 *
 * La plateforme n'encaisse rien elle-même : elle affiche où payer, recueille la
 * déclaration du parent, et laisse l'établissement confirmer. Le reçu ne porte
 * la mention « réglé » qu'après cette vérification — annoncer un paiement que
 * personne n'a constaté reviendrait à écrire un reçu faux.
 */
class MobileMoney
{
    /**
     * Les opérateurs, avec ce qu'il faut pour composer.
     *
     * `ussd` est celui du service au Gabon ; il ne dépend pas de
     * l'établissement, contrairement au code marchand.
     */
    public const OPERATEURS = [
        'airtel_money' => [
            'libelle' => 'Airtel Money',
            'ussd' => '*150#',
            'champ_code' => 'airtel_money_code',
            'champ_nom' => 'airtel_money_nom',
            'couleur' => 'corail',
        ],
        'moov_money' => [
            'libelle' => 'Moov Money',
            'ussd' => '*155#',
            'champ_code' => 'moov_money_code',
            'champ_nom' => 'moov_money_nom',
            'couleur' => 'ogar',
        ],
    ];

    /**
     * Ceux que l'établissement a renseignés.
     *
     * Un opérateur sans code marchand n'est pas proposé : afficher « payez au
     * … » sans numéro enverrait le parent nulle part.
     *
     * @return array<string, array{libelle: string, ussd: string, code: string, nom: ?string, couleur: string}>
     */
    public static function disponibles(?SchoolSettings $reglages): array
    {
        if (! $reglages || ! $reglages->mobile_money_actif) {
            return [];
        }

        $ouverts = [];

        foreach (self::OPERATEURS as $cle => $operateur) {
            $code = trim((string) $reglages->{$operateur['champ_code']});

            if ($code === '') {
                continue;
            }

            $ouverts[$cle] = [
                'libelle' => $operateur['libelle'],
                'ussd' => $operateur['ussd'],
                'code' => $code,
                'nom' => $reglages->{$operateur['champ_nom']} ?: ($reglages->school_name ?? null),
                'couleur' => $operateur['couleur'],
            ];
        }

        return $ouverts;
    }

    public static function estOuvert(?SchoolSettings $reglages): bool
    {
        return self::disponibles($reglages) !== [];
    }

    public static function libelle(?string $operateur): string
    {
        return self::OPERATEURS[$operateur]['libelle'] ?? 'Mobile money';
    }

    /**
     * La marche à suivre, pas à pas.
     *
     * Écrite ici parce qu'elle est la même partout : seuls le code USSD et le
     * code marchand changent. L'établissement qui a ses propres consignes les
     * saisit dans ses paramètres, et elles prennent alors la place de
     * celles-ci.
     *
     * @return array<int, string>
     */
    public static function etapes(array $operateur, float $montant = 0): array
    {
        $somme = $montant > 0
            ? number_format($montant, 0, ',', ' ').' FCFA'
            : 'le montant à régler';

        return [
            'Composez '.$operateur['ussd'].' depuis le téléphone dont le numéro est enregistré chez l’opérateur.',
            'Choisissez « Paiement marchand » dans le menu.',
            'Entrez le code marchand '.$operateur['code'].' — l’écran doit afficher '
                .($operateur['nom'] ?: 'le nom de l’établissement').'. S’il affiche autre chose, n’allez pas plus loin.',
            'Entrez '.$somme.', puis validez avec votre code secret.',
            'Conservez le SMS de confirmation : son identifiant de transaction vous sera demandé ci-dessous.',
        ];
    }

    /**
     * Les consignes à afficher : celles de l'établissement, sinon les nôtres.
     *
     * @return array{propres: bool, lignes: array<int, string>}
     */
    public static function consignes(?SchoolSettings $reglages, array $operateur, float $montant = 0): array
    {
        $propres = trim((string) ($reglages->mobile_money_consignes ?? ''));

        if ($propres !== '') {
            $lignes = preg_split('/\r\n|\r|\n/', $propres);
            $lignes = array_values(array_filter(array_map('trim', $lignes), fn ($l) => $l !== ''));

            return ['propres' => true, 'lignes' => $lignes];
        }

        return ['propres' => false, 'lignes' => self::etapes($operateur, $montant)];
    }
}
