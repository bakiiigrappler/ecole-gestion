<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\StudentGrade;
use App\Models\AcademicYear;

class TrimesterGradesSeeder extends Seeder
{
    public function run()
    {
        // Récupérer l'année académique active
        $academicYear = AcademicYear::where('is_current', true)->first();
        if (!$academicYear) {
            $this->command->error('Aucune année académique active trouvée');
            return;
        }

        // Récupérer les classes actives
        $classes = SchoolClass::all();
        if ($classes->isEmpty()) {
            $this->command->error('Aucune classe active trouvée');
            return;
        }

        // Récupérer les matières
        $subjects = Subject::all();
        if ($subjects->isEmpty()) {
            $this->command->error('Aucune matière active trouvée');
            return;
        }

        // Trimestres
        $trimesters = ['1er trimestre', '2ème trimestre', '3ème trimestre'];

        $this->command->info('Création des notes par trimestre...');

        foreach ($classes as $class) {
            $this->command->info("Traitement de la classe: {$class->name}");

            // Récupérer les élèves de cette classe
            $students = Student::whereHas('enrollments', function($query) use ($class, $academicYear) {
                $query->where('class_id', $class->id)
                      ->where('academic_year_id', $academicYear->id)
                      ->where('status', 'active');
            })->get();

            if ($students->isEmpty()) {
                $this->command->warn("Aucun élève trouvé pour la classe {$class->name}");
                continue;
            }

            foreach ($students as $student) {
                $this->command->info("  - Élève: {$student->first_name} {$student->last_name}");

                foreach ($trimesters as $trimester) {
                    // Déterminer si cet élève a des notes pour ce trimestre (70% de chance)
                    if (rand(1, 100) <= 70) {
                        $this->command->info("    + {$trimester}");

                        foreach ($subjects as $subject) {
                            // Déterminer si cette matière a des notes pour ce trimestre (80% de chance)
                            if (rand(1, 100) <= 80) {
                                // Générer des notes réalistes
                                $score = $this->generateRealisticScore();
                                $maxScore = 20;

                                StudentGrade::create([
                                    'student_id' => $student->id,
                                    'class_id' => $class->id,
                                    'subject_id' => $subject->id,
                                    'teacher_id' => $this->getRandomTeacherId(),
                                    'academic_year_id' => $academicYear->id,
                                    'score' => $score,
                                    'max_score' => $maxScore,
                                    'term' => $trimester,
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);
                            }
                        }
                    }
                }
            }
        }

        $this->command->info('✅ Seeder terminé avec succès!');
    }

    private function generateRealisticScore()
    {
        // Générer des notes réalistes avec une distribution normale
        $base = rand(8, 18); // Base entre 8 et 18
        $variation = rand(-2, 2); // Variation de ±2 points
        $score = max(0, min(20, $base + $variation));
        
        return round($score, 1);
    }

    private function getRandomTeacherId()
    {
        // Récupérer un ID de professeur aléatoire
        $teacher = DB::table('teachers')->inRandomOrder()->first();
        return $teacher ? $teacher->id : 1;
    }
}
