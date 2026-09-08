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

        // Exclure les superadmins de la liste
        $query->where('role', '!=', 'superadmin');

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
        
        return view('admin.users.index', compact('users'));
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
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            // 'parent' manquait alors que la liste en affiche : enregistrer un tel
            // compte echouait sur la validation du role.
            'role' => 'required|in:superadmin,admin,teacher,secretary,parent',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si l'utilisateur peut créer un superadmin
        if ($request->role === 'superadmin' && !auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un superadmin peut créer un autre superadmin.'
            ], 403);
        }

        // Générer le mot de passe par défaut : premier nom + 1234
        $firstName = explode(' ', trim($request->name))[0];
        $defaultPassword = $firstName . '1234';

        // Générer le matricule automatiquement
        $matricule = User::generateMatricule($request->name);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($defaultPassword),
            'matricule' => $matricule,
            'role' => $request->role,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Utilisateur créé avec succès ! Matricule : {$matricule} - Mot de passe : {$defaultPassword}",
            'user' => $user
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

        // Charger les relations pour les parents
        if ($user->role === 'parent') {
            $parentModel = \App\Models\ParentModel::where('user_id', $user->id)
                ->with('students')
                ->first();
        }

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

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

        // Un admin ne peut pas modifier un superadmin
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Vous ne pouvez pas modifier un superadmin.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'password' => 'nullable|string|min:8|confirmed',
            // 'parent' manquait alors que la liste en affiche : enregistrer un tel
            // compte echouait sur la validation du role.
            'role' => 'required|in:superadmin,admin,teacher,secretary,parent',
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

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
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

        // Empêcher la suppression de son propre compte
        if ($user->id === auth()->id()) {
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

        return response()->json([
            'success' => true,
            'message' => "Utilisateur '{$userName}' supprimé avec succès !"
        ]);
    }

    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleStatus(User $user)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        // Empêcher la désactivation de son propre compte
        if ($user->id === auth()->id()) {
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
        
        return response()->json([
            'success' => true,
            'message' => "Utilisateur {$status} avec succès !",
            'is_active' => $user->is_active
        ]);
    }
}