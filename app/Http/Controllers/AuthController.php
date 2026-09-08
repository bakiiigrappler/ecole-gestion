<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\School;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Afficher le formulaire de connexion
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Traiter la connexion
     */
    public function login(Request $request)
    {
        // Les élèves du lycée se connectent avec leur matricule : le champ
        // accepte donc les deux formes d'identifiant.
        $request->validate([
            'email' => 'required|string',
            'password' => 'required',
        ]);

        $identifiant = trim($request->input('email'));
        $champ = filter_var($identifiant, FILTER_VALIDATE_EMAIL) ? 'email' : 'matricule';

        if (Auth::attempt([$champ => $identifiant, 'password' => $request->input('password')], $request->boolean('remember'))) {
            $utilisateur = Auth::user();

            // Un établissement fermé ne laisse plus entrer ses comptes ; le
            // superadmin, lui, n'appartient à aucun.
            $etablissement = $utilisateur->school_id ? School::find($utilisateur->school_id) : null;

            if ($etablissement && ! $etablissement->is_active) {
                Auth::logout();
                $request->session()->invalidate();

                throw ValidationException::withMessages([
                    'email' => ['L’accès de votre établissement est suspendu. Rapprochez-vous de son administration.'],
                ]);
            }

            /*
             * Connexion fermee : le temps d'une intervention, seuls les super
             * administrateurs entrent. Le controle vient apres l'authentification
             * pour ne pas reveler quels comptes existent.
             */
            if (! \App\Support\ParametresPlateforme::actif('connexion_ouverte')
                && $utilisateur->role !== 'superadmin') {
                Auth::logout();
                $request->session()->invalidate();

                throw ValidationException::withMessages([
                    'email' => ['L’accès à la plateforme est momentanément fermé. Réessayez plus tard.'],
                ]);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Connexion réussie ! Bienvenue dans le système de gestion d\'école.');
        }

        throw ValidationException::withMessages([
            'email' => ['Les identifiants fournis ne correspondent pas à nos enregistrements.'],
        ]);
    }

    /**
     * Afficher le formulaire d'inscription
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Traiter l'inscription
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Compte créé avec succès ! Bienvenue dans le système de gestion d\'école.');
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Vous avez été déconnecté avec succès.');
    }
}
