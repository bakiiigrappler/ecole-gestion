@extends('layouts.app')

@section('titre', 'Frais scolaires')
@section('sous-titre', $bilan['actifs'].' frais actif(s) sur '.$bilan['total'].' — '.number_format($bilan['montant_annuel'], 0, ',', ' ').' FCFA par an et par élève')

@section('actions-entete')
    <a href="{{ route('fees.dashboard') }}" class="bouton-secondaire">Tableau de bord</a>
    <a href="{{ route('fees.create') }}" class="bouton-primaire">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        <span class="hidden sm:inline">Nouveau frais</span>
    </a>
@endsection

@section('contenu')

@php
    $montant = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';

    // Classes écrites en entier : Tailwind ne compile pas une teinte interpolée.
    $puceType = [
        'tuition' => 'ogar', 'registration' => 'emerald', 'uniform' => 'violet',
        'transport' => 'amber', 'meal' => 'sky', 'other' => 'slate',
    ];

    $filtres = request()->only(['recherche', 'fee_type', 'frequency', 'level_id', 'etat']);
    $filtreActif = collect($filtres)->filter()->isNotEmpty();
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Frais configurés" :valeur="$bilan['total']"
                       detail="Toutes catégories" couleur="ogar"/>
        <x-statistique libelle="Actifs" :valeur="$bilan['actifs']"
                       :detail="$bilan['total'] - $bilan['actifs'].' désactivé(s)'"
                       couleur="emerald"/>
        <x-statistique libelle="Obligatoires" :valeur="$bilan['obligatoires']"
                       detail="Exigibles de tous" couleur="amber"/>
        <x-statistique libelle="Coût annuel"
                       :valeur="number_format($bilan['montant_annuel'], 0, ',', ' ')"
                       detail="FCFA — frais actifs cumulés" couleur="violet"/>
    </div>

    {{-- ----------------------------------------------------------------
         Filtres
         ---------------------------------------------------------------- --}}
    <form method="GET" action="{{ route('fees.index') }}"
          data-filtre-dynamique="frais"
          class="carte mt-6 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-44 flex-1">
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Recherche</label>
            <input type="search" name="recherche" value="{{ request('recherche') }}"
                   placeholder="Libellé du frais…" class="champ w-full text-sm">
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Type</label>
            <select name="fee_type" class="champ w-40 text-sm">
                <option value="">Tous</option>
                @foreach ($feeTypes as $cle => $libelle)
                    <option value="{{ $cle }}" @selected(request('fee_type') === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Périodicité</label>
            <select name="frequency" class="champ w-36 text-sm">
                <option value="">Toutes</option>
                @foreach ($frequencies as $cle => $libelle)
                    <option value="{{ $cle }}" @selected(request('frequency') === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Niveau</label>
            <select name="level_id" class="champ w-40 text-sm">
                <option value="">Tous</option>
                @foreach ($levels as $niveau)
                    <option value="{{ $niveau->id }}" @selected((string) request('level_id') === (string) $niveau->id)>
                        {{ $niveau->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">État</label>
            <select name="etat" class="champ w-32 text-sm">
                <option value="">Tous</option>
                <option value="actif" @selected(request('etat') === 'actif')>Actif</option>
                <option value="inactif" @selected(request('etat') === 'inactif')>Désactivé</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bouton-primaire">Filtrer</button>
            @if ($filtreActif)
                <a href="{{ route('fees.index') }}" class="bouton-secondaire">Réinitialiser</a>
            @endif
        </div>
    </form>

    {{-- ----------------------------------------------------------------
         Les frais
         ---------------------------------------------------------------- --}}
    <div class="carte relative mt-4 overflow-hidden" data-liste-dynamique="frais">
        <x-chargement data-voile-chargement hidden/>

        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Grille tarifaire</h2>
            <span class="text-xs text-gris-400">
                {{ $fees->total() }} frais{{ $filtreActif ? ' après filtrage' : '' }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Frais</th>
                        <th>Type</th>
                        <th>Applicable à</th>
                        <th class="text-right">Montant</th>
                        <th>Périodicité</th>
                        <th class="text-right">Coût annuel</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fees as $frais)
                        <tr class="{{ $frais->is_active ? '' : 'opacity-60' }}">
                            <td>
                                <a href="{{ route('fees.show', $frais) }}"
                                   class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                    {{ $frais->name }}
                                </a>
                                <div class="flex gap-1 pt-0.5">
                                    @if ($frais->is_mandatory)
                                        <span class="text-[10px] font-semibold uppercase tracking-wide text-corail-600">Obligatoire</span>
                                    @endif
                                    @unless ($frais->is_active)
                                        <span class="text-[10px] font-semibold uppercase tracking-wide text-gris-400">Désactivé</span>
                                    @endunless
                                </div>
                            </td>
                            <td>
                                <x-puce :couleur="$puceType[$frais->fee_type] ?? 'slate'">
                                    {{ $feeTypes[$frais->fee_type] ?? $frais->fee_type }}
                                </x-puce>
                            </td>
                            <td class="text-gris-600">
                                @if ($frais->schoolClass)
                                    {{ $frais->schoolClass->name }}
                                @elseif ($frais->niveau)
                                    {{ $frais->niveau->name }}
                                @else
                                    <span class="text-xs text-gris-400">Tout l’établissement</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right font-semibold text-gris-800">
                                {{ $montant($frais->amount) }}
                            </td>
                            <td class="text-gris-600">{{ $frequencies[$frais->frequency] ?? $frais->frequency }}</td>
                            <td class="whitespace-nowrap text-right text-gris-700">
                                {{ $montant($frais->montantAnnuel()) }}
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('fees.edit', $frais) }}" class="bouton-mini">Modifier</a>

                                    {{-- Un confirm() natif ne se stylise pas et se laisse
                                         desactiver par le navigateur : le modal maison. --}}
                                    <x-confirmation :action="route('fees.destroy', $frais)"
                                                    methode="DELETE"
                                                    titre="Supprimer ce frais ?"
                                                    :message="'« '.$frais->name.' » ('.$montant($frais->amount).') sera retiré de la grille tarifaire. Cette action est définitive.'"
                                                    confirmer="Supprimer"
                                                    bouton="bouton-mini text-corail-600">
                                        Supprimer
                                    </x-confirmation>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="7" message="Aucun frais ne correspond à ces filtres."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($fees->hasPages())
            <div class="border-t border-gris-200 px-4 py-3" data-pagination>
                {{ $fees->links() }}
            </div>
        @endif
    </div>

@endsection
