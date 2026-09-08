<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\Series;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SeriesController extends Controller
{
    /** Colonnes sur lesquelles le tri est autorisé. */
    private const TRIS = ['order', 'code', 'name', 'level_id', 'created_at'];

    /**
     * Niveaux qui portent des séries : ceux du lycée, lus en base.
     *
     * La liste était écrite en dur — « 2nde, 1ère, Terminale » — et devait
     * correspondre au mot près à `levels.name`. Elle n'y correspondait pas :
     * la table des niveaux écrit « Terminal », et les quatorze séries de
     * Terminale n'étaient rattachables à rien.
     */
    private function niveauxDuLycee()
    {
        return Level::where('cycle', 'lycee')->orderBy('order')->get();
    }

    /**
     * Liste des séries
     */
    public function index(Request $request)
    {
        $query = Series::query()->with('niveau')
            // Les classes rattachées : la relation visait `classes.series_id`,
            // colonne qui n'existait pas. Elle existe désormais.
            ->withCount('classes');

        if ($recherche = trim((string) $request->input('recherche'))) {
            $query->where(fn ($q) => $q
                ->where('name', 'ilike', "%{$recherche}%")
                ->orWhere('code', 'ilike', "%{$recherche}%")
                ->orWhere('description', 'ilike', "%{$recherche}%"));
        }

        if ($niveau = $request->input('niveau')) {
            $query->where('level_id', $niveau);
        }

        if (($statut = $request->input('statut')) !== null && $statut !== '') {
            $query->where('is_active', $statut === 'actif');
        }

        /*
         * Le tri partait directement dans `orderBy` depuis la requête : une
         * colonne inconnue faisait échouer la page, et la valeur n'était jamais
         * contrôlée. Elle est maintenant prise dans une liste fermée.
         */
        $tri = in_array($request->input('tri'), self::TRIS, true) ? $request->input('tri') : 'order';
        $sens = $request->input('sens') === 'desc' ? 'desc' : 'asc';

        // Taille de page : celle demandee si elle est permise, sinon celle
        // reglee pour la plateforme.
        $parPage = \App\Support\ParametresPlateforme::pagination($request->input('per_page'));

        $series = $query->orderBy('level_id')->orderBy($tri, $sens)
            ->paginate($parPage)
            ->withQueryString();

        $niveaux = $this->niveauxDuLycee();

        return view('series.index', [
            'series' => $series,
            'niveaux' => $niveaux,
            'tri' => $tri,
            'sens' => $sens,
            'statistiques' => [
                'total' => Series::count(),
                'actives' => Series::where('is_active', true)->count(),
                'sans_classe' => Series::doesntHave('classes')->count(),
                // Indexe par nom : c'est ce que la vue affiche.
                'par_niveau' => Series::selectRaw('level_id, count(*) as n')
                    ->groupBy('level_id')->pluck('n', 'level_id')
                    ->mapWithKeys(fn ($n, $id) => [(string) ($niveaux->firstWhere('id', $id)?->name ?? $id) => $n]),
            ],
        ]);
    }

    /**
     * Formulaire de création
     */
    public function create(Request $request)
    {
        $niveaux = $this->niveauxDuLycee();

        return view('series.create', [
            // Le rang par défaut place la nouvelle série en fin de son niveau.
            'series' => new Series([
                'level_id' => $niveaux->contains('id', $request->input('niveau')) ? $request->input('niveau') : null,
                'is_active' => true,
                'order' => 0,
            ]),
            'niveaux' => $niveaux,
            'rangsUtilises' => $this->rangsUtilises(),
        ]);
    }

    /**
     * Enregistrer une série
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->regles(), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // `create($request->all())` acceptait n'importe quelle clé envoyée :
            // seules les valeurs validées sont écrites.
            Series::create($this->donneesValidees($validator, $request));

            return redirect()->route('series.index')
                ->with('success', 'Série créée avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Erreur lors de la création de la série : '.$e->getMessage());
        }
    }

    /**
     * Fiche d'une série
     */
    public function show(Series $series)
    {
        $series->load([
            'niveau',
            'classes' => fn ($q) => $q->with('level')->orderBy('name'),
        ]);

        // Effectif de chaque classe de la série, et matières qui la visent.
        $effectifs = DB::table('enrollments')
            ->whereIn('class_id', $series->classes->pluck('id'))
            ->where('status', 'active')
            ->selectRaw('class_id, count(*) as n')
            ->groupBy('class_id')
            ->pluck('n', 'class_id');

        // `subjects.series` stocke la lettre de série, suffixe du code.
        $lettre = str_contains($series->code, '-')
            ? substr($series->code, strpos($series->code, '-') + 1)
            : $series->code;

        $matieres = Subject::where('is_active', true)
            ->whereJsonContains('series', $lettre)
            ->orderBy('name')
            ->get();

        return view('series.show', compact('series', 'effectifs', 'matieres', 'lettre'));
    }

    /**
     * Formulaire d'édition
     */
    public function edit(Series $series)
    {
        return view('series.edit', [
            'series' => $series,
            'niveaux' => $this->niveauxDuLycee(),
            'rangsUtilises' => $this->rangsUtilises(),
        ]);
    }

    /**
     * Mettre à jour une série
     */
    public function update(Request $request, Series $series)
    {
        $validator = Validator::make($request->all(), $this->regles($series), $this->messages());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $series->update($this->donneesValidees($validator, $request));

            return redirect()->route('series.show', $series->id)
                ->with('success', 'Série mise à jour avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Erreur lors de la mise à jour de la série : '.$e->getMessage());
        }
    }

    /**
     * Supprimer une série
     */
    public function destroy(Series $series)
    {
        $classes = $series->classes()->count();

        if ($classes > 0) {
            return redirect()->route('series.index')->with(
                'error',
                'Impossible de supprimer « '.$series->name.' » : '.$classes.' classe(s) y sont rattachées. '
                .'Désactivez-la plutôt que de la supprimer.'
            );
        }

        try {
            $series->delete();

            return redirect()->route('series.index')
                ->with('success', 'Série supprimée avec succès.');
        } catch (\Exception $e) {
            return redirect()->route('series.index')
                ->with('error', 'Erreur lors de la suppression de la série : '.$e->getMessage());
        }
    }

    /**
     * Code propose pour un niveau et une lettre de serie.
     *
     * La regle vit dans le modele : le formulaire l'interroge plutot que de la
     * reecrire en JavaScript.
     */
    public function proposerCode(Request $request)
    {
        $niveau = $request->input('niveau');

        if (! $this->niveauxDuLycee()->contains('id', $niveau)) {
            return response()->json(['code' => '', 'lettre' => '']);
        }

        $lettre = $request->input('lettre') ?: Series::prochaineLettre($niveau);

        return response()->json([
            'code' => Series::genererCode(
                $niveau,
                $lettre,
                $request->filled('id') ? (int) $request->input('id') : null
            ),
            'lettre' => $lettre,
        ]);
    }

    // ------------------------------------------------------------------
    // Outils internes
    // ------------------------------------------------------------------

    private function regles(?Series $series = null): array
    {
        return [
            'code' => 'nullable|string|max:10|unique:series,code'.($series ? ','.$series->id : ''),
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level_id' => 'required|exists:levels,id',
            'order' => 'required|integer|min:0|max:99',
            'is_active' => 'nullable|boolean',
        ];
    }

    private function messages(): array
    {
        return [
            'code.unique' => 'Ce code est déjà porté par une autre série.',
            'level_id.required' => 'Choisissez le niveau du lycée auquel la série appartient.',
            'order.max' => 'Le rang d’affichage doit rester inférieur à 100.',
        ];
    }

    private function donneesValidees($validator, Request $request): array
    {
        $donnees = $validator->validated();

        // Le code se compose du préfixe du niveau et de la lettre de série. Il
        // reste modifiable à la main ; laissé vide, il est reconstruit ici.
        if (empty($donnees['code'])) {
            $donnees['code'] = Series::genererCode(
                $donnees['level_id'],
                $request->input('lettre'),
                $request->route('series')?->id
            );
        }

        // Une case décochée n'est pas envoyée : sans cette ligne, une série ne
        // pourrait jamais être désactivée depuis le formulaire.
        $donnees['is_active'] = $request->boolean('is_active');

        return $donnees;
    }

    /**
     * Rangs déjà pris par niveau, pour signaler un doublon dans le formulaire.
     */
    private function rangsUtilises(): array
    {
        return Series::orderBy('order')
            ->get(['id', 'level_id', 'order', 'code'])
            ->groupBy('level_id')
            ->map(fn ($g) => $g->mapWithKeys(fn ($s) => [$s->order => $s->code])->all())
            ->all();
    }
}
