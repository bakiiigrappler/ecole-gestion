<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les coordonnées mobile money de l'établissement.
 *
 * Au Gabon, la scolarité se règle en grande partie par Airtel Money et Moov
 * Money : le parent fait un paiement marchand, puis apporte le SMS de
 * confirmation au secrétariat. Le portail lui affichait pourtant un formulaire
 * de carte bancaire, et rien du numéro à composer.
 *
 * Ces colonnes portent ce qu'il faut afficher : le code marchand de chaque
 * opérateur, le nom sous lequel le compte apparaît à l'écran du téléphone — le
 * parent doit pouvoir vérifier qu'il paie bien son école — et, au besoin, des
 * consignes propres à l'établissement. Elles se remplissent depuis
 * « Paramètres établissement », par l'administrateur de l'école : le numéro
 * n'est pas le même d'un établissement à l'autre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->boolean('mobile_money_actif')->default(false);

            $table->string('airtel_money_code', 60)->nullable();
            $table->string('airtel_money_nom', 120)->nullable();

            $table->string('moov_money_code', 60)->nullable();
            $table->string('moov_money_nom', 120)->nullable();

            // Consignes propres à l'établissement : elles remplacent la marche
            // à suivre par défaut lorsqu'elles sont renseignées.
            $table->text('mobile_money_consignes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn([
                'mobile_money_actif',
                'airtel_money_code',
                'airtel_money_nom',
                'moov_money_code',
                'moov_money_nom',
                'mobile_money_consignes',
            ]);
        });
    }
};
