@extends('layouts.app')

@section('titre', 'Répertoire d’orientation')
@section('sous-titre', 'Les établissements vers lesquels partent vos élèves')

@section('actions-entete')
    <a href="{{ route('orientation.index') }}" class="bouton-secondaire">Les dossiers</a>
@endsection

@section('contenu')

<div class="grid gap-6 lg:grid-cols-3">

    <div class="space-y-4 lg:col-span-2">

        <form method="GET" action="{{ route('orientation.repertoire') }}"
              class="carte flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-48 flex-1">
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Recherche</label>
                <input type="search" name="recherche" value="{{ request('recherche') }}"
                       placeholder="Nom ou ville…" class="champ w-full text-sm">
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Type</label>
                <select name="type" class="champ w-56 text-sm">
                    <option value="">Tous</option>
                    @foreach (\App\Models\OrientationEtablissement::TYPES as $cle => $libelle)
                        <option value="{{ $cle }}" @selected($type === $cle)>
                            {{ $libelle }} ({{ $compte[$cle] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="bouton-primaire">Filtrer</button>
        </form>

        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Établissements</h2>
                <span class="text-xs text-gris-400">{{ $etablissements->total() }} au répertoire</span>
            </div>

            <ul class="divide-y divide-gris-100">
                @forelse ($etablissements as $etablissement)
                    <li class="px-5 py-3" x-data="{ ouvert: false }">
                        <div class="flex flex-wrap items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gris-900">{{ $etablissement->nom_complet }}</p>
                                <p class="text-[11px] text-gris-500">
                                    {{ $etablissement->libelle_type }}
                                    · {{ \App\Models\OrientationEtablissement::STATUTS[$etablissement->statut] ?? $etablissement->statut }}
                                    @if ($etablissement->ville) · {{ $etablissement->ville }} @endif
                                    @if ($etablissement->quartier) · {{ $etablissement->quartier }} @endif
                                </p>
                                @if ($etablissement->filieres)
                                    <p class="mt-1 text-[11px] leading-snug text-gris-500">
                                        {{ implode(' · ', $etablissement->filieres) }}
                                    </p>
                                @endif
                            </div>

                            <div class="shrink-0 text-right">
                                @if ($etablissement->capacite !== null)
                                    <x-puce :couleur="$etablissement->est_complet ? 'rose' : 'emerald'">
                                        {{ $etablissement->est_complet
                                            ? 'Complet'
                                            : $etablissement->places_libres.' place(s)' }}
                                    </x-puce>
                                    <p class="mt-0.5 text-[11px] tabular-nums text-gris-400">
                                        {{ $etablissement->inscrits ?? 0 }} / {{ $etablissement->capacite }}
                                    </p>
                                @else
                                    <span class="text-[11px] text-gris-400">Places non déclarées</span>
                                @endif
                            </div>

                            <button type="button" @click="ouvert = ! ouvert" class="bouton-mini shrink-0">
                                <span x-show="! ouvert">Places</span>
                                <span x-show="ouvert" x-cloak>Fermer</span>
                            </button>
                        </div>

                        {{-- Le répertoire national ne se réécrit pas depuis une école :
                             seules les places déclarées, qui servent à orienter ses
                             propres élèves, restent modifiables. --}}
                        <form method="POST" action="{{ route('orientation.repertoire.modifier', $etablissement) }}"
                              x-show="ouvert" x-cloak class="mt-3 flex flex-wrap items-end gap-3">
                            @csrf
                            <input type="hidden" name="nom" value="{{ $etablissement->nom }}">
                            <input type="hidden" name="type" value="{{ $etablissement->type }}">
                            <input type="hidden" name="statut" value="{{ $etablissement->statut }}">

                            <div>
                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                                    Capacité
                                </label>
                                <input type="number" name="capacite" min="0" class="champ w-32 text-sm tabular-nums"
                                       value="{{ $etablissement->capacite }}">
                            </div>

                            <div>
                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                                    Inscrits
                                </label>
                                <input type="number" name="inscrits" min="0" class="champ w-32 text-sm tabular-nums"
                                       value="{{ $etablissement->inscrits }}">
                            </div>

                            <button type="submit" class="bouton-secondaire">Enregistrer</button>

                            @if ($etablissement->school_id === null)
                                <p class="w-full text-[11px] text-gris-400">
                                    Entrée du répertoire national : seules les places se modifient ici.
                                </p>
                            @endif
                        </form>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gris-500">
                        Aucun établissement ne correspond à cette recherche.
                    </li>
                @endforelse
            </ul>

            @if ($etablissements->hasPages())
                <div class="border-t border-gris-200 px-4 py-3">{{ $etablissements->links() }}</div>
            @endif
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         Ajouter un établissement propre à l'école
         ------------------------------------------------------------------ --}}
    <div class="space-y-4">
        <form method="POST" action="{{ route('orientation.repertoire.ajouter') }}" class="carte overflow-hidden">
            @csrf

            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Ajouter un établissement</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        Visible de vos seuls élèves — le répertoire national, lui, vaut pour tous.
                    </p>
                </div>
            </div>

            <div class="space-y-3 p-5">
                <div>
                    <label for="nom" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Nom <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="nom" id="nom" required value="{{ old('nom') }}" class="champ w-full text-sm">
                    @error('nom')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="type" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Type <span class="text-corail-600">*</span>
                        </label>
                        <select name="type" id="type" required class="champ w-full text-sm">
                            @foreach (\App\Models\OrientationEtablissement::TYPES as $cle => $libelle)
                                <option value="{{ $cle }}" @selected(old('type') === $cle)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="statut" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Statut
                        </label>
                        <select name="statut" id="statut" class="champ w-full text-sm">
                            @foreach (\App\Models\OrientationEtablissement::STATUTS as $cle => $libelle)
                                <option value="{{ $cle }}" @selected(old('statut') === $cle)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="ville" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Ville
                        </label>
                        <input type="text" name="ville" id="ville" value="{{ old('ville') }}" class="champ w-full text-sm">
                    </div>

                    <div>
                        <label for="quartier" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Quartier
                        </label>
                        <input type="text" name="quartier" id="quartier" value="{{ old('quartier') }}" class="champ w-full text-sm">
                    </div>

                    <div>
                        <label for="capacite" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Capacité
                        </label>
                        <input type="number" name="capacite" id="capacite" min="0" value="{{ old('capacite') }}"
                               class="champ w-full text-sm tabular-nums">
                    </div>

                    <div>
                        <label for="inscrits" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Inscrits
                        </label>
                        <input type="number" name="inscrits" id="inscrits" min="0" value="{{ old('inscrits') }}"
                               class="champ w-full text-sm tabular-nums">
                    </div>
                </div>

                <div>
                    <label for="filieres" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Filières
                    </label>
                    <textarea name="filieres" id="filieres" rows="3" class="champ w-full text-sm"
                              placeholder="Une par ligne, ou séparées par des virgules.">{{ old('filieres') }}</textarea>
                </div>

                <div>
                    <label for="telephone" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Téléphone
                    </label>
                    <input type="text" name="telephone" id="telephone" value="{{ old('telephone') }}"
                           class="champ w-full text-sm tabular-nums">
                </div>
            </div>

            <div class="border-t border-gris-100 p-5">
                <button type="submit" class="bouton-primaire w-full justify-center">Ajouter au répertoire</button>
            </div>
        </form>
    </div>
</div>

@endsection
