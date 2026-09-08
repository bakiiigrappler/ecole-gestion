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
        Schema::table('students', function (Blueprint $table) {
            // Statut actuel de l'élève
            $table->enum('current_status', ['actif', 'ancien', 'transfere', 'diplome'])->default('actif')->after('status');
            
            // Nombre total d'inscriptions
            $table->integer('total_enrollments')->default(0)->after('current_status');
            
            // Nombre de redoublements
            $table->integer('total_redoublements')->default(0)->after('total_enrollments');
            
            // Première année d'inscription
            $table->unsignedBigInteger('first_enrollment_year_id')->nullable()->after('total_redoublements');
            
            // Dernière année d'inscription
            $table->unsignedBigInteger('last_enrollment_year_id')->nullable()->after('first_enrollment_year_id');
            
            // Indicateur si l'élève a déjà été inscrit
            $table->boolean('has_been_enrolled')->default(false)->after('last_enrollment_year_id');
            
            // Date de la dernière inscription
            $table->date('last_enrollment_date')->nullable()->after('has_been_enrolled');
            
            // Commentaires sur l'historique
            $table->text('history_comments')->nullable()->after('last_enrollment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'current_status',
                'total_enrollments',
                'total_redoublements',
                'first_enrollment_year_id',
                'last_enrollment_year_id',
                'has_been_enrolled',
                'last_enrollment_date',
                'history_comments'
            ]);
        });
    }
};