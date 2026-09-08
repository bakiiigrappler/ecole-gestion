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
        Schema::create('pre_primary_competencies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Code unique pour la compétence
            $table->string('name'); // Nom de la compétence
            $table->text('description')->nullable(); // Description détaillée
            $table->string('domain'); // Domaine de compétence (ex: Langue orale, EPS, etc.)
            $table->integer('sort_order')->default(0); // Ordre d'affichage
            $table->boolean('is_active')->default(true); // Compétence active ou non
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('domain');
            $table->index(['domain', 'sort_order']);
        });

        // Pose la cle etrangere laissee en attente par la migration 090211,
        // qui cree la table enfant avant celle-ci.
        if (Schema::hasTable('pre_primary_competency_evaluations')) {
            Schema::table('pre_primary_competency_evaluations', function (Blueprint $table) {
                $table->foreign('pre_primary_competency_id', 'pp_comp_eval_comp_id_fk')->references('id')->on('pre_primary_competencies')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_primary_competencies');
    }
};
