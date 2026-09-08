<?php

use App\Support\Roles;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les titres de direction gabonais entrent dans les rôles.
 *
 * Un établissement n'a pas seulement un « administrateur » : il a un
 * **directeur** s'il s'agit d'une école primaire ou préprimaire, un
 * **proviseur** pour un collège ou un lycée, et un **censeur** qui le seconde.
 * Ces titres désignent des fonctions réelles, inscrites sur les documents
 * officiels — le bulletin porte déjà « Le Proviseur » en bas de page.
 *
 * La colonne `role` est tenue par une contrainte CHECK : elle refuserait ces
 * valeurs tant qu'elles n'y figurent pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        self::poserLaContrainte(array_keys(Roles::CATALOGUE));
    }

    public function down(): void
    {
        // Les comptes portant un titre de direction redeviennent administrateurs :
        // les supprimer priverait l'établissement de son chef.
        DB::table('users')
            ->whereIn('role', ['directeur', 'proviseur', 'censeur'])
            ->update(['role' => 'admin']);

        self::poserLaContrainte(['superadmin', 'admin', 'teacher', 'secretary', 'parent', 'student']);
    }

    /**
     * SQLite ne sait pas modifier une contrainte de table : il faudrait
     * reconstruire `users` entière. La règle y est tenue par la validation des
     * formulaires, qui lit le même catalogue.
     */
    private static function poserLaContrainte(array $roles): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $liste = implode(', ', array_map(fn ($r) => "'{$r}'", $roles));

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY[{$liste}]::text[]))");
    }
};
