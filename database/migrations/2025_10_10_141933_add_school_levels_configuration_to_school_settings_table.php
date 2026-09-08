<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ajouter la configuration flexible pour gérer différents niveaux scolaires
     * avec des noms différents (Complexe scolaire pour primaire, Collège/Lycée pour secondaire)
     */
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            // Configuration des niveaux actifs
            $table->boolean('has_preprimary')->default(true)->after('school_level')
                  ->comment('Active le niveau préprimaire (maternelle)');
            $table->boolean('has_primary')->default(true)->after('has_preprimary')
                  ->comment('Active le niveau primaire');
            $table->boolean('has_secondary')->default(true)->after('has_primary')
                  ->comment('Active le niveau secondaire (collège/lycée)');
            
            // Noms spécifiques selon le niveau
            $table->string('primary_school_name')->nullable()->after('school_name')
                  ->comment('Nom du complexe scolaire (pour préprimaire et primaire)');
            $table->string('secondary_school_name')->nullable()->after('primary_school_name')
                  ->comment('Nom du collège/lycée (pour secondaire)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn([
                'has_preprimary',
                'has_primary',
                'has_secondary',
                'primary_school_name',
                'secondary_school_name'
            ]);
        });
    }
};
