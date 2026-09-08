<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les unicités deviennent propres à chaque établissement.
 *
 * Elles étaient globales, héritées de l'école unique : deux établissements ne
 * pouvaient pas avoir la même année scolaire « 2026-2027 », ni un niveau
 * « 6EME », ni deux élèves porter le même matricule. Ce sont pourtant des
 * référentiels internes à chaque école.
 *
 * `users.email` et `users.matricule` restent **globalement uniques** : ce sont
 * des identifiants de connexion, ils doivent désigner une personne et une
 * seule sur toute l'application.
 */
return new class extends Migration
{
    /** Contrainte → colonnes qui la composent, une fois ramenée à l'école. */
    private const CONTRAINTES = [
        'academic_years' => ['academic_years_name_unique', 'name'],
        'levels' => ['levels_code_unique', 'code'],
        'series' => ['series_code_unique', 'code'],
        'students' => ['students_student_id_unique', 'student_id'],
        'subjects' => ['subjects_code_unique', 'code'],
        'teachers_email' => ['teachers_email_unique', 'email'],
        'teachers_matricule' => ['teachers_employee_id_unique', 'employee_id'],
    ];

    public function up(): void
    {
        foreach (self::CONTRAINTES as $cle => [$contrainte, $colonne]) {
            $table = str_contains($cle, '_') && ! in_array($cle, ['academic_years'], true)
                ? explode('_', $cle)[0]
                : $cle;

            // Le nom de la table se déduit du préfixe pour les tables qui
            // portent deux contraintes.
            $table = in_array($cle, ['teachers_email', 'teachers_matricule'], true) ? 'teachers' : $cle;

            /*
             * PostgreSQL pose ces unicités comme des contraintes de table ;
             * SQLite, lui, ne connaît que les index — et n'a pas de
             * `DROP CONSTRAINT`. Le résultat est le même dans les deux cas :
             * un index unique sur (school_id, colonne).
             */
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$contrainte}");
            } else {
                DB::statement("DROP INDEX IF EXISTS {$contrainte}");
            }

            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS {$contrainte} ON {$table} (school_id, {$colonne})");
        }
    }

    public function down(): void
    {
        foreach (self::CONTRAINTES as $cle => [$contrainte, $colonne]) {
            $table = in_array($cle, ['teachers_email', 'teachers_matricule'], true) ? 'teachers' : $cle;

            DB::statement("DROP INDEX IF EXISTS {$contrainte}");
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$contrainte} UNIQUE ({$colonne})");
        }
    }
};
