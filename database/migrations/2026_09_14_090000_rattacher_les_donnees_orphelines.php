<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rattraper les données qui n'appartiennent à aucun établissement.
 *
 * La migration qui a ouvert le multi-établissements crée le premier
 * établissement puis lui rattache tout l'existant. Sur une base neuve, elle
 * passe sur des tables vides : le peuplement vient après, et il naissait sans
 * établissement — le trait qui pose `school_id` lit l'utilisateur connecté, et
 * un seeder n'en a pas. Les bases déjà déployées portent donc des milliers de
 * lignes sans rattachement : le cloisonnement n'y cloisonne rien, et le portail
 * parent y répond 404.
 *
 * Le peuplement est corrigé, mais une base déjà en service ne se repeuple pas.
 * Cette migration la répare : ce qui n'a pas d'établissement rejoint le
 * premier. Les établissements ouverts depuis, eux, portent déjà le leur — ils
 * ne sont pas touchés.
 */
return new class extends Migration
{
    /**
     * Le super administrateur excepté : il surplombe les établissements.
     */
    private const TABLES = [
        'academic_years', 'attendances', 'class_fees', 'classes', 'enrollment_fees',
        'enrollments', 'fees', 'grades', 'level_fees', 'levels', 'online_payments',
        'parent_accounts', 'parents', 'payment_refunds', 'payments',
        'pre_primary_competency_evaluations', 'schedules', 'school_settings', 'series',
        'student_competency_evaluations', 'student_grades', 'students', 'subjects',
        'teachers', 'users',
    ];

    public function up(): void
    {
        $premier = DB::table('schools')->orderBy('id')->value('id');

        if (! $premier) {
            return;
        }

        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'school_id')) {
                continue;
            }

            DB::table($table)
                ->whereNull('school_id')
                ->when($table === 'users', fn ($q) => $q->where('role', '!=', 'superadmin'))
                ->update(['school_id' => $premier]);
        }
    }

    /**
     * Rien à défaire : on ne saurait pas distinguer les lignes rattachées ici
     * de celles qui l'étaient déjà, et les délier reviendrait à rouvrir la
     * faille.
     */
    public function down(): void
    {
    }
};
