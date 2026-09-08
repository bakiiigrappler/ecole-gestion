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
