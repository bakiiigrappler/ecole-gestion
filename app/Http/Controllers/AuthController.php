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
        /*
         * Trois formes d'identifiant, pour un seul champ.
         *
         * Une adresse électronique, un matricule — les élèves du lycée n'ont
         * que celui-là — ou un numéro de téléphone : un parent n'a pas
         * toujours de courriel, et un enseignant retient plus sûrement son
         * numéro que l'adresse ouverte pour l'occasion.
         *
         * Le numéro est ramené à ses chiffres avant d'être cherché : « 06 12
         * 34 56 78 » et « +241 06123456 78 » sont le même abonné.
         */
        $request->validate([
            'email' => 'required|string',
            'password' => 'required',
        ]);

        $identifiant = trim($request->input('email'));

        $sansLettre = ! preg_match('/\p{L}/u', $identifiant);

        if (filter_var($identifiant, FILTER_VALIDATE_EMAIL)) {
            $champ = 'email';
        } elseif ($sansLettre && $numero = \App\Support\ComptesUtilisateurs::normaliserLeNumero($identifiant)) {
            /*
             * L'absence de lettre départage : un matricule de parent tel que
             * « PAR000123 » porte six chiffres et passerait sans cela pour un
             * numéro de téléphone, que l'on chercherait en vain.
             */
            $champ = 'telephone';
            $identifiant = $numero;
        } else {
            $champ = 'matricule';
        }

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
