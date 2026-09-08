@extends('layouts.app')

@section('titre', 'Personnalisation de la plateforme')
@section('sous-titre', 'Nom, logo et textes communs à tous les établissements')

@section('actions-entete')
    <a href="{{ route('login') }}" target="_blank" class="bouton-secondaire">Voir la page de connexion</a>
@endsection

@section('contenu')

    {{-- ----------------------------------------------------------------
         Ce qui se règle ici ne concerne pas une école en particulier
         ---------------------------------------------------------------- --}}
    <div class="flex items-start gap-3 rounded-xl border border-ogar-200 bg-ogar-50 px-5 py-3 text-sm text-ogar-800">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <span>
            Ces réglages habillent <strong>l’application</strong> : la barre latérale, la page de connexion, le pied de page.
            Le nom, le logo et la devise de chaque école se règlent, eux, dans
            <em>Paramètres établissement</em>, depuis le compte de son administrateur.
        </span>
    </div>

@php
    /*
     * Production ou test : deux instances separees, deux bases. Le bandeau dit
     * laquelle on regarde, et propose d'aller sur l'autre quand son adresse est
     * renseignee dans le .env.
     */
    $environnement = config('environnement.nom');
    $estUnTest = $environnement === 'test';
@endphp

    <div class="carte mt-6 overflow-hidden {{ $estUnTest ? 'border-soleil-300' : '' }}">
        <div class="flex items-center gap-2 border-b px-5 py-3
                    {{ $estUnTest ? 'border-soleil-200 bg-soleil-50' : 'border-gris-100 bg-gris-50' }}">
            <svg class="h-5 w-5 {{ $estUnTest ? 'text-soleil-600' : 'text-ogar-600' }}"
                 fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5m4.75-11.396c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/>
            </svg>
            <h2 class="text-sm font-semibold {{ $estUnTest ? 'text-soleil-700' : 'text-ogar-700' }}">Environnement</h2>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4 p-5">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold
                             {{ $estUnTest ? 'bg-soleil-100 text-soleil-700' : 'bg-emerald-100 text-emerald-700' }}">
                    <span class="h-2 w-2 rounded-full {{ $estUnTest ? 'bg-soleil-500' : 'bg-emerald-500' }}"></span>
                    {{ $estUnTest ? 'TEST' : 'PRODUCTION' }}
                </span>

                <p class="text-xs text-gris-500">
                    {{ $estUnTest
                        ? 'Données fictives : la production n’est pas touchée. C’est ici qu’on essaie et qu’on forme.'
                        : 'Vraie base de l’établissement : toute action porte sur les données réelles.' }}
                </p>
            </div>

            @if ($estUnTest && config('environnement.url_prod'))
                <a href="{{ config('environnement.url_prod') }}" class="bouton-secondaire shrink-0">
                    Aller en production
                </a>
            @elseif (! $estUnTest && config('environnement.url_test'))
                <a href="{{ config('environnement.url_test') }}" target="_blank"
                   class="bouton-secondaire shrink-0 border-soleil-300 bg-soleil-50 text-soleil-700">
                    Ouvrir l’environnement de test
                </a>
            @else
                <p class="shrink-0 text-[11px] text-gris-400">
                    Aucune autre instance déclarée (EGESCO_URL_TEST dans le .env).
                </p>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('admin.plateforme.personnalisation.save') }}"
          enctype="multipart/form-data"
          x-data="{ apercuLogo: @js($logo) }"
          class="mt-6 space-y-4">
        @csrf

        {{-- ------------------------------------------------------------
             Identité
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Identité de l’application</h2>
                    <p class="mt-0.5 text-xs text-gris-400">Ce que porte la barre latérale et l’onglet du navigateur.</p>
                </div>
            </div>

            <div class="grid gap-6 p-5 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <div>
                        <label for="app_nom" class="etiquette">Nom de l’application <span class="text-corail-600">*</span></label>
                        <input type="text" name="app_nom" id="app_nom" maxlength="60" required class="champ"
                               value="{{ old('app_nom', $marque['app_nom']) }}">
                        @error('app_nom') <p class="mt-1 text-xs text-corail-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="app_slogan" class="etiquette">Slogan</label>
                        <input type="text" name="app_slogan" id="app_slogan" maxlength="120" class="champ"
                               value="{{ old('app_slogan', $marque['app_slogan']) }}" placeholder="Gestion scolaire">
                        <p class="mt-1 text-xs text-gris-400">Affiché sous le nom, dans la barre latérale.</p>
                        @error('app_slogan') <p class="mt-1 text-xs text-corail-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Logo --}}
                <div>
                    <span class="etiquette">Logo</span>
                    <div class="mt-1 flex flex-col items-center gap-3 rounded-xl border-2 border-dashed border-gris-300 bg-gris-50 p-5 text-center">
                        <template x-if="apercuLogo">
                            <img :src="apercuLogo" alt="Logo de la plateforme" class="h-16 w-auto max-w-[150px] object-contain">
                        </template>
                        <template x-if="! apercuLogo">
                            <span class="flex flex-col items-center gap-2">
                                <x-logo taille="lg" :application="true"/>
                                <span class="text-[11px] text-gris-400">Emblème dessiné, par défaut</span>
                            </span>
                        </template>

                        <label class="bouton-secondaire cursor-pointer">
                            Choisir un logo
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="sr-only"
                                   @change="const f = $event.target.files[0]; if (f) apercuLogo = URL.createObjectURL(f)">
                        </label>

                        <p class="text-[11px] text-gris-400">PNG, JPG, WEBP ou SVG &middot; 2 Mo maximum</p>
                    </div>
                    @error('logo') <p class="mt-1 text-xs text-corail-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Disposition de la navigation
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden"
             x-data="{ nav: '{{ old('nav_layout', in_array(request('nav'), ['sidebar', 'header'], true) ? request('nav') : ($marque['nav_layout'] ?? 'sidebar')) }}' }">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Disposition de la navigation</h2>
                    <p class="mt-0.5 text-xs text-gris-400">Où se tient le menu : sur le côté, ou en haut de la page.</p>
                </div>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2">

                {{-- Barre latérale --}}
                <label class="cursor-pointer">
                    <input type="radio" name="nav_layout" value="sidebar" x-model="nav" class="sr-only">

                    <div class="rounded-2xl border-2 bg-white p-3 transition"
                         :class="nav === 'sidebar' ? 'border-ogar-500 bg-ogar-50/40 shadow-sm' : 'border-gris-200 hover:border-ogar-300'">

                        {{-- Maquette : colonne sombre à gauche, contenu à droite --}}
                        <div class="flex h-28 overflow-hidden rounded-lg ring-1 ring-gris-200">
                            <div class="flex w-1/3 flex-col gap-1.5 bg-ardoise-900 p-2">
                                <div class="mb-0.5 flex items-center gap-1">
                                    <span class="h-3 w-3 rounded bg-white"></span>
                                    <span class="h-1.5 w-7 rounded bg-white/50"></span>
                                </div>
                                <span class="h-2.5 w-full rounded bg-ogar-600"></span>
                                <span class="h-2.5 w-3/4 rounded bg-white/20"></span>
                                <span class="h-2.5 w-3/4 rounded bg-white/20"></span>
                                <span class="h-2.5 w-2/3 rounded bg-white/20"></span>
                            </div>
                            <div class="flex flex-1 flex-col bg-white">
                                <div class="flex h-6 items-center gap-1 border-b border-gris-100 px-2">
                                    <span class="h-2 w-14 rounded bg-gris-300"></span>
                                    <span class="ml-auto h-3 w-3 rounded-full bg-gris-200"></span>
                                </div>
                                <div class="flex flex-1 flex-col gap-1.5 p-2">
                                    <span class="h-3.5 w-full rounded bg-gris-100"></span>
                                    <span class="h-3.5 w-full rounded bg-gris-100"></span>
                                    <span class="h-3.5 w-2/3 rounded bg-gris-100"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-2">
                            <span>
                                <span class="block text-sm font-semibold text-gris-800">Barre latérale</span>
                                <span class="block text-xs text-gris-500">Menu vertical à gauche</span>
                            </span>
                            <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full border-2 transition"
                                  :class="nav === 'sidebar' ? 'border-ogar-600 bg-ogar-600 text-white' : 'border-gris-300 text-transparent'">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </label>

                {{-- Barre d'en-tête --}}
                <label class="cursor-pointer">
                    <input type="radio" name="nav_layout" value="header" x-model="nav" class="sr-only">

                    <div class="rounded-2xl border-2 bg-white p-3 transition"
                         :class="nav === 'header' ? 'border-ogar-500 bg-ogar-50/40 shadow-sm' : 'border-gris-200 hover:border-ogar-300'">

                        {{-- Maquette : barre sombre, sous-barre claire, contenu --}}
                        <div class="flex h-28 flex-col overflow-hidden rounded-lg ring-1 ring-gris-200">
                            <div class="flex items-center gap-1 bg-ardoise-900 px-2 py-1.5">
                                <span class="h-3 w-3 rounded bg-white"></span>
                                <span class="ml-0.5 h-2 w-8 rounded bg-ogar-600"></span>
                                <span class="h-2 w-6 rounded bg-white/25"></span>
                                <span class="h-2 w-6 rounded bg-white/25"></span>
                                <span class="hidden h-2 w-5 rounded bg-white/25 sm:block"></span>
                                <span class="ml-auto h-3 w-3 rounded-full bg-white/30"></span>
                            </div>
                            <div class="flex items-center gap-2 border-b border-gris-100 bg-white px-2 py-1.5">
                                <span class="h-1.5 w-8 rounded bg-ogar-500"></span>
                                <span class="h-1.5 w-6 rounded bg-gris-200"></span>
                                <span class="h-1.5 w-6 rounded bg-gris-200"></span>
                            </div>
                            <div class="flex flex-1 flex-col gap-1.5 bg-white p-2">
                                <span class="h-3.5 w-full rounded bg-gris-100"></span>
                                <span class="h-3.5 w-full rounded bg-gris-100"></span>
                                <span class="h-3.5 w-2/3 rounded bg-gris-100"></span>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-2">
                            <span>
                                <span class="block text-sm font-semibold text-gris-800">Barre d’en-tête</span>
                                <span class="block text-xs text-gris-500">Menu horizontal + sous-menu</span>
                            </span>
                            <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full border-2 transition"
                                  :class="nav === 'header' ? 'border-ogar-600 bg-ogar-600 text-white' : 'border-gris-300 text-transparent'">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </label>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gris-100 px-5 py-3">
                <p class="text-xs text-gris-400">
                    La barre d’en-tête reprend la même arborescence à deux niveaux — rubriques en haut,
                    liens de la rubrique en dessous — avec un contenu centré.
                </p>

                <a :href="'{{ route('dashboard') }}?apercu_nav=' + nav" target="_blank"
                   class="bouton-secondaire shrink-0">
                    Prévisualiser <span class="font-normal text-gris-400">(sans enregistrer)</span>
                </a>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Page de connexion
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Page de connexion</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        La seule page commune à tous les établissements : elle ne peut nommer aucune école.
                    </p>
                </div>
            </div>

            <div class="space-y-4 p-5">
                <div>
                    <label for="login_titre" class="etiquette">Titre</label>
                    <input type="text" name="login_titre" id="login_titre" maxlength="120" class="champ"
                           value="{{ old('login_titre', $marque['login_titre']) }}">
                </div>

                <div>
                    <label for="login_sous_titre" class="etiquette">Phrase d’accueil</label>
                    <textarea name="login_sous_titre" id="login_sous_titre" rows="2" maxlength="400" class="champ">{{ old('login_sous_titre', $marque['login_sous_titre']) }}</textarea>
                </div>

                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gris-200 bg-gris-50 px-4 py-3"
                       x-data="{ actif: {{ $marque['login_acces_rapide'] ? 'true' : 'false' }} }">
                    <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition"
                          :class="actif ? 'bg-ogar-600' : 'bg-gris-300'">
                        <input type="checkbox" name="login_acces_rapide" value="1" x-model="actif" class="sr-only">
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition"
                              :class="actif ? 'translate-x-[18px]' : 'translate-x-1'"></span>
                    </span>
                    <span>
                        <span class="block text-sm font-medium text-gris-800">Accès rapide aux comptes de démonstration</span>
                        <span class="block text-xs text-gris-500">
                            Affiche les boutons qui pré-remplissent les identifiants. À couper en production.
                        </span>
                    </span>
                </label>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Pied de page
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Pied de page</h2>
            </div>

            <div class="p-5">
                <label for="pied_de_page" class="etiquette">Mention</label>
                <input type="text" name="pied_de_page" id="pied_de_page" maxlength="200" class="champ"
                       value="{{ old('pied_de_page', $marque['pied_de_page']) }}">
                <p class="mt-1 text-xs text-gris-400">
                    Affichée en bas de chaque page, après le nom de l’établissement et l’année.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gris-200 pt-4">
            <x-confirmation :action="route('admin.plateforme.personnalisation.reset')" methode="POST"
                            titre="Revenir aux valeurs d’origine ?"
                            message="Le nom, le slogan, les textes de connexion et le logo téléversé seront effacés."
                            confirmer="Réinitialiser"
                            bouton="bouton-secondaire">
                Réinitialiser
            </x-confirmation>

            <button type="submit" class="bouton-primaire">Enregistrer la personnalisation</button>
        </div>
    </form>

@endsection
