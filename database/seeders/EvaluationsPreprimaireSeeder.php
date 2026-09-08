<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\PrePrimaryCompetency;
use App\Models\PrePrimaryCompetencyEvaluation;
use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

/**
 * Évaluations du préprimaire, trimestre par trimestre.
 *
 * Le référentiel existait (`PrePrimaryCompetencySeeder`) mais aucune
 * appréciation n'avait jamais été saisie : le module « Notes préprimaire »
 * n'affichait que des colonnes vides. Une ligne par élève et par compétence,
 * avec un code par trimestre écoulé.
 */
class EvaluationsPreprimaireSeeder extends Seeder
{
    /**
     * Codes de la colonne `trimester_x_code`, du plus au moins acquis.
     * MAX : maîtrise maximale · MIN : minimale · PART : partielle · NM : non maîtrisé.
     */
    private const CODES = ['MAX', 'MIN', 'PART', 'NM'];

    /** Appréciations proposées à l'enseignant, reprises telles quelles. */
    private const COMMENTAIRES = [
        'MAX' => ['Acquis avec aisance', 'Très bonne maîtrise', 'Fait seul et accompagne ses camarades'],
        'MIN' => ['Acquis', 'Réussit la plupart du temps', 'Progresse régulièrement'],
        'PART' => ['En cours d\'acquisition', 'Réussit avec de l\'aide', 'À consolider'],
        'NM' => ['Non acquis à ce jour', 'A besoin d\'un accompagnement soutenu', 'À reprendre'],
    ];

    public function run(): void
    {
        $annee = AcademicYear::where('is_current', true)->first();

        if (! $annee) {
            $this->command->warn('Aucune année scolaire courante : évaluations ignorées.');

            return;
        }

        $competences = PrePrimaryCompetency::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($competences->isEmpty()) {
            $this->command->warn('Référentiel préprimaire vide : lancez PrePrimaryCompetencySeeder d\'abord.');

            return;
        }

        $classes = SchoolClass::with('level')
            ->get()
            ->filter(fn ($c) => ($c->level->cycle ?? null) === 'preprimaire');

        if ($classes->isEmpty()) {
            $this->command->warn('Aucune classe de préprimaire : évaluations ignorées.');

            return;
        }

        // Seuls les trimestres écoulés sont renseignés : remplir les trois
        // dès la rentrée donnerait un bulletin annuel dès septembre.
        $trimestresEcoules = $this->trimestresEcoules($annee);

        PrePrimaryCompetencyEvaluation::where('academic_year_id', $annee->id)->delete();

        $lignes = [];
        $eleves = 0;

        foreach ($classes as $classe) {
            $liste = $classe->students()
                ->wherePivot('academic_year_id', $annee->id)
                ->wherePivot('status', 'active')
                ->get(['students.id']);

            if ($liste->isEmpty()) {
                continue;
            }

            $enseignant = $classe->allTeachers()->first() ?? Teacher::where('cycle', 'preprimaire')->first();

            foreach ($liste as $eleve) {
                // Le niveau d'ensemble de l'enfant, tenu d'une compétence a
                // l'autre : sans lui, chaque ligne serait un tirage isolé.
                $niveau = random_int(0, 100);
                $eleves++;

                foreach ($competences as $competence) {
                    $ligne = [
                        'student_id' => $eleve->id,
                        'pre_primary_competency_id' => $competence->id,
                        'class_id' => $classe->id,
                        'academic_year_id' => $annee->id,
                        'teacher_id' => $enseignant?->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    foreach ([1, 2, 3] as $trimestre) {
                        $acquis = in_array($trimestre, $trimestresEcoules, true);

                        // Les acquis se consolident au fil de l'année.
                        $code = $acquis
                            ? $this->code($niveau + ($trimestre - 1) * random_int(0, 12))
                            : null;

                        $ligne["trimester_{$trimestre}_code"] = $code;
                        $ligne["trimester_{$trimestre}_comment"] = $code
                            ? self::COMMENTAIRES[$code][array_rand(self::COMMENTAIRES[$code])]
                            : null;
                    }

                    $lignes[] = $ligne;
                }
            }
        }

        foreach (array_chunk($lignes, 500) as $lot) {
            PrePrimaryCompetencyEvaluation::insert($lot);
        }

        $this->command->info(sprintf(
            '%d évaluations préprimaire créées pour %d enfant(s), sur %d trimestre(s) écoulé(s).',
            count($lignes), $eleves, count($trimestresEcoules)
        ));
    }

    /**
     * Traduit un niveau (0-100) en code de maîtrise.
     */
    private function code(int $niveau): string
    {
        return match (true) {
            $niveau >= 75 => 'MAX',
            $niveau >= 50 => 'MIN',
            $niveau >= 25 => 'PART',
            default => 'NM',
        };
    }

    /**
     * Trimestres déjà écoulés, avec le même découpage que les bulletins :
     * septembre-décembre, janvier-mars, avril-juin.
     */
    private function trimestresEcoules(AcademicYear $annee): array
    {
        $mois = (int) now()->format('n');

        $courant = match (true) {
            $mois >= 9 => 1,
            $mois <= 3 => 2,
            default => 3,
        };

        return range(1, $courant);
    }
}
