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
        Schema::table('enrollments', function (Blueprint $table) {
            // Champ pour indiquer si c'est une réinscription
            $table->boolean('is_reinscription')->default(false)->after('is_new_enrollment');
            
            // Matricule de l'élève pour la réinscription (pour vérifier l'historique)
            $table->string('reinscription_student_id')->nullable()->after('is_reinscription');
            
            // Statut de l'élève : nouveau, redoublant, ou passant
            $table->enum('student_status', ['nouveau', 'redoublant', 'passant'])->default('nouveau')->after('reinscription_student_id');
            
            // Classe précédente (pour les réinscriptions)
            $table->unsignedBigInteger('previous_class_id')->nullable()->after('student_status');
            
            // Année scolaire précédente
            $table->unsignedBigInteger('previous_academic_year_id')->nullable()->after('previous_class_id');
            
            // Résultat de l'année précédente (admis/redouble)
            $table->enum('previous_year_result', ['admis', 'redouble', 'non_applicable'])->default('non_applicable')->after('previous_academic_year_id');
            
            // Moyenne générale de l'année précédente
            $table->decimal('previous_year_average', 5, 2)->nullable()->after('previous_year_result');
            
            // Commentaires sur le statut
            $table->text('status_comments')->nullable()->after('previous_year_average');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'is_reinscription',
                'reinscription_student_id',
                'student_status',
                'previous_class_id',
                'previous_academic_year_id',
                'previous_year_result',
                'previous_year_average',
                'status_comments'
            ]);
        });
    }
};
