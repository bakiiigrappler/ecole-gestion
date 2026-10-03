<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\OrientationDossier;
use App\Models\OrientationEtablissement;
use App\Models\ParentModel;
use App\Models\Student;
use App\Support\AccesEleve;
use App\Support\Orientation\Conseil;
use App\Support\Orientation\MotifsOrientation;
use App\Support\Orientation\ProfilEleve;
use App\Support\Orientation\VoiesApresTerminale;
use App\Support\Orientation\VoiesApresTroisieme;
use App\Support\Roles;
use Illuminate\Http\Request;

/**
 * Le service d'orientation : de la 3ème au lycée, de la terminale au supérieur.
 *
 * Deux passages décident d'une scolarité, et l'école ne les accompagnait pas.
 * L'élève de 3ème choisissait une seconde sans savoir ce que ses notes
 * ouvraient ; celui de terminale listait des universités sans savoir ce que sa
 * série y permettait. Le conseil tenait dans une phrase dite en fin d'année, et
 * rien n'en restait.
 *
 * Ici, le profil se calcule sur les notes réelles de l'année — l'établissement
 * les a déjà, personne ne ressaisit rien — et chaque filière dit ce qu'elle
 * exige. L'élève pose jusqu'à trois vœux, le conseiller tranche en motivant, et
 * le dossier garde trace de tout.
 */
class OrientationController extends Controller
{
    private const MAX_VOEUX = 3;

    /* ==================================================================
       Côté élève et parent
       ================================================================== */

    /**
     * Le dossier d'un élève : son profil, ce qui s'ouvre, ses vœux.
     */
    public function monDossier(Request $request)
    {
        $eleve = $this->eleveConcerne($request);

        return $this->rendreLeDossier($eleve, $request);
    }

    /**
     * Enregistrer la voie et la filière choisies, ou les vœux.
     */
    public function enregistrer(Request $request)
    {
        $eleve = $this->eleveConcerne($request);
        $dossier = $this->dossier($eleve);

        if ($dossier->estDecide()) {
            return back()->with('error', 'Ce dossier a été tranché : il ne se modifie plus.');
        }

        $niveau = $this->niveau($eleve);

        abort_unless($niveau, 403, 'L’orientation concerne les classes de 3ème et de terminale.');

        $donnees = $request->validate([
            'voie' => 'nullable|string|max:40',
            'filiere' => 'nullable|string|max:255',
            'commentaire_eleve' => 'nullable|string|max:1000',
            'voeux' => 'nullable|array|max:'.self::MAX_VOEUX,
            'voeux.*' => 'nullable|integer',
            'filieres_voeux' => 'nullable|array',
            'filieres_voeux.*' => 'nullable|string|max:255',
        ]);

        $profil = ProfilEleve::etablir($eleve, $this->annee());

        // Les vœux retenus, dans l'ordre donné, sans doublon ni case vide.
        $choisis = collect($donnees['voeux'] ?? [])->filter()->unique()->values();

        $etablissements = OrientationEtablissement::visiblesPar()
            ->whereIn('id', $choisis)
            ->get()
            ->keyBy('id');

        $voeux = $choisis
            ->filter(fn ($id) => $etablissements->has($id))
            ->values()
            ->map(fn ($id, $rang) => [
                'rang' => $rang + 1,
                'etablissement_id' => (int) $id,
                // Le nom est recopié : un établissement retiré du répertoire
                // ne doit pas rendre le vœu illisible.
                'nom' => $etablissements[$id]->nom,
                'filiere' => $donnees['filieres_voeux'][$id] ?? ($donnees['filiere'] ?? null),
            ])
            ->all();

        $filiere = $donnees['filiere'] ?? null;
        $voie = $niveau === 'troisieme' ? ($donnees['voie'] ?? null) : 'superieur';

        $dossier->fill([
            'niveau' => $niveau,
            'serie_actuelle' => $this->serie($eleve),
            'profil' => $profil['profil'],
            'moyennes' => $this->moyennesARetenir($profil),
            'voie' => $voie,
            'filiere' => $filiere,
            'mode_admission' => $niveau === 'troisieme'
                ? Conseil::modeAdmission($profil['generale'], VoiesApresTroisieme::filiere($voie, $filiere))
                : 'dossier',
            'voeux' => $voeux,
            'commentaire_eleve' => $donnees['commentaire_eleve'] ?? null,
        ])->save();

        return back()->with('success', 'Vos choix sont enregistrés. Ils ne sont pas encore transmis.');
    }

