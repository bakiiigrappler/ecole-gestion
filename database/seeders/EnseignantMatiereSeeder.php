<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Rattache chaque enseignant aux matières qu'il enseigne.
 *
 * La table `subject_teacher` était vide : le constructeur d'emploi du temps
 * proposait alors les 22 enseignants pour n'importe quelle matière, et la
 * fiche enseignant n'affichait aucune spécialité. Le rattachement se déduit
 * de `teachers.specialization` quand elle est renseignée, et d'une répartition
 * par cycle sinon, pour que chaque matière ait au moins deux enseignants.
 */
class EnseignantMatiereSeeder extends Seeder
{
    /**
     * Correspondance entre la spécialité saisie sur la fiche enseignant et
     * les matières du référentiel, qui ne portent pas toujours le même nom.
     */
    private const SPECIALITES = [
        'Mathématiques' => ['Mathématiques'],
        'Français' => ['Français'],
        'Anglais' => ['Anglais'],
        'Langues' => ['Anglais', 'Espagnol', 'Allemand'],
        'Histoire-Géographie' => ['Histoire-Géographie'],
        'Physique-Chimie' => ['Sciences physiques', 'Physique-Chimie'],
        'SVT' => ['Sciences de la Vie et de la Terre', "Sciences d'observation"],
        'Sciences' => ['Sciences de la Vie et de la Terre', "Sciences d'observation", 'Sciences physiques'],
        'EPS' => ['Éducation physique et sportive'],
        'Philosophie' => ['Philosophie'],
    ];

    public function run(): void
    {
        $enseignants = Teacher::where('status', 'active')->get();
        $matieres = Subject::where('is_active', true)->get();

        if ($enseignants->isEmpty() || $matieres->isEmpty()) {
            $this->command->warn('Enseignants ou matières manquants : rattachement ignoré.');

            return;
        }

        DB::table('subject_teacher')->delete();

        $liens = [];
        $ajouter = function (Teacher $enseignant, Subject $matiere) use (&$liens) {
            $cle = $enseignant->id . '-' . $matiere->id;

            if (! isset($liens[$cle])) {
                $liens[$cle] = [
                    'teacher_id' => $enseignant->id,
                    'subject_id' => $matiere->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        };

        // 1. Ce que la fiche enseignant déclare déjà.
        foreach ($enseignants as $enseignant) {
            $noms = self::SPECIALITES[$enseignant->specialization] ?? [];

            foreach ($noms as $nom) {
                foreach ($matieres->where('name', $nom) as $matiere) {
                    $ajouter($enseignant, $matiere);
                }
            }
        }

        // 2. Le primaire et le préprimaire sont polyvalents : un instituteur
        //    assure toutes les matières de son cycle.
        foreach ($enseignants->whereIn('cycle', ['preprimaire', 'primaire']) as $enseignant) {
            foreach ($matieres->where('cycle', 'primaire') as $matiere) {
                $ajouter($enseignant, $matiere);
            }
        }

        // 3. Toute matière du secondaire doit rester assurable : on complète
        //    avec les enseignants du cycle les moins chargés.
        $charge = $enseignants->mapWithKeys(fn ($e) => [$e->id => 0])->all();

        foreach ($liens as $lien) {
            $charge[$lien['teacher_id']] = ($charge[$lien['teacher_id']] ?? 0) + 1;
        }

        foreach ($matieres->whereIn('cycle', ['college', 'lycee']) as $matiere) {
            $deja = collect($liens)->where('subject_id', $matiere->id)->count();

            if ($deja >= 2) {
                continue;
            }

            $candidats = $enseignants
                ->where('cycle', $matiere->cycle)
                ->sortBy(fn ($e) => $charge[$e->id] ?? 0);

            // Un cycle sans enseignant propre se rabat sur tout le secondaire.
            if ($candidats->isEmpty()) {
                $candidats = $enseignants
                    ->whereIn('cycle', ['college', 'lycee'])
                    ->sortBy(fn ($e) => $charge[$e->id] ?? 0);
            }

            foreach ($candidats->take(2 - $deja) as $enseignant) {
                $ajouter($enseignant, $matiere);
                $charge[$enseignant->id] = ($charge[$enseignant->id] ?? 0) + 1;
            }
        }

        DB::table('subject_teacher')->insert(array_values($liens));

        $this->command->info(count($liens) . ' rattachements enseignant/matière créés.');
    }
}
