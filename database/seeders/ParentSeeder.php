<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ParentModel;

class ParentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 50; $i++) {
            // Le prenom decoule du sexe : sans cela le jeu de demonstration
            // produisait des « Sabine » declarees peres.
            $sexe = fake()->randomElement(['male', 'female']);

            ParentModel::create([
                'first_name' => fake()->firstName($sexe),
                'last_name' => fake()->lastName(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake()->phoneNumber(),
                'phone_2' => fake()->phoneNumber(),
                'gender' => $sexe,
                'address' => fake()->address(),
                'profession' => fake()->jobTitle(),
                'workplace' => fake()->company(),
            ]);
        }

        $this->command->info('Parents créés avec succès !');
    }
}
