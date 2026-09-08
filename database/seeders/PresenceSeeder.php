<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Pointe les présences de la rentrée jusqu'à aujourd'hui.
 *
 * L'ancien seeder ne couvrait que trois classes sur sept jours : les rapports
 * d'assiduité étaient vides pour presque tout l'établissement. Ici, chaque
 * classe est appelée chaque jour ouvré écoulé, avec quelques élèves
 * durablement absentéistes pour que les rapports aient quelque chose à dire.
 */
class PresenceSeeder extends Seeder
{
    /** Motifs d'absence les plus courants, pour un relevé lisible. */
    private const MOTIFS = [
        'Maladie',
        'Rendez-vous médical',
        'Raison familiale',
        'Transport',
        'Absence non justifiée',
    ];

    public function run(): void
    {
        $annee = AcademicYear::where('is_current', true)->first();

        if (! $annee) {
            $this->command->warn('Aucune année scolaire courante : présences ignorées.');

            return;
        }

        $debut = Carbon::parse($annee->start_date)->startOfDay();
        $fin = Carbon::today()->min(Carbon::parse($annee->end_date));

        if ($fin->lt($debut)) {
            $this->command->warn('L\'année scolaire n\'a pas encore commencé : présences ignorées.');

            return;
        }

        $jours = $this->joursDeClasse($debut, $fin);

        if (empty($jours)) {
            $this->command->warn('Aucun jour ouvré écoulé : présences ignorées.');

            return;
        }

        Attendance::whereBetween('attendance_date', [$debut->toDateString(), $fin->toDateString()])->delete();

        $lignes = [];
        $classes = SchoolClass::all();

        foreach ($classes as $classe) {
            $eleves = $classe->students()
                ->wherePivot('academic_year_id', $annee->id)
                ->wherePivot('status', 'active')
                ->get(['students.id']);

            if ($eleves->isEmpty()) {
                continue;
            }

            // Un élève sur douze traîne un absentéisme chronique : sans eux,
            // tous les taux se ressemblent et le rapport n'apprend rien.
            $fragiles = $eleves->random(max(1, (int) floor($eleves->count() / 12)))->pluck('id')->all();

            foreach ($jours as $jour) {
                foreach ($eleves as $eleve) {
                    $statut = $this->tirerStatut(in_array($eleve->id, $fragiles, true));

                    $lignes[] = [
                        'student_id' => $eleve->id,
                        'class_id' => $classe->id,
                        'attendance_date' => $jour,
                        'time_slot' => 'journee',
                        'status' => $statut,
                        'arrival_time' => $statut === 'late' ? sprintf('08:%02d:00', random_int(5, 45)) : null,
                        'reason' => in_array($statut, ['absent', 'excused'], true)
                            ? self::MOTIFS[array_rand(self::MOTIFS)]
                            : null,
                        'justified' => $statut === 'excused',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($lignes, 1000) as $lot) {
            Attendance::insert($lot);
        }

        $this->command->info(
            count($lignes) . ' pointages créés sur ' . count($jours) . ' jour(s) de classe, '
            . 'du ' . $debut->format('d/m/Y') . ' au ' . $fin->format('d/m/Y') . '.'
        );
    }

    /**
     * Les jours ouvrés de la période : on ne fait pas l'appel le week-end.
     */
    private function joursDeClasse(Carbon $debut, Carbon $fin): array
    {
        $jours = [];

        for ($jour = $debut->copy(); $jour->lte($fin); $jour->addDay()) {
            if ($jour->isWeekend()) {
                continue;
            }

            $jours[] = $jour->toDateString();
        }

        return $jours;
    }

    /**
     * Tirage réaliste : la très grande majorité des élèves est présente.
     */
    private function tirerStatut(bool $fragile): string
    {
        $tirage = random_int(1, 100);

        if ($fragile) {
            return match (true) {
                $tirage <= 60 => 'present',
                $tirage <= 82 => 'absent',
                $tirage <= 93 => 'late',
                default => 'excused',
            };
        }

        return match (true) {
            $tirage <= 93 => 'present',
            $tirage <= 96 => 'absent',
            $tirage <= 98 => 'late',
            default => 'excused',
        };
    }
}
