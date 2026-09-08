<?php

namespace Database\Seeders;

use App\Support\ComptesEleves;
use Illuminate\Database\Seeder;

/**
 * Ouvre les comptes des élèves du lycée.
 *
 * Décidé avec l'utilisateur : seuls les lycéens ont un compte, et ils se
 * connectent avec leur **matricule**, mot de passe initial identique.
 *
 * Ces comptes naissaient d'une commande lancée à la main : sur une
 * installation neuve, aucun élève ne pouvait donc se connecter, et l'accès
 * rapide de la page de connexion n'avait aucun compte élève à proposer.
 */
class ComptesElevesSeeder extends Seeder
{
    public function run(): void
    {
        $ouverts = ComptesEleves::ouvrirPourLeLycee();

        $this->command->info("Comptes élèves du lycée : {$ouverts} ouvert(s).");

        if ($ouverts === 0) {
            $this->command->warn(
                'Aucun élève de lycée inscrit : rien à ouvrir. Les comptes se créeront à la première inscription au lycée.'
            );
        }
    }
}
