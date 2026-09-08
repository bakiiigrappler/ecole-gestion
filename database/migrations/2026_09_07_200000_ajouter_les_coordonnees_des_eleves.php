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

        /*
         * Reprise depuis l'inscription la plus récente qui porte l'information.
         *
         * `UPDATE ... FROM` et `DISTINCT ON` n'existent que chez PostgreSQL.
         * La reprise par sous-requêtes corrélées dit la même chose et vaut
         * partout ; elle est seulement plus lente, ce qui est sans importance
         * pour un rattrapage qui ne s'exécute qu'une fois.
         */
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
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

            return;
        }

        DB::statement("
            UPDATE students
            SET phone = (
                    SELECT applicant_phone FROM enrollments e
                    WHERE e.student_id = students.id
                    ORDER BY e.enrollment_date DESC LIMIT 1
                ),
                email = (
                    SELECT applicant_email FROM enrollments e
                    WHERE e.student_id = students.id
                    ORDER BY e.enrollment_date DESC LIMIT 1
                )
            WHERE EXISTS (
                SELECT 1 FROM enrollments e
                WHERE e.student_id = students.id
                  AND (e.applicant_phone IS NOT NULL OR e.applicant_email IS NOT NULL)
            )
        ");
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['phone', 'email']);
        });
    }
};
