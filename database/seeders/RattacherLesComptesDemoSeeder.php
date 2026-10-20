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
     * Le compte de démonstration d'un rôle, et lui seul.
     *
     * La recherche passe par `config/demo.php` : ce seeder tourne aussi à
     * l'ouverture d'un déploiement, et il ne doit toucher aucun compte réel —
     * relier au hasard le compte d'un vrai parent à la fiche d'une autre
     * famille lui ouvrirait le dossier d'enfants qui ne sont pas les siens.
     */
    private function compteDeDemonstration(string $role): ?User
    {
        $courriels = collect(config('demo.comptes', []))
            ->where('role', $role)
            ->pluck('email')
            ->filter()
            ->all();

        if ($courriels === []) {
            return null;
        }

        return User::whereIn('email', $courriels)->orderBy('id')->first();
    }

    /**
     * Le compte parent reçoit la fiche d'une famille, choisie pour ce qu'elle
     * donne à voir.
     *
     * Une fratrie, pour que la démonstration montre plusieurs dossiers — mais
     * la plus modeste que le tirage aléatoire ait produite : le parent aux
     * quarante enfants donnait des écrans qu'aucune famille ne connaîtra.
     *
     * Et, à taille égale, une famille **concernée par les examens**. Le portail
     * des parents doit pouvoir montrer les deux moitiés de la chose : un enfant
     * dont la session est encore en cours, dont on ne sait rien parce que
     * l'anonymat l'interdit, et un enfant dont les résultats sont proclamés,
     * avec son relevé et son attestation. Une fratrie de CP et de CE1 ne
     * montrerait ni l'un ni l'autre.
     */
    private function rattacherLeParent(): void
    {
        $compte = $this->compteDeDemonstration('parent');

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

        // De la plus petite fratrie a la plus grande : a egalite d'interet, c'est
        // toujours la plus modeste qu'on veut.
        $duPlusPetit = $libres->sortBy('students_count')->values();

        $parent = $duPlusPetit->first(fn ($p) => $this->aUnEnfantEn($p, 'terminale') && $this->aUnEnfantEn($p, 'troisieme'))
            ?? $duPlusPetit->first(fn ($p) => $this->aUnEnfantEn($p, 'terminale'))
            ?? $duPlusPetit->first(fn ($p) => $this->aUnEnfantEn($p, 'troisieme'))
            ?? $duPlusPetit->first(fn ($p) => $p->students_count >= 2)
            ?? $duPlusPetit->first();

        if (! $parent) {
            $this->command?->warn('Aucun parent avec enfants : le compte parent reste sans fiche.');

            return;
        }

        $parent->forceFill(['user_id' => $compte->id])->save();

        // Le compte suit l'établissement de la fiche : sans cela il verrait la
        // plateforme entière, ou rien du tout.
        $compte->forceFill([
            'school_id' => $compte->school_id ?: $parent->school_id,
            'telephone' => $compte->telephone ?: \App\Support\ComptesUtilisateurs::normaliserLeNumero($parent->phone),
        ])->save();

        $this->command?->info(sprintf(
            'Compte parent rattaché à %s %s (%d enfant(s)).',
            $parent->first_name,
            $parent->last_name,
            $parent->students_count
        ));
    }

    /**
     * Cette famille a-t-elle un enfant dans la classe qui passe un examen ?
     *
     * La terminale passe le baccalauréat, la troisième le brevet. Les motifs
     * sont ceux du peuplement des examens, et il faut qu'ils le restent : une
     * famille choisie sur un autre critère que celui de l'inscription ne serait
     * pas inscrite.
     */
    private function aUnEnfantEn(ParentModel $parent, string $niveau): bool
    {
        $motif = $niveau === 'troisieme' ? '%3%' : '%erminale%';

        return $parent->students()
            ->whereHas('enrollments', fn ($q) => $q
                ->where('status', 'active')
                ->whereHas('schoolClass.level', fn ($n) => $n->where('name', 'like', $motif)))
            ->exists();
    }

    /**
     * Le compte enseignant reçoit la fiche d'un enseignant en activité.
     */
    private function rattacherLEnseignant(): void
    {
        $compte = $this->compteDeDemonstration('teacher');

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
            $this->command?->warn('Aucun enseignant libre : le compte enseignant reste sans fiche.');

            return;
        }

        $enseignant->forceFill(['user_id' => $compte->id])->save();

        $compte->forceFill([
            'school_id' => $compte->school_id ?: $enseignant->school_id,
        ])->save();

        $this->command?->info(sprintf(
            'Compte enseignant rattaché à %s %s.',
            $enseignant->first_name,
            $enseignant->last_name
        ));
    }
}
