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
        Schema::create('pre_primary_competency_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('pre_primary_competency_id');
            // La table parente pre_primary_competencies est creee par la migration
            // suivante (090244). Sur une base neuve elle n'existe pas encore : la cle
            // etrangere est alors posee par cette migration-la.
            if (Schema::hasTable('pre_primary_competencies')) {
                $table->foreign('pre_primary_competency_id', 'pp_comp_eval_comp_id_fk')->references('id')->on('pre_primary_competencies')->onDelete('cascade');
            }
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            
            // Évaluations par trimestre - Codes: MAX (Maximale), MIN (Minimale), PART (Partielle), NM (Non Maîtrise)
            $table->enum('trimester_1_code', ['MAX', 'MIN', 'PART', 'NM'])->nullable();
            $table->enum('trimester_2_code', ['MAX', 'MIN', 'PART', 'NM'])->nullable();
            $table->enum('trimester_3_code', ['MAX', 'MIN', 'PART', 'NM'])->nullable();
            
            // Commentaires par trimestre
            $table->text('trimester_1_comment')->nullable();
            $table->text('trimester_2_comment')->nullable();
            $table->text('trimester_3_comment')->nullable();
            
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index(['student_id', 'academic_year_id'], 'pp_eval_student_year_idx');
            $table->index(['class_id', 'academic_year_id'], 'pp_eval_class_year_idx');
            $table->unique(['student_id', 'pre_primary_competency_id', 'academic_year_id'], 'pp_eval_unique_student_comp_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_primary_competency_evaluations');
    }
};
