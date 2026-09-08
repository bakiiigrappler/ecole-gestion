<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Level;
use App\Models\ParentModel;
use App\Models\Enrollment;
use App\Models\AcademicYear;

class CompleteTestDataSeeder extends Seeder
{
    public function run()
    {
        // Créer l'année académique si elle n'existe pas
        $academicYear = AcademicYear::firstOrCreate(
            ['is_current' => true],
            [
                'name' => date('Y') . '-' . (date('Y') + 1),
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear(),
                'is_current' => true
            ]
        );

        // Créer un niveau primaire si nécessaire
        $level = Level::firstOrCreate(
            ['name' => 'CE1'],
            [
                'code' => 'CE1',
                'cycle' => 'primaire',
                'order' => 2,
                'is_active' => true
            ]
        );

        // Créer une classe CE1 A si nécessaire
        $class = SchoolClass::firstOrCreate(
            ['name' => 'CE1 A', 'level_id' => $level->id],
            [
                'description' => 'Classe CE1 A',
                'capacity' => 30,
                'is_active' => true,
            ]
        );

        // Créer des parents de test
        $parents = [];
        for ($i = 1; $i <= 10; $i++) {
            $parents[] = ParentModel::create([
                'first_name' => 'Parent' . $i,
                'last_name' => 'Test' . $i,
                'phone' => '077000000' . $i,
                'email' => 'parent' . $i . '@test.com',
                'address' => 'Adresse Test ' . $i,
                'profession' => 'Profession Test',
                'workplace' => 'Lieu de travail Test'
            ]);
        }

        // Créer des élèves de test
        $students = [];
        for ($i = 1; $i <= 10; $i++) {
            $student = Student::create([
                'student_id' => 'STU' . date('Y') . str_pad($i, 4, '0', STR_PAD_LEFT),
                'first_name' => 'Élève' . $i,
                'last_name' => 'Test' . $i,
                'date_of_birth' => now()->subYears(7)->subDays($i * 10),
                'gender' => $i % 2 === 0 ? 'male' : 'female',
                'place_of_birth' => 'Libreville',
                'address' => 'Adresse Élève ' . $i,
                'emergency_contact' => '077000000' . $i,
                'enrollment_date' => now()->subMonths(6),
                'status' => 'active'
            ]);

            // Associer l'élève à un parent
            $student->parents()->attach($parents[($i - 1) % count($parents)]);

            // Créer l'inscription
            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'academic_year_id' => $academicYear->id,
                'enrollment_date' => now()->subMonths(6),
                'status' => 'active'
            ]);

            $students[] = $student;
        }

        $this->command->info('Données de test créées :');
        $this->command->info('- 1 niveau primaire (CE1)');
        $this->command->info('- 1 classe (CE1 A)');
        $this->command->info('- 10 parents');
        $this->command->info('- 10 élèves');
        $this->command->info('- 10 inscriptions');
    }
}
