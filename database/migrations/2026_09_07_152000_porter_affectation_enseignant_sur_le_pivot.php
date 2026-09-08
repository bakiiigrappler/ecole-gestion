<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * L'affectation d'un enseignant à une classe décrit un lien, pas l'enseignant.
 *
 * Deux mécanismes la portaient sans jamais se synchroniser :
 *   - `teachers.assigned_class_id`, rempli par la fiche enseignant ;
 *   - le pivot `class_teacher` et son rôle, rempli par l'écran des classes.
 *
 * Les 16 affectations existantes ne figuraient que du premier côté, si bien que
 * la liste des classes annonçait « aucun enseignant affecté » pour toutes.
 * Seul le pivot subsiste : être professeur principal y devient un rôle.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Report des affectations vers le pivot, sans écraser un rôle existant.
        $affectations = DB::table('teachers')
            ->whereNotNull('assigned_class_id')
            ->select('id', 'assigned_class_id')
            ->get();

        $maintenant = now();

        foreach ($affectations as $affectation) {
            $dejaPresent = DB::table('class_teacher')
                ->where('teacher_id', $affectation->id)
                ->where('class_id', $affectation->assigned_class_id)
                ->exists();

            if ($dejaPresent) {
                continue;
            }

            DB::table('class_teacher')->insert([
                'teacher_id' => $affectation->id,
                'class_id' => $affectation->assigned_class_id,
                'role' => 'principal',
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ]);
        }

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropForeign(['assigned_class_id']);
            $table->dropColumn('assigned_class_id');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->foreignId('assigned_class_id')->nullable()->constrained('classes')->nullOnDelete();
        });

        // On restitue la classe où l'enseignant est professeur principal.
        DB::statement("
            update teachers set assigned_class_id = (
                select ct.class_id from class_teacher ct
                where ct.teacher_id = teachers.id and ct.role = 'principal'
                order by ct.id limit 1
            )
        ");
    }
};
