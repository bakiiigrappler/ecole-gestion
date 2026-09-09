<?php

namespace Database\Seeders;

use App\Models\ParentModel;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Donner une fiche aux comptes de démonstration qui en réclament une.
 *
 * Le compte parent et le compte enseignant ne sont pas des comptes comme les
 * autres : leur portail part de leur fiche — les enfants pour l'un, les classes
 * pour l'autre. Un compte sans fiche n'a rien à afficher, et le portail parent
 * répondait « 404 : profil parent non trouvé » dès la connexion, sur toute base
 * fraîchement peuplée. C'est ce que voyait quiconque ouvrait la démonstration
 * en ligne.
 *
 * Le rattachement se fait ici plutôt que dans le seeder des comptes : il faut
 * que les parents, les enseignants et les liens parent-élève existent déjà.
 */
class RattacherLesComptesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->rattacherLeParent();
        $this->rattacherLEnseignant();
    }

    /**
     * Le compte parent reçoit la fiche d'une famille, la plus petite possible.
     *
     * Une fratrie, pour que la démonstration montre plusieurs dossiers — mais
     * la plus modeste que le tirage aléatoire ait produite : le parent aux
     * quarante enfants donnait des écrans qu'aucune famille ne connaîtra.
     */
    private function rattacherLeParent(): void
    {
        $compte = User::where('role', 'parent')->orderBy('id')->first();

        if (! $compte) {
            return;
        }

        $dejaLie = ParentModel::withoutGlobalScopes()->where('user_id', $compte->id)->exists();

        if ($dejaLie) {
            return;
        }

        // Le tri et le filtre se font en mémoire : SQLite refuse un HAVING sur
        // un compte de sous-requête, et la table des parents tient dans la main.
        $libres = ParentModel::withoutGlobalScopes()
            ->withCount('students')
            ->whereNull('user_id')
            ->get()
            ->filter(fn ($p) => $p->students_count > 0)
            ->sortByDesc('students_count')
            ->values();

        $parent = $libres->first(fn ($p) => $p->students_count >= 2 && $p->students_count <= 4)
            ?? $libres->last();

        if (! $parent) {
            $this->command->warn('Aucun parent avec enfants : le compte parent reste sans fiche.');

            return;
        }

        $parent->forceFill(['user_id' => $compte->id])->save();

        // Le compte suit l'établissement de la fiche : sans cela il verrait la
        // plateforme entière, ou rien du tout.
        $compte->forceFill([
            'school_id' => $compte->school_id ?: $parent->school_id,
            'telephone' => $compte->telephone ?: \App\Support\ComptesUtilisateurs::normaliserLeNumero($parent->phone),
        ])->save();

        $this->command->info(sprintf(
            'Compte parent rattaché à %s %s (%d enfant(s)).',
            $parent->first_name,
            $parent->last_name,
            $parent->students_count
        ));
    }

    /**
     * Le compte enseignant reçoit la fiche d'un enseignant en activité.
     */
    private function rattacherLEnseignant(): void
    {
        $compte = User::where('role', 'teacher')->orderBy('id')->first();

        if (! $compte) {
            return;
        }

        if (Teacher::withoutGlobalScopes()->where('user_id', $compte->id)->exists()) {
            return;
        }

        // Celui qui enseigne le plus : ses écrans ne seront pas vides.
        $enseignant = Teacher::withoutGlobalScopes()
            ->whereNull('user_id')
            ->withCount('schedules')
            ->orderByDesc('schedules_count')
            ->first();

        if (! $enseignant) {
            $this->command->warn('Aucun enseignant libre : le compte enseignant reste sans fiche.');

            return;
        }

        $enseignant->forceFill(['user_id' => $compte->id])->save();

        $compte->forceFill([
            'school_id' => $compte->school_id ?: $enseignant->school_id,
        ])->save();

        $this->command->info(sprintf(
            'Compte enseignant rattaché à %s %s.',
            $enseignant->first_name,
            $enseignant->last_name
        ));
    }
}
