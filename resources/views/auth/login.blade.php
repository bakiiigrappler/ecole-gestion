<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1C75BC">
    <title>{{ $marque['login_titre'] ?: 'Connexion' }} &middot; {{ $marque['app_nom'] ?? 'Egesco' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-gris-50 font-sans text-gris-800 antialiased">

{{-- Fond decoratif --}}
<div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-ogar-100/70 blur-3xl"></div>
    <div class="absolute -bottom-40 -right-24 h-[28rem] w-[28rem] rounded-full bg-soleil-100/60 blur-3xl"></div>
</div>

<main class="relative flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:py-12">
    <div class="w-full max-w-5xl">

        <div class="entree-douce grid overflow-hidden rounded-2xl border border-gris-200 bg-white shadow-xl lg:grid-cols-2">

            {{-- ------------------------------------------------------------
                 Volet illustré
                 ------------------------------------------------------------ --}}
            <section class="relative hidden flex-col justify-between bg-gradient-to-br from-ogar-50 via-white to-ogar-100 p-10 lg:flex">
                <div>
                    <h2 class="text-2xl font-semibold leading-snug text-ardoise-900">
                        La scolarité de vos élèves,<br>
                        <span class="text-ogar-600">suivie de bout en bout.</span>
                    </h2>
                    <p class="mt-3 max-w-md text-sm leading-relaxed text-gris-600">
                        Inscriptions, notes, bulletins, présences et frais scolaires du préprimaire
                        au lycée, réunis dans un dossier unique par élève.
                    </p>
                </div>

                {{-- Illustration : un dossier d'élève qui se remplit --}}
                <div class="flex justify-center py-4">

                    <svg class="scene-flottante h-56 w-auto" viewBox="0 0 260 200" fill="none" role="img"
                         aria-label="Dossiers scolaires numérisés">
                        <circle class="halo-pulse" cx="130" cy="100" r="78" fill="#ddeefa"/>

                        {{-- Feuilles en arrière-plan --}}
                        <rect x="72" y="46" width="104" height="128" rx="8" fill="#ffffff" stroke="#b4ddf5" stroke-width="2"/>
                        <rect x="84" y="34" width="104" height="128" rx="8" fill="#ffffff" stroke="#7fc4ec" stroke-width="2"/>

                        {{-- Feuille principale --}}
                        <rect x="96" y="22" width="104" height="128" rx="8" fill="#ffffff" stroke="#1c75bc" stroke-width="2"/>

                        {{-- En-tête de la fiche --}}
                        <rect x="110" y="38" width="34" height="34" rx="17" fill="#ddeefa"/>
                        <circle cx="127" cy="50" r="7" fill="#1c75bc"/>
                        <path d="M115 66c2.6-5.2 7.2-8 12-8s9.4 2.8 12 8" fill="#1c75bc"/>
                        <rect x="152" y="42" width="36" height="6" rx="3" fill="#b4ddf5"/>
                        <rect x="152" y="55" width="24" height="6" rx="3" fill="#ddeefa"/>

                        {{-- Lignes de la fiche --}}
                        <rect x="110" y="88" width="78" height="6" rx="3" fill="#ececed"/>
                        <rect x="110" y="102" width="60" height="6" rx="3" fill="#ececed"/>
                        <rect x="110" y="116" width="70" height="6" rx="3" fill="#ececed"/>

                        {{-- Mention validée --}}
                        <circle cx="118" cy="134" r="9" fill="#f7941e"/>
                        <path d="M114 134l3 3 6-6" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="134" y="131" width="54" height="6" rx="3" fill="#ffefd4"/>

                        {{-- Toque, en rappel de l'emblème --}}
                        <path d="M196 150l26 11-26 11-26-11 26-11Z" fill="#1c75bc"/>
                        <path d="M181 167v9c0 3.6 6.7 6.5 15 6.5s15-2.9 15-6.5v-9l-15 6.4-15-6.4Z" fill="#1a4f7e"/>
                    </svg>
                </div>

                <ul class="grid grid-cols-3 gap-3 border-t border-ogar-200/70 pt-6">
                    @foreach ([
                        ['libelle' => 'Dossiers centralisés', 'couleur' => 'text-ogar-600', 'trace' => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z'],
                        ['libelle' => 'Bulletins édités', 'couleur' => 'text-soleil-600', 'trace' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['libelle' => 'Accès sécurisé', 'couleur' => 'text-corail-600', 'trace' => 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z'],
                    ] as $atout)
                        <li class="flex flex-col items-center gap-2 text-center">
                            <svg class="h-6 w-6 {{ $atout['couleur'] }}" fill="none" stroke="currentColor"
                                 stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $atout['trace'] }}"/>
                            </svg>
                            <span class="text-xs font-medium text-gris-600">{{ $atout['libelle'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- ------------------------------------------------------------
                 Formulaire
                 ------------------------------------------------------------ --}}
            <section class="flex flex-col justify-center p-8 sm:p-10 lg:p-12">

                {{-- Page partagée par tous les établissements : l'emblème est celui
                     de l'application, pas celui d'une école en particulier. --}}
                <x-logo taille="2xl" :application="true" class="entree-douce mb-6"/>

                <div class="entree-douce entree-douce--2">
                    {{-- Page commune à tous les établissements : nommer l'une
                         d'elles ici serait faux dès la deuxième école. Ces textes se
                         règlent dans la personnalisation de la plateforme. --}}
                    <h1 class="text-2xl font-semibold text-ardoise-900">
                        {{ $marque['login_titre'] ?: 'Connexion' }}
                    </h1>
                    <p class="mt-1 text-sm text-gris-500">
                        {{ $marque['app_nom'] ?? 'Egesco' }} &mdash; {{ $marque['login_sous_titre'] }}
                    </p>
                </div>

                {{-- Annonce de la plateforme : maintenance prevue, fermeture
                     temporaire. Elle s'ecrit dans les parametres. --}}
                @if (! empty($plateforme['message_connexion']))
                    <div role="status"
                         class="entree-douce mt-6 flex items-start gap-3 rounded-lg border border-soleil-200 bg-soleil-50 px-4 py-3 text-sm text-soleil-800">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"
                             viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                        </svg>
                        <span>{{ $plateforme['message_connexion'] }}</span>
                    </div>
                @endif

                @unless ($plateforme['connexion_ouverte'] ?? true)
                    <div role="status"
                         class="entree-douce mt-6 flex items-start gap-3 rounded-lg border border-corail-200 bg-corail-50 px-4 py-3 text-sm text-corail-800">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"
                             viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                        </svg>
                        <span>
                            <strong>La plateforme est momentanément fermée.</strong>
                            Seuls les super administrateurs peuvent se connecter.
                        </span>
                    </div>
                @endunless

                @if (session('success'))
                    <div role="status"
                         class="entree-douce mt-6 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"
                             viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div role="alert"
                         class="mt-6 flex items-start gap-3 rounded-lg border border-corail-200 bg-corail-50 px-4 py-3 text-sm text-corail-800">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"
                             viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}"
                      x-data="{
                          motDePasseVisible: false,
                          envoi: false,
                          profil: null,
                          remplir(compte) {
                              this.profil = compte.email;
                              this.$refs.identifiant.value = compte.email;
                              this.$refs.motDePasse.value = compte.motDePasse;
                              this.$refs.envoyer.focus();
                          },
                      }"
                      @submit="envoi = true"
                      class="entree-douce entree-douce--3 mt-7 space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="etiquette">Adresse e-mail ou matricule</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-gris-400" fill="none" stroke="currentColor" stroke-width="1.6"
                                     viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                </svg>
                            </span>
                            {{-- type="text" et non "email" : le navigateur refuserait un matricule,
                                 avec lequel les élèves du lycée se connectent. --}}
                            <input id="email" name="email" type="text" required autofocus x-ref="identifiant"
                                   value="{{ old('email') }}" placeholder="vous@exemple.ga ou STU00123"
                                   autocomplete="username" spellcheck="false"
                                   @if ($errors->any()) aria-invalid="true" @endif
                                   class="champ py-3 pl-11 text-base">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="etiquette">Mot de passe</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-gris-400" fill="none" stroke="currentColor" stroke-width="1.6"
                                     viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                </svg>
                            </span>

                            <input id="password" name="password" required x-ref="motDePasse"
                                   :type="motDePasseVisible ? 'text' : 'password'"
                                   autocomplete="current-password"
                                   class="champ py-3 pl-11 pr-12 text-base">

                            <button type="button" @click="motDePasseVisible = !motDePasseVisible"
                                    class="absolute inset-y-0 right-0 flex w-12 cursor-pointer items-center justify-center rounded-r-lg text-gris-400 transition-colors hover:text-ogar-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ogar-600"
                                    :aria-label="motDePasseVisible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                                <svg x-show="!motDePasseVisible" class="h-5 w-5" fill="none" stroke="currentColor"
                                     stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <svg x-show="motDePasseVisible" x-cloak class="h-5 w-5" fill="none" stroke="currentColor"
                                     stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.774 3.162 10.066 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gris-600">
                        <input type="checkbox" name="remember" value="1"
                               class="h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                        Rester connecté sur ce poste
                    </label>

                    <button type="submit" x-ref="envoyer" class="bouton-primaire w-full py-3 text-base" :disabled="envoi">
                        <svg x-show="envoi" x-cloak class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                        </svg>
                        <span x-text="envoi ? 'Connexion en cours...' : 'Se connecter'">Se connecter</span>
                    </button>

                    {{-- Accès rapide : un bouton par profil, qui remplit les deux champs.
                         Confort de démonstration, coupé en production. --}}
                    @php
                        /*
                         * Aux comptes de démonstration s'ajoute un élève réel du
                         * lycée : son compte n'est pas fixé en configuration, il
                         * naît de son inscription. Identifiant et mot de passe
                         * initial sont son matricule.
                         */
                        $comptesDemo = config('demo.comptes', []);

                        if (config('demo.acces_rapide') && ($marque['login_acces_rapide'] ?? true)) {
                            $eleve = \App\Models\User::where('role', 'student')
                                ->whereNotNull('matricule')
                                ->orderBy('matricule')
                                ->first();

                            if ($eleve) {
                                $comptesDemo[] = [
                                    'libelle' => 'Élève (lycée)',
                                    'email' => $eleve->matricule,
                                    'mot_de_passe' => $eleve->matricule,
                                ];
                            }
                        }
                    @endphp

                    @if (config('demo.acces_rapide') && ($marque['login_acces_rapide'] ?? true) && ! empty($comptesDemo))
                        <div class="border-t border-gris-200 pt-5">
                            <div class="mb-3 flex items-baseline justify-between gap-3">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gris-500">
                                    Accès rapide
                                </span>
                                <span class="text-[11px] text-gris-400">Remplit les champs, ne connecte pas</span>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($comptesDemo as $compte)
                                    <button type="button"
                                            @click="remplir({{ Js::from(['email' => $compte['email'], 'motDePasse' => $compte['mot_de_passe']]) }})"
                                            :aria-pressed="profil === @js($compte['email'])"
                                            :class="profil === @js($compte['email'])
                                                ? 'border-ogar-600 bg-ogar-50 text-ogar-800'
                                                : 'border-gris-200 bg-white text-gris-700 hover:border-ogar-300 hover:bg-gris-50'"
                                            class="flex min-h-11 cursor-pointer flex-col items-start justify-center rounded-lg border px-3 py-2 text-left transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ogar-600">
                                        <span class="text-xs font-semibold leading-tight">{{ $compte['libelle'] }}</span>
                                        <span class="truncate text-[11px] text-gris-400">{{ $compte['email'] }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <p class="mt-3 text-[11px] leading-relaxed text-gris-400">
                                Choisissez un profil, puis &laquo; Se connecter &raquo;. Le mot de passe
                                est pré-rempli : rendez-le visible avec l’icône en bout de champ.
                            </p>
                        </div>
                    @endif
                </form>

                <p class="entree-douce entree-douce--4 mt-7 flex items-start gap-2.5 rounded-lg bg-gris-50 px-4 py-3 text-xs leading-relaxed text-gris-500">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-ogar-600" fill="none" stroke="currentColor"
                         stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Parent d'élève ? Le suivi de votre enfant se fait depuis
                        <a href="{{ route('parent-portal.login') }}" class="font-semibold text-ogar-600 hover:underline">le portail des parents</a>.</span>
                </p>
            </section>
        </div>

        <p class="mt-6 text-center text-xs text-gris-400">
            {{ $marque['app_nom'] ?? 'Egesco' }} &middot; &copy; {{ date('Y') }}
        </p>
    </div>
</main>

<style>[x-cloak]{display:none !important}</style>
</body>
</html>
