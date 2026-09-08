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
        Schema::table('level_fees', function (Blueprint $table) {
            // Rendre level_id nullable pour permettre les frais généraux
            $table->foreignId('level_id')->nullable()->change();
            
            // Ajouter un champ pour le cycle (pour les frais par cycle)
            $table->enum('cycle', ['preprimaire', 'primaire', 'college', 'lycee'])->nullable()->after('level_id');
            
            // Ajouter un champ pour indiquer si c'est un frais général
            $table->boolean('is_general')->default(false)->after('cycle');
            
            // Ajouter un index pour le cycle
            $table->index('cycle');
            $table->index('is_general');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('level_fees', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['cycle']);
            $table->dropIndex(['is_general']);
            
            // Supprimer les colonnes
            $table->dropColumn(['cycle', 'is_general']);
            
            // Remettre level_id en non nullable
            $table->foreignId('level_id')->nullable(false)->change();
        });
    }
};