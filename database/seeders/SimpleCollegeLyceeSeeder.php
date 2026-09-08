<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\StudentGrade;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

class SimpleCollegeLyceeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "\n📚 Création simple des données pour le collège et lycée...\n\n";

        // Supprimer les données existantes
        echo "🗑️  Suppression des données existantes...\n";
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('enrollment_fees')->delete();
        DB::table('student_grades')->delete();
        DB::table('enrollments')->delete();
        DB::table('students')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        echo "✅ Données existantes supprimées\n\n";

        $academicYear = AcademicYear::where('is_current', true)->first();
        if (!$academicYear) {
            echo "❌ Aucune année scolaire en cours trouvée.\n";
            return;
        }

        // Créer seulement 5 étudiants pour le collège et 5 pour le lycée
        echo "👨‍🎓 Création des étudiants...\n";
        
        // Étudiants du collège
        $collegeStudents = [
            ['first_name' => 'Marie', 'last_name' => 'Nguema', 'gender' => 'female'],
            ['first_name' => 'Jean', 'last_name' => 'Mba', 'gender' => 'male'],
            ['first_name' => 'Sophie', 'last_name' => 'Obame', 'gender' => 'female'],
            ['first_name' => 'Pierre', 'last_name' => 'Essono', 'gender' => 'male'],
            ['first_name' => 'Claire', 'last_name' => 'Bongo', 'gender' => 'female'],
        ];

        // Étudiants du lycée
        $lyceeStudents = [
            ['first_name' => 'Amélie', 'last_name' => 'Ndong', 'gender' => 'female'],
            ['first_name' => 'Gabriel', 'last_name' => 'Mba', 'gender' => 'male'],
            ['first_name' => 'Isabelle', 'last_name' => 'Bongo', 'gender' => 'female'],
            ['first_name' => 'Lucas', 'last_name' => 'Essono', 'gender' => 'male'],
            ['first_name' => 'Camille', 'last_name' => 'Ondimba', 'gender' => 'female'],
        ];

        $createdStudents = [];

        // Créer les étudiants du collège
        foreach ($collegeStudents as $index => $studentData) {
            $student = Student::create([
                'student_id' => 'COL2025' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'first_name' => $studentData['first_name'],
                'last_name' => $studentData['last_name'],
                'date_of_birth' => '2010-01-01',
                'gender' => $studentData['gender'],
                'address' => 'Libreville, Gabon',
                'enrollment_date' => now(),
                'status' => 'active',
                'current_status' => 'actif'
            ]);

            // Inscrire dans une classe du collège (6ème A)
            $collegeClass = SchoolClass::whereHas('level', function($q) {
                $q->where('cycle', 'college')->where('name', '6ème');
            })->first();

            if ($collegeClass) {
                $student->enrollments()->create([
                    'class_id' => $collegeClass->id,
                    'academic_year_id' => $academicYear->id,
                    'enrollment_date' => now(),
                    'status' => 'active',
                    'is_new_enrollment' => true,
                    'student_status' => 'nouveau'
                ]);
            }

            $createdStudents[] = $student;
            echo "✅ {$studentData['first_name']} {$studentData['last_name']} (Collège)\n";
        }

        // Créer les étudiants du lycée
        foreach ($lyceeStudents as $index => $studentData) {
            $student = Student::create([
                'student_id' => 'LYC2025' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'first_name' => $studentData['first_name'],
                'last_name' => $studentData['last_name'],
                'date_of_birth' => '2008-01-01',
                'gender' => $studentData['gender'],
                'address' => 'Libreville, Gabon',
                'enrollment_date' => now(),
                'status' => 'active',
                'current_status' => 'actif'
            ]);

            // Inscrire dans une classe du lycée (Seconde A)
            $lyceeClass = SchoolClass::whereHas('level', function($q) {
                $q->where('cycle', 'lycee')->where('name', '2nde');
            })->first();

            if ($lyceeClass) {
                $student->enrollments()->create([
                    'class_id' => $lyceeClass->id,
                    'academic_year_id' => $academicYear->id,
                    'enrollment_date' => now(),
                    'status' => 'active',
                    'is_new_enrollment' => true,
                    'student_status' => 'nouveau'
                ]);
            }

            $createdStudents[] = $student;
            echo "✅ {$studentData['first_name']} {$studentData['last_name']} (Lycée)\n";
        }

        // Créer quelques notes simples
        echo "\n📝 Création des notes...\n";
        $subjects = Subject::whereIn('cycle', ['college', 'lycee'])->take(5)->get();
        $teachers = Teacher::where('status', 'active')->take(5)->get();

        foreach ($createdStudents as $student) {
            foreach ($subjects as $subject) {
                // Créer une note par trimestre
                $terms = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
                
                foreach ($terms as $term) {
                    StudentGrade::create([
                        'student_id' => $student->id,
                        'subject_id' => $subject->id,
                        'class_id' => $student->enrollments()->first()->class_id,
                        'teacher_id' => $teachers->random()->id,
                        'academic_year_id' => $academicYear->id,
                        'score' => rand(10, 17),
                        'max_score' => 20,
                        'term' => $term,
                        'comments' => 'Note de test'
                    ]);
                }
            }
        }

        echo "✅ " . count($createdStudents) * $subjects->count() * 3 . " notes créées\n";
        echo "\n🎉 Données du collège et lycée créées avec succès!\n";
        echo "📊 Résumé:\n";
        echo "  • Étudiants créés : " . count($createdStudents) . "\n";
        echo "  • Notes créées : " . StudentGrade::count() . "\n\n";
    }
}
