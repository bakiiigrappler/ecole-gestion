<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer le compte Super Administrateur
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@ecole.com'],
            [
                'name' => 'Super Administrateur',
                'password' => Hash::make('superadmin123'),
                'role' => 'superadmin',
                'is_active' => true,
                'matricule' => 'SUPER001',
                'email_verified_at' => now(),
            ]
        );

        // Créer le compte Administrateur
        $admin = User::firstOrCreate(
            ['email' => 'admin@ecole.com'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
                'matricule' => 'ADMIN001',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Comptes administrateurs créés avec succès :');
        $this->command->info('Super Admin - Email: superadmin@ecole.com - Mot de passe: superadmin123');
        $this->command->info('Admin - Email: admin@ecole.com - Mot de passe: admin123');
    }
}
