@extends('layouts.app')

@section('titre', 'Établissements')
@section('sous-titre', $bilan['actifs'].' actif(s) sur '.$bilan['total'].' — '.number_format($bilan['eleves'], 0, ',', ' ').' élève(s) au total')

@section('actions-entete')
    <a href="{{ route('admin.settings.index') }}" class="bouton-secondaire">Paramètres généraux</a>
    <a href="{{ route('admin.schools.create') }}" class="bouton-primaire">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        <span class="hidden sm:inline">Nouvel établissement</span>
    </a>
@endsection

@section('contenu')

@php
    $nombre = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $courante = \App\Support\EcoleCourante::id();

    // Classes écrites en entier : Tailwind ne compile pas une teinte interpolée.
    $puceCycle = [
        'preprimaire' => 'amber', 'primaire' => 'emerald',
        'college' => 'sky', 'lycee' => 'violet',
    ];

    $filtres = request()->only(['recherche', 'etat', 'cycle']);
    $filtreActif = collect($filtres)->filter()->isNotEmpty();
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Établissements" :valeur="$bilan['total']"
                       :detail="$bilan['actifs'].' ouvert(s)'" couleur="ogar"/>
        <x-statistique libelle="Désactivés" :valeur="$bilan['total'] - $bilan['actifs']"
                       detail="Accès suspendu" :couleur="$bilan['total'] - $bilan['actifs'] > 0 ? 'rose' : 'violet'"/>
        <x-statistique libelle="Élèves" :valeur="$nombre($bilan['eleves'])"
                       detail="Tous établissements" couleur="emerald"/>
        <x-statistique libelle="Comptes" :valeur="$nombre($bilan['comptes'])"
                       detail="Hors super administrateur" couleur="amber"/>
    </div>

    {{-- ----------------------------------------------------------------
         Où l'on se trouve — et comment en sortir
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 flex flex-wrap items-center justify-between gap-3 p-4">
        <div class="flex items-center gap-2 text-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Vous travaillez dans</span>
            @if ($courante)
                <span class="font-semibold text-ogar-800">{{ optional(\App\Models\School::find($courante))->name }}</span>
            @else
                <span class="font-semibold text-gris-800">Tous les établissements</span>
                <span class="text-xs text-gris-400">— vue d’ensemble</span>
            @endif
        </div>

        @if ($courante)
            <form method="POST" action="{{ route('admin.schools.basculer') }}">
                @csrf
                <button type="submit" class="bouton-secondaire">Revenir à la vue d’ensemble</button>
            </form>
        @endif
    </div>

    {{-- ----------------------------------------------------------------
         Filtres
         ---------------------------------------------------------------- --}}
    <form method="GET" action="{{ route('admin.schools.index') }}"
          class="carte mt-4 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-48 flex-1">
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Recherche</label>
            <input type="search" name="recherche" value="{{ request('recherche') }}"
                   placeholder="Nom, code ou ville…" class="champ w-full text-sm">
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Cycle</label>
            <select name="cycle" class="champ w-40 text-sm">
                <option value="">Tous</option>
                @foreach (\App\Models\School::CYCLES as $cle => $libelle)
                    <option value="{{ $cle }}" @selected(request('cycle') === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">État</label>
            <select name="etat" class="champ w-36 text-sm">
                <option value="">Tous</option>
                <option value="actif" @selected(request('etat') === 'actif')>Actif</option>
                <option value="inactif" @selected(request('etat') === 'inactif')>Désactivé</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bouton-primaire">Filtrer</button>
            @if ($filtreActif)
                <a href="{{ route('admin.schools.index') }}" class="bouton-secondaire">Réinitialiser</a>
            @endif
        </div>
    </form>

    {{-- ----------------------------------------------------------------
         Les établissements
         ---------------------------------------------------------------- --}}
    <div class="carte mt-4 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Liste des établissements</h2>
            <span class="text-xs text-gris-400">
                {{ $schools->total() }} établissement(s){{ $filtreActif ? ' après filtrage' : '' }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Établissement</th>
                        <th>Cycles ouverts</th>
                        <th class="text-center">Élèves</th>
                        <th class="text-center">Enseignants</th>
                        <th class="text-center">Comptes</th>
                        <th>État</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schools as $etablissement)
                        <tr class="{{ $etablissement->is_active ? '' : 'opacity-60' }}">
                            <td>
                                <a href="{{ route('admin.schools.show', $etablissement) }}"
                                   class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                    {{ $etablissement->name }}
                                </a>
                                <div class="text-[11px] text-gris-400">
                                    <span class="font-mono">{{ $etablissement->code }}</span>
                                    @if ($etablissement->city) · {{ $etablissement->city }} @endif
                                    @if ((int) $courante === $etablissement->id)
                                        · <span class="font-semibold text-ogar-600">en cours</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($etablissement->cyclesOuverts() as $cycle)
                                        <x-puce :couleur="$puceCycle[$cycle] ?? 'slate'">
                                            {{ \App\Models\School::CYCLES[$cycle] }}
                                        </x-puce>
                                    @empty
                                        <span class="text-xs text-corail-600">Aucun cycle</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="text-center text-gris-700">{{ $nombre($effectifs[$etablissement->id] ?? 0) }}</td>
                            <td class="text-center text-gris-600">{{ $enseignants[$etablissement->id] ?? 0 }}</td>
                            <td class="text-center text-gris-600">{{ $comptes[$etablissement->id] ?? 0 }}</td>
                            <td>
                                <x-puce :couleur="$etablissement->is_active ? 'emerald' : 'slate'">
                                    {{ $etablissement->is_active ? 'Actif' : 'Désactivé' }}
                                </x-puce>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if ((int) $courante !== $etablissement->id)
                                        <form method="POST" action="{{ route('admin.schools.basculer') }}">
                                            @csrf
                                            <input type="hidden" name="school_id" value="{{ $etablissement->id }}">
                                            <button type="submit" class="bouton-mini">Travailler ici</button>
                                        </form>
                                    @endif

                                    <a href="{{ route('admin.schools.edit', $etablissement) }}" class="bouton-mini">Modifier</a>

                                    <x-confirmation :action="route('admin.schools.toggle-status', $etablissement)"
                                                    methode="POST"
                                                    :titre="$etablissement->is_active ? 'Désactiver cet établissement ?' : 'Réactiver cet établissement ?'"
                                                    :message="$etablissement->is_active
                                                        ? 'Les comptes de « '.$etablissement->name.' » perdront l’accès. Aucune donnée n’est effacée : la réactivation les rétablit.'
                                                        : 'Les comptes de « '.$etablissement->name.' » retrouveront l’accès à l’application.'"
                                                    :confirmer="$etablissement->is_active ? 'Désactiver' : 'Réactiver'"
                                                    :ton="$etablissement->is_active ? 'danger' : 'primaire'"
                                                    bouton="bouton-mini">
                                        {{ $etablissement->is_active ? 'Désactiver' : 'Réactiver' }}
                                    </x-confirmation>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="7" message="Aucun établissement ne correspond à ces filtres."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($schools->hasPages())
            <div class="border-t border-gris-200 px-4 py-3">
                {{ $schools->links() }}
            </div>
        @endif
    </div>

@endsection
