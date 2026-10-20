<?php

namespace Database\Seeders;

use App\Models\Examen;
use App\Models\ExamenCandidat;
use App\Models\ExamenCorrecteur;
use App\Models\ExamenNote;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\Examens\Deliberation;
use App\Support\Examens\ResultatsPublies;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Une session d'examen complète, de l'inscription à la proclamation.
 *
 * Un module d'examen ne se juge pas sur des écrans vides : on ne voit ni ce
 * qu'un paquet de copies donne à l'écran du correcteur, ni ce qu'une
 * délibération produit, ni comment se présente un relevé. Ce peuplement pose
 * donc une session entière — baccalauréat, série C — avec ses épreuves, deux
 * centres, les terminales de la plateforme comme candidats, des correcteurs
 * convoqués parmi les enseignants, toutes les copies notées, la délibération
 * prononcée, le procès-verbal scellé et les résultats proclamés.
 *
 * Elle sert aussi de banc d'essai : c'est sur elle qu'on mesure si la
 * consultation tient la charge.
 */
class SessionExamenDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Examen::where('type', 'bac_general')->exists()) {
            $this->command?->info('Une session de baccalauréat existe déjà : rien à poser.');

            return;
        }

        $annee = (int) now()->year;

        $examen = Examen::create([
            'nom' => 'Baccalauréat général, session '.$annee,
            'type' => 'bac_general',
            'annee' => $annee,
            'serie' => 'C',
            'statut' => 'correction',
            'debut_epreuves' => now()->subDays(30)->toDateString(),
            'fin_epreuves' => now()->subDays(25)->toDateString(),
            'moyenne_admission' => 10,
            'moyenne_second_tour' => 8,
        ]);

        $this->poserLesEpreuves($examen);
        $centres = $this->poserLesCentres($examen);
        $candidats = $this->inscrireLesTerminales($examen, $centres);

        if ($candidats === 0) {
            $this->command?->warn('Aucun élève de terminale : la session reste vide.');

            return;
        }

        $this->tirerLAnonymat($examen);
        $correcteurs = $this->convoquerDesCorrecteurs($examen, $centres);
        $notes = $this->corriger($examen, $correcteurs);

        $bilan = Deliberation::prononcer($examen);
        $examen->forceFill(['statut' => 'deliberation'])->save();

        $this->signerEtProclamer($examen);

        $this->command?->info(sprintf(
            'Session « %s » : %d candidats, %d notes, %d admis, %d au second tour, %d ajournés — proclamée.',
            $examen->nom,
            $bilan['candidats'],
            $notes,
            $bilan['admis'],
            $bilan['second_tour'],
            $bilan['ajournes']
        ));
    }

    /**
     * Les épreuves d'une série C : les sciences y pèsent lourd, et c'est tout
     * le sens de la série.
     */
    private function poserLesEpreuves(Examen $examen): void
    {
        $epreuves = [
            ['Mathématiques', 6, 20, 240],
            ['Sciences physiques', 5, 20, 240],
            ['Sciences de la vie et de la terre', 3, 20, 180],
            ['Français', 3, 20, 240],
            ['Philosophie', 2, 20, 240],
            ['Anglais', 2, 20, 120],
            ['Histoire-Géographie', 2, 20, 180],
            ['Éducation physique et sportive', 1, 20, 60],
        ];

        foreach ($epreuves as $rang => [$intitule, $coefficient, $bareme, $duree]) {
            $examen->epreuves()->create([
                'intitule' => $intitule,
                'coefficient' => $coefficient,
                'bareme' => $bareme,
                'duree_minutes' => $duree,
                'date_epreuve' => $examen->debut_epreuves?->copy()->addDays(intdiv($rang, 2)),
                'ordre' => $rang + 1,
            ]);
        }
    }

    private function poserLesCentres(Examen $examen)
    {
        $ecoles = School::orderBy('id')->take(2)->get();

        $centres = collect();

        foreach ($ecoles as $rang => $ecole) {
            $centres->push($examen->centres()->create([
                'nom' => 'Centre de '.$ecole->name,
                'code' => 'C'.str_pad((string) ($rang + 1), 2, '0', STR_PAD_LEFT),
                'ville' => $ecole->city ?: 'Libreville',
                'school_id' => $ecole->id,
                'capacite' => 400,
                'president_jury' => 'Le Proviseur de '.$ecole->name,
            ]));
        }

        if ($centres->isEmpty()) {
            $centres->push($examen->centres()->create([
                'nom' => 'Centre unique',
                'code' => 'C01',
                'ville' => 'Libreville',
                'capacite' => 400,
            ]));
        }

        return $centres;
    }

    /**
     * Les élèves de terminale de la plateforme deviennent candidats.
     */
    private function inscrireLesTerminales(Examen $examen, $centres): int
    {
        $eleves = Student::withoutGlobalScopes()
            ->whereHas('enrollments', fn ($q) => $q
                ->where('status', 'active')
                ->whereHas('schoolClass.level', fn ($n) => $n->where('name', 'like', '%erminale%')))
            ->with(['enrollments.schoolClass.serie', 'school:id,name'])
            ->get();

        $rang = 0;

        foreach ($eleves as $eleve) {
            $rang++;

            $examen->candidats()->create([
                'student_id' => $eleve->id,
                'school_id' => $eleve->school_id,
                // Les candidats se répartissent entre les centres : un centre
                // unique ne montrerait pas le regroupement par centre.
                'centre_id' => $centres[$rang % $centres->count()]->id,
                'numero_candidat' => 'BAC'.substr((string) $examen->annee, -2).'-'
                    .str_pad((string) $rang, 5, '0', STR_PAD_LEFT),
                'nom' => $eleve->last_name,
                'prenom' => $eleve->first_name,
                'date_naissance' => $eleve->date_of_birth,
                'lieu_naissance' => $eleve->place_of_birth,
                'sexe' => $eleve->gender,
                'serie' => $eleve->enrollments->firstWhere('status', 'active')?->schoolClass?->serie?->code ?? 'TERM-C',
                'etablissement_origine' => $eleve->school->name ?? null,
                'statut' => 'present',
            ]);
        }

        return $rang;
    }

    private function tirerLAnonymat(Examen $examen): void
    {
        $candidats = $examen->candidats()->get();
        $numeros = collect(range(1, $candidats->count()))->shuffle();

        foreach ($candidats->values() as $rang => $candidat) {
            $candidat->forceFill([
                'numero_anonymat' => 'A'.str_pad((string) $numeros[$rang], 5, '0', STR_PAD_LEFT),
            ])->save();
        }
    }

    /**
     * Un correcteur par épreuve, pris chez les enseignants qui ont un compte.
     *
     * Avec un compte, parce qu'un correcteur sans compte ne peut pas saisir :
     * la démonstration montrerait un paquet que personne ne peut corriger.
     */
    private function convoquerDesCorrecteurs(Examen $examen, $centres)
    {
        $enseignants = Teacher::withoutGlobalScopes()
            ->whereNotNull('user_id')
            ->inRandomOrder()
            ->take(max(1, $examen->epreuves->count()))
            ->get();

        if ($enseignants->isEmpty()) {
            $enseignants = Teacher::withoutGlobalScopes()->take(1)->get();
        }

        $correcteurs = collect();

        foreach ($examen->epreuves as $rang => $epreuve) {
            $enseignant = $enseignants[$rang % max(1, $enseignants->count())] ?? null;

            if (! $enseignant) {
                continue;
            }

            $correcteurs->push(ExamenCorrecteur::create([
                'examen_id' => $examen->id,
                'epreuve_id' => $epreuve->id,
                // Sans centre : il corrige tout le paquet de son épreuve.
                'centre_id' => null,
                'teacher_id' => $enseignant->id,
                'user_id' => $enseignant->user_id,
                'school_id' => $enseignant->school_id,
                'nom' => trim($enseignant->first_name.' '.$enseignant->last_name),
                'role' => 'correcteur',
                'statut' => 'accepte',
                'convoque_le' => now()->subDays(20),
            ]));
        }

        // Un président de jury par centre : c'est lui qui signera.
        foreach ($centres as $centre) {
            $enseignant = $enseignants->first();

            if (! $enseignant) {
                continue;
            }

            ExamenCorrecteur::create([
                'examen_id' => $examen->id,
                'epreuve_id' => null,
                'centre_id' => $centre->id,
                'teacher_id' => $enseignant->id,
                'user_id' => $enseignant->user_id,
                'school_id' => $enseignant->school_id,
                'nom' => $centre->president_jury ?: 'Président du jury',
                'role' => 'president_jury',
                'statut' => 'accepte',
                'convoque_le' => now()->subDays(20),
            ]);
        }

        return $correcteurs;
    }

    /**
     * Toutes les copies notées, avec un niveau propre à chaque candidat.
     */
    private function corriger(Examen $examen, $correcteurs): int
    {
        $parEpreuve = $correcteurs->keyBy('epreuve_id');
        $notes = [];
        $maintenant = now();

        foreach ($examen->candidats()->get() as $candidat) {
            // Un niveau par candidat, autour duquel les notes gravitent : sans
            // cela, toutes les moyennes se vaudraient et la délibération
            // n'aurait rien à trancher.
            $niveau = mt_rand(55, 150) / 10;

            foreach ($examen->epreuves as $epreuve) {
                $note = max(0, min($epreuve->bareme, $niveau + mt_rand(-30, 30) / 10));

                $notes[] = [
                    'examen_id' => $examen->id,
                    'epreuve_id' => $epreuve->id,
                    'candidat_id' => $candidat->id,
                    'correcteur_id' => $parEpreuve[$epreuve->id]->id ?? null,
                    'note' => round($note, 2),
                    'absent' => false,
                    'saisie_par' => $parEpreuve[$epreuve->id]->user_id ?? null,
                    'saisie_le' => $maintenant,
                    'created_at' => $maintenant,
                    'updated_at' => $maintenant,
                ];
            }
        }

        foreach (array_chunk($notes, 500) as $lot) {
            ExamenNote::insert($lot);
        }

        foreach ($correcteurs as $correcteur) {
            $correcteur->forceFill(['statut' => 'termine', 'termine_le' => now()])->save();
        }

        return count($notes);
    }

    private function signerEtProclamer(Examen $examen): void
    {
        $signataire = User::where('role', 'superadmin')->first();

        $examen->documents()->create([
            'type' => 'proces_verbal',
            'intitule' => 'Procès-verbal de délibération — '.$examen->intitule_complet,
            'empreinte' => \App\Models\ExamenDocument::empreinteDu($examen),
            'signe_par' => $signataire?->id,
            'signataire_nom' => $signataire?->name ?? 'Direction des examens',
            'signataire_qualite' => 'Président du jury national',
            'signe_le' => now()->subDay(),
            'mode_signature' => 'electronique',
            'jeton_verification' => strtoupper(Str::random(12)),
        ]);

        $examen->forceFill([
            'statut' => 'proclame',
            'proclamation' => now()->subDay()->toDateString(),
        ])->save();

        ResultatsPublies::reconstruire($examen);
    }
}
