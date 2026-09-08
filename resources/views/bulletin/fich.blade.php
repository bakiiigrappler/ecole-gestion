@extends('layouts.app')

@section('titre', 'Bulletins')
@section('sous-titre', $repartition->sum().' classe(s) — accès par cycle, par niveau ou par classe')

@section('actions-entete')
    <a href="{{ route('grades.index') }}" class="bouton-secondaire">Notes du secondaire</a>
@endsection

@section('contenu')

@php
    $libellesCycle = ['preprimaire' => 'Préprimaire', 'primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    $teintesCycle = ['preprimaire' => 'amber', 'primaire' => 'emerald', 'college' => 'sky', 'lycee' => 'violet'];

    // Le préprimaire et le primaire s'évaluent par compétences, pas par notes :
    // le bouton doit mener au bon module.
    $moduleDuCycle = [
        'preprimaire' => 'pre-primary-evaluations.index',
        'primaire' => 'competency-evaluations.index',
    ];

    $filtres = request()->only(['recherche', 'classe', 'cycle']);
    $filtreActif = collect($filtres)->filter()->isNotEmpty();
    $classesParCycle = $listeClasses->groupBy(fn ($c) => $c->level->cycle ?? 'primaire');
@endphp

    {{-- ----------------------------------------------------------------
         Accès par cycle
         ---------------------------------------------------------------- --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($libellesCycle as $cycle => $libelle)
            @php($nombre = $repartition[$cycle] ?? 0)
            <a href="{{ route('bulletins.byCycle', $cycle) }}"
               class="carte flex items-center justify-between p-4 transition-colors hover:bg-gris-50">
                <span>
                    <span class="block text-sm font-semibold text-gris-900">{{ $libelle }}</span>
                    <span class="block text-xs text-gris-400">
                        {{ $nombre }} classe(s)
                        @if (isset($moduleDuCycle[$cycle])) &middot; par compétences @else &middot; par notes @endif
                    </span>
                </span>
                <x-puce :couleur="$teintesCycle[$cycle]">{{ $nombre }}</x-puce>
            </a>
        @endforeach
    </div>

    {{-- ----------------------------------------------------------------
         Niveaux, groupés par cycle
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Navigation par niveau</h2>
            <span class="text-xs text-gris-400">
                {{ collect($levelsByCycle)->flatten()->count() }} niveau(x)
            </span>
        </div>

        <div class="divide-y divide-gris-100">
            @foreach ($levelsByCycle as $cycle => $niveaux)
                @continue($niveaux->isEmpty())
                <div class="px-5 py-4">
                    <div class="flex items-center gap-2">
                        <x-puce :couleur="$teintesCycle[$cycle] ?? 'slate'">{{ $libellesCycle[$cycle] ?? $cycle }}</x-puce>
                        <span class="text-xs text-gris-400">{{ $niveaux->count() }} niveau(x)</span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($niveaux as $niveau)
                            <a href="{{ route('bulletins.byLevel', $niveau->id) }}"
                               class="rounded-lg border border-gris-200 px-3 py-1.5 text-sm text-gris-700 transition-colors hover:border-ogar-300 hover:bg-ogar-50">
                                {{ $niveau->name }}
                                <span class="ml-1 text-xs text-gris-400">
                                    ({{ $niveau->classes->count() }})
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Toutes les classes — filtrées et paginées
         ---------------------------------------------------------------- --}}
    <form method="GET" action="{{ route('bulletins.index') }}"
          data-filtre-dynamique="bulletins"
          class="carte mt-6 flex flex-wrap items-end gap-3 p-4">
        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Classe</label>
            <select name="classe" class="champ w-56 text-sm">
                <option value="">Toutes les classes</option>
                @foreach ($classesParCycle as $cycle => $duCycle)
                    <optgroup label="{{ $libellesCycle[$cycle] ?? ucfirst($cycle) }}">
                        @foreach ($duCycle as $c)
                            <option value="{{ $c->id }}" @selected((string) request('classe') === (string) $c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Cycle</label>
            <select name="cycle" class="champ w-40 text-sm">
                <option value="">Tous</option>
                @foreach ($libellesCycle as $cle => $libelle)
                    <option value="{{ $cle }}" @selected(request('cycle') === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex-1">
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Recherche</label>
            <input type="search" name="recherche" value="{{ request('recherche') }}"
                   placeholder="Nom de la classe…" class="champ w-full min-w-48 text-sm">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bouton-primaire">Filtrer</button>
            @if ($filtreActif)
                <a href="{{ route('bulletins.index') }}" class="bouton-secondaire">Réinitialiser</a>
            @endif
        </div>
    </form>

    <div class="carte relative mt-4 overflow-hidden" data-liste-dynamique="bulletins">
        <x-chargement data-voile-chargement hidden/>

        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Toutes les classes</h2>
            <span class="text-xs text-gris-400">
                {{ $classes->total() }} classe(s){{ $filtreActif ? ' après filtrage' : '' }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Classe</th>
                        <th>Niveau</th>
                        <th>Cycle</th>
                        <th class="text-center">Élèves</th>
                        <th>Bulletins</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $classe)
                        @php($cycle = $classe->getSafeCycle())
                        @php($parCompetences = isset($moduleDuCycle[$cycle]))
                        @php($notesClasse = (int) ($notes[$classe->id] ?? 0))
                        <tr>
                            <td>
                                <a href="{{ route('bulletins.class', $classe->id) }}"
                                   class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                    {{ $classe->name }}
                                </a>
                            </td>
                            <td class="text-gris-600">{{ $classe->getSafeLevelName() }}</td>
                            <td>
                                <x-puce :couleur="$teintesCycle[$cycle] ?? 'slate'">
                                    {{ $libellesCycle[$cycle] ?? $cycle }}
                                </x-puce>
                            </td>
                            <td class="text-center {{ $classe->effectif > 0 ? 'font-medium text-gris-700' : 'text-corail-600' }}">
                                {{ $classe->effectif }}
                            </td>
                            <td>
                                @if ($parCompetences)
                                    <span class="text-xs text-gris-500">Par compétences</span>
                                @elseif ($classe->effectif === 0)
                                    <span class="text-xs text-gris-400">Aucun élève inscrit</span>
                                @elseif ($notesClasse === 0)
                                    <x-puce couleur="rose">Aucune note saisie</x-puce>
                                @elseif ($notesClasse < $classe->effectif)
                                    <x-puce couleur="amber">{{ $classe->effectif - $notesClasse }} élève(s) sans note</x-puce>
                                @else
                                    <x-puce couleur="emerald">Éditables</x-puce>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('bulletins.class', $classe->id) }}" class="bouton-mini">Bulletins</a>

                                    @if ($parCompetences)
                                        <a href="{{ route($moduleDuCycle[$cycle]) }}" class="bouton-mini">Compétences</a>
                                    @else
                                        <a href="{{ route('grades.index', ['class_id' => $classe->id]) }}" class="bouton-mini">Notes</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="6" message="Aucune classe ne correspond à ces filtres."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($classes->hasPages())
            <div class="border-t border-gris-200 px-4 py-3" data-pagination>
                {{ $classes->links() }}
            </div>
        @endif
    </div>

@endsection
