<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte d'unicité
            $table->dropUnique(['student_id', 'class_id', 'attendance_date']);
            
            // Ajouter la nouvelle contrainte d'unicité qui inclut time_slot
            $table->unique(['student_id', 'class_id', 'attendance_date', 'time_slot'], 'attendances_unique_constraint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Supprimer la nouvelle contrainte
            $table->dropUnique('attendances_unique_constraint');
            
            // Remettre l'ancienne contrainte
            $table->unique(['student_id', 'class_id', 'attendance_date']);
        });
    }
};
