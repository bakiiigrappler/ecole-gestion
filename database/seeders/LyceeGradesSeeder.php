<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\StudentGrade;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Series;
use Illuminate\Support\Facades\DB;

class LyceeGradesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Désactiver les contraintes de clés étrangères temporairement
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Supprimer les données existantes
        DB::table('student_grades')->delete();
        DB::table('students')->delete();
        DB::table('enrollments')->delete();
        
        // Réactiver les contraintes de clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Récupérer l'année académique active
        $academicYear = AcademicYear::where('is_current', true)->first();
        if (!$academicYear) {
            $this->command->error('Aucune année académique active trouvée.');
            return;
        }

        // Récupérer les niveaux du lycée
        $lyceeLevels = Level::where('cycle', 'lycee')->get();
        if ($lyceeLevels->isEmpty()) {
            $this->command->error('Aucun niveau de lycée trouvé.');
            return;
        }

        // Récupérer les séries
        $series = Series::all()->keyBy('code');
        if ($series->isEmpty()) {
            $this->command->error('Aucune série trouvée.');
            return;
        }

        // Récupérer les matières
        $subjects = Subject::all()->keyBy('name');
        if ($subjects->isEmpty()) {
            $this->command->error('Aucune matière trouvée.');
            return;
        }

        $this->command->info('Création des notes pour le lycée...');

        // Créer des étudiants et des notes pour chaque niveau du lycée
        foreach ($lyceeLevels as $level) {
            $this->createStudentsForLevel($level, $academicYear, $series, $subjects);
        }

        $this->command->info('Notes du lycée créées avec succès !');
    }

    private function createStudentsForLevel($level, $academicYear, $series, $subjects)
    {
        $this->command->info("Création des étudiants pour le niveau: {$level->name}");

        // Récupérer les classes de ce niveau
        $classes = SchoolClass::where('level_id', $level->id)->get();
        
        if ($classes->isEmpty()) {
            $this->command->warn("Aucune classe trouvée pour le niveau: {$level->name}");
            return;
        }

        foreach ($classes as $class) {
            $this->createStudentsForClass($class, $academicYear, $series, $subjects);
        }
    }

    private function createStudentsForClass($class, $academicYear, $series, $subjects)
    {
        $this->command->info("Création des étudiants pour la classe: {$class->name}");

        // Déterminer la série de la classe
        $classSeries = $class->series;
        $seriesData = $series->get($classSeries);
        
        if (!$seriesData) {
            $this->command->warn("Série non trouvée pour la classe: {$class->name}");
            return;
        }

        // Créer 5-8 étudiants par classe
        $studentCount = rand(5, 8);
        
        for ($i = 1; $i <= $studentCount; $i++) {
            // Créer un étudiant
            $student = Student::create([
                'student_id' => $this->generateStudentId($class->level->cycle, $class->level->code, $i),
                'first_name' => $this->getRandomFirstName(),
                'last_name' => $this->getRandomLastName(),
                'date_of_birth' => $this->getRandomDateOfBirth(),
                'gender' => $this->getRandomGender(),
                'address' => $this->getRandomAddress(),
                'enrollment_date' => now(),
                'status' => 'active'
            ]);

            // Créer l'inscription
            DB::table('enrollments')->insert([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'academic_year_id' => $academicYear->id,
                'enrollment_date' => now(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Créer des notes pour chaque trimestre
            $this->createGradesForStudent($student, $class, $subjects, $seriesData);
        }
    }

    private function createGradesForStudent($student, $class, $subjects, $seriesData)
    {
        $trimesters = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
        
        // Définir les matières selon la série
        $subjectNames = $this->getSubjectsForSeries($seriesData->code);
        
        foreach ($trimesters as $trimester) {
            foreach ($subjectNames as $subjectName) {
                $subject = $subjects->get($subjectName);
                if (!$subject) {
                    continue;
                }

                // Créer une seule note par matière et par trimestre
                $maxScore = 20;
                $score = $this->getRandomScore();
                
                StudentGrade::create([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'class_id' => $class->id,
                    'academic_year_id' => 1, // Année académique par défaut
                    'teacher_id' => 1, // Professeur par défaut
                    'term' => $trimester,
                    'score' => $score,
                    'max_score' => $maxScore,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    private function getSubjectsForSeries($seriesCode)
    {
        $subjectsBySeries = [
            '2NDE-S' => [
                'Mathématiques', 'Sciences physiques', 'Sciences de la Vie et de la Terre',
                'Français', 'Histoire-Géographie', 'Anglais', 'Éducation physique et sportive'
            ],
            '2NDE-LE' => [
                'Français', 'Histoire-Géographie', 'Anglais', 'Mathématiques',
                'Éducation physique et sportive'
            ],
            '1ERE-S' => [
                'Mathématiques', 'Sciences physiques', 'Sciences de la Vie et de la Terre',
                'Français', 'Histoire-Géographie', 'Anglais', 'Philosophie', 'Éducation physique et sportive'
            ],
            '1ERE-A1' => [
                'Français', 'Littérature', 'Latin', 'Histoire-Géographie',
                'Anglais', 'Mathématiques', 'Philosophie', 'Éducation physique et sportive'
            ],
            '1ERE-A2' => [
                'Français', 'Littérature', 'Espagnol', 'Histoire-Géographie',
                'Anglais', 'Mathématiques', 'Philosophie', 'Éducation physique et sportive'
            ],
            '1ERE-B' => [
                'Sciences économiques et sociales', 'Mathématiques', 'Français',
                'Histoire-Géographie', 'Anglais', 'Philosophie', 'Éducation physique et sportive'
            ],
            'TERM-S' => [
                'Mathématiques', 'Sciences physiques', 'Sciences de la Vie et de la Terre',
                'Français', 'Histoire-Géographie', 'Anglais', 'Philosophie', 'Éducation physique et sportive'
            ],
            'TERM-A1' => [
                'Français', 'Littérature', 'Latin', 'Histoire-Géographie',
                'Anglais', 'Mathématiques', 'Philosophie', 'Éducation physique et sportive'
            ],
            'TERM-A2' => [
                'Français', 'Littérature', 'Espagnol', 'Histoire-Géographie',
                'Anglais', 'Mathématiques', 'Philosophie', 'Éducation physique et sportive'
            ],
            'TERM-B' => [
                'Sciences économiques et sociales', 'Mathématiques', 'Français',
                'Histoire-Géographie', 'Anglais', 'Philosophie', 'Éducation physique et sportive'
            ],
            'TERM-C' => [
                'Mathématiques', 'Sciences physiques', 'Sciences de la Vie et de la Terre',
                'Français', 'Histoire-Géographie', 'Anglais', 'Philosophie', 'Éducation physique et sportive'
            ],
            'TERM-D' => [
                'Sciences de la Vie et de la Terre', 'Mathématiques', 'Sciences physiques',
                'Français', 'Histoire-Géographie', 'Anglais', 'Philosophie', 'Éducation physique et sportive'
            ]
        ];

        return $subjectsBySeries[$seriesCode] ?? $subjectsBySeries['2NDE-S'];
    }

    private function generateStudentId($cycle, $levelCode, $index)
    {
        $prefix = strtoupper(substr($cycle, 0, 3));
        $year = date('Y');
        $levelCode = strtoupper($levelCode);
        $index = str_pad($index, 3, '0', STR_PAD_LEFT);
        
        // Ajouter un timestamp pour éviter les doublons
        $timestamp = substr(time(), -3);
        
        return "{$prefix}{$year}{$levelCode}{$timestamp}{$index}";
    }

    private function getRandomFirstName()
    {
        $firstNames = [
            'Jean', 'Marie', 'Pierre', 'Sophie', 'Paul', 'Claire', 'Marc', 'Julie',
            'Antoine', 'Camille', 'Thomas', 'Léa', 'Nicolas', 'Emma', 'David', 'Sarah',
            'Alexandre', 'Laura', 'Julien', 'Chloé', 'Maxime', 'Manon', 'Lucas', 'Emma',
            'Hugo', 'Léa', 'Gabriel', 'Camille', 'Romain', 'Océane', 'Baptiste', 'Anaïs'
        ];
        
        return $firstNames[array_rand($firstNames)];
    }

    private function getRandomLastName()
    {
        $lastNames = [
            'Martin', 'Bernard', 'Thomas', 'Petit', 'Robert', 'Richard', 'Durand', 'Dubois',
            'Moreau', 'Laurent', 'Simon', 'Michel', 'Lefebvre', 'Leroy', 'Roux', 'David',
            'Bertrand', 'Morel', 'Fournier', 'Girard', 'André', 'Lefèvre', 'Mercier', 'Garcia',
            'Dupont', 'Lambert', 'Bonnet', 'François', 'Martinez', 'Legrand', 'Garnier', 'Faure'
        ];
        
        return $lastNames[array_rand($lastNames)];
    }

    private function getRandomDateOfBirth()
    {
        $startDate = strtotime('-18 years');
        $endDate = strtotime('-15 years');
        $randomTimestamp = rand($startDate, $endDate);
        
        return date('Y-m-d', $randomTimestamp);
    }

    private function getRandomGender()
    {
        return rand(0, 1) ? 'male' : 'female';
    }

    private function getRandomAddress()
    {
        $addresses = [
            '123 Avenue de la République, Libreville',
            '456 Boulevard de l\'Indépendance, Libreville',
            '789 Rue du Commerce, Libreville',
            '321 Avenue du Port, Libreville',
            '654 Boulevard de la Paix, Libreville'
        ];
        
        return $addresses[array_rand($addresses)];
    }


    private function getRandomScore()
    {
        // Générer des scores réalistes avec une distribution normale
        $mean = 12; // Moyenne autour de 12/20
        $stdDev = 4; // Écart-type de 4
        
        $score = round($mean + $stdDev * $this->gaussianRandom());
        
        // S'assurer que le score est entre 0 et 20
        return max(0, min(20, $score));
    }

    private function gaussianRandom()
    {
        // Box-Muller transform pour générer des nombres aléatoires avec distribution normale
        static $spare = null;
        
        if ($spare !== null) {
            $result = $spare;
            $spare = null;
            return $result;
        }
        
        $u = rand() / getrandmax();
        $v = rand() / getrandmax();
        
        $z = sqrt(-2 * log($u)) * cos(2 * pi() * $v);
        $spare = sqrt(-2 * log($u)) * sin(2 * pi() * $v);
        
        return $z;
    }

}
