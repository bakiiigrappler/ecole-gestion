<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le numéro de l'opérateur, et l'interrupteur de chacun.
 *
 * Toutes les écoles n'ont pas de compte marchand : beaucoup encaissent sur un
 * simple numéro Airtel Money ou Moov Money, par transfert. Le code marchand
 * seul ne suffisait donc pas à décrire où payer.
 *
 * Chaque opérateur reçoit aussi son propre interrupteur : une école peut
 * n'avoir de compte que chez l'un des deux, ou fermer temporairement l'un
 * d'eux sans effacer ses coordonnées — les décocher ne doit pas obliger à les
 * ressaisir plus tard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->boolean('airtel_money_actif')->default(true);
            $table->string('airtel_money_numero', 40)->nullable();

            $table->boolean('moov_money_actif')->default(true);
            $table->string('moov_money_numero', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn([
                'airtel_money_actif',
                'airtel_money_numero',
                'moov_money_actif',
                'moov_money_numero',
            ]);
        });
    }
};
