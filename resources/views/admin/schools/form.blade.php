@extends('layouts.app')

@section('titre', $school->exists ? 'Modifier — '.$school->name : 'Nouvel établissement')
@section('sous-titre', $school->exists
    ? 'Code '.$school->code.' · '.($school->city ?? '—')
    : 'Créer un établissement et son compte administrateur')

@section('actions-entete')
    <a href="{{ route('admin.schools.index') }}" class="bouton-secondaire">Tous les établissements</a>
@endsection

@section('contenu')

@php
    $cyclesChoisis = old('cycles', $school->exists ? $school->cyclesOuverts() : []);
@endphp

<form method="POST"
      action="{{ $school->exists ? route('admin.schools.update', $school) : route('admin.schools.store') }}"
      class="grid gap-6 lg:grid-cols-3">
    @csrf
    @if ($school->exists)
        @method('PUT')
    @endif

    <div class="space-y-4 lg:col-span-2">

        {{-- ------------------------------------------------------------
             Identité — le strict nécessaire pour ouvrir l'établissement.
             Coordonnées, logo, sceau et direction sont saisis par son
             propre administrateur, dans « Paramètres établissement ».
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Identité</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        Les coordonnées et les emblèmes sont renseignés par l’administrateur de l’établissement.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label for="name" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Nom de l’établissement <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="name" id="name" required
                           value="{{ old('name', $school->name) }}"
                           placeholder="Complexe scolaire Les Étoiles" class="champ w-full text-sm">
                    @error('name')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Code</span>
                    {{-- Engendré à la création et figé ensuite : il sert de
                         référence aux matricules et aux comptes. --}}
                    <p class="rounded-lg border border-gris-200 bg-gris-50 px-3 py-2 font-mono text-sm text-gris-600">
                        {{ $school->code ?: $codePropose }}
                    </p>
                    <p class="mt-1 text-[11px] text-gris-400">
                        {{ $school->exists ? 'Attribué à la création, non modifiable.' : 'Attribué automatiquement.' }}
                    </p>
                </div>

                <div class="md:col-span-3">
                    <label for="city" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Ville</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $school->city) }}"
                           class="champ w-full text-sm md:w-1/3">
                    <p class="mt-1 text-[11px] text-gris-400">Sert à distinguer deux établissements de même nom.</p>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Cycles — un établissement peut porter les quatre
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Cycles ouverts</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        Un établissement peut en porter un seul comme les quatre à la fois.
                    </p>
                </div>
            </div>

            <div class="grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach (\App\Models\School::CYCLES as $cle => $libelle)
                    <label class="flex cursor-pointer items-start gap-2 rounded-lg border p-3 transition-colors
                                  {{ in_array($cle, $cyclesChoisis, true) ? 'border-ogar-300 bg-ogar-50' : 'border-gris-200 hover:bg-gris-50' }}">
                        <input type="checkbox" name="cycles[]" value="{{ $cle }}" class="mt-0.5"
                               @checked(in_array($cle, $cyclesChoisis, true))>
                        <span class="text-sm">
                            <span class="block font-medium text-gris-800">{{ $libelle }}</span>
                            <span class="block text-[11px] text-gris-400">
                                {{ ['preprimaire' => 'Maternelle', 'primaire' => 'CP à CM2', 'college' => '6ème à 3ème', 'lycee' => '2nde à Terminale'][$cle] }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            @error('cycles')
                <p class="px-5 pb-4 text-[11px] text-corail-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- ------------------------------------------------------------
             Compte administrateur — exigé à la création
             ------------------------------------------------------------ --}}
        @unless ($school->exists)
            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <div>
                        <h2 class="text-sm font-semibold text-gris-900">Compte administrateur</h2>
                        <p class="mt-0.5 text-xs text-gris-400">
                            Chaque établissement a son administrateur : sans lui, personne ne peut y entrer.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <div>
                        <label for="admin_nom" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Nom complet <span class="text-corail-600">*</span>
                        </label>
                        <input type="text" name="admin_nom" id="admin_nom" required
                               value="{{ old('admin_nom') }}" class="champ w-full text-sm">
                        @error('admin_nom')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="admin_email" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Courriel <span class="text-corail-600">*</span>
                        </label>
                        <input type="email" name="admin_email" id="admin_email" required
                               value="{{ old('admin_email') }}" class="champ w-full text-sm">
                        <p class="mt-1 text-[11px] text-gris-400">Sert d’identifiant de connexion.</p>
                        @error('admin_email')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="admin_mot_de_passe" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Mot de passe <span class="text-corail-600">*</span>
                        </label>
                        <input type="password" name="admin_mot_de_passe" id="admin_mot_de_passe" required
                               autocomplete="new-password" class="champ w-full text-sm">
                        <p class="mt-1 text-[11px] text-gris-400">8 caractères au minimum.</p>
                        @error('admin_mot_de_passe')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="admin_mot_de_passe_confirmation" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Confirmation <span class="text-corail-600">*</span>
                        </label>
                        <input type="password" name="admin_mot_de_passe_confirmation" id="admin_mot_de_passe_confirmation"
                               required autocomplete="new-password" class="champ w-full text-sm">
                    </div>
                </div>
            </div>
        @endunless
    </div>

    {{-- ----------------------------------------------------------------
         État et enregistrement
         ---------------------------------------------------------------- --}}
    <div class="space-y-4">
        <div class="carte p-5">
            <h2 class="mb-3 text-sm font-semibold text-gris-900">
                {{ $school->exists ? 'Enregistrer' : 'Ouverture' }}
            </h2>

            @unless ($school->exists)
                <label class="flex cursor-pointer items-start gap-2">
                    <input type="checkbox" name="is_active" value="1" class="mt-0.5" @checked(old('is_active', true))>
                    <span class="text-sm">
                        <span class="block font-medium text-gris-800">Établissement actif</span>
                        <span class="block text-[11px] text-gris-400">
                            Décochez pour le préparer sans ouvrir l’accès.
                        </span>
                    </span>
                </label>
            @else
                <div class="mb-4 flex items-center gap-2">
                    <x-puce :couleur="$school->is_active ? 'emerald' : 'slate'">
                        {{ $school->is_active ? 'Actif' : 'Désactivé' }}
                    </x-puce>
                    <span class="text-[11px] text-gris-400">L’activation se change depuis la liste.</span>
                </div>
            @endif

            <div class="mt-4">
                <label for="notes" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Notes</label>
                <textarea name="notes" id="notes" rows="3" class="champ w-full text-sm">{{ old('notes', $school->notes) }}</textarea>
            </div>

            <div class="mt-5 space-y-2 border-t border-gris-100 pt-4">
                <button type="submit" class="bouton-primaire w-full justify-center">
                    {{ $school->exists ? 'Enregistrer les modifications' : 'Créer l’établissement' }}
                </button>
                <a href="{{ route('admin.schools.index') }}" class="bouton-secondaire w-full justify-center">Annuler</a>
            </div>
        </div>

        @unless ($school->exists)
            <div class="carte p-5">
                <h2 class="mb-2 text-sm font-semibold text-gris-900">Ce qui sera créé</h2>
                <ul class="space-y-1.5 text-xs leading-relaxed text-gris-600">
                    <li>· L’établissement et ses cycles.</li>
                    <li>· Ses <strong>paramètres de documents</strong> — sans eux, les bulletins sortiraient sans en-tête.</li>
                    <li>· Son <strong>compte administrateur</strong>, qui ne verra que cet établissement.</li>
                </ul>
            </div>
        @endunless
    </div>
</form>

@endsection
