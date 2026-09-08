<?php

namespace App\Http\Controllers;

use App\Models\ParentModel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class ParentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ParentModel::query()->with('students');

        // Recherche textuelle sur l'identité et les coordonnées.
        if ($terme = trim((string) $request->input('search'))) {
            $motif = '%'.mb_strtolower($terme).'%';

            $query->where(function ($q) use ($motif) {
                $q->whereRaw('LOWER(first_name) LIKE ?', [$motif])
                  ->orWhereRaw('LOWER(last_name) LIKE ?', [$motif])
                  ->orWhereRaw('LOWER(email) LIKE ?', [$motif])
                  ->orWhereRaw('LOWER(phone) LIKE ?', [$motif]);
            });
        }

        /*
         * Le lien de parenté et les autorisations vivent sur le pivot : un même
         * adulte peut être père de son fils et tuteur de son neveu. On filtre
         * donc sur « a au moins un lien qui correspond ».
         */
        if ($lien = $request->input('relationship')) {
            $query->whereHas('students', fn ($q) => $q->where('student_parent.relationship_type', $lien));
        }

        if (($principal = $request->input('is_primary_contact')) !== null && $principal !== '') {
            $query->whereHas('students', fn ($q) => $q->where('student_parent.is_primary_contact', (bool) $principal));
        }

        if (($recuperation = $request->input('can_pickup')) !== null && $recuperation !== '') {
            $query->whereHas('students', fn ($q) => $q->where('student_parent.can_pickup', (bool) $recuperation));
        }

        // Taille de page : celle demandee si elle est permise, sinon celle
        // reglee pour la plateforme.
        $perPage = \App\Support\ParametresPlateforme::pagination($request->input('per_page'));

        $parents = $query->orderBy('last_name')->orderBy('first_name')
            ->paginate($perPage)
            ->withQueryString();

        // --- Statistiques ---------------------------------------------------
        $totalParents = ParentModel::count();

        // Joignable = au moins un moyen de contact renseigné.
        $activeContacts = ParentModel::where(function ($q) {
            $q->where(fn ($x) => $x->whereNotNull('phone')->where('phone', '!=', ''))
              ->orWhere(fn ($x) => $x->whereNotNull('email')->where('email', '!=', ''));
        })->count();

        // Comptés sur les liens, seul niveau où l'information existe désormais.
        $primaryContacts = ParentModel::whereHas('students', fn ($q) => $q->where('student_parent.is_primary_contact', true))->count();
        $canPickup = ParentModel::whereHas('students', fn ($q) => $q->where('student_parent.can_pickup', true))->count();

        $parentsByRelation = collect(['father', 'mother', 'guardian', 'other'])
            ->mapWithKeys(fn ($lien) => [
                $lien => ParentModel::whereHas('students', fn ($q) => $q->where('student_parent.relationship_type', $lien))->count(),
            ])
            ->all();

        return view('parents.index', compact(
            'parents',
            'totalParents',
            'activeContacts',
            'primaryContacts',
            'canPickup',
            'parentsByRelation'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $students = Student::active()
            ->with(['enrollments' => fn ($q) => $q->where('status', 'active')
                ->with('schoolClass')
                ->latest()
                ->limit(1)])
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        // Si on vient de l'inscription ou de la création d'élève
        $preselectedStudent = null;
        $fromContext = $request->get('from'); // 'enrollment' ou 'student'
        
        if ($request->has('student_id')) {
            $studentId = $request->get('student_id');
            
            if ($fromContext === 'enrollment') {
                // L'ID vient d'une inscription, récupérer l'élève via l'inscription
                $enrollment = \App\Models\Enrollment::find($studentId);
                if ($enrollment && $enrollment->student) {
                    $preselectedStudent = $enrollment->student;
                }
            } else {
                // L'ID est directement celui de l'élève
                $preselectedStudent = Student::find($studentId);
            }
        }
        
        return view('parents.create', compact('students', 'preselectedStudent', 'fromContext'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:parents|unique:users',
            'phone' => 'required|string|max:255',
            'phone_2' => 'nullable|string|max:255',
            'gender' => 'required|in:male,female',
            'address' => 'nullable|string',
            'profession' => 'nullable|string|max:255',
            'workplace' => 'nullable|string|max:255',
            // Un lien par enfant : le lien de parente et les autorisations
            // decrivent la relation, pas la personne.
            'liens' => 'required|array|min:1',
            'liens.*.student_id' => 'required|exists:students,id',
            'liens.*.relationship_type' => ['required', 'in:father,mother,guardian,other', $this->lienCompatibleAvecLeSexe($request)],
            'liens.*.is_primary_contact' => 'nullable|boolean',
            'liens.*.lives_with_student' => 'nullable|boolean',
            'liens.*.can_pickup' => 'nullable|boolean',
        ], [
            'liens.required' => 'Rattachez le parent a au moins un eleve.',
            'liens.min' => 'Rattachez le parent a au moins un eleve.',
        ]);

        $parent = ParentModel::create(Arr::except($validated, 'liens'));
        
        $successMessage = 'Parent ajouté avec succès!';
        
        // Créer un compte utilisateur pour le parent seulement si un email est fourni
        if (!empty($validated['email'])) {
            $generatedPassword = $validated['phone'] . '1234';
            $user = User::create([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($generatedPassword),
                'role' => 'parent',
                'matricule' => 'PAR' . str_pad($parent->id, 6, '0', STR_PAD_LEFT)
            ]);
            
            // Lier le parent à l'utilisateur
            $parent->update(['user_id' => $user->id]);
            
            $successMessage = 'Parent ajouté avec succès! Mot de passe généré: ' . $generatedPassword;
        }
        
        $parent->students()->sync($this->liensParEleve($validated['liens']));

        return redirect()->route('parents.show', $parent)->with('success', $successMessage);
    }

    /**
     * Display the specified resource.
     */
    public function show(ParentModel $parent)
    {
        $parent->load([
            'students.enrollments' => fn ($q) => $q->where('status', 'active')
                ->with(['schoolClass.level', 'academicYear'])
                ->latest()
                ->limit(1),
        ]);

        return view('parents.show', compact('parent'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ParentModel $parent)
    {
        $students = Student::active()
            ->with(['enrollments' => fn ($q) => $q->where('status', 'active')
                ->with('schoolClass')
                ->latest()
                ->limit(1)])
            ->orderBy('last_name')->orderBy('first_name')
            ->get();
        $parent->load('students');

        return view('parents.edit', compact('parent', 'students'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ParentModel $parent)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:parents,email,' . $parent->id . '|unique:users,email,' . ($parent->user_id ?? 'NULL'),
            'phone' => 'required|string|max:255',
            'phone_2' => 'nullable|string|max:255',
            'gender' => 'required|in:male,female',
            'address' => 'nullable|string',
            'profession' => 'nullable|string|max:255',
            'workplace' => 'nullable|string|max:255',
            // Un lien par enfant : le lien de parente et les autorisations
            // decrivent la relation, pas la personne.
            'liens' => 'required|array|min:1',
            'liens.*.student_id' => 'required|exists:students,id',
            'liens.*.relationship_type' => ['required', 'in:father,mother,guardian,other', $this->lienCompatibleAvecLeSexe($request)],
            'liens.*.is_primary_contact' => 'nullable|boolean',
            'liens.*.lives_with_student' => 'nullable|boolean',
            'liens.*.can_pickup' => 'nullable|boolean',
        ], [
            'liens.required' => 'Rattachez le parent a au moins un eleve.',
            'liens.min' => 'Rattachez le parent a au moins un eleve.',
        ]);

        $parent->update(Arr::except($validated, 'liens'));
        $parent->students()->sync($this->liensParEleve($validated['liens']));

        return redirect()->route('parents.show', $parent)
            ->with('success', 'Parent modifié avec succès.');
    }

    /**
     * Un homme ne peut pas etre declare « mere », ni une femme « pere ».
     *
     * Rien ne l'empechait jusqu'ici : la base contenait 232 hommes enregistres
     * comme meres. Le lien reste libre par enfant — pere de son fils, tuteur
     * de son neveu — mais il doit rester compatible avec le sexe saisi.
     */
    private function lienCompatibleAvecLeSexe(Request $request): \Closure
    {
        $sexe = $request->input('gender');

        $interdit = match ($sexe) {
            'male' => 'mother',
            'female' => 'father',
            default => null,
        };

        return function (string $attribut, mixed $valeur, \Closure $echec) use ($interdit, $sexe) {
            if ($interdit === null || $valeur !== $interdit) {
                return;
            }

            $echec($sexe === 'male'
                ? 'Un parent de sexe masculin ne peut pas être enregistré comme mère.'
                : 'Un parent de sexe féminin ne peut pas être enregistré comme père.');
        };
    }

    /**
     * Met les liens saisis au format attendu par sync() : un identifiant
     * d'eleve en cle, ses attributs de pivot en valeur.
     *
     * C'est ce que l'ancien code omettait : attach() recevait une simple liste
     * d'identifiants, si bien que chaque lien retombait sur les valeurs par
     * defaut de la table — « pere, contact secondaire » — quel que soit le
     * formulaire.
     */
    private function liensParEleve(array $liens): array
    {
        return collect($liens)
            ->mapWithKeys(fn (array $lien) => [
                $lien['student_id'] => [
                    'relationship_type' => $lien['relationship_type'],
                    'is_primary_contact' => (bool) ($lien['is_primary_contact'] ?? false),
                    'lives_with_student' => (bool) ($lien['lives_with_student'] ?? false),
                    'can_pickup' => (bool) ($lien['can_pickup'] ?? false),
                ],
            ])
            ->all();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, ParentModel $parent)
    {
        $parent->delete();

            // La liste supprime via un formulaire classique ; l'API attend du JSON.
            if (! $request->expectsJson()) {
                return redirect()->route('parents.index')
                    ->with('success', 'Parent supprimé avec succès.');
            }

        return response()->json([
            'success' => true,
            'message' => 'Parent supprimé avec succès!'
        ]);
    }

    /**
     * Search parents
     */
    public function search(Request $request)
    {
        $query = ParentModel::query();

        // Recherche par 'q' (pour l'API)
        if ($request->has('q') && $request->q) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('relationship') && $request->relationship) {
            $query->where('relationship', $request->relationship);
        }

        $parents = $query->select('id', 'first_name', 'last_name', 'phone', 'email')
                        ->limit(20)
                        ->get();

        return response()->json($parents);
    }

    /**
     * Create and link a parent to a student
     */
    public function createAndLink(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'profession' => 'nullable|string|max:255',
            'is_primary_contact' => 'boolean',
            'student_id' => 'required|exists:students,id'
        ]);

        try {
            $parent = ParentModel::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'profession' => $validated['profession'] ?? null,
                'is_primary_contact' => $validated['is_primary_contact'] ?? false,
                'relationship' => 'guardian', // Par défaut
                'gender' => 'male', // Par défaut, peut être modifié plus tard
                'can_pickup' => true
            ]);

            // Lier le parent à l'étudiant
            $parent->students()->attach($validated['student_id']);

            return response()->json([
                'success' => true,
                'message' => 'Parent créé et lié avec succès',
                'parent' => $parent
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du parent: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Link an existing parent to a student
     */
    public function linkToStudent(Request $request, $studentId, $parentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            $parent = ParentModel::findOrFail($parentId);

            // Vérifier si la liaison existe déjà
            if ($student->parents()->where('parent_id', $parentId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce parent est déjà lié à cet élève'
                ], 400);
            }

            // Lier le parent à l'étudiant
            $student->parents()->attach($parentId);

            return response()->json([
                'success' => true,
                'message' => 'Parent lié avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la liaison: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unlink a parent from a student
     */
    public function unlinkFromStudent(Request $request, $studentId, $parentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            $parent = ParentModel::findOrFail($parentId);

            // Délier le parent de l'étudiant
            $student->parents()->detach($parentId);

            return response()->json([
                'success' => true,
                'message' => 'Parent délié avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du délien: ' . $e->getMessage()
            ], 500);
        }
    }
}
