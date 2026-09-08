<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Passage en multi-établissements.
 *
 * L'application ne connaissait qu'une école : aucune table ne portait de clé
 * d'appartenance, et `school_settings` tenait sur une ligne unique. On
 * introduit ici la table `schools`, on y bascule l'établissement existant, et
 * on rattache toutes les données du domaine.
 *
 * Les référentiels nationaux — compétences du primaire et du préprimaire,
 * critères, passerelles de paiement — restent **communs** à tous les
 * établissements : les dupliquer par école n'aurait aucun sens.
 */
return new class extends Migration
{
    /**
     * Tables du domaine, rattachées à un établissement.
     *
     * Les tables de liaison (class_teacher, student_parent, subject_teacher)
     * en sont absentes : elles tiennent leur appartenance de leurs deux bouts.
     */
    private const TABLES = [
        'academic_years',
        'attendances',
        'class_fees',
        'classes',
        'enrollment_fees',
        'enrollments',
        'fees',
        'grades',
        'level_fees',
        'levels',
        'online_payments',
        'parent_accounts',
        'parents',
        'payment_refunds',
        'payments',
        'pre_primary_competency_evaluations',
        'schedules',
        'school_settings',
        'series',
        'student_competency_evaluations',
        'student_grades',
        'students',
        'subjects',
        'teachers',
        'users',
    ];

    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();

            // Un établissement peut porter les quatre cycles à la fois.
            $table->boolean('has_preprimaire')->default(false);
            $table->boolean('has_primaire')->default(false);
            $table->boolean('has_college')->default(false);
            $table->boolean('has_lycee')->default(false);

            // Désactiver ferme l'accès sans rien effacer.
            $table->boolean('is_active')->default(true);

            $table->string('city')->nullable();
            $table->string('country')->default('Gabon');
            $table->string('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('bp', 100)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'name']);
        });

        // L'établissement existant devient le premier de la liste.
        $existant = DB::table('school_settings')->first();

        $premier = DB::table('schools')->insertGetId([
            'name' => $existant->school_name ?? 'Établissement scolaire',
            'code' => 'ETB001',
            'has_preprimaire' => (bool) ($existant->has_preprimary ?? true),
            'has_primaire' => (bool) ($existant->has_primary ?? true),
            'has_college' => (bool) ($existant->has_secondary ?? true),
            'has_lycee' => (bool) ($existant->has_secondary ?? true),
            'is_active' => true,
            'city' => $existant->city ?? null,
            'country' => $existant->country ?? 'Gabon',
            'address' => $existant->school_address ?? null,
            'phone' => $existant->school_phone ?? null,
            'email' => $existant->school_email ?? null,
            'bp' => $existant->school_bp ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (self::TABLES as $nom) {
            if (! Schema::hasTable($nom) || Schema::hasColumn($nom, 'school_id')) {
                continue;
            }

            Schema::table($nom, function (Blueprint $table) {
                $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->index('school_id');
            });

            // Toutes les données existantes appartiennent au premier
            // établissement — sauf le superadmin, qui les surplombe tous.
            DB::table($nom)
                ->when($nom === 'users', fn ($q) => $q->where('role', '!=', 'superadmin'))
                ->update(['school_id' => $premier]);
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $nom) {
            if (! Schema::hasTable($nom) || ! Schema::hasColumn($nom, 'school_id')) {
                continue;
            }

            Schema::table($nom, function (Blueprint $table) {
                $table->dropConstrainedForeignId('school_id');
            });
        }

        Schema::dropIfExists('schools');
    }
};
