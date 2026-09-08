<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\Series;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    /** Cycles qui portent des matières. Le préprimaire est polyvalent. */
    private const CYCLES = ['primaire', 'college', 'lycee'];

    /**
     * Afficher la liste des matières
     */
    public function index(Request $request)
    {
        $query = Subject::query()
            // Les compteurs alimentent la colonne « Usage » : sans eux, chaque
            // ligne du tableau relancerait deux requêtes.
            ->withCount(['grades', 'teachers', 'schedules']);

        if ($recherche = trim((string) $request->input('recherche'))) {
            $query->where(fn ($q) => $q
                ->where('name', 'ilike', "%{$recherche}%")
                ->orWhere('code', 'ilike', "%{$recherche}%")
                ->orWhere('description', 'ilike', "%{$recherche}%"));
        }

        if ($cycle = $request->input('cycle')) {
            $query->where('cycle', $cycle);
        }

        if ($serie = $request->input('serie')) {
            $query->whereJsonContains('series', $serie);
        }

        if (($statut = $request->input('statut')) !== null && $statut !== '') {
            $query->where('is_active', $statut === 'actif');
        }

        // Enseignant : filtre les matières que personne ne peut assurer.
        if ($request->input('sans_enseignant') === '1') {
            $query->doesntHave('teachers');
        }

        // Taille de page : celle demandee si elle est permise, sinon celle
        // reglee pour la plateforme.
        $parPage = \App\Support\ParametresPlateforme::pagination($request->input('per_page'));

        $subjects = $query->orderBy('cycle')->orderBy('name')
            ->paginate($parPage)
            ->withQueryString();

        return view('subjects.index', [
            'subjects' => $subjects,
            'series' => $this->lettresDeSerie(),
            'statistiques' => $this->statistiques(),
        ]);
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        return view('subjects.create', [
            'subject' => new Subject(['coefficient' => 1, 'is_active' => true]),
            'series' => $this->lettresDeSerie(),
        ]);
    }

    /**
     * Enregistrer une nouvelle matière
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->regles($request), $this->messages());

        if ($validator->fails()) {
            return $this->echecValidation($request, $validator);
        }

        $donnees = $this->donneesValidees($validator, $request);

        try {
            $subject = Subject::create($donnees);

            if (! $request->expectsJson()) {
                return redirect()->route('subjects.show', $subject->id)
                    ->with('success', 'Matière créée avec succès.');
            }

            return response()->json(['success' => true, 'message' => 'Matière créée avec succès!', 'subject' => $subject]);
        } catch (\Exception $e) {
            return $this->echecEnregistrement($request, $e, 'la création');
        }
    }

    /**
     * Afficher une matière spécifique
     */
    public function show(Subject $subject)
    {
        $subject->load([
            'teachers' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name'),
            'schedules.schoolClass',
            'schedules.teacher',
        ]);

        // Répartition des notes : c'est ce qui dit si la matière est réellement
        // enseignée, indépendamment des enseignants déclarés.
        $notes = $subject->grades()
            ->selectRaw("term, count(*) as total, avg(case when max_score > 0 then score / max_score * 20 end) as moyenne")
            ->groupBy('term')
            ->orderBy('term')
            ->get();

        return view('subjects.show', [
            'subject' => $subject,
            'notes' => $notes,
            'totalNotes' => (int) $notes->sum('total'),
        ]);
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Subject $subject)
    {
        return view('subjects.edit', [
            'subject' => $subject,
            'series' => $this->lettresDeSerie(),
        ]);
    }

    /**
     * Mettre à jour une matière
     */
    public function update(Request $request, Subject $subject)
    {
        $validator = Validator::make($request->all(), $this->regles($request, $subject), $this->messages());

        if ($validator->fails()) {
            return $this->echecValidation($request, $validator);
        }

        $donnees = $this->donneesValidees($validator, $request);

        try {
            $subject->update($donnees);

            if (! $request->expectsJson()) {
                return redirect()->route('subjects.show', $subject->id)
                    ->with('success', 'Matière mise à jour avec succès.');
            }

            return response()->json(['success' => true, 'message' => 'Matière mise à jour avec succès!', 'subject' => $subject]);
        } catch (\Exception $e) {
            return $this->echecEnregistrement($request, $e, 'la mise à jour');
        }
    }

    /**
     * Supprimer une matière
     */
    public function destroy(Request $request, Subject $subject)
    {
        /*
         * `grades()` visait la table `grades`, vide et morte : le contrôle
         * comptait toujours zéro et laissait supprimer une matière portant des
         * centaines de notes. La relation pointe maintenant sur `student_grades`,
         * et les créneaux sont vérifiés en plus.
         */
        $notes = $subject->grades()->count();
        $creneaux = $subject->schedules()->count();

        if ($notes > 0 || $creneaux > 0) {
            $obstacles = [];
            if ($notes > 0) {
                $obstacles[] = "{$notes} note(s)";
            }
            if ($creneaux > 0) {
                $obstacles[] = "{$creneaux} créneau(x) d’emploi du temps";
            }

            $message = 'Impossible de supprimer « '.$subject->name.' » : elle porte '
                .implode(' et ', $obstacles).'. Désactivez-la plutôt que de la supprimer.';

            if (! $request->expectsJson()) {
                return redirect()->route('subjects.index')->with('error', $message);
            }

            return response()->json(['success' => false, 'message' => $message], 422);
        }

        try {
            // Le rattachement aux enseignants ne bloque pas : il se dénoue.
            $subject->teachers()->detach();
            $subject->delete();

            if (! $request->expectsJson()) {
                return redirect()->route('subjects.index')->with('success', 'Matière supprimée avec succès.');
            }

            return response()->json(['success' => true, 'message' => 'Matière supprimée avec succès!']);
        } catch (\Exception $e) {
            return $this->echecEnregistrement($request, $e, 'la suppression');
        }
    }

    /**
     * Code propose pour un intitule et un cycle donnes.
     *
     * La generation vit dans le modele : le formulaire l'interroge plutot que
     * de reimplementer la regle en JavaScript, ou les deux versions finiraient
     * par diverger.
     */
    public function proposerCode(Request $request)
    {
        $nom = trim((string) $request->input('nom'));

        if ($nom === '') {
            return response()->json(['code' => '']);
        }

        return response()->json([
            'code' => Subject::genererCode(
                $nom,
                $request->input('cycle'),
                $request->filled('id') ? (int) $request->input('id') : null
            ),
        ]);
    }

    /**
     * Matieres d'un niveau, au format JSON.
     *
     * La route `subjects.byLevel` existait mais visait une methode absente du
     * controleur : l'URL renvoyait une erreur 500. Une matiere est rattachee a
     * un cycle, pas a un niveau : on passe donc par le cycle du niveau demande.
     */
    public function byLevel(Level $level)
    {
        $matieres = Subject::where('is_active', true)
            ->where('cycle', $level->cycle)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'coefficient', 'series']);

        return response()->json([
            'success' => true,
            'level' => $level->only(['id', 'name', 'cycle']),
            'subjects' => $matieres,
        ]);
    }

    // ------------------------------------------------------------------
    // Outils internes
    // ------------------------------------------------------------------

    private function regles(Request $request, ?Subject $subject = null): array
    {
        $regles = [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:subjects,code'.($subject ? ','.$subject->id : ''),
            'description' => 'nullable|string',
            'coefficient' => 'required|integer|min:1|max:10',
            'cycle' => 'required|in:'.implode(',', self::CYCLES),
            'is_active' => 'nullable|boolean',
        ];

        // Au lycée une matière ne vaut que pour certaines séries : la liste des
        // valeurs acceptées vient du référentiel, pas d'une énumération figée.
        if ($request->input('cycle') === 'lycee') {
            $regles['series'] = 'required|array|min:1';
            $regles['series.*'] = 'in:'.$this->lettresDeSerie()->implode(',');
        }

        return $regles;
    }

    private function messages(): array
    {
        return [
            'series.required' => 'Une matière de lycée doit viser au moins une série.',
            'coefficient.min' => 'Le coefficient doit valoir au moins 1.',
            'coefficient.integer' => 'Le coefficient est un nombre entier.',
            'code.unique' => 'Ce code est déjà porté par une autre matière.',
        ];
    }

    private function donneesValidees($validator, Request $request): array
    {
        $donnees = $validator->validated();

        // Le code se déduit de l'intitulé et du cycle. Il reste modifiable à la
        // main dans le formulaire ; laissé vide, il est reconstruit ici.
        if (empty($donnees['code'])) {
            $donnees['code'] = Subject::genererCode(
                $donnees['name'],
                $donnees['cycle'],
                $request->route('subject')?->id
            );
        }

        // Hors lycée la notion de série n'existe pas.
        if ($request->input('cycle') !== 'lycee') {
            $donnees['series'] = null;
        }

        // Une case décochée n'est pas envoyée : sans cette ligne on ne pourrait
        // jamais désactiver une matière depuis le formulaire.
        $donnees['is_active'] = $request->boolean('is_active');

        return $donnees;
    }

    /**
     * Lettres de série reconnues, déduites du référentiel `series` : les codes
     * y sont préfixés par le niveau (`TERM-C`), la lettre est le suffixe.
     */
    private function lettresDeSerie()
    {
        return Series::where('is_active', true)
            ->orderBy('order')
            ->pluck('code')
            ->map(fn ($code) => str_contains($code, '-') ? substr($code, strpos($code, '-') + 1) : $code)
            ->unique()
            ->sort()
            ->values();
    }

    private function statistiques(): array
    {
        return [
            'total' => Subject::count(),
            'actives' => Subject::where('is_active', true)->count(),
            'sans_enseignant' => Subject::doesntHave('teachers')->count(),
            'par_cycle' => Subject::selectRaw('cycle, count(*) as n')->groupBy('cycle')->pluck('n', 'cycle'),
        ];
    }

    private function echecValidation(Request $request, $validator)
    {
        if (! $request->expectsJson()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return response()->json([
            'success' => false,
            'message' => 'Erreurs de validation: '.implode(', ', $validator->errors()->all()),
            'errors' => $validator->errors(),
        ], 422);
    }

    private function echecEnregistrement(Request $request, \Exception $e, string $action)
    {
        Log::error("Erreur lors de {$action} de la matière", ['erreur' => $e->getMessage()]);

        if (! $request->expectsJson()) {
            return redirect()->back()->withInput()
                ->with('error', "Erreur lors de {$action} de la matière.");
        }

        return response()->json([
            'success' => false,
            'message' => "Erreur lors de {$action} de la matière : ".$e->getMessage(),
        ], 500);
    }
}
