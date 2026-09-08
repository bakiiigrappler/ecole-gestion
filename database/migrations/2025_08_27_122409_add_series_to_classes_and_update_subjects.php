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
        // Ajouter le champ série aux classes pour le lycée
        Schema::table('classes', function (Blueprint $table) {
            $table->string('series')->nullable()->after('level_id')->comment('Série pour le lycée (S, A1, A2, B, C, D, etc.)');
        });

        // Modifier les matières pour les lier au cycle au lieu du niveau
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropColumn('level_id');
            /*
             * Une valeur par defaut, et non une colonne simplement NOT NULL :
             * SQLite refuse categoriquement d'ajouter une colonne NOT NULL sans
             * defaut — « Cannot add a NOT NULL column with default value NULL ».
             * PostgreSQL l'accepte sur une table vide, ce qui masquait le
             * probleme jusqu'au premier deploiement sur SQLite.
             *
             * « college » est le cycle le plus courant ; les matieres existantes
             * sont de toute facon reaffectees par les seeders et par l'ecran des
             * matieres.
             */
            $table->string('cycle')->default('college')->after('description')->comment('Cycle auquel appartient la matière (primaire, college, lycee)');
            $table->json('series')->nullable()->after('cycle')->comment('Séries concernées pour le lycée (JSON array)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['cycle', 'series']);
            $table->foreignId('level_id')->constrained('levels')->onDelete('cascade');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('series');
        });
    }
};