    /**
     * Transmettre le dossier au service d'orientation.
     */
    public function soumettre(Request $request)
    {
        $eleve = $this->eleveConcerne($request);
        $dossier = $this->dossier($eleve);

        if ($dossier->estDecide()) {
            return back()->with('error', 'Ce dossier a déjà été tranché.');
        }

        if (empty($dossier->voeux)) {
            return back()->with('error', 'Choisissez au moins un établissement avant de transmettre.');
        }

        if ($dossier->niveau === 'troisieme' && ! $dossier->voie) {
            return back()->with('error', 'Choisissez une voie avant de transmettre.');
        }

        $dossier->forceFill([
            'statut' => 'soumis',
            'soumis_le' => now(),
            // Les moyennes sont figées à la transmission : le dossier doit se
            // relire tel qu'il a été étudié, même si les notes changent après.
            'moyennes' => $this->moyennesARetenir(ProfilEleve::etablir($eleve, $this->annee())),
        ])->save();

        return back()->with('success', 'Dossier transmis au service d’orientation de l’établissement.');
    }

    /**
     * L'élève ou le parent accuse lecture de la décision.
     */
    public function accuserLaDecision(Request $request, OrientationDossier $dossier)
    {
        $eleve = $dossier->student;

        abort_unless($eleve, 404);
        AccesEleve::verifier($eleve);

        $dossier->forceFill(['decision_vue_le' => now()])->save();

        return back()->with('success', 'Vous avez pris connaissance de la décision.');
    }

    /* ==================================================================
       Côté établissement
       ================================================================== */

