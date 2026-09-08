<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Competency;
use App\Models\SchoolClass;
use App\Models\StudentCompetencyEvaluation;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Évaluations par compétences du primaire.
 *
 * Le référentiel existait (`CompetencySeeder`) mais aucune évaluation n'avait
 * jamais été saisie : le module « Compétences (primaire) » et les bulletins du
 * cycle restaient vides. Chaque élève du primaire est ici évalué sur chaque
 * compétence, palier par palier, avec des points par critère cohérents avec le
 * barème du référentiel.
 */
class EvaluationsCompetencesSeeder extends Seeder
{
    /** Les paliers déjà écoulés à la date du seeding. */
    private const PALIERS = [1, 2];

    /**
     * Seuils de maîtrise, en part des points obtenus.
     *
     * Ils suivent le vocabulaire de la colonne `competency_mastery` :
     * maximale, minimale, partielle, non_maitrise.
     */
    private const SEUILS = [
        ['part' => 0.80, 'niveau' => 'maximale'],
        ['part' => 0.60, 'niveau' => 'minimale'],
        ['part' => 0.40, 'niveau' => 'partielle'],
    ];

    public function run(): void
    {
        $annee = AcademicYear::where('is_current', true)->first();

        if (! $annee) {
            $this->command->warn('Aucune année scolaire courante : évaluations ignorées.');

            return;
        }

        $competences = Competency::with('criteria')->where('is_active', true)->orderBy('sort_order')->get();

        if ($competences->isEmpty()) {
            $this->command->warn('Référentiel de compétences vide : lancez CompetencySeeder d\'abord.');

            return;
        }

        $classes = SchoolClass::with('level')
            ->get()
            ->filter(fn ($c) => ($c->level->cycle ?? null) === 'primaire');

        if ($classes->isEmpty()) {
            $this->command->warn('Aucune classe de primaire : évaluations ignorées.');

            return;
        }

        StudentCompetencyEvaluation::where('academic_year_id', $annee->id)->delete();

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

            $enseignant = $classe->allTeachers()->first() ?? Teacher::where('cycle', 'primaire')->first();

            foreach ($liste as $eleve) {
                // Un élève garde un niveau d'ensemble d'un palier à l'autre :
                // des tirages indépendants donneraient des profils incohérents.
                $aisance = random_int(35, 98) / 100;
                $eleves++;

                foreach (self::PALIERS as $palier) {
                    // La progression au fil de l'année, plus ou moins marquée.
                    $progression = min(1.0, $aisance + ($palier - 1) * random_int(0, 8) / 100);

                    foreach ($competences as $competence) {
                        $lignes[] = $this->evaluer(
                            $eleve->id,
                            $competence,
                            $classe->id,
                            $annee,
                            $enseignant?->id,
                            $palier,
                            $progression
                        );
                    }
                }
            }
        }

        foreach (array_chunk($lignes, 500) as $lot) {
            StudentCompetencyEvaluation::insert($lot);
        }

        $this->command->info(sprintf(
            '%d évaluations de compétences créées pour %d élève(s) du primaire, sur %d palier(s).',
            count($lignes), $eleves, count(self::PALIERS)
        ));
    }

    /**
     * Une ligne d'évaluation : les points de chaque critère, leur total, et le
     * niveau de maîtrise qui en découle.
     */
    private function evaluer(int $eleveId, Competency $competence, int $classeId, AcademicYear $annee, ?int $enseignantId, int $palier, float $aisance): array
    {
        $criteres = $competence->criteria->take(4)->values();
        $points = [];
        $maxima = [];

        foreach (range(0, 3) as $rang) {
            $critere = $criteres->get($rang);
            $max = $critere ? (int) $critere->max_points : 0;

            $maxima[$rang] = $max;

            // Un peu de dispersion autour de l'aisance de l'élève : un enfant
            // n'obtient pas exactement la même part sur chaque critère.
            $part = max(0, min(1, $aisance + random_int(-12, 12) / 100));
            $points[$rang] = (int) round($max * $part);
        }

        $obtenus = array_sum($points);
        $total = array_sum($maxima);
        $maitrise = $this->maitrise($obtenus, $total);

        return [
            'student_id' => $eleveId,
            'competency_id' => $competence->id,
            'class_id' => $classeId,
            'academic_year_id' => $annee->id,
            'teacher_id' => $enseignantId,
            'palier' => $palier,
            'evaluation_date' => $this->dateDuPalier($annee, $palier),
            'c1_points' => $points[0],
            'c2_points' => $points[1],
            'c3_points' => $points[2],
            'c4_points' => $points[3],
            'c1_max_points' => $maxima[0],
            'c2_max_points' => $maxima[1],
            'c3_max_points' => $maxima[2],
            'c4_max_points' => $maxima[3],
            'total_points_obtained' => $obtenus,
            'total_points_max' => $total,
            'competency_mastery' => $maitrise,
            'subject_mastery' => $maitrise,
            'palier_mastery' => $maitrise,
            'is_exit_profile' => false,
            'exit_profile' => null,
            'comments' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function maitrise(int $obtenus, int $total): string
    {
        if ($total <= 0) {
            return 'non_maitrise';
        }

        $part = $obtenus / $total;

        foreach (self::SEUILS as $seuil) {
            if ($part >= $seuil['part']) {
                return $seuil['niveau'];
            }
        }

        return 'non_maitrise';
    }

    /**
     * Date indicative du palier, dans les bornes de l'année scolaire.
     */
    private function dateDuPalier(AcademicYear $annee, int $palier): string
    {
        $debut = Carbon::parse($annee->start_date);
        $fin = Carbon::parse($annee->end_date)->min(Carbon::today());

        $date = $debut->copy()->addWeeks($palier * 6);

        return $date->gt($fin) ? $fin->toDateString() : $date->toDateString();
    }
}
