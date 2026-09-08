<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Level;
use App\Models\ParentModel;
use App\Models\Enrollment;
use App\Models\AcademicYear;

class FinalPrimaryClassSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🚀 Création d\'une classe primaire complète...');

        // Récupérer l'année académique
        $academicYear = AcademicYear::where('is_current', true)->first();
        if (!$academicYear) {
            $this->command->error('Aucune année académique actuelle trouvée.');
            return;
        }

        // Créer le niveau CP1 (Cours Préparatoire 1ère année)
        $level = Level::firstOrCreate(
            ['name' => 'CP1'],
            [
                'code' => 'CP1',
                'cycle' => 'primaire',
                'order' => 1,
                'is_active' => true
            ]
        );

        // Créer la classe CP1 A
        $class = SchoolClass::firstOrCreate(
            ['name' => 'CP1 A', 'level_id' => $level->id],
            [
                'description' => 'Classe CP1 A - Cours Préparatoire 1ère année',
                'capacity' => 25,
                'is_active' => true,
            ]
        );

        $this->command->info("✅ Classe créée: {$class->name} ({$level->cycle})");

        // Créer 6 élèves pour cette classe
        $studentsData = [
            ['first_name' => 'Aïcha', 'last_name' => 'Nguema', 'gender' => 'female'],
            ['first_name' => 'Kévin', 'last_name' => 'Mba', 'gender' => 'male'],
            ['first_name' => 'Grace', 'last_name' => 'Ondo', 'gender' => 'female'],
            ['first_name' => 'Jordan', 'last_name' => 'Ndong', 'gender' => 'male'],
            ['first_name' => 'Fatima', 'last_name' => 'Essono', 'gender' => 'female'],
            ['first_name' => 'David', 'last_name' => 'Mba', 'gender' => 'male']
        ];

        $students = collect();

        foreach ($studentsData as $index => $studentData) {
            // Générer un matricule unique
            $matricule = 'STU' . date('Y') . str_pad($index + 100, 4, '0', STR_PAD_LEFT);

            // Créer l'élève
            $student = Student::create([
                'student_id' => $matricule,
                'first_name' => $studentData['first_name'],
                'last_name' => $studentData['last_name'],
                'date_of_birth' => now()->subYears(6)->subDays($index * 20),
                'gender' => $studentData['gender'],
                'place_of_birth' => 'Libreville',
                'address' => 'Quartier ' . ($index + 1) . ', Libreville',
                'emergency_contact' => '077000000' . ($index + 100),
                'enrollment_date' => now()->subMonths(3),
                'status' => 'active'
            ]);

            // Créer un parent
            $parent = ParentModel::create([
                'first_name' => 'Parent' . ($index + 100),
                'last_name' => $studentData['last_name'],
                'phone' => '077000000' . ($index + 100),
                'email' => 'parent' . ($index + 100) . '@test.com',
                'address' => 'Adresse Parent ' . ($index + 100),
                'profession' => 'Fonctionnaire',
                'workplace' => 'Ministère de l\'Éducation'
            ]);

            // Associer l'élève au parent
            $student->parents()->attach($parent);

            // Créer l'inscription
            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'academic_year_id' => $academicYear->id,
                'enrollment_date' => now()->subMonths(3),
                'status' => 'active'
            ]);

            $students->push($student);
            $this->command->info("  👤 {$student->first_name} {$student->last_name} ({$student->student_id})");
        }

        $this->command->info('✅ Classe primaire créée avec succès !');
        $this->command->info("📊 Résumé:");
        $this->command->info("- Niveau: {$level->name} ({$level->cycle})");
        $this->command->info("- Classe: {$class->name}");
        $this->command->info("- Élèves: {$students->count()}");
        $this->command->info("- Parents: {$students->count()}");
        $this->command->info("- Inscriptions: {$students->count()}");
        $this->command->info("🎯 Maintenant exécutez: php artisan db:seed --class=UniversalCompetencySeeder");
    }
}
