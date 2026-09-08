<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aptitude de l'élève.
 *
 * Un élève peut être déclaré inapte — le plus souvent à l'éducation physique,
 * parfois plus largement. L'information n'existait nulle part : elle se
 * retrouvait noyée dans le champ libre « informations médicales », donc
 * introuvable et impossible à filtrer.
 *
 * Le motif est obligatoire dès que l'élève est inapte : une inaptitude sans
 * raison n'est pas exploitable par l'équipe pédagogique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('fitness_status', 20)->default('apte')->after('medical_conditions');
            $table->text('unfitness_reason')->nullable()->after('fitness_status');
        });

        // Contrainte de cohérence : les deux seules valeurs admises, et un motif
        // present des que l'eleve est declare inapte.
        DB::statement("
            ALTER TABLE students
            ADD CONSTRAINT students_fitness_status_check
            CHECK (fitness_status IN ('apte', 'inapte'))
        ");

        DB::statement("
            ALTER TABLE students
            ADD CONSTRAINT students_unfitness_reason_check
            CHECK (fitness_status <> 'inapte' OR unfitness_reason IS NOT NULL)
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE students DROP CONSTRAINT IF EXISTS students_unfitness_reason_check');
        DB::statement('ALTER TABLE students DROP CONSTRAINT IF EXISTS students_fitness_status_check');

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['fitness_status', 'unfitness_reason']);
        });
    }
};
