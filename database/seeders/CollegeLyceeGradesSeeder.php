<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\StudentGrade;
use App\Models\AcademicYear;
use App\Models\Level;
use Illuminate\Support\Facades\DB;

class CollegeLyceeGradesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "\n📚 Création des données pour le collège et lycée...\n\n";

        // Supprimer toutes les données existantes en respectant les contraintes
        echo "🗑️  Suppression des données existantes...\n";
        
        // Désactiver temporairement les contraintes de clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Supprimer les tables liées
        DB::table('enrollment_fees')->delete();
        DB::table('student_grades')->delete();
        DB::table('enrollments')->delete();
        DB::table('students')->delete();
        
        // Réactiver les contraintes de clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        echo "✅ Données existantes supprimées\n\n";

        // Récupérer l'année scolaire en cours
        $academicYear = AcademicYear::where('is_current', true)->first();
        if (!$academicYear) {
            echo "❌ Aucune année scolaire en cours trouvée.\n";
            return;
        }

        // Récupérer les classes du collège et du lycée
        $collegeLevels = Level::where('cycle', 'college')->where('is_active', true)->get();
        $lyceeLevels = Level::where('cycle', 'lycee')->where('is_active', true)->get();

        $collegeClasses = SchoolClass::whereIn('level_id', $collegeLevels->pluck('id'))
            ->where('is_active', true)
            ->get();
        
        $lyceeClasses = SchoolClass::whereIn('level_id', $lyceeLevels->pluck('id'))
            ->where('is_active', true)
            ->get();

        echo "📚 Classes du collège trouvées : " . $collegeClasses->count() . "\n";
        echo "🎓 Classes du lycée trouvées : " . $lyceeClasses->count() . "\n\n";

        // Créer des étudiants pour le collège
        echo "👨‍🎓 Création des étudiants du collège...\n";
        $collegeStudents = $this->createStudentsForClasses($collegeClasses, 'college');
        echo "✅ " . $collegeStudents->count() . " étudiants du collège créés\n\n";

        // Créer des étudiants pour le lycée
        echo "🎓 Création des étudiants du lycée...\n";
        $lyceeStudents = $this->createStudentsForClasses($lyceeClasses, 'lycee');
        echo "✅ " . $lyceeStudents->count() . " étudiants du lycée créés\n\n";

        // Créer des matières pour le collège
        echo "📖 Création des matières du collège...\n";
        $collegeSubjects = $this->createCollegeSubjects();
        echo "✅ " . $collegeSubjects->count() . " matières du collège créées\n\n";

        // Créer des matières pour le lycée
        echo "📚 Création des matières du lycée...\n";
        $lyceeSubjects = $this->createLyceeSubjects();
        echo "✅ " . $lyceeSubjects->count() . " matières du lycée créées\n\n";

        // Créer des notes pour le collège
        echo "📝 Création des notes du collège...\n";
        $this->createGradesForStudents($collegeStudents, $collegeSubjects, $collegeClasses);
        echo "✅ Notes du collège créées\n\n";

        // Créer des notes pour le lycée
        echo "📝 Création des notes du lycée...\n";
        $this->createGradesForStudents($lyceeStudents, $lyceeSubjects, $lyceeClasses);
        echo "✅ Notes du lycée créées\n\n";

        echo "🎉 Données du collège et lycée créées avec succès!\n";
        echo "📊 Résumé:\n";
        echo "  • Étudiants du collège : " . $collegeStudents->count() . "\n";
        echo "  • Étudiants du lycée : " . $lyceeStudents->count() . "\n";
        echo "  • Matières du collège : " . $collegeSubjects->count() . "\n";
        echo "  • Matières du lycée : " . $lyceeSubjects->count() . "\n";
        echo "  • Total des notes créées : " . StudentGrade::count() . "\n\n";
    }

    private function createStudentsForClasses($classes, $cycle)
    {
        $students = collect();
        $studentNames = $this->getStudentNames($cycle);

        foreach ($classes as $class) {
            // Créer 5-8 étudiants par classe
            $studentCount = rand(5, 8);
            
            for ($i = 1; $i <= $studentCount; $i++) {
                $nameIndex = ($students->count() % count($studentNames));
                $studentName = $studentNames[$nameIndex];
                
                $student = Student::create([
                    'student_id' => $this->generateStudentId($cycle),
                    'first_name' => $studentName['first_name'],
                    'last_name' => $studentName['last_name'],
                    'date_of_birth' => $this->generateBirthDate($cycle),
                    'gender' => $studentName['gender'],
                    'address' => 'Libreville, Gabon',
                    'enrollment_date' => now(),
                    'status' => 'active',
                    'current_status' => 'actif'
                ]);

                // Inscrire l'étudiant dans la classe (vérifier d'abord s'il n'est pas déjà inscrit)
                $academicYearId = AcademicYear::where('is_current', true)->first()->id;
                $existingEnrollment = $student->enrollments()
                    ->where('class_id', $class->id)
                    ->where('academic_year_id', $academicYearId)
                    ->first();
                
                if (!$existingEnrollment) {
                    $student->enrollments()->create([
                        'class_id' => $class->id,
                        'academic_year_id' => $academicYearId,
                        'enrollment_date' => now(),
                        'status' => 'active',
                        'is_new_enrollment' => true,
                        'student_status' => 'nouveau'
                    ]);
                }

                $students->push($student);
            }
        }

        return $students;
    }

    private function getStudentNames($cycle)
    {
        if ($cycle === 'college') {
            return [
                ['first_name' => 'Marie', 'last_name' => 'Nguema', 'gender' => 'female'],
                ['first_name' => 'Jean', 'last_name' => 'Mba', 'gender' => 'male'],
                ['first_name' => 'Sophie', 'last_name' => 'Obame', 'gender' => 'female'],
                ['first_name' => 'Pierre', 'last_name' => 'Essono', 'gender' => 'male'],
                ['first_name' => 'Claire', 'last_name' => 'Bongo', 'gender' => 'female'],
                ['first_name' => 'Antoine', 'last_name' => 'Ondimba', 'gender' => 'male'],
                ['first_name' => 'Fatou', 'last_name' => 'Moussavou', 'gender' => 'female'],
                ['first_name' => 'Kevin', 'last_name' => 'Mangue', 'gender' => 'male'],
                ['first_name' => 'Grace', 'last_name' => 'Minko', 'gender' => 'female'],
                ['first_name' => 'David', 'last_name' => 'Nzamba', 'gender' => 'male']
            ];
        } else {
            return [
                ['first_name' => 'Amélie', 'last_name' => 'Ndong', 'gender' => 'female'],
                ['first_name' => 'Gabriel', 'last_name' => 'Mba', 'gender' => 'male'],
                ['first_name' => 'Isabelle', 'last_name' => 'Bongo', 'gender' => 'female'],
                ['first_name' => 'Lucas', 'last_name' => 'Essono', 'gender' => 'male'],
                ['first_name' => 'Camille', 'last_name' => 'Ondimba', 'gender' => 'female'],
                ['first_name' => 'Thomas', 'last_name' => 'Nguema', 'gender' => 'male'],
                ['first_name' => 'Emma', 'last_name' => 'Moussavou', 'gender' => 'female'],
                ['first_name' => 'Nicolas', 'last_name' => 'Mangue', 'gender' => 'male'],
                ['first_name' => 'Léa', 'last_name' => 'Minko', 'gender' => 'female'],
                ['first_name' => 'Alexandre', 'last_name' => 'Nzamba', 'gender' => 'male']
            ];
        }
    }

    private function generateStudentId($cycle)
    {
        $prefix = $cycle === 'college' ? 'COL' : 'LYC';
        $year = date('Y');
        
        // Générer un ID unique
        do {
            $number = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $studentId = "{$prefix}{$year}{$number}";
        } while (Student::where('student_id', $studentId)->exists());
        
        return $studentId;
    }

    private function generateBirthDate($cycle)
    {
        if ($cycle === 'college') {
            // Âge entre 11 et 16 ans pour le collège
            $year = date('Y') - rand(11, 16);
        } else {
            // Âge entre 15 et 19 ans pour le lycée
            $year = date('Y') - rand(15, 19);
        }
        
        $month = rand(1, 12);
        $day = rand(1, 28);
        
        return "{$year}-{$month}-{$day}";
    }

    private function createCollegeSubjects()
    {
        $subjects = [
            ['name' => 'Mathématiques', 'code' => 'MATH', 'coefficient' => 3],
            ['name' => 'Français', 'code' => 'FR', 'coefficient' => 3],
            ['name' => 'Anglais', 'code' => 'ANG', 'coefficient' => 2],
            ['name' => 'Sciences Physiques', 'code' => 'SP', 'coefficient' => 2],
            ['name' => 'Sciences de la Vie et de la Terre', 'code' => 'SVT', 'coefficient' => 2],
            ['name' => 'Histoire-Géographie', 'code' => 'HG', 'coefficient' => 2],
            ['name' => 'Éducation Physique et Sportive', 'code' => 'EPS', 'coefficient' => 1],
            ['name' => 'Arts Plastiques', 'code' => 'ART', 'coefficient' => 1],
        ];

        $createdSubjects = collect();
        foreach ($subjects as $subjectData) {
            $existingSubject = Subject::where('code', $subjectData['code'])->first();
            if ($existingSubject) {
                $createdSubjects->push($existingSubject);
                echo "⚠️  Matière {$subjectData['name']} existe déjà\n";
            } else {
                $subject = Subject::create([
                    'name' => $subjectData['name'],
                    'code' => $subjectData['code'],
                    'description' => 'Matière du collège',
                    'coefficient' => $subjectData['coefficient'],
                    'cycle' => 'college',
                    'is_active' => true
                ]);
                $createdSubjects->push($subject);
                echo "✅ Matière {$subjectData['name']} créée\n";
            }
        }

        return $createdSubjects;
    }

    private function createLyceeSubjects()
    {
        $subjects = [
            ['name' => 'Mathématiques', 'code' => 'MATH', 'coefficient' => 4],
            ['name' => 'Français', 'code' => 'FR', 'coefficient' => 3],
            ['name' => 'Anglais', 'code' => 'ANG', 'coefficient' => 2],
            ['name' => 'Sciences Physiques', 'code' => 'SP', 'coefficient' => 3],
            ['name' => 'Sciences de la Vie et de la Terre', 'code' => 'SVT', 'coefficient' => 3],
            ['name' => 'Histoire-Géographie', 'code' => 'HG', 'coefficient' => 2],
            ['name' => 'Philosophie', 'code' => 'PHILO', 'coefficient' => 2],
            ['name' => 'Éducation Physique et Sportive', 'code' => 'EPS', 'coefficient' => 1],
        ];

        $createdSubjects = collect();
        foreach ($subjects as $subjectData) {
            $existingSubject = Subject::where('code', $subjectData['code'])->first();
            if ($existingSubject) {
                $createdSubjects->push($existingSubject);
                echo "⚠️  Matière {$subjectData['name']} existe déjà\n";
            } else {
                $subject = Subject::create([
                    'name' => $subjectData['name'],
                    'code' => $subjectData['code'],
                    'description' => 'Matière du lycée',
                    'coefficient' => $subjectData['coefficient'],
                    'cycle' => 'lycee',
                    'is_active' => true
                ]);
                $createdSubjects->push($subject);
                echo "✅ Matière {$subjectData['name']} créée\n";
            }
        }

        return $createdSubjects;
    }

    private function createGradesForStudents($students, $subjects, $classes)
    {
        $teachers = Teacher::where('status', 'active')->get();
        
        foreach ($students as $student) {
            $studentClass = $classes->first(); // Simplification pour l'exemple
            
            foreach ($subjects as $subject) {
                // Créer une note par trimestre pour chaque matière
                $terms = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
                
                foreach ($terms as $term) {
                    $score = rand(8, 18); // Notes entre 8 et 18
                    $maxScore = 20;
                    $academicYearId = AcademicYear::where('is_current', true)->first()->id;
                    
                    // Vérifier si la note existe déjà
                    $existingGrade = StudentGrade::where('student_id', $student->id)
                        ->where('subject_id', $subject->id)
                        ->where('term', $term)
                        ->where('academic_year_id', $academicYearId)
                        ->first();
                    
                    if (!$existingGrade) {
                        StudentGrade::create([
                            'student_id' => $student->id,
                            'subject_id' => $subject->id,
                            'class_id' => $studentClass->id,
                            'teacher_id' => $teachers->random()->id,
                            'academic_year_id' => $academicYearId,
                            'score' => $score,
                            'max_score' => $maxScore,
                            'term' => $term,
                            'comments' => $this->getRandomComment($score)
                        ]);
                    }
                }
            }
        }
    }

    private function getRandomExamType()
    {
        $types = ['devoir', 'composition', 'controle', 'oral'];
        return $types[array_rand($types)];
    }

    private function getRandomTerm()
    {
        $terms = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
        return $terms[array_rand($terms)];
    }

    private function getRandomComment($score)
    {
        if ($score >= 16) {
            return 'Excellent travail !';
        } elseif ($score >= 14) {
            return 'Très bon travail.';
        } elseif ($score >= 12) {
            return 'Bon travail.';
        } elseif ($score >= 10) {
            return 'Travail correct.';
        } else {
            return 'Peut mieux faire.';
        }
    }
}
