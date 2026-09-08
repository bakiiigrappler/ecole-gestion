<?php

namespace App\Support;

/**
 * Le comportement de la plateforme, commun à tous les établissements.
 *
 * Chaque école règle sa pédagogie dans `school_settings` ; ce qui relève de
 * l'application elle-même — la taille des listes, la durée de conservation en
 * corbeille, l'ouverture des inscriptions en ligne — se règle ici.
 *
 * Chaque réglage de cette classe est réellement lu quelque part. Un
 * interrupteur qui ne commanderait rien serait pire que son absence : il
 * ferait croire à un pouvoir qu'on n'a pas.
 */
class ParametresPlateforme extends ReglagesJson
{
    protected static function fichier(): string
    {
        return 'plateforme.json';
    }

    public static function defauts(): array
    {
        return [
            // Lu par les listes paginées, quand l'URL ne dit rien.
            'pagination_defaut' => 10,

            // Lu par la corbeille : au-delà, une suppression est proposée au vidage.
            'corbeille_jours' => 30,

            // Lu par le portail public d'inscription en ligne.
            'inscriptions_en_ligne' => true,

            // Lu par la page de connexion.
            'connexion_ouverte' => true,
            'message_connexion' => null,
        ];
    }

    /** Les tailles de page proposées, la valeur par défaut comprise. */
    public const PAGINATIONS = [10, 25, 50, 100];

    /**
     * La taille de page à appliquer : celle demandée si elle est permise,
     * sinon celle réglée pour la plateforme.
     */
    public static function pagination($demandee = null): int
    {
        $demandee = (int) $demandee;

        if (in_array($demandee, self::PAGINATIONS, true)) {
            return $demandee;
        }

        $defaut = (int) static::get('pagination_defaut', 10);

        return in_array($defaut, self::PAGINATIONS, true) ? $defaut : 10;
    }

    public static function joursDeCorbeille(): int
    {
        return max(1, (int) static::get('corbeille_jours', 30));
    }
}
