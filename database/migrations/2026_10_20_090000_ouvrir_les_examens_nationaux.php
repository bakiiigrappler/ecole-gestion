<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les examens nationaux : CEP, BEPC, baccalauréat.
 *
 * Un examen national n'appartient à aucun établissement — c'est l'inverse :
 * les établissements y envoient leurs candidats, et leurs enseignants y sont
 * convoqués pour corriger. Ces tables vivent donc au niveau de la plateforme,
 * sans `school_id`, et le cloisonnement se fait par ce que chaque table porte :
 * un établissement voit les candidats qu'il a inscrits, un correcteur voit les
 * copies de son épreuve, un candidat voit son relevé.
 *
 * Sept tables, dans l'ordre où elles se remplissent :
 *
 *   `examens`            la session — « Baccalauréat général, session 2027 »
 *   `examen_epreuves`    ce qui se compose, avec son coefficient
 *   `examen_centres`     où l'on compose
 *   `examen_candidats`   qui compose, sous quel numéro, dans quel centre
 *   `examen_correcteurs` qui corrige quoi, et où
 *   `examen_notes`       une note par copie et par correcteur
 *   `examen_documents`   le procès-verbal, scellé ou signé à la main
 *
 * L'anonymat des copies tient à une colonne : `numero_anonymat`. Le correcteur
 * ne voit jamais le nom du candidat, seulement ce numéro — c'est ainsi que se
 * corrige un examen, et une application qui afficherait le nom rendrait la
 * correction attaquable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examens', function (Blueprint $table) {
            $table->id();

            $table->string('nom');
            $table->string('type', 20);              // cep, bepc, bac_general, bac_technique
            $table->unsignedSmallInteger('annee');
            $table->string('serie', 20)->nullable(); // pour le bac : A1, B, C, D, E, F, G

            /*
             * Le fil de la session. Chaque étape ouvre ce que la précédente a
             * rendu possible : on ne corrige pas avant d'avoir composé, on ne
             * délibère pas sur des copies non corrigées, et on ne proclame pas
             * un résultat que personne n'a signé.
             */
            $table->string('statut', 20)->default('preparation');
            // preparation, inscriptions, epreuves, correction, deliberation, signe, proclame

            $table->date('debut_epreuves')->nullable();
            $table->date('fin_epreuves')->nullable();
            $table->date('proclamation')->nullable();

            // Moyenne d'admission, et seuil du second tour.
            $table->decimal('moyenne_admission', 4, 2)->default(10);
            $table->decimal('moyenne_second_tour', 4, 2)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['type', 'annee', 'serie']);
            $table->index('statut');
        });

        Schema::create('examen_epreuves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examens')->cascadeOnDelete();

            $table->string('intitule');                        // « Mathématiques »
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->decimal('coefficient', 4, 1)->default(1);
            $table->unsignedSmallInteger('bareme')->default(20);
            $table->unsignedSmallInteger('duree_minutes')->nullable();
            $table->date('date_epreuve')->nullable();
            $table->time('heure_debut')->nullable();

            // Une épreuve facultative ne compte que pour les points au-dessus
            // de la moyenne : la règle doit être portée par l'épreuve.
            $table->boolean('facultative')->default(false);

            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['examen_id', 'ordre']);
        });

        Schema::create('examen_centres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examens')->cascadeOnDelete();

            $table->string('nom');
            $table->string('code', 20)->nullable();
            $table->string('ville')->nullable();

            // L'établissement qui héberge le centre, s'il est de la plateforme.
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();

            $table->unsignedInteger('capacite')->nullable();
            $table->string('president_jury')->nullable();
            $table->timestamps();

            $table->index(['examen_id', 'nom']);
        });

        Schema::create('examen_candidats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examens')->cascadeOnDelete();

            // L'élève de la plateforme, ou null pour un candidat libre.
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('centre_id')->nullable()->constrained('examen_centres')->nullOnDelete();

            // Ce que le candidat saisit sur KEWA : son numéro et sa naissance.
            $table->string('numero_candidat', 30);

            /*
             * Le numéro d'anonymat : seul lien entre une copie et un candidat
             * pendant la correction. Il se tire à l'ouverture des épreuves et
             * ne se lève qu'à la délibération.
             */
            $table->string('numero_anonymat', 30)->nullable();

            $table->string('nom');
            $table->string('prenom');
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->string('sexe', 10)->nullable();
            $table->string('serie', 20)->nullable();
            $table->string('etablissement_origine')->nullable();

            $table->string('statut', 20)->default('inscrit');
            // inscrit, present, absent, exclu, admis, second_tour, ajourne

            $table->decimal('moyenne', 5, 2)->nullable();
            $table->decimal('total_points', 7, 2)->nullable();
            $table->string('mention', 30)->nullable();
            $table->text('observation')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['examen_id', 'numero_candidat']);
            $table->unique(['examen_id', 'numero_anonymat']);
            $table->index(['examen_id', 'statut']);
            $table->index(['examen_id', 'school_id']);
            $table->index('student_id');
        });

        Schema::create('examen_correcteurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examens')->cascadeOnDelete();
            $table->foreignId('epreuve_id')->nullable()->constrained('examen_epreuves')->cascadeOnDelete();
            $table->foreignId('centre_id')->nullable()->constrained('examen_centres')->nullOnDelete();

            // L'enseignant convoqué, et son compte pour qu'il puisse saisir.
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();

            $table->string('nom')->nullable();
            $table->string('role', 20)->default('correcteur'); // correcteur, president_jury, secretaire

            $table->string('statut', 20)->default('convoque'); // convoque, accepte, excuse, termine
            $table->timestamp('convoque_le')->nullable();
            $table->timestamp('termine_le')->nullable();

            $table->timestamps();

            $table->unique(['examen_id', 'epreuve_id', 'teacher_id'], 'examen_correcteur_unique');
            $table->index(['examen_id', 'user_id']);
        });

        Schema::create('examen_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examens')->cascadeOnDelete();
            $table->foreignId('epreuve_id')->constrained('examen_epreuves')->cascadeOnDelete();
            $table->foreignId('candidat_id')->constrained('examen_candidats')->cascadeOnDelete();
            $table->foreignId('correcteur_id')->nullable()->constrained('examen_correcteurs')->nullOnDelete();

            $table->decimal('note', 5, 2)->nullable();
            $table->boolean('absent')->default(false);
            $table->text('observation')->nullable();

            // Qui a saisi, et quand : une note d'examen se conteste.
            $table->foreignId('saisie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('saisie_le')->nullable();

            $table->timestamps();

            $table->unique(['epreuve_id', 'candidat_id']);
            $table->index(['examen_id', 'epreuve_id']);
        });

        Schema::create('examen_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examens')->cascadeOnDelete();
            $table->foreignId('centre_id')->nullable()->constrained('examen_centres')->nullOnDelete();

            $table->string('type', 30);   // proces_verbal, releve_centre, attestation
            $table->string('intitule');

            /*
             * Le scellement électronique : l'empreinte du contenu au moment de
             * la signature, l'identité du signataire et l'heure. Qui change une
             * note après coup casse l'empreinte, et la vérification le dit.
             */
            $table->string('empreinte', 64)->nullable();
            $table->foreignId('signe_par')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signataire_nom')->nullable();
            $table->string('signataire_qualite')->nullable();
            $table->timestamp('signe_le')->nullable();

            // Le signataire peut préférer imprimer, signer à la main et déposer
            // le scan : le document fait foi de la même façon.
            $table->string('fichier')->nullable();
            $table->string('mode_signature', 20)->nullable(); // electronique, manuscrite

            $table->string('jeton_verification', 40)->nullable();

            $table->timestamps();

            $table->index(['examen_id', 'type']);
            $table->unique('jeton_verification');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examen_documents');
        Schema::dropIfExists('examen_notes');
        Schema::dropIfExists('examen_correcteurs');
        Schema::dropIfExists('examen_candidats');
        Schema::dropIfExists('examen_centres');
        Schema::dropIfExists('examen_epreuves');
        Schema::dropIfExists('examens');
    }
};
