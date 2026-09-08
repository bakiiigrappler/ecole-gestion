<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Relie un compte de connexion à sa fiche enseignant.
 *
 * Rien ne les rattachait : un compte de rôle `teacher` ne désignait aucun
 * enseignant en particulier, il était donc impossible de savoir quelles
 * classes, quelles matières et quelles heures le concernent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('teachers', 'user_id')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('employee_id')
                    ->constrained('users')->nullOnDelete();
            });
        }

        // Rattacher ce qui peut l'être sans ambiguïté : même adresse courriel.
        DB::statement("
            UPDATE teachers
               SET user_id = users.id
              FROM users
             WHERE users.role = 'teacher'
               AND users.email IS NOT NULL
               AND LOWER(users.email) = LOWER(teachers.email)
               AND teachers.user_id IS NULL
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('teachers', 'user_id')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
