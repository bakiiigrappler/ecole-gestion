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
            // Ajouter un champ pour identifier les frais de scolarité de base
            $table->boolean('is_base_tuition')->default(false)->after('is_general');
            
            // Ajouter un index
            $table->index('is_base_tuition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('level_fees', function (Blueprint $table) {
            // Supprimer l'index
            $table->dropIndex(['is_base_tuition']);
            
            // Supprimer la colonne
            $table->dropColumn('is_base_tuition');
        });
    }
};