<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le service d'orientation : le répertoire, et les dossiers.
 *
 * Deux moments décident d'une scolarité gabonaise : le passage de la 3ème au
 * lycée — général, technique ou professionnel, et dans quel établissement — et
 * celui de la terminale vers le supérieur. Ils se jouaient sur un conseil oral,
 * un formulaire papier et la réputation d'un établissement ; l'élève choisissait
 * sans savoir ce que ses notes permettaient, et l'école décidait sans garder
 * trace de ce qu'elle avait conseillé.
 *
 * Deux tables, donc.
 *
 * `orientation_etablissements` : ce vers quoi on oriente — lycées, universités,
 * grandes écoles, centres professionnels. Le répertoire est national : un
 * `school_id` nul signifie qu'il vaut pour toute la plateforme ; renseigné, il
 * s'agit d'un établissement ajouté par une école pour elle seule.
 *
 * `orientation_dossiers` : le vœu d'un élève pour une année. Il porte le profil
 * calculé sur ses notes réelles — c'est là toute la différence avec un
 * questionnaire où l'on saisit ses moyennes de mémoire — la voie et la filière
 * choisies, jusqu'à trois établissements classés par ordre de préférence, et la
 * décision motivée du conseiller.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orientation_etablissements', function (Blueprint $table) {
            $table->id();

            // Nul : le répertoire national, visible de tous les établissements.
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();

            $table->string('nom');
            $table->string('sigle', 40)->nullable();
            $table->string('type', 30);          // lycee, universite, ecole_superieure, centre_professionnel
            $table->string('statut', 20)->default('public');  // public, prive, confessionnel
            $table->string('ville')->nullable();
            $table->string('quartier')->nullable();

            // Pour classer les lycées par proximité du domicile de l'élève.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->unsignedInteger('capacite')->nullable();
            $table->unsignedInteger('inscrits')->nullable();
            $table->unsignedTinyInteger('age_min')->nullable();
            $table->unsignedTinyInteger('age_max')->nullable();

            // Séries du lycée, ou filières du supérieur selon le type.
            $table->json('filieres')->nullable();
            $table->text('description')->nullable();
            $table->string('telephone', 40)->nullable();
            $table->string('site', 255)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'ville']);
            $table->index('school_id');
        });

        Schema::create('orientation_dossiers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();

            $table->string('niveau', 20);        // troisieme, terminale
            $table->string('serie_actuelle', 20)->nullable();

            /*
             * Le profil et les moyennes sont figés au moment de la soumission :
             * un dossier doit pouvoir être relu dans dix ans tel qu'il a été
             * décidé, même si les notes ont changé depuis.
             */
            $table->string('profil', 30)->nullable();
            $table->json('moyennes')->nullable();

            $table->string('voie', 40)->nullable();      // generale, technique_industrielle, technique_gestion, professionnelle, superieur
            $table->string('filiere')->nullable();
            $table->string('mode_admission', 20)->nullable();  // moyenne, concours, dossier

            // Jusqu'à trois établissements, classés : [{rang, etablissement_id, filiere}]
            $table->json('voeux')->nullable();

            $table->string('statut', 20)->default('brouillon');  // brouillon, soumis, accorde, refuse
            $table->text('commentaire_eleve')->nullable();

            $table->string('motif_code', 40)->nullable();
            $table->text('motif_precision')->nullable();
            $table->foreignId('decide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decide_le')->nullable();
            $table->timestamp('soumis_le')->nullable();

            // L'élève a-t-il pris connaissance de la décision ?
            $table->timestamp('decision_vue_le')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Un dossier par élève et par année : le second vœu remplace le premier.
            $table->unique(['student_id', 'academic_year_id']);
            $table->index(['school_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orientation_dossiers');
        Schema::dropIfExists('orientation_etablissements');
    }
};
