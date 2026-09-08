<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\EcoleCourante;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Gestion des établissements, réservée au super administrateur.
 *
 * Créer un établissement, c'est aussi lui donner un compte administrateur et
 * ses paramètres de documents : les trois vont ensemble, sinon l'école existe
 * sans que personne puisse y entrer.
 */
class SchoolController extends Controller implements HasMiddleware
{
    /**
     * Tout le controleur est reserve au super administrateur.
     *
     * Laravel 11 a retire $this->middleware() des controleurs : la protection
     * se declare desormais ici.
     */
    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                if (! auth()->user()?->isSuperAdmin()) {
                    abort(403, 'Accès réservé au super administrateur.');
                }

                return $next($request);
            },
        ];
    }

    public function index(Request $request)
    {
        $requete = School::query();

        if ($terme = trim((string) $request->input('recherche'))) {
            $motif = '%'.mb_strtolower($terme).'%';
            $requete->where(fn ($q) => $q
                ->whereRaw('LOWER(name) LIKE ?', [$motif])
                ->orWhereRaw('LOWER(code) LIKE ?', [$motif])
                ->orWhereRaw('LOWER(city) LIKE ?', [$motif]));
        }

        if ($request->filled('etat')) {
            $requete->where('is_active', $request->input('etat') === 'actif');
        }

        if ($cycle = $request->input('cycle')) {
            $requete->where('has_'.$cycle, true);
        }

        $schools = $requete->orderBy('name')->paginate(10)->withQueryString();

        // Les effectifs se comptent hors filtre d'établissement : c'est
        // précisément la vue d'ensemble que cette page doit donner.
        $effectifs = Student::tousEtablissements()
            ->selectRaw('school_id, count(*) as eleves')
            ->groupBy('school_id')
            ->pluck('eleves', 'school_id');

        $enseignants = Teacher::tousEtablissements()
            ->selectRaw('school_id, count(*) as nombre')
            ->groupBy('school_id')
            ->pluck('nombre', 'school_id');

        $comptes = User::where('role', '!=', 'superadmin')
            ->selectRaw('school_id, count(*) as nombre')
            ->groupBy('school_id')
            ->pluck('nombre', 'school_id');

        $bilan = [
            'total' => School::count(),
            'actifs' => School::where('is_active', true)->count(),
            'eleves' => $effectifs->sum(),
            'comptes' => $comptes->sum(),
        ];

        return view('admin.schools.index', compact(
            'schools', 'effectifs', 'enseignants', 'comptes', 'bilan'
        ));
    }

    public function create()
    {
        return view('admin.schools.form', [
            'school' => new School(['country' => 'Gabon', 'is_active' => true]),
            'codePropose' => School::prochainCode(),
        ]);
    }

    public function store(Request $request)
    {
        $valide = $this->valider($request);

        DB::beginTransaction();

        try {
            $school = School::create($valide['etablissement']);

            // Paramètres de documents : sans eux, les bulletins de la nouvelle
            // école sortiraient sans en-tête.
            SchoolSettings::create([
                'school_id' => $school->id,
                'school_name' => $school->name,
                'primary_school_name' => $school->name,
                'secondary_school_name' => $school->name,
                // Coordonnees laissees a completer par l'administrateur de
                // l'etablissement. Ces colonnes sont NOT NULL en base : une
                // valeur neutre evite de faire echouer l'insertion.
                'school_address' => null,
                'school_phone' => '—',
                'school_email' => null,
                'school_bp' => '—',
                'principal_title' => 'Le Chef d’établissement',
                'academic_year' => now()->year.'-'.(now()->year + 1),
                'has_preprimary' => $school->has_preprimaire,
                'has_primary' => $school->has_primaire,
                'has_secondary' => $school->has_college || $school->has_lycee,
                'school_type' => 'Établissement scolaire',
                'school_level' => implode(', ', $school->libellesDesCycles()) ?: 'Tous niveaux',
                'city' => $school->city ?: '—',
                'country' => 'Gabon',
                'timezone' => 'Africa/Libreville',
                'currency' => 'FCFA',
                'language' => 'fr',
                'is_active' => true,
            ]);

            User::create([
                'name' => $valide['compte']['nom'],
                'email' => $valide['compte']['email'],
                'password' => Hash::make($valide['compte']['mot_de_passe']),
                'role' => 'admin',
                'matricule' => $school->code.'-ADM',
                'school_id' => $school->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.schools.index')
                ->with('success', 'Établissement « '.$school->name.' » créé, avec son compte administrateur.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Création impossible : '.$e->getMessage());
        }
    }

    public function show(School $school)
    {
        $school->load('settings');

        $chiffres = [
            'eleves' => Student::tousEtablissements()->where('school_id', $school->id)->count(),
            'enseignants' => Teacher::tousEtablissements()->where('school_id', $school->id)->count(),
            'classes' => DB::table('classes')->where('school_id', $school->id)->count(),
            'comptes' => User::where('school_id', $school->id)->count(),
        ];

        $comptes = User::where('school_id', $school->id)
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return view('admin.schools.show', compact('school', 'chiffres', 'comptes'));
    }

    public function edit(School $school)
    {
        return view('admin.schools.form', [
            'school' => $school,
            'codePropose' => $school->code,
        ]);
    }

    public function update(Request $request, School $school)
    {
        $valide = $this->valider($request, $school);

        $school->update($valide['etablissement']);

        // Les paramètres de documents suivent le nom de l'établissement.
        $school->settings?->update(['school_name' => $school->name]);

        return redirect()
            ->route('admin.schools.show', $school)
            ->with('success', 'Établissement mis à jour.');
    }

    /**
     * Ouvrir ou fermer un établissement.
     *
     * Rien n'est effacé : les comptes rattachés perdent simplement l'accès,
     * et le rétablissent dès la réactivation.
     */
    public function toggleStatus(School $school)
    {
        $school->update(['is_active' => ! $school->is_active]);

        return back()->with(
            'success',
            $school->is_active
                ? 'Établissement « '.$school->name.' » réactivé.'
                : 'Établissement « '.$school->name.' » désactivé : ses comptes n’ont plus accès.'
        );
    }

    /**
     * Le super administrateur se place dans un établissement, ou en ressort.
     */
    public function basculer(Request $request)
    {
        $ecoleId = $request->input('school_id');

        if ($ecoleId && ! School::whereKey($ecoleId)->exists()) {
            return back()->with('error', 'Établissement introuvable.');
        }

        EcoleCourante::choisir($ecoleId ? (int) $ecoleId : null);

        return back()->with(
            'success',
            $ecoleId
                ? 'Vous travaillez dans « '.School::find($ecoleId)->name.' ».'
                : 'Vue d’ensemble : tous les établissements.'
        );
    }

    /**
     * Règles communes à la création et à la modification.
     */
    private function valider(Request $request, ?School $school = null): array
    {
        // Le superadmin ne saisit que ce qui definit l'etablissement comme
        // locataire. Ses coordonnees relevent de son propre administrateur.
        // Le code n'est pas saisi : il est engendre a la creation et ne bouge
        // plus ensuite — c'est la reference des matricules et des comptes.
        $regles = [
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'cycles' => 'required|array|min:1',
            'cycles.*' => 'in:preprimaire,primaire,college,lycee',
        ];

        // Le compte administrateur n'est demandé qu'à la création.
        if (! $school) {
            $regles += [
                'admin_nom' => 'required|string|max:255',
                'admin_email' => 'required|email|max:255|unique:users,email',
                'admin_mot_de_passe' => 'required|string|min:8|confirmed',
            ];
        }

        $donnees = $request->validate($regles, [], [
            'cycles' => 'cycles ouverts',
            'admin_nom' => 'nom de l’administrateur',
            'admin_email' => 'courriel de l’administrateur',
            'admin_mot_de_passe' => 'mot de passe',
        ]);

        $etablissement = collect($donnees)
            ->only(['name', 'city', 'notes'])
            ->all();

        // Un etablissement neuf recoit son code ; un etablissement existant
        // garde le sien.
        if (! $school) {
            $etablissement['code'] = School::prochainCode();
        }

        foreach (array_keys(School::CYCLES) as $cycle) {
            $etablissement['has_'.$cycle] = in_array($cycle, $donnees['cycles'], true);
        }

        if (! $school) {
            $etablissement['is_active'] = $request->boolean('is_active', true);
        }

        return [
            'etablissement' => $etablissement,
            'compte' => [
                'nom' => $donnees['admin_nom'] ?? null,
                'email' => $donnees['admin_email'] ?? null,
                'mot_de_passe' => $donnees['admin_mot_de_passe'] ?? null,
            ],
        ];
    }
}
