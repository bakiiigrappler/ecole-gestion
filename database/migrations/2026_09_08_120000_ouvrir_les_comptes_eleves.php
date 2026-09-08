<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Comptes élèves.
 *
 * Décidé avec l'utilisateur : **seuls les élèves du lycée** ont un compte, et
 * ils se connectent avec leur **matricule**. Deux ajouts suffisent — le rôle
 * `student` dans la contrainte des rôles, et le lien de l'élève vers son
 * compte.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pgsql = Schema::getConnection()->getDriverName() === 'pgsql';

        if ($pgsql) {
            // La contrainte de la colonne `role` est un CHECK : elle refuserait
            // « student » tant qu'il n'y figure pas.
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['superadmin', 'admin', 'teacher', 'secretary', 'parent', 'student']::text[]))");
        }

        /*
         * Le courriel n'est plus obligatoire : un élève se connecte par son
         * matricule, et n'a pas nécessairement d'adresse.
         *
         * SQLite ne sait pas modifier une colonne en place ; `change()` s'en
         * charge en reconstruisant la table, et vaut sur les deux moteurs.
         */
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        if (! Schema::hasColumn('students', 'user_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('student_id')
                    ->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'user_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        DB::table('users')->where('role', 'student')->delete();

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['superadmin', 'admin', 'teacher', 'secretary', 'parent']::text[]))");
        }
    }
};
