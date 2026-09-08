<?php

namespace App\Support;

/**
 * Une somme écrite en toutes lettres, comme sur un reçu.
 *
 * Un reçu qui ne porte que des chiffres se rature trop facilement : l'usage
 * veut que le montant soit aussi écrit en lettres, et c'est cette écriture-là
 * qui fait foi en cas de désaccord.
 *
 * Écrit à la main plutôt que confié à `NumberFormatter` : l'extension `intl`
 * n'est pas présente partout — absente du poste de développement, présente
 * dans l'image de production — et le même reçu se serait imprimé
 * différemment selon la machine.
 *
 * Orthographe française appliquée : « quatre-vingts » prend son s seul,
 * « quatre-vingt-un » le perd ; « vingt et un » prend sa conjonction,
 * « quatre-vingt-un » non ; « mille » est invariable, « cent » et « million »
 * s'accordent.
 */
class SommeEnLettres
{
    private const UNITES = [
        0 => 'zéro', 1 => 'un', 2 => 'deux', 3 => 'trois', 4 => 'quatre',
        5 => 'cinq', 6 => 'six', 7 => 'sept', 8 => 'huit', 9 => 'neuf',
        10 => 'dix', 11 => 'onze', 12 => 'douze', 13 => 'treize',
        14 => 'quatorze', 15 => 'quinze', 16 => 'seize',
    ];

    private const DIZAINES = [
        2 => 'vingt', 3 => 'trente', 4 => 'quarante',
        5 => 'cinquante', 6 => 'soixante',
    ];

    /**
     * Le montant en lettres, suivi de sa devise.
     */
    public static function francs(float $montant): string
    {
        return self::convertir((int) round($montant)).' francs CFA';
    }

    public static function convertir(int $nombre): string
    {
        if ($nombre < 0) {
            return 'moins '.self::convertir(-$nombre);
        }

        if ($nombre < 100) {
            return ucfirst(self::souscent($nombre));
        }

        return ucfirst(self::audela($nombre));
    }

    /** De 0 à 99, là où vivent toutes les irrégularités du français. */
    private static function souscent(int $n): string
    {
        if ($n <= 16) {
            return self::UNITES[$n];
        }

        if ($n < 20) {
            return 'dix-'.self::UNITES[$n - 10];
        }

        $dizaine = intdiv($n, 10);
        $unite = $n % 10;

        // 70 et 90 se disent « soixante-dix » et « quatre-vingt-dix » : ils se
        // construisent sur la dizaine inférieure, augmentée de dix à dix-neuf.
        if ($dizaine === 7 || $dizaine === 9) {
            $base = $dizaine === 7 ? 'soixante' : 'quatre-vingt';
            $reste = self::souscent(10 + $unite);

            // « soixante et onze », mais « quatre-vingt-onze ».
            $liaison = ($dizaine === 7 && $unite === 1) ? ' et ' : '-';

            return $base.$liaison.$reste;
        }

        if ($dizaine === 8) {
            // « quatre-vingts » seul prend son s ; suivi d'une unité, il le perd.
            return $unite === 0 ? 'quatre-vingts' : 'quatre-vingt-'.self::UNITES[$unite];
        }

        $mot = self::DIZAINES[$dizaine];

        if ($unite === 0) {
            return $mot;
        }

        // « vingt et un », « trente et un »… jusqu'à « soixante et un ».
        return $unite === 1 ? $mot.' et un' : $mot.'-'.self::UNITES[$unite];
    }

    /** À partir de cent : centaines, milliers, millions, milliards. */
    private static function audela(int $n): string
    {
        foreach ([
            1000000000 => ['milliard', 'milliards'],
            1000000 => ['million', 'millions'],
            1000 => ['mille', 'mille'],
            100 => ['cent', 'cents'],
        ] as $palier => [$singulier, $pluriel]) {
            if ($n < $palier) {
                continue;
            }

            $combien = intdiv($n, $palier);
            $reste = $n % $palier;

            // « mille » et « cent » ne s'annoncent pas quand ils sont seuls :
            // on dit « cent », pas « un cent ».
            $prefixe = ($combien === 1 && $palier <= 1000)
                ? ''
                : self::sansPluriel(self::audela($combien)).' ';

            /*
             * L'accord du pluriel ne vaut que si rien ne suit : « deux cents »
             * mais « deux cent trois ». « Mille » ne s'accorde jamais.
             */
            $mot = ($combien > 1 && $reste === 0) ? $pluriel : $singulier;

            return trim($prefixe.$mot.($reste > 0 ? ' '.self::audela($reste) : ''));
        }

        return self::souscent($n);
    }

    /**
     * « cent » et « quatre-vingt » ne prennent leur s qu'en fin de nombre.
     *
     * Deux cents, mais deux cent mille. Quatre-vingts, mais quatre-vingt
     * mille. Dès qu'un mot les suit, l'accord tombe — et en position de
     * multiplicateur, un mot suit toujours.
     */
    private static function sansPluriel(string $mots): string
    {
        foreach (['quatre-vingts' => 'quatre-vingt', 'cents' => 'cent'] as $pluriel => $singulier) {
            if (str_ends_with($mots, $pluriel)) {
                return substr($mots, 0, -strlen($pluriel)).$singulier;
            }
        }

        return $mots;
    }
}
