@extends('layouts.app')

@section('titre', 'Paramètres de la plateforme')
@section('sous-titre', 'Règles communes à tous les établissements')

@section('contenu')

@php
    $interrupteur = function ($nom, $valeur) {
        return $valeur ? 'true' : 'false';
    };
@endphp

    <div class="flex items-start gap-3 rounded-xl border border-ogar-200 bg-ogar-50 px-5 py-3 text-sm text-ogar-800">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <span>
            Chacun de ces réglages commande réellement quelque chose : la page qu’il gouverne est indiquée sous lui.
            Les règles pédagogiques — cycles ouverts, barème, trimestres — restent propres à chaque école.
        </span>
    </div>

    <form method="POST" action="{{ route('admin.plateforme.parametres.save') }}" class="mt-6 space-y-4">
        @csrf

        {{-- ------------------------------------------------------------
             Affichage
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Affichage des listes</h2>
            </div>

            <div class="p-5">
                <label for="pagination_defaut" class="etiquette">Nombre de lignes par page</label>

                <div class="mt-1 flex flex-wrap gap-2">
                    @foreach ($paginations as $taille)
                        <label class="cursor-pointer">
                            <input type="radio" name="pagination_defaut" value="{{ $taille }}" class="peer sr-only"
                                   @checked((int) old('pagination_defaut', $parametres['pagination_defaut']) === $taille)>
                            <span class="block rounded-lg border border-gris-300 px-5 py-2 text-sm font-semibold text-gris-600
                                         transition peer-checked:border-ogar-500 peer-checked:bg-ogar-50 peer-checked:text-ogar-700">
                                {{ $taille }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <p class="mt-2 text-xs text-gris-400">
                    Valeur retenue quand l’URL n’en demande pas d’autre. L’utilisateur peut toujours la changer sur la page.
                </p>
                @error('pagination_defaut') <p class="mt-1 text-xs text-corail-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Corbeille
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Corbeille</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        Une suppression n’est plus définitive : elle passe d’abord par la corbeille.
                    </p>
                </div>
                <a href="{{ route('admin.plateforme.corbeille') }}"
                   class="text-xs font-semibold text-ogar-600 hover:underline">Ouvrir la corbeille</a>
            </div>

            <div class="p-5" x-data="{ jours: {{ (int) old('corbeille_jours', $parametres['corbeille_jours']) }} }">
                <label for="corbeille_jours" class="etiquette">Durée de conservation</label>

                <div class="mt-1 flex max-w-xs items-stretch overflow-hidden rounded-lg border border-gris-300">
                    <input type="number" name="corbeille_jours" id="corbeille_jours" min="1" max="365" x-model="jours"
                           class="w-full border-0 px-3 py-2 text-center text-lg font-semibold text-gris-800 outline-none">
                    <span class="grid place-items-center bg-gris-50 px-4 text-xs text-gris-500">jours</span>
                </div>

                <p class="mt-2 text-xs text-gris-400">
                    Au-delà de <span class="font-semibold text-gris-600" x-text="jours"></span> jours, un élément
                    devient éligible au vidage de la corbeille. Rien n’est détruit sans une action explicite.
                </p>
                @error('corbeille_jours') <p class="mt-1 text-xs text-corail-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Accès
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Accès à la plateforme</h2>
            </div>

            <div class="divide-y divide-gris-100">
                <label class="flex cursor-pointer items-center gap-3 px-5 py-4"
                       x-data="{ actif: {{ $interrupteur('connexion_ouverte', $parametres['connexion_ouverte']) }} }">
                    <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition"
                          :class="actif ? 'bg-ogar-600' : 'bg-gris-300'">
                        <input type="checkbox" name="connexion_ouverte" value="1" x-model="actif" class="sr-only">
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition"
                              :class="actif ? 'translate-x-[18px]' : 'translate-x-1'"></span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-gris-800">Connexion ouverte</span>
                        <span class="block text-xs text-gris-500">
                            Coupée, seuls les super administrateurs peuvent encore se connecter — le temps d’une intervention.
                        </span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-center gap-3 px-5 py-4"
                       x-data="{ actif: {{ $interrupteur('inscriptions_en_ligne', $parametres['inscriptions_en_ligne']) }} }">
                    <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition"
                          :class="actif ? 'bg-ogar-600' : 'bg-gris-300'">
                        <input type="checkbox" name="inscriptions_en_ligne" value="1" x-model="actif" class="sr-only">
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition"
                              :class="actif ? 'translate-x-[18px]' : 'translate-x-1'"></span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-gris-800">Inscriptions en ligne</span>
                        <span class="block text-xs text-gris-500">
                            Ouvre le formulaire public d’inscription du portail parent.
                        </span>
                    </span>
                </label>

                <div class="px-5 py-4">
                    <label for="message_connexion" class="etiquette">Message sur la page de connexion</label>
                    <input type="text" name="message_connexion" id="message_connexion" maxlength="300" class="champ"
                           value="{{ old('message_connexion', $parametres['message_connexion']) }}"
                           placeholder="Maintenance prévue samedi de 8 h à 10 h">
                    <p class="mt-1 text-xs text-gris-400">
                        Bandeau affiché à tous, avant la saisie des identifiants. Laisser vide pour ne rien annoncer.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex justify-end border-t border-gris-200 pt-4">
            <button type="submit" class="bouton-primaire">Enregistrer les paramètres</button>
        </div>
    </form>

@endsection
