<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use App\Models\SchoolClass;

class TeacherSeeder extends Seeder
{
    /**
     * rand(1, 999) sur 28 enseignants entre en collision une fois sur trois
     * (paradoxe des anniversaires) et faisait echouer `migrate:fresh --seed`
     * de facon aleatoire. Un compteur garantit l'unicite.
     */
    private int $compteurMatricule = 0;

    private function prochainMatricule(): string
    {
        return 'EMP' . str_pad(++$this->compteurMatricule, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Enseignants pour le pré-primaire (généralistes)
        $preprimaireClasses = SchoolClass::whereHas('level', fn($q) => $q->where('cycle', 'preprimaire'))->get();

        foreach ($preprimaireClasses as $class) {
            // Le prenom suit le sexe : sinon le jeu de demonstration melange
            // prenoms feminins et sexe masculin.
            $sexe = fake()->randomElement(['male', 'female']);
            Teacher::create([
                'employee_id' => $this->prochainMatricule(),
                'first_name' => fake()->firstName($sexe),
                'last_name' => fake()->lastName(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake()->phoneNumber(),
                'date_of_birth' => fake()->date('Y-m-d', '-25 years'),
                'gender' => $sexe,
                'address' => fake()->address(),
                'qualification' => 'Diplôme en éducation préscolaire',
                'specialization' => null,
                'cycle' => 'preprimaire',
                'teacher_type' => 'general',
                'assigned_class_id' => $class->id,
                'hire_date' => fake()->date('Y-m-d', '-2 years'),
                'salary' => rand(80000, 120000),
                'status' => 'active',
            ]);
        }

        // Enseignants pour le primaire (généralistes)
        $primaireClasses = SchoolClass::whereHas('level', fn($q) => $q->where('cycle', 'primaire'))->get();

        foreach ($primaireClasses as $class) {
            // Le prenom suit le sexe : sinon le jeu de demonstration melange
            // prenoms feminins et sexe masculin.
            $sexe = fake()->randomElement(['male', 'female']);
            Teacher::create([
                'employee_id' => $this->prochainMatricule(),
                'first_name' => fake()->firstName($sexe),
                'last_name' => fake()->lastName(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake()->phoneNumber(),
                'date_of_birth' => fake()->date('Y-m-d', '-30 years'),
                'gender' => $sexe,
                'address' => fake()->address(),
                'qualification' => 'Licence en éducation primaire',
                'specialization' => null,
                'cycle' => 'primaire',
                'teacher_type' => 'general',
                'assigned_class_id' => $class->id,
                'hire_date' => fake()->date('Y-m-d', '-3 years'),
                'salary' => rand(90000, 140000),
                'status' => 'active',
            ]);
        }

        // Enseignants pour le collège (spécialisés)
        $collegeSubjects = ['Mathématiques', 'Français', 'Histoire-Géographie', 'Sciences', 'Anglais', 'EPS'];
        
        foreach ($collegeSubjects as $subject) {
            // Le prenom suit le sexe : sinon le jeu de demonstration melange
            // prenoms feminins et sexe masculin.
            $sexe = fake()->randomElement(['male', 'female']);
            Teacher::create([
                'employee_id' => $this->prochainMatricule(),
                'first_name' => fake()->firstName($sexe),
                'last_name' => fake()->lastName(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake()->phoneNumber(),
                'date_of_birth' => fake()->date('Y-m-d', '-35 years'),
                'gender' => $sexe,
                'address' => fake()->address(),
                'qualification' => 'Master en ' . $subject,
                'specialization' => $subject,
                'cycle' => 'college',
                'teacher_type' => 'specialized',
                'assigned_class_id' => null,
                'hire_date' => fake()->date('Y-m-d', '-4 years'),
                'salary' => rand(100000, 160000),
                'status' => 'active',
            ]);
        }

        // Enseignants pour le lycée (spécialisés) - inactifs pour l'instant
        $lyceeSubjects = ['Mathématiques', 'Physique-Chimie', 'SVT', 'Histoire-Géographie', 'Philosophie', 'Langues'];
        
        foreach ($lyceeSubjects as $subject) {
            // Le prenom suit le sexe : sinon le jeu de demonstration melange
            // prenoms feminins et sexe masculin.
            $sexe = fake()->randomElement(['male', 'female']);
            Teacher::create([
                'employee_id' => $this->prochainMatricule(),
                'first_name' => fake()->firstName($sexe),
                'last_name' => fake()->lastName(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake()->phoneNumber(),
                'date_of_birth' => fake()->date('Y-m-d', '-40 years'),
                'gender' => $sexe,
                'address' => fake()->address(),
                'qualification' => 'Master en ' . $subject,
                'specialization' => $subject,
                'cycle' => 'lycee',
                'teacher_type' => 'specialized',
                'assigned_class_id' => null,
                'hire_date' => fake()->date('Y-m-d', '-5 years'),
                'salary' => rand(120000, 180000),
                'status' => 'inactive', // Inactif car le lycée n'est pas encore ouvert
            ]);
        }

        $this->command->info('Enseignants créés avec succès !');
    }
}
