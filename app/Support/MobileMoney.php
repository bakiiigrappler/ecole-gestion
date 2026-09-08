<?php

namespace App\Support;

use App\Models\SchoolSettings;

/**
 * Le paiement par téléphone, tel qu'il se pratique au Gabon.
 *
 * Deux opérateurs, et deux façons de recevoir. L'école qui a un compte
 * marchand publie son code : le parent choisit « paiement marchand », et le
 * nom de l'établissement s'affiche avant qu'il valide. Celle qui n'en a pas
 * encaisse sur un numéro ordinaire, par transfert. Beaucoup d'écoles sont dans
 * le second cas — décrire uniquement le code marchand laissait le portail muet
 * pour elles.
 *
 * Dans les deux cas, le parent reçoit un SMS portant un identifiant de
 * transaction : c'est lui qu'il rapporte, et que le secrétariat confronte au
 * relevé de l'opérateur. La plateforme n'encaisse rien elle-même — elle
 * affiche où payer, recueille la déclaration, et laisse l'établissement
 * confirmer. Le reçu ne porte « réglé » qu'après cette vérification ;
 * l'annoncer plus tôt reviendrait à écrire un reçu faux.
 */
class MobileMoney
{
    /**
     * Les opérateurs, avec ce qu'il faut pour composer.
     *
     * `ussd` est celui du service au Gabon ; il ne dépend pas de
     * l'établissement, contrairement au code marchand et au numéro.
     */
    public const OPERATEURS = [
        'airtel_money' => [
            'libelle' => 'Airtel Money',
            'ussd' => '*150#',
            'champ_actif' => 'airtel_money_actif',
            'champ_code' => 'airtel_money_code',
            'champ_numero' => 'airtel_money_numero',
            'champ_nom' => 'airtel_money_nom',
            'couleur' => 'corail',
        ],
        'moov_money' => [
            'libelle' => 'Moov Money',
            'ussd' => '*155#',
            'champ_actif' => 'moov_money_actif',
            'champ_code' => 'moov_money_code',
            'champ_numero' => 'moov_money_numero',
            'champ_nom' => 'moov_money_nom',
            'couleur' => 'ogar',
        ],
    ];

    /**
     * Ceux que l'établissement a ouverts et renseignés.
     *
     * Un opérateur décoché, ou sans code marchand ni numéro, n'est pas
     * proposé : afficher « payez au … » sans coordonnées enverrait le parent
     * nulle part.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function disponibles(?SchoolSettings $reglages): array
    {
        if (! $reglages || ! $reglages->mobile_money_actif) {
            return [];
        }

        $ouverts = [];

        foreach (self::OPERATEURS as $cle => $operateur) {
            if (! $reglages->{$operateur['champ_actif']}) {
                continue;
            }

            $code = trim((string) $reglages->{$operateur['champ_code']});
            $numero = trim((string) $reglages->{$operateur['champ_numero']});

            if ($code === '' && $numero === '') {
                continue;
            }

            $ouverts[$cle] = [
                'libelle' => $operateur['libelle'],
                'ussd' => $operateur['ussd'],
                'code' => $code ?: null,
                'numero' => $numero ?: null,
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
     * Elle diffère selon ce que l'école a publié : paiement marchand quand
     * elle a un code, transfert vers un numéro sinon. Le reste est identique
     * d'un opérateur à l'autre — seul le code USSD change.
     *
     * @return array<int, string>
     */
    public static function etapes(array $operateur, float $montant = 0): array
    {
        $somme = $montant > 0
            ? number_format($montant, 0, ',', ' ').' FCFA'
            : 'le montant à régler';

        $etapes = [
            'Composez '.$operateur['ussd'].' depuis le téléphone dont le numéro est enregistré chez l’opérateur.',
        ];

        if ($operateur['code']) {
            $etapes[] = 'Choisissez « Paiement marchand » dans le menu.';
            $etapes[] = 'Entrez le code marchand '.$operateur['code'].' — l’écran doit afficher '
                .($operateur['nom'] ?: 'le nom de l’établissement')
                .'. S’il affiche autre chose, n’allez pas plus loin.';
        } else {
            $etapes[] = 'Choisissez « Transfert d’argent » dans le menu.';
            $etapes[] = 'Entrez le numéro de l’établissement '.$operateur['numero']
                .' — l’écran doit afficher '.($operateur['nom'] ?: 'le nom de l’établissement')
                .'. S’il affiche autre chose, n’allez pas plus loin.';
        }

        $etapes[] = 'Entrez '.$somme.', puis validez avec votre code secret.';
        $etapes[] = 'Conservez le SMS de confirmation : son identifiant de transaction vous sera demandé ci-dessous.';

        return $etapes;
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
