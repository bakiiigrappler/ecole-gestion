<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSuperAdminSeeder::class,
            AdminUserSeeder::class,
            LevelSeeder::class,
            SubjectSeeder::class,
            AcademicYearSeeder::class,
            ClassSeeder::class,
            TeacherSeeder::class,
            ParentSeeder::class,
            StudentSeeder::class,
            EnrollmentSeeder::class,
            FeeSeeder::class,
            AttendanceSeeder::class,
        ]);
    }
}
