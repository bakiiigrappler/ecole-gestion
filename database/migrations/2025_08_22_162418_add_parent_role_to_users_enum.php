<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sur une base neuve, 2025_08_21_081615_add_role_to_users_table cree deja
        // l'enum avec « parent » : il n'y a rien a rattraper. Cette migration ne
        // sert qu'aux bases MySQL creees avant cet ajout, et la syntaxe MODIFY
        // COLUMN ... ENUM n'existe que chez MySQL et MariaDB.
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'teacher', 'secretary', 'parent') DEFAULT 'teacher'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'teacher', 'secretary') DEFAULT 'teacher'");
    }
};
