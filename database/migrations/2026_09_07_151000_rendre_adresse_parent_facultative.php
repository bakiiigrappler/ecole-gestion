<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `parents.address` était NOT NULL alors que le formulaire et la validation la
 * donnent pour facultative : enregistrer un parent sans adresse renvoyait une
 * erreur 500, sans message exploitable.
 *
 * La colonne suit désormais l'intention du formulaire. Une adresse n'est pas
 * toujours connue au moment où l'on saisit un responsable légal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->text('address')->nullable()->change();
        });
    }

    public function down(): void
    {
        // On ne peut pas revenir à NOT NULL sans valeur pour les lignes vides.
        Schema::table('parents', function (Blueprint $table) {
            $table->text('address')->nullable(false)->default('')->change();
        });
    }
};
