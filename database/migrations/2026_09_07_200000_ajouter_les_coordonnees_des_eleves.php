<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Coordonnées propres à l'élève.
 *
 * Le dossier d'inscription collectait déjà `applicant_phone` et
 * `applicant_email`, et deux endroits du code tentaient de les recopier sur
 * l'élève — mais les colonnes n'existaient pas et Eloquent ignorait
 * silencieusement les affectations. Un élève n'avait donc jamais de
 * coordonnées propres : seul son dossier en portait.
 *
 * On les reprend depuis la dernière inscription qui en déclare, quand il y en a.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('address');
            $table->string('email')->nullable()->after('phone');
        });

        // Reprise depuis l'inscription la plus recente qui porte l'information.
        DB::statement("
            UPDATE students s
            SET phone = c.applicant_phone,
                email = c.applicant_email
            FROM (
                SELECT DISTINCT ON (student_id)
                       student_id, applicant_phone, applicant_email
                FROM enrollments
                WHERE student_id IS NOT NULL
                  AND (applicant_phone IS NOT NULL OR applicant_email IS NOT NULL)
                ORDER BY student_id, enrollment_date DESC
            ) AS c
            WHERE c.student_id = s.id
        ");
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['phone', 'email']);
        });
    }
};
