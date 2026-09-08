<?php

namespace App\Support;

/**
 * L'identité de la plateforme : son nom, son logo, ses textes.
 *
 * Le nom « Egesco » et les phrases de la page de connexion étaient écrits en
 * dur dans les gabarits. Une plateforme qui héberge plusieurs établissements
 * sous une marque donnée doit pouvoir la changer sans toucher au code — c'est
 * le rôle de cette classe, et de l'écran de personnalisation qui l'alimente.
 *
 * À ne pas confondre avec `SchoolSettings`, qui porte l'identité de **chaque
 * école** — son nom, son logo, sa devise. Ici, c'est l'application elle-même.
 */
class Marque extends ReglagesJson
{
    protected static function fichier(): string
    {
        return 'marque.json';
    }

    public static function defauts(): array
    {
        return [
            'app_nom' => 'Egesco',
            'app_slogan' => 'Gestion scolaire',

            // Chemin relatif dans `public`, ex. « marque/logo.png ».
            'logo' => null,

            'login_titre' => 'Connexion',
            'login_sous_titre' => 'Adresse e-mail, ou matricule pour les élèves du lycée.',
            'login_acces_rapide' => true,

            'pied_de_page' => 'Travail - Rigueur - Discipline',
        ];
    }

    /**
     * L'URL du logo : celui qui a été téléversé, sinon l'emblème dessiné.
     *
     * Renvoie `null` quand aucun logo n'a été posé : les gabarits retombent
     * alors sur `<x-logo :application="true" />`, qui n'a besoin d'aucun
     * fichier.
     */
    public static function logoUrl(): ?string
    {
        $logo = static::get('logo');

        return $logo ? asset($logo) : null;
    }

    public static function nom(): string
    {
        return static::get('app_nom') ?: 'Egesco';
    }
}
