<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Des réglages qui vivent dans un fichier, pas en base.
 *
 * La page de connexion doit connaître le nom de l'application, son logo et ses
 * textes — or à ce moment-là personne n'est authentifié, aucun établissement
 * n'est résolu, et les réglages d'école (`school_settings`) sont hors de
 * portée. Un fichier JSON, lui, se lit toujours.
 *
 * Ces réglages sont ceux de la **plateforme**, communs à tous les
 * établissements : ils ne se confondent pas avec les paramètres de chaque
 * école, qui restent en base et portent son `school_id`.
 */
abstract class ReglagesJson
{
    /** Mémorisé par requête : ces valeurs sont lues à chaque vue. */
    private static array $cache = [];

    /** Nom du fichier, sous `storage/app`. */
    abstract protected static function fichier(): string;

    /** Valeurs livrées d'origine, sur lesquelles le fichier se superpose. */
    abstract public static function defauts(): array;

    protected static function chemin(): string
    {
        return storage_path('app/'.static::fichier());
    }

    /**
     * Les réglages effectifs : les défauts, complétés par le fichier.
     */
    public static function tous(): array
    {
        if (isset(self::$cache[static::class])) {
            return self::$cache[static::class];
        }

        $enregistres = [];

        if (File::exists(static::chemin())) {
            $enregistres = json_decode(File::get(static::chemin()), true) ?: [];
        }

        return self::$cache[static::class] = array_merge(static::defauts(), $enregistres);
    }

    public static function get(string $cle, $defaut = null)
    {
        return static::tous()[$cle] ?? $defaut;
    }

    /** Un réglage booléen, quelle que soit la forme sous laquelle il a été posté. */
    public static function actif(string $cle): bool
    {
        return filter_var(static::get($cle), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Enregistre un jeu de réglages, fusionné avec l'existant.
     */
    public static function enregistrer(array $donnees): void
    {
        $fusion = array_merge(static::tous(), $donnees);

        File::ensureDirectoryExists(dirname(static::chemin()));
        File::put(
            static::chemin(),
            json_encode($fusion, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        self::$cache[static::class] = $fusion;
    }

    /** Revenir aux valeurs d'origine : le fichier disparaît. */
    public static function reinitialiser(): void
    {
        File::delete(static::chemin());

        unset(self::$cache[static::class]);
    }

    public static function oublier(): void
    {
        unset(self::$cache[static::class]);
    }
}
