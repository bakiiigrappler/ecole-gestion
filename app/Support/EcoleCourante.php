<?php

namespace App\Support;

use App\Models\School;

/**
 * Quel établissement l'application regarde en ce moment.
 *
 * Pour tout le monde sauf le super administrateur, c'est celui du compte : il
 * n'y a rien à choisir. Le super administrateur, lui, surplombe l'ensemble et
 * peut se placer dans un établissement le temps d'une visite ; tant qu'il n'a
 * rien choisi, il voit tout.
 */
class EcoleCourante
{
    public const CLE_SESSION = 'ecole_courante';

    /** Mémorisé par requête : la résolution touche la session et la base. */
    private static ?int $memorise = null;

    private static bool $resolue = false;

    /**
     * Identifiant de l'établissement courant, ou null si la vue est globale.
     */
    public static function id(): ?int
    {
        if (self::$resolue) {
            return self::$memorise;
        }

        self::$resolue = true;

        $utilisateur = auth()->user();

        if (! $utilisateur) {
            return self::$memorise = null;
        }

        // Un compte rattaché ne voit que son établissement, sans échappatoire.
        if ($utilisateur->role !== 'superadmin') {
            return self::$memorise = $utilisateur->school_id;
        }

        $choisi = session(self::CLE_SESSION);

        // L'établissement choisi a pu être supprimé entre deux visites.
        if ($choisi && ! School::whereKey($choisi)->exists()) {
            session()->forget(self::CLE_SESSION);

            return self::$memorise = null;
        }

        return self::$memorise = $choisi ? (int) $choisi : null;
    }

    public static function modele(): ?School
    {
        $id = self::id();

        return $id ? School::find($id) : null;
    }

    /**
     * Le super administrateur se place dans un établissement, ou en sort.
     */
    public static function choisir(?int $ecoleId): void
    {
        if ($ecoleId) {
            session([self::CLE_SESSION => $ecoleId]);
        } else {
            session()->forget(self::CLE_SESSION);
        }

        self::oublier();
    }

    /** Vue d'ensemble : aucun établissement choisi par un superadmin. */
    public static function estGlobale(): bool
    {
        return self::id() === null;
    }

    /**
     * Force l'etablissement courant, hors de toute session.
     *
     * Reserve aux traitements sans utilisateur connecte — seeders, commandes,
     * taches planifiees — pour que les enregistrements crees soient rattaches
     * a la bonne ecole sans avoir a le repeter partout.
     */
    public static function forcer(?int $ecoleId): void
    {
        self::$memorise = $ecoleId;
        self::$resolue = true;
    }

    public static function oublier(): void
    {
        self::$memorise = null;
        self::$resolue = false;
    }
}
