<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La corbeille : une suppression cesse d'être définitive.
 *
 * Supprimer un élève effaçait sa fiche, ses notes et ses inscriptions sans
 * retour possible — une fausse manœuvre coûtait un dossier. Ces tables passent
 * en suppression douce : la ligne reste, marquée d'une date, et le super
 * administrateur peut la restaurer ou la détruire pour de bon.
 *
 * Les unicités restent posées sur les lignes supprimées : un matricule occupé
 * par un élève en corbeille n'est pas réutilisable tant qu'il y séjourne.
 * C'est voulu — c'est ce qui permet de le restaurer intact.
 */
return new class extends Migration
{
    /** Les tables qui gagnent une corbeille. */
    private const TABLES = [
        'students',
        'teachers',
        'classes',
        'parents',
        'subjects',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'deleted_at')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->softDeletes();
                $t->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'deleted_at')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['deleted_at', 'deleted_by']);
            });
        }
    }
};
