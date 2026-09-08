<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `classes.series` était une chaîne libre, sans lien avec la table `series`
 * pourtant remplie de vingt séries. Le modèle `Series::classes()` visait déjà
 * une colonne `series_id` qui n'a jamais existé : toute lecture de la relation
 * — la fiche d'une série, le contrôle avant suppression — partait en erreur SQL.
 *
 * On crée la clé étrangère attendue, on reprend les valeurs textuelles
 * existantes quand elles correspondent à une série connue, puis on retire la
 * colonne texte pour qu'il ne reste qu'une seule source.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('series_id')->nullable()->after('level_id')
                ->constrained('series')->nullOnDelete();
        });

        // Reprise des valeurs saisies avant l'existence du lien. Le rapprochement
        // se fait sur le nom puis sur le code ; ce qui ne correspond à rien reste
        // vide plutôt que d'inventer une série.
        foreach (DB::table('classes')->whereNotNull('series')->get(['id', 'series']) as $classe) {
            $serie = DB::table('series')
                ->where('name', $classe->series)
                ->orWhere('code', $classe->series)
                ->first();

            if ($serie) {
                DB::table('classes')->where('id', $classe->id)->update(['series_id' => $serie->id]);
            }
        }

        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('series');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->string('series')->nullable()->after('level_id');
        });

        foreach (DB::table('classes')->whereNotNull('series_id')->get(['id', 'series_id']) as $classe) {
            $nom = DB::table('series')->where('id', $classe->series_id)->value('name');
            DB::table('classes')->where('id', $classe->id)->update(['series' => $nom]);
        }

        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropColumn('series_id');
        });
    }
};
