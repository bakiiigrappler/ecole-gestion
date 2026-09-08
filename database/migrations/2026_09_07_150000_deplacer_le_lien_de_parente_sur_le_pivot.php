<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le lien de parenté et les autorisations décrivent la relation entre un
 * parent et un enfant, pas la personne : un même adulte peut être père de son
 * fils et tuteur de son neveu, contact principal pour l'un et pas pour l'autre.
 *
 * Les colonnes vivaient aux deux niveaux — sur `parents` et sur le pivot
 * `student_parent` — et divergeaient déjà sur plus de la moitié des liens.
 * Cette migration ne garde que le pivot, qui est le bon niveau et celui que
 * les fiches d'élève lisaient déjà.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. L'autorisation de récupérer l'enfant rejoint le lien.
        Schema::table('student_parent', function (Blueprint $table) {
            $table->boolean('can_pickup')->default(true)->after('lives_with_student');
        });

        // 2. Report de la valeur portée par le parent sur chacun de ses liens.
        if (Schema::hasColumn('parents', 'can_pickup')) {
            DB::table('student_parent')->update([
                'can_pickup' => DB::raw(
                    '(select p.can_pickup from parents p where p.id = student_parent.parent_id)'
                ),
            ]);
        }

        // 3. Les colonnes redondantes quittent la table `parents`.
        //    `relationship_type` et `is_primary_contact` du pivot font foi.
        Schema::table('parents', function (Blueprint $table) {
            $table->dropColumn(['relationship', 'is_primary_contact', 'can_pickup']);
        });
    }

    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->string('relationship')->default('father');
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('can_pickup')->default(true);
        });

        // On reconstitue une valeur par parent à partir de son premier lien :
        // l'information par enfant ne peut pas être restituée telle quelle.
        DB::statement("
            update parents set
                relationship = coalesce((
                    select sp.relationship_type from student_parent sp
                    where sp.parent_id = parents.id order by sp.id limit 1
                ), 'father'),
                is_primary_contact = coalesce((
                    select sp.is_primary_contact from student_parent sp
                    where sp.parent_id = parents.id order by sp.id limit 1
                ), false),
                can_pickup = coalesce((
                    select sp.can_pickup from student_parent sp
                    where sp.parent_id = parents.id order by sp.id limit 1
                ), true)
        ");

        Schema::table('student_parent', function (Blueprint $table) {
            $table->dropColumn('can_pickup');
        });
    }
};
