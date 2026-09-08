<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

/**
 * Construit un emploi du temps hebdomadaire pour chaque classe.
 *
 * Le volume horaire de chaque matière suit son coefficient, et un enseignant
 * n'est jamais placé dans deux classes à la même heure : c'est exactement le
 * conflit que le constructeur signale en rouge, autant ne pas en semer.
 */
class EmploiDuTempsSeeder extends Seeder
{
    /** Trame horaire, par cycle. Les récréations sont des créneaux « break ». */
    private const TRAMES = [
        'primaire' => [
            ['08:00', '09:00', 'course'],
            ['09:00', '10:00', 'course'],
            ['10:00', '10:15', 'break'],
            ['10:15', '11:15', 'course'],
            ['11:15', '12:15', 'course'],
            ['14:00', '15:00', 'course'],
            ['15:00', '16:00', 'course'],
        ],
        'secondaire' => [
            ['07:30', '08:30', 'course'],
            ['08:30', '09:30', 'course'],
            ['09:30', '10:30', 'course'],
            ['10:30', '10:45', 'break'],
            ['10:45', '11:45', 'course'],
            ['11:45', '12:45', 'course'],
            ['14:00', '15:00', 'course'],
            ['15:00', '16:00', 'course'],
            ['16:00', '17:00', 'course'],
        ],
    ];

    /** Lundi à vendredi, plus le samedi matin dans le secondaire. */
    private const JOURS = [1, 2, 3, 4, 5];

    public function run(): void
    {
        $annee = AcademicYear::where('is_current', true)->first();

        if (! $annee) {
            $this->command->warn('Aucune année scolaire courante : emplois du temps ignorés.');

            return;
        }

        $classes = SchoolClass::with('level')->get();
        $enseignants = Teacher::where('status', 'active')->with('subjects:id')->get();

        Schedule::where('academic_year_id', $annee->id)->delete();

        // Un enseignant occupé ailleurs ne peut pas être repris : la clé est
        // « enseignant-jour-heure », partagée par toutes les classes.
        $occupations = [];
        $charges = $enseignants->mapWithKeys(fn ($e) => [$e->id => 0])->all();
        $creneaux = [];
        $classesServies = 0;
        $sansMatiere = [];

        foreach ($classes as $classe) {
            $cycle = $classe->level->cycle ?? 'primaire';

            $matieres = Subject::where('is_active', true)
                ->where('cycle', $cycle === 'preprimaire' ? 'primaire' : $cycle)
                ->orderByDesc('coefficient')
                ->get();

            if ($matieres->isEmpty()) {
                $sansMatiere[] = $classe->name;
                continue;
            }

            $trame = self::TRAMES[in_array($cycle, ['college', 'lycee'], true) ? 'secondaire' : 'primaire'];
            $heuresDeCours = collect($trame)->where(2, 'course')->count() * count(self::JOURS);

            $lignes = $this->repartir($matieres, $heuresDeCours);
            $position = 0;

            foreach (self::JOURS as $jour) {
                foreach ($trame as [$debut, $fin, $type]) {
                    if ($type === 'break') {
                        $creneaux[] = $this->ligne($classe, $annee, $jour, $debut, $fin, null, null, 'break', 'Récréation');
                        continue;
                    }

                    if (! isset($lignes[$position])) {
                        continue;
                    }

                    $matiere = $lignes[$position++];
                    $enseignant = $this->enseignantLibre($enseignants, $matiere, $cycle, $jour, $debut, $occupations, $charges);

                    if ($enseignant) {
                        $occupations["{$enseignant->id}-{$jour}-{$debut}"] = true;
                        $charges[$enseignant->id] = ($charges[$enseignant->id] ?? 0) + 1;
                    }

                    $creneaux[] = $this->ligne(
                        $classe, $annee, $jour, $debut, $fin,
                        $matiere->id, $enseignant?->id, 'course', null
                    );
                }
            }

            $classesServies++;
        }

        foreach (array_chunk($creneaux, 500) as $lot) {
            Schedule::insert($lot);
        }

        $this->command->info(count($creneaux) . " créneaux créés pour {$classesServies} classe(s).");

        if ($sansMatiere) {
            $this->command->warn(
                'Sans emploi du temps faute de matières au référentiel : ' . implode(', ', $sansMatiere)
            );
        }

        // L'effectif enseignant borne la couverture : un enseignant ne peut
        // couvrir qu'une trame par semaine. Le dire evite de croire a un bug.
        $decouvertes = collect($creneaux)->where('type', 'course')->whereNull('teacher_id')->count();

        if ($decouvertes > 0) {
            $cours = collect($creneaux)->where('type', 'course')->count();
            $this->command->warn(sprintf(
                '%d heures de cours sur %d restent sans enseignant : %d enseignants actifs ne suffisent pas a %d classes.',
                $decouvertes, $cours, $enseignants->count(), $classesServies
            ));
        }
    }

    /**
     * Répartir les heures de la semaine entre les matières, au prorata de
     * leur coefficient : le français pèse plus que les arts plastiques.
     */
    private function repartir($matieres, int $heures): array
    {
        $total = max(1, $matieres->sum('coefficient'));
        $lignes = [];

        foreach ($matieres as $matiere) {
            $part = (int) round($heures * ($matiere->coefficient / $total));

            for ($i = 0; $i < max(1, $part); $i++) {
                $lignes[] = $matiere;
            }
        }

        // Compléter ou rogner pour tomber juste sur le nombre d'heures.
        while (count($lignes) < $heures) {
            $lignes[] = $matieres->first();
        }

        $lignes = array_slice($lignes, 0, $heures);

        // Entrelacer pour éviter cinq heures de la même matière d'affilée.
        $parMatiere = collect($lignes)->groupBy(fn ($m) => $m->id)->values();
        $etale = [];

        while ($parMatiere->flatten()->isNotEmpty()) {
            foreach ($parMatiere as $groupe) {
                if ($groupe->isNotEmpty()) {
                    $etale[] = $groupe->shift();
                }
            }
        }

        return $etale;
    }

    /**
     * Un enseignant de la matière, libre à cette heure-là dans toute l'école.
     *
     * Le moins chargé passe en premier : servir toujours le même laisserait
     * les dernières classes sans personne alors que des collègues sont libres.
     */
    private function enseignantLibre($enseignants, Subject $matiere, string $cycle, int $jour, string $debut, array $occupations, array $charges): ?Teacher
    {
        $candidats = $enseignants
            ->filter(fn ($e) => $e->subjects->contains('id', $matiere->id))
            ->filter(fn ($e) => ! isset($occupations["{$e->id}-{$jour}-{$debut}"]))
            ->sortBy(fn ($e) => $charges[$e->id] ?? 0);

        if ($candidats->isEmpty()) {
            return null;
        }

        // Privilégier un enseignant du bon cycle quand il en reste un de libre.
        return $candidats->firstWhere('cycle', $cycle) ?? $candidats->first();
    }

    private function ligne(SchoolClass $classe, AcademicYear $annee, int $jour, string $debut, string $fin, ?int $matiere, ?int $enseignant, string $type, ?string $titre): array
    {
        return [
            'class_id' => $classe->id,
            'academic_year_id' => $annee->id,
            'day_of_week' => $jour,
            'start_time' => $debut . ':00',
            'end_time' => $fin . ':00',
            'subject_id' => $matiere,
            'teacher_id' => $enseignant,
            'type' => $type,
            'title' => $titre,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
