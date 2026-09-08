<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_competency_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('competency_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('teacher_id');
            $table->integer('palier'); // 1, 2, 3, 4, 5 (périodes d'évaluation)
            $table->date('evaluation_date');
            
            // Points par critère (C1, C2, C3, C4)
            $table->integer('c1_points')->default(0);
            $table->integer('c2_points')->default(0);
            $table->integer('c3_points')->default(0);
            $table->integer('c4_points')->default(0);
            
            // Points maximum par critère
            $table->integer('c1_max_points')->default(0);
            $table->integer('c2_max_points')->default(0);
            $table->integer('c3_max_points')->default(0);
            $table->integer('c4_max_points')->default(0);
            
            // Total des points obtenus pour la compétence
            $table->integer('total_points_obtained')->default(0);
            $table->integer('total_points_max')->default(0);
            
            // Évaluations qualitatives
            $table->enum('competency_mastery', ['maximale', 'minimale', 'partielle', 'non_maitrise'])->nullable();
            $table->enum('subject_mastery', ['maximale', 'minimale', 'partielle', 'non_maitrise'])->nullable();
            $table->enum('palier_mastery', ['maximale', 'minimale', 'partielle', 'non_maitrise'])->nullable();
            
            // Profil de sortie (synthèse annuelle)
            $table->boolean('is_exit_profile')->default(false);
            $table->enum('exit_profile', ['maximale', 'minimale', 'partielle', 'non_maitrise'])->nullable();
            
            $table->text('comments')->nullable();
            $table->timestamps();
            
            // Index pour les performances
            $table->index(['student_id', 'competency_id', 'palier'], 'sce_student_competency_palier');
            $table->index(['class_id', 'academic_year_id', 'palier'], 'sce_class_year_palier');
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_competency_evaluations');
    }
};
