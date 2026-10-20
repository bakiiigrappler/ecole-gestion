<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Un compte par rôle, pour parcourir l'application sous chaque profil.
 *
 * La liste vit dans config/demo.php : la page de connexion s'appuie sur la même
 * source pour son accès rapide, ce qui évite que les deux divergent.
 */
class ComptesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $comptes = config('demo.comptes', []);

        if (empty($comptes)) {
            $this->command->warn('Aucun compte déclaré dans config/demo.php.');
            return;
        }

        /*
         * `User` ne porte pas le trait qui rattache un modele a son
         * etablissement — le scope interrogerait l'utilisateur connecte, dont
         * la resolution passe par le modele. Le rattachement se fait donc ici,
         * a la main : sans lui, l'administrateur de demonstration n'appartenait
         * a aucune ecole, et voyait donc les donnees de toutes.
         *
         * Le super administrateur reste hors etablissement : il les surplombe.
         */
        /*
         * Un repli sur le premier etablissement quand aucun n'est force.
         *
         * Ce seeder tourne d'ordinaire dans la chaine, ou l'etablissement
         * courant est pose. Lance seul — pour ajouter un role, par exemple — il
         * n'en avait aucun, et `updateOrCreate` remettait alors `school_id` a
         * nul sur des comptes deja rattaches : l'administrateur de
         * demonstration se retrouvait a voir les donnees de toutes les ecoles.
         * Un seeder ne doit pas pouvoir elargir des droits parce qu'on l'a
         * appele hors de son contexte habituel.
         */
        $ecole = \App\Support\EcoleCourante::id()
            ?? \App\Models\School::withoutGlobalScopes()->orderBy('id')->value('id');

        foreach ($comptes as $compte) {
            User::updateOrCreate(
                ['email' => $compte['email']],
                [
                    'name' => $compte['nom'],
                    'password' => Hash::make($compte['mot_de_passe']),
                    'role' => $compte['role'],
                    'matricule' => $compte['matricule'],
                    'school_id' => $compte['role'] === 'superadmin' ? null : $ecole,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command->info('Comptes de démonstration :');
        foreach ($comptes as $compte) {
            $this->command->line(sprintf(
                '  %-28s %-16s (%s)',
                $compte['email'],
                $compte['mot_de_passe'],
                $compte['role']
            ));
        }
    }
}
