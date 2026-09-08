<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\ParentModel;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = SchoolClass::where('is_active', true)->get();
        $parents = ParentModel::all();
        $counter = 1;

        foreach ($classes as $class) {
            // Créer 15-25 étudiants par classe
            $studentCount = rand(15, 25);
            
            for ($i = 1; $i <= $studentCount; $i++) {
                $parent = $parents->random();
                
                // Le prenom suit le sexe : sinon le jeu de demonstration melange
                
                // prenoms feminins et sexe masculin.
                
                $sexe = fake()->randomElement(['male', 'female']);
                
                Student::create([
                    'student_id' => 'STU' . str_pad($counter++, 5, '0', STR_PAD_LEFT),
                    'first_name' => fake()->firstName($sexe),
                    'last_name' => fake()->lastName(),
                    'date_of_birth' => fake()->date('Y-m-d', '-6 years'),
                    'gender' => $sexe,
                    'place_of_birth' => fake()->city(),
                    'address' => fake()->address(),
                    'emergency_contact' => fake()->phoneNumber(),
                    'medical_conditions' => null,
                    'photo' => null,
                    'enrollment_date' => fake()->date('Y-m-d', '-1 year'),
                    'status' => 'active',
                ]);
            }
        }

        $this->command->info('Étudiants créés avec succès !');
    }
}
