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
        if (Examen::exists()) {
            $this->command?->info('Des sessions d’examen existent déjà : rien à poser.');

            return;
        }

        $this->poserLeBaccalaureat();
        $this->poserLeBrevet();
    }

    /**
     * Le baccalauréat, mené jusqu'à la proclamation.
     *
     * C'est la session qui montre le bout de la chaîne : délibération, procès-
     * verbal scellé, résultats consultables par numéro de candidat.
     */
    private function poserLeBaccalaureat(): void
    {
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
        $candidats = $this->inscrireUnNiveau($examen, $centres, 'terminale');

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
     * Le brevet, laissé en pleine correction.
     *
     * Une session proclamée ferme la saisie : le panel du correcteur n'y
     * montrerait qu'un tableau en lecture seule. Celle-ci reste ouverte, avec
     * un paquet à moitié corrigé — c'est l'écran que l'enseignant convoqué a
     * réellement sous les yeux, et c'est celui qu'il faut pouvoir juger.
     */
    private function poserLeBrevet(): void
    {
        $annee = (int) now()->year;

        $examen = Examen::create([
            'nom' => 'Brevet d’études du premier cycle, session '.$annee,
            'type' => 'bepc',
            'annee' => $annee,
            'statut' => 'correction',
            'debut_epreuves' => now()->subDays(8)->toDateString(),
            'fin_epreuves' => now()->subDays(5)->toDateString(),
            'moyenne_admission' => 10,
            'moyenne_second_tour' => 8,
        ]);

        foreach ([
            ['Mathématiques', 4, 20, 180],
            ['Français', 4, 20, 180],
            ['Sciences physiques', 2, 20, 120],
            ['Sciences de la vie et de la terre', 2, 20, 120],
            ['Histoire-Géographie', 2, 20, 120],
            ['Anglais', 2, 20, 120],
        ] as $rang => [$intitule, $coefficient, $bareme, $duree]) {
            $examen->epreuves()->create([
                'intitule' => $intitule,
                'coefficient' => $coefficient,
                'bareme' => $bareme,
                'duree_minutes' => $duree,
                'date_epreuve' => $examen->debut_epreuves?->copy()->addDays(intdiv($rang, 2)),
                'ordre' => $rang + 1,
            ]);
        }

        $centres = $this->poserLesCentres($examen);
        $candidats = $this->inscrireUnNiveau($examen, $centres, 'troisieme');

        if ($candidats === 0) {
            $this->command?->warn('Aucun élève de 3ème : la session du brevet reste vide.');

            return;
        }

        $this->ajouterDesCandidatsLibres($examen, $centres);
        $this->tirerLAnonymat($examen);

        $correcteurs = $this->convoquerDesCorrecteurs($examen, $centres, 0.5);

        // À moitié corrigé : les premières épreuves sont faites, les dernières
        // attendent. C'est l'état normal d'une session, et celui qui donne au
        // correcteur quelque chose à faire.
        $notes = $this->corriger($examen, $correcteurs, 0.5);

        $this->command?->info(sprintf(
            'Session « %s » : %d candidats, %d notes saisies, correction en cours.',
            $examen->nom,
            $examen->candidats()->count(),
            $notes
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
     * Les élèves d'un niveau deviennent candidats.
     *
     * Le niveau commande l'examen : la terminale passe le baccalauréat, la
     * troisième le brevet. Inscrire les uns à l'examen des autres n'aurait
     * aucun sens, et le peuplement ne doit pas raconter une scolarité fausse.
     */
    private function inscrireUnNiveau(Examen $examen, $centres, string $niveau = 'terminale'): int
    {
        $motif = $niveau === 'troisieme' ? '%3%' : '%erminale%';

        $eleves = Student::withoutGlobalScopes()
            ->whereHas('enrollments', fn ($q) => $q
                ->where('status', 'active')
                ->whereHas('schoolClass.level', fn ($n) => $n->where('name', 'like', $motif)))
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
                'numero_candidat' => $this->prefixe($examen)
                    .str_pad((string) $rang, 5, '0', STR_PAD_LEFT),
                'nom' => $eleve->last_name,
                'prenom' => $eleve->first_name,
                'date_naissance' => $eleve->date_of_birth,
                'lieu_naissance' => $eleve->place_of_birth,
                'sexe' => $eleve->gender,
                'serie' => $examen->type === 'bepc'
                    ? null
                    : ($eleve->enrollments->firstWhere('status', 'active')?->schoolClass?->serie?->code ?? 'TERM-C'),
                'etablissement_origine' => $eleve->school->name ?? null,
                'statut' => 'present',
            ]);
        }

        return $rang;
    }

    /**
     * Quelques candidats libres : ils existent, et ils ne sont rattachés à
     * aucun établissement. Un module qui ne les prévoirait pas laisserait une
     * part des candidats réels à la porte.
     */
    private function ajouterDesCandidatsLibres(Examen $examen, $centres): void
    {
        $libres = [
            ['Ondo', 'Christelle', '2008-03-14', 'Libreville'],
            ['Mba', 'Serge', '2007-11-02', 'Oyem'],
            ['Nguema', 'Prisca', '2008-07-21', 'Port-Gentil'],
        ];

        $rang = $examen->candidats()->count();

        foreach ($libres as [$nom, $prenom, $naissance, $lieu]) {
            $rang++;

            $examen->candidats()->create([
                'student_id' => null,
                'school_id' => null,
                'centre_id' => $centres[$rang % $centres->count()]->id,
                'numero_candidat' => $this->prefixe($examen).str_pad((string) $rang, 5, '0', STR_PAD_LEFT),
                'nom' => $nom,
                'prenom' => $prenom,
                'date_naissance' => $naissance,
                'lieu_naissance' => $lieu,
                'etablissement_origine' => 'Candidat libre',
                'statut' => 'present',
            ]);
        }
    }

    /** Le préfixe d'un numéro de candidat : « BAC26- », « BEP26- ». */
    private function prefixe(Examen $examen): string
    {
        return strtoupper(substr(str_replace('_', '', $examen->type), 0, 3))
            .substr((string) $examen->annee, -2).'-';
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
     * Un correcteur par épreuve, réparti sur plusieurs enseignants.
     *
     * Les épreuves qui restent à corriger passent d'abord aux enseignants qui
     * ont un compte : un paquet confié à quelqu'un qui ne peut pas se connecter
     * est un paquet que personne ne corrigera. Les autres épreuves vont aux
     * enseignants convoqués sans compte — c'est le cas courant, et la liste des
     * correcteurs doit le montrer plutôt que de poser tout le jury sur un seul
     * nom.
     */
    private function convoquerDesCorrecteurs(Examen $examen, $centres, float $part = 1.0)
    {
        $avecCompte = Teacher::withoutGlobalScopes()
            ->whereNotNull('user_id')
            ->inRandomOrder()
            ->get();

        $sansCompte = Teacher::withoutGlobalScopes()
            ->whereNull('user_id')
            ->inRandomOrder()
            ->take(max(1, $examen->epreuves->count()))
            ->get();

        if ($avecCompte->isEmpty() && $sansCompte->isEmpty()) {
            return collect();
        }

        $epreuves = $examen->epreuves->values();
        $faites = (int) ceil($epreuves->count() * $part);

        // Deux blocs : ce qui reste à corriger, puis ce qui est déjà fait.
        // Chacun recommence par les enseignants qui ont un compte, de sorte
        // qu'un correcteur connecté ait à la fois un paquet terminé et un
        // paquet en cours.
        $correcteurs = collect();

        foreach ([$epreuves->slice($faites), $epreuves->take($faites)] as $bloc) {
            foreach ($bloc->values() as $rang => $epreuve) {
                $enseignant = $avecCompte[$rang]
                    ?? $sansCompte[($rang - $avecCompte->count()) % max(1, $sansCompte->count())]
                    ?? $avecCompte->first();

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
        }

        // Un président de jury par centre : c'est lui qui signera.
        $president = $avecCompte->first() ?? $sansCompte->first();

        foreach ($centres as $centre) {
            if (! $president) {
                continue;
            }

            ExamenCorrecteur::create([
                'examen_id' => $examen->id,
                'epreuve_id' => null,
                'centre_id' => $centre->id,
                'teacher_id' => $president->id,
                'user_id' => $president->user_id,
                'school_id' => $president->school_id,
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
    private function corriger(Examen $examen, $correcteurs, float $part = 1.0): int
    {
        $parEpreuve = $correcteurs->keyBy('epreuve_id');
        $notes = [];
        $maintenant = now();

        // Les épreuves corrigées d'abord, dans l'ordre : une session à moitié
        // faite l'est épreuve par épreuve, pas copie par copie au hasard.
        $corrigees = $examen->epreuves->take((int) ceil($examen->epreuves->count() * $part));

        foreach ($examen->candidats()->get() as $candidat) {
            // Un niveau par candidat, autour duquel les notes gravitent : sans
            // cela, toutes les moyennes se vaudraient et la délibération
            // n'aurait rien à trancher.
            $niveau = mt_rand(55, 150) / 10;

            foreach ($corrigees as $epreuve) {
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

        // Seuls ceux dont l'épreuve est finie ont terminé.
        foreach ($correcteurs as $correcteur) {
            if ($corrigees->contains('id', $correcteur->epreuve_id)) {
                $correcteur->forceFill(['statut' => 'termine', 'termine_le' => now()])->save();
            }
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
