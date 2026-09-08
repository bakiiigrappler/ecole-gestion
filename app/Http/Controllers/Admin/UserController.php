<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        $query = User::query();

        /*
         * Cet ecran gere le personnel de l'etablissement : direction et
         * secretariat. Les enseignants, les parents et les eleves ont leur
         * compte cree depuis leur propre fiche, avec ce qu'elle porte de
         * classes, d'enfants ou d'inscription — les lister ici melangerait
         * 900 comptes a la dizaine qui se gere vraiment.
         *
         * Le super administrateur reste hors liste : il ne depend d'aucun
         * etablissement.
         */
        $query->whereIn('role', \App\Support\Roles::personnel())
            ->where('role', '!=', 'superadmin');

        /*
         * Et seulement les comptes de son etablissement. Le modele `User` ne
         * porte pas le filtre global — le scope interrogerait l'utilisateur
         * connecte, dont la resolution passe par le modele — si bien que cet
         * ecran montrait a l'administrateur d'une ecole les comptes de toutes
         * les autres. Le super administrateur, lui, surplombe l'ensemble tant
         * qu'il ne s'est place dans aucun etablissement.
         */
        if ($ecole = \App\Support\EcoleCourante::id()) {
            $query->where('school_id', $ecole);
        }

        // Filtrage par rôle
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Filtrage par statut
        if ($request->has('status') && $request->status !== '') {
            $active = $request->status === 'active';
            $query->where('is_active', $active);
        }

        // Recherche
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return view('admin.users.index', [
            'users' => $users,
            'roles' => \App\Support\Roles::personnel(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        return view('admin.users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        /*
         * Seuls les roles du personnel s'attribuent ici, et le super
         * administrateur ne se transmet qu'entre super administrateurs.
         */
        $roles = \App\Support\Roles::attribuablesPar(auth()->user()->role);

        $donnees = $request->validate([
            'name' => 'required|string|max:255',
            // Le courriel devient facultatif : le matricule attribue suffit a
            // entrer, et tout le personnel n'a pas d'adresse.
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'telephone' => 'nullable|string|max:30',
            'role' => 'required|in:'.implode(',', $roles),
            'is_active' => 'boolean',
        ], [], [
            'name' => 'nom complet',
            'role' => 'rôle',
        ]);

        $numero = \App\Support\ComptesUtilisateurs::normaliserLeNumero($donnees['telephone'] ?? null);

        if ($numero && User::where('telephone', $numero)->exists()) {
            return back()->withInput()->withErrors([
                'telephone' => 'Ce numéro sert déjà à un autre compte.',
            ]);
        }

        $matricule = User::generateMatricule($donnees['name']);
        $motDePasse = \App\Support\ComptesUtilisateurs::motDePasseInitial();

        $user = User::create([
            'name' => $donnees['name'],
            'email' => $donnees['email'] ?: null,
            'telephone' => $numero,
            'password' => Hash::make($motDePasse),
            'matricule' => $matricule,
            'role' => $donnees['role'],
            // Le compte appartient a l'etablissement d'ou il est cree ; sans
            // cela il ne verrait aucune donnee une fois connecte.
            'school_id' => \App\Support\EcoleCourante::id(),
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        /*
         * Une redirection, et non du JSON : le formulaire poste normalement, et
         * l'ancienne version affichait sa reponse brute a l'ecran.
         */
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Compte créé. Matricule {$matricule}, mot de passe {$motDePasse}.",
                'user' => $user,
            ]);
        }

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Compte créé avec succès.')
            ->with('compte_ouvert', [
                'titre' => 'Compte '.mb_strtolower(\App\Support\Roles::libelle($user->role)).' ouvert',
                'identifiant' => $matricule,
                'courriel' => $user->email,
                'telephone' => $user->telephone,
                'mot_de_passe' => $motDePasse,
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        self::verifierLEtablissement($user);

        return view('admin.users.show', compact('user'));
    }

    /**
     * Engendrer un nouveau mot de passe, et l'afficher une fois.
     *
     * Le mot de passe initial n'est lisible qu'à l'écran qui suit la création :
     * il n'est pas conservé en clair. Passé cet écran, remettre un accès à
     * quelqu'un qui l'a perdu demandait d'aller en retaper un dans le
     * formulaire de modification. Ce bouton en engendre un et l'affiche dans
     * le même encart, prêt à être copié.
     */
    public function reinitialiserMotDePasse(User $user)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        self::verifierLEtablissement($user);

        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Vous ne pouvez pas modifier un super administrateur.');
        }

        $motDePasse = \App\Support\ComptesUtilisateurs::motDePasseInitial();

        $user->update(['password' => Hash::make($motDePasse)]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Un nouveau mot de passe a été engendré. L’ancien ne fonctionne plus.')
            ->with('compte_ouvert', [
                'titre' => 'Nouveau mot de passe — '.$user->name,
                'identifiant' => $user->matricule ?: $user->email,
                'courriel' => $user->email,
                'telephone' => $user->telephone,
                'mot_de_passe' => $motDePasse,
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        self::verifierLEtablissement($user);

        // Un admin ne peut pas modifier un superadmin
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Vous ne pouvez pas modifier un superadmin.');
        }

        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        self::verifierLEtablissement($user);

        // Un admin ne peut pas modifier un superadmin
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Vous ne pouvez pas modifier un superadmin.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // Facultatif, comme a la creation : le matricule suffit a entrer.
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'telephone' => 'nullable|string|max:30',
            'password' => 'nullable|string|min:8|confirmed',
            /*
             * Le catalogue fait foi. La liste etait ecrite ici a la main, et
             * elle avait deja diverge de ce que la vue proposait. Le role
             * actuel du compte y est ajoute : un enseignant ou un parent
             * modifie depuis cet ecran garderait sinon un role refuse par la
             * validation.
             */
            'role' => ['required', Rule::in(array_unique(array_merge(
                \App\Support\Roles::attribuablesPar(auth()->user()->role),
                [$user->role],
            )))],
            'is_active' => 'boolean'
        ]);

        // Un formulaire classique recevait le JSON brut a l'ecran : la reponse
        // suit desormais le format demande par l'appelant.
        if ($validator->fails()) {
            if (! $request->expectsJson()) {
                return back()->withErrors($validator)->withInput();
            }

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si l'utilisateur peut modifier le rôle vers superadmin
        if ($request->role === 'superadmin' && !auth()->user()->isSuperAdmin()) {
            if (! $request->expectsJson()) {
                return back()->withInput()->with('error', 'Seul un super administrateur peut attribuer ce rôle.');
            }

            return response()->json([
                'success' => false,
                'message' => 'Seul un superadmin peut attribuer le rôle superadmin.'
            ], 403);
        }

        $numero = \App\Support\ComptesUtilisateurs::normaliserLeNumero($request->telephone);

        if ($numero && User::where('telephone', $numero)->where('id', '!=', $user->id)->exists()) {
            return back()->withInput()->withErrors([
                'telephone' => 'Ce numéro sert déjà à un autre compte.',
            ]);
        }

        $updateData = [
            'name' => $request->name,
            'email' => $request->email ?: null,
            'telephone' => $numero,
            'role' => $request->role,
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        if (! $request->expectsJson()) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', 'Compte de '.$user->name.' mis à jour.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès !',
            'user' => $user->fresh()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        self::verifierLEtablissement($user);

        // Empêcher la suppression de son propre compte
        if ($user->id === auth()->id()) {
            if (! request()->expectsJson()) {
                return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            }

            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.'
            ], 422);
        }

        // Un admin ne peut pas supprimer un superadmin
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer un superadmin.'
            ], 403);
        }

        // Vérifier s'il s'agit du dernier superadmin
        if ($user->isSuperAdmin()) {
            $superAdminCount = User::where('role', 'superadmin')->count();
            if ($superAdminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer le dernier superadmin.'
                ], 422);
            }
        }

        $userName = $user->name;
        $user->delete();

        // La liste poste un formulaire ordinaire : lui repondre en JSON
        // affichait l'accolade brute a l'ecran.
        if (! request()->expectsJson()) {
            return redirect()->route('admin.users.index')
                ->with('success', 'Le compte de '.$userName.' a été supprimé.');
        }

        return response()->json([
            'success' => true,
            'message' => "Utilisateur '{$userName}' supprimé avec succès !"
        ]);
    }

    /**
     * Le compte relève-t-il bien de l'établissement où l'on se trouve ?
     *
     * La liste est cloisonnée, mais l'adresse d'une fiche se devine à un
     * chiffre près : sans ce garde, l'administrateur d'une école ouvrait —
     * et réinitialisait — le compte du proviseur d'une autre.
     */
    private static function verifierLEtablissement(User $user): void
    {
        $ecole = \App\Support\EcoleCourante::id();

        if ($ecole && $user->school_id && $user->school_id !== $ecole) {
            abort(403, 'Ce compte relève d’un autre établissement.');
        }
    }

    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleStatus(User $user)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        self::verifierLEtablissement($user);

        // Empêcher la désactivation de son propre compte
        if ($user->id === auth()->id()) {
            if (! request()->expectsJson()) {
                return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
            }

            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas désactiver votre propre compte.'
            ], 422);
        }

        // Un admin ne peut pas désactiver un superadmin
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier le statut d\'un superadmin.'
            ], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activé' : 'désactivé';
        
        if (! request()->expectsJson()) {
            return back()->with('success', 'Le compte de '.$user->name.' a été '.$status.'.');
        }

        return response()->json([
            'success' => true,
            'message' => "Utilisateur {$status} avec succès !",
            'is_active' => $user->is_active
        ]);
    }
}