    /**
     * Les dossiers d'orientation : à étudier, accordés, refusés.
     */
    public function index(Request $request)
    {
        $this->seulementLeConseil();

        $etats = [
            'a-etudier' => ['soumis'],
            'accordes' => ['accorde'],
            'refuses' => ['refuse'],
            'brouillons' => ['brouillon'],
        ];

        $onglet = array_key_exists($request->input('onglet'), $etats) ? $request->input('onglet') : 'a-etudier';

        $annee = $this->annee();

        $base = fn () => OrientationDossier::query()
            ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id));

        $dossiers = $base()
            ->with(['student:id,first_name,last_name,student_id', 'decideur:id,name'])
            ->whereIn('statut', $etats[$onglet])
            // Les plus anciennement transmis d'abord : c'est l'élève qui
            // attend depuis le plus longtemps qu'il faut servir.
            ->orderBy($onglet === 'a-etudier' ? 'soumis_le' : 'decide_le', $onglet === 'a-etudier' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        $compte = [];

        foreach ($etats as $cle => $statuts) {
            $compte[$cle] = $base()->whereIn('statut', $statuts)->count();
        }

        return view('orientation.index', [
            'dossiers' => $dossiers,
            'onglet' => $onglet,
            'compte' => $compte,
            'annee' => $annee,
            'repartition' => $this->repartitionDesVoeux($annee),
        ]);
    }

    /**
     * Un dossier, tel que le conseiller doit le lire avant de trancher.
     */
    public function show(OrientationDossier $dossier)
    {
        $this->seulementLeConseil();

        $dossier->load(['student.enrollments.schoolClass.level', 'decideur:id,name', 'academicYear']);

        return view('orientation.show', [
            'dossier' => $dossier,
            // Le profil du jour à côté de celui figé : si les notes ont bougé
            // depuis la transmission, le conseiller doit le voir.
            'profilActuel' => ProfilEleve::etablir($dossier->student, $dossier->academicYear),
            'voeux' => $dossier->voeuxDetailles(),
        ]);
    }

    /**
     * Accorder ou refuser, en disant pourquoi.
     */
    public function decider(Request $request, OrientationDossier $dossier)
    {
        $this->seulementLeConseil();

        if (! $dossier->estSoumis()) {
            return back()->with('error', 'Seul un dossier transmis peut être tranché.');
        }

        /*
         * Les regles se construisent plutot qu'elles ne s'ecrivent d'un bloc :
         * `required_if` accompagne de `nullable` laisse passer la valeur
         * absente — verifie, un refus sans motif etait accepte. Le cas se
         * decide donc explicitement.
         */
        $regles = [
            'decision' => 'required|in:accorde,refuse',
            'motif_code' => 'nullable|string|max:40',
            'motif_precision' => 'nullable|string|max:1000',
        ];

        if ($request->input('decision') === 'refuse') {
            $regles['motif_code'] = 'required|in:'.implode(',', MotifsOrientation::cles());

            // « Autre motif » ne dit rien a lui seul : sans un mot, l'eleve
            // resterait devant une porte close sans savoir quoi corriger.
            if (MotifsOrientation::exigeUnePrecision($request->input('motif_code'))) {
                $regles['motif_precision'] = 'required|string|max:1000';
            }
        }

        $donnees = $request->validate($regles, [
            'motif_code.required' => 'Indiquez pourquoi ce vœu est refusé : l’élève n’aura que cette phrase.',
            'motif_precision.required' => 'Précisez le motif : l’élève n’aura que cette phrase pour comprendre.',
        ]);

        $dossier->forceFill([
            'statut' => $donnees['decision'],
            'motif_code' => $donnees['motif_code'] ?? null,
            'motif_precision' => $donnees['motif_precision'] ?? null,
            'decide_par' => auth()->id(),
            'decide_le' => now(),
            'decision_vue_le' => null,
        ])->save();

        return redirect()->route('orientation.index')
            ->with('success', $donnees['decision'] === 'accorde'
                ? 'Vœu accordé. L’élève en est informé sur son espace.'
                : 'Vœu refusé, motif transmis à l’élève.');
    }

    /* ==================================================================
       Le répertoire
       ================================================================== */

    public function repertoire(Request $request)
    {
        $this->seulementLeConseil();

        $type = array_key_exists($request->input('type'), OrientationEtablissement::TYPES)
            ? $request->input('type')
            : null;

        $etablissements = OrientationEtablissement::visiblesPar()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($terme = trim((string) $request->input('recherche')), function ($q) use ($terme) {
                $motif = '%'.mb_strtolower($terme).'%';
                $q->where(fn ($r) => $r->whereRaw('LOWER(nom) LIKE ?', [$motif])
                    ->orWhereRaw('LOWER(COALESCE(ville, \'\')) LIKE ?', [$motif]));
            })
            ->orderBy('type')
            ->orderBy('nom')
            ->paginate(25)
            ->withQueryString();

        return view('orientation.repertoire', [
            'etablissements' => $etablissements,
            'type' => $type,
            'compte' => OrientationEtablissement::visiblesPar()
                ->selectRaw('type, count(*) as nombre')
                ->groupBy('type')
                ->pluck('nombre', 'type'),
        ]);
    }

    public function enregistrerEtablissement(Request $request, ?OrientationEtablissement $etablissement = null)
    {
        $this->seulementLeConseil();

        $donnees = $request->validate([
            'nom' => 'required|string|max:255',
            'sigle' => 'nullable|string|max:40',
            'type' => 'required|in:'.implode(',', array_keys(OrientationEtablissement::TYPES)),
            'statut' => 'required|in:'.implode(',', array_keys(OrientationEtablissement::STATUTS)),
            'ville' => 'nullable|string|max:255',
            'quartier' => 'nullable|string|max:255',
            'capacite' => 'nullable|integer|min:0',
            'inscrits' => 'nullable|integer|min:0',
            'filieres' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:1000',
            'telephone' => 'nullable|string|max:40',
        ]);

        $donnees['filieres'] = collect(preg_split('/[\r\n,;]+/', (string) ($donnees['filieres'] ?? '')))
            ->map(fn ($f) => trim($f))
            ->filter()
            ->values()
            ->all();

        if ($etablissement && $etablissement->exists) {
            /*
             * Le répertoire national ne se modifie pas depuis une école : une
             * capacité corrigée par l'une changerait l'écran de toutes les
             * autres. Seules les places déclarées, qui regardent l'orientation
             * de ses propres élèves, restent ouvertes.
             */
            if ($etablissement->school_id === null) {
                $etablissement->forceFill([
                    'capacite' => $donnees['capacite'] ?? $etablissement->capacite,
                    'inscrits' => $donnees['inscrits'] ?? $etablissement->inscrits,
                ])->save();

                return back()->with('success', 'Places mises à jour pour « '.$etablissement->nom.' ».');
            }

            $etablissement->update($donnees);

            return back()->with('success', 'Établissement mis à jour.');
        }

        OrientationEtablissement::create($donnees + [
            'school_id' => \App\Support\EcoleCourante::id(),
            'is_active' => true,
        ]);

        return back()->with('success', 'Établissement ajouté à votre répertoire.');
    }

    /* ==================================================================
       Les rouages
       ================================================================== */

    private function seulementLeConseil(): void
    {
        $role = auth()->user()?->role;

        /*
         * L'orientation relève de la direction et du censeur — la scolarité est
         * son domaine — et du secrétariat, qui monte les dossiers. Elle ne
         * relève pas de l'enseignant : son avis passe par le conseil de classe,
         * pas par cet écran.
         */
        abort_unless(
            Roles::estDeDirection($role) || $role === 'secretary',
            403,
            'Le service d’orientation est réservé à la direction et au secrétariat.'
        );
    }

    /**
     * L'élève dont on regarde le dossier : soi-même, ou son enfant.
     */
    private function eleveConcerne(Request $request): Student
    {
        $utilisateur = auth()->user();

        if ($utilisateur?->role === 'student') {
            $eleve = Student::where('user_id', $utilisateur->id)->first();

            abort_unless($eleve, 403, 'Ce compte n’est rattaché à aucun élève.');

            return $eleve;
        }

        if ($utilisateur?->role === 'parent') {
            $parent = ParentModel::where('user_id', $utilisateur->id)->first();

            abort_unless($parent, 403, 'Ce compte n’est rattaché à aucune fiche parent.');

            $enfants = $parent->students()->get();

            abort_if($enfants->isEmpty(), 404, 'Aucun enfant n’est rattaché à votre compte.');

            $choisi = $request->integer('eleve');

            $eleve = $enfants->firstWhere('id', $choisi) ?? $enfants->first();

            return $eleve;
        }

        // Direction et secrétariat consultent par le dossier, pas par ici.
        abort(403, 'Cet écran est celui de l’élève et de sa famille.');
    }

    private function annee(): ?AcademicYear
    {
        return AcademicYear::where('is_current', true)->first();
    }

    /**
     * Le niveau d'orientation d'un élève, ou null s'il n'y est pas encore.
     */
    private function niveau(Student $eleve): ?string
    {
        $inscription = $eleve->enrollments()
            ->with('schoolClass.level')
            ->where('status', 'active')
            ->latest('id')
            ->first();

        $nom = mb_strtolower((string) ($inscription->schoolClass->level->name ?? $inscription->schoolClass->name ?? ''));

        if (str_contains($nom, '3') || str_contains($nom, 'troisi')) {
            return 'troisieme';
        }

        if (str_contains($nom, 'terminale') || str_contains($nom, 'tle')) {
            return 'terminale';
        }

        return null;
    }

    private function serie(Student $eleve): ?string
    {
        $inscription = $eleve->enrollments()
            // `serie()` est la relation ; `series` n'est que le nom affiche.
            ->with('schoolClass.serie')
            ->where('status', 'active')
            ->latest('id')
            ->first();

        return $inscription?->schoolClass?->serie?->code;
    }

    private function dossier(Student $eleve): OrientationDossier
    {
        $annee = $this->annee();

        return OrientationDossier::firstOrCreate(
            ['student_id' => $eleve->id, 'academic_year_id' => $annee?->id],
            [
                'school_id' => $eleve->school_id,
                'niveau' => $this->niveau($eleve) ?? 'troisieme',
                'statut' => 'brouillon',
            ]
        );
    }

    private function moyennesARetenir(array $profil): array
    {
        return [
            'generale' => $profil['generale'],
            'sciences' => $profil['sciences'],
            'lettres' => $profil['lettres'],
            'gestion' => $profil['gestion'],
            'notes' => $profil['notes'],
            'par_matiere' => $profil['par_matiere'],
            'libelle' => $profil['libelle'],
            'lecture' => $profil['lecture'],
        ];
    }

    /**
     * Ce que l'écran de l'élève doit montrer, selon son niveau.
     */
    private function rendreLeDossier(Student $eleve, Request $request)
    {
        $annee = $this->annee();
        $niveau = $this->niveau($eleve);
        $profil = ProfilEleve::etablir($eleve, $annee);
        $dossier = $niveau ? $this->dossier($eleve) : null;

        $serie = VoiesApresTerminale::depuisLeCode($this->serie($eleve));
        $pratique = $request->boolean('pratique', ($dossier?->voie ?? '') !== '' && str_contains((string) $dossier?->voie, 'techn'));

        $voieChoisie = $dossier?->voie ?: null;

        $typeRecherche = $niveau === 'terminale'
            ? ['universite', 'ecole_superieure']
            : VoiesApresTroisieme::typesEtablissement($voieChoisie);

        $etablissements = $niveau
            ? OrientationEtablissement::visiblesPar()
                ->duType($typeRecherche)
                ->where('is_active', true)
                ->orderBy('nom')
                ->get()
            : collect();

        /*
         * Les voies techniques et professionnelles ne se preparent pas partout :
         * un lycee qui n'annonce que le general n'y mene pas. Le tri se fait
         * ici plutot qu'en base — les filieres sont du JSON, et la liste tient
         * dans la main.
         */
        $mots = $niveau === 'troisieme' ? VoiesApresTroisieme::motsDesFilieres($voieChoisie) : [];

        if ($mots) {
            $retenus = $etablissements->filter(function ($e) use ($mots) {
                $texte = mb_strtolower($e->nom.' '.implode(' ', $e->filieres ?? []));

                foreach ($mots as $mot) {
                    if (str_contains($texte, $mot)) {
                        return true;
                    }
                }

                return false;
            });

            // Si le tri ne laisse rien, mieux vaut la liste entiere qu'un ecran vide.
            $etablissements = $retenus->isNotEmpty() ? $retenus->values() : $etablissements;
        }

        return view('orientation.mon-dossier', [
            'eleve' => $eleve,
            'niveau' => $niveau,
            'profil' => $profil,
            'dossier' => $dossier,
            'serie' => $serie,
            'pratique' => $pratique,
            'voies' => $niveau === 'troisieme' ? Conseil::voies($profil, $pratique) : [],
            'filieres' => $niveau === 'troisieme' && $voieChoisie ? Conseil::filieres($voieChoisie, $profil) : [],
            'etablissements' => $etablissements,
            'maxVoeux' => self::MAX_VOEUX,
            'enfants' => auth()->user()?->role === 'parent'
                ? ParentModel::where('user_id', auth()->id())->first()?->students()->get() ?? collect()
                : collect(),
        ]);
    }

    /**
     * Où vont les élèves, cette année : de quoi nourrir le conseil de classe.
     *
     * @return array<int, array{libelle: string, nombre: int}>
     */
    private function repartitionDesVoeux(?AcademicYear $annee): array
    {
        $dossiers = OrientationDossier::query()
            ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
            ->whereIn('statut', ['soumis', 'accorde'])
            ->get(['niveau', 'voie', 'filiere']);

        return $dossiers
            ->groupBy(fn ($d) => $d->niveau === 'terminale'
                ? ($d->filiere ?: 'Supérieur, filière à préciser')
                : VoiesApresTroisieme::libelle($d->voie))
            ->map(fn ($lot, $libelle) => ['libelle' => $libelle, 'nombre' => $lot->count()])
            ->sortByDesc('nombre')
            ->values()
            ->all();
    }
}
