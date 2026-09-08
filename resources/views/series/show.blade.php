@extends('layouts.app')

@section('titre', $series->name)
@section('sous-titre', $series->code.' — '.$series->level)

@section('actions-entete')
    <a href="{{ route('series.edit', $series->id) }}" class="bouton-secondaire">Modifier</a>
    <a href="{{ route('series.index') }}" class="bouton-primaire">Toutes les séries</a>
@endsection

@section('contenu')

@php
    $teintesNiveau = ['2nde' => 'sky', '1ère' => 'amber', 'Terminale' => 'violet'];

    $classes = $series->classes;
    $effectifTotal = $classes->sum(fn ($c) => (int) ($effectifs[$c->id] ?? 0));
    $coefficientTotal = $matieres->sum('coefficient');

    $onglets = [
        'detail' => 'Détail',
        'classes' => 'Classes ('.$classes->count().')',
        'matieres' => 'Programme ('.$matieres->count().')',
    ];

    $ongletInitial = request('onglet', 'detail');
    $ongletInitial = array_key_exists($ongletInitial, $onglets) ? $ongletInitial : 'detail';
@endphp

<div x-data="{ onglet: '{{ $ongletInitial }}' }">

    {{-- ------------------------------------------------------------------
         En-tête de la série
         ------------------------------------------------------------------ --}}
    <div class="carte mb-4 p-5">
        <div class="flex flex-wrap items-start gap-5">
            <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-ogar-50 ring-1 ring-ogar-100">
                <span class="font-mono text-lg font-bold text-ogar-700">{{ $lettre }}</span>
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-bold text-gris-900">{{ $series->name }}</h2>
                    <x-puce :couleur="$teintesNiveau[$series->level] ?? 'slate'">{{ $series->level }}</x-puce>
                    @if (! $series->is_active)
                        <x-puce couleur="rose">Inactive</x-puce>
                    @endif
                    @if ($classes->isEmpty())
                        <x-puce couleur="rose">Aucune classe</x-puce>
                    @endif
                </div>

                <p class="mt-1 font-mono text-sm text-gris-500">{{ $series->code }}</p>

                <div class="mt-3 flex flex-wrap gap-x-8 gap-y-1.5 text-sm [&>div]:whitespace-nowrap">
                    <div>
                        <span class="text-gris-400">Rang</span>
                        <span class="font-medium">{{ $series->order }}</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Coefficients cumulés</span>
                        <span class="font-medium">{{ $coefficientTotal ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Créée le</span>
                        <span class="font-medium">{{ $series->created_at?->translatedFormat('j M Y') ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-lg {{ $classes->isEmpty() ? 'bg-corail-50' : 'bg-ogar-50' }} px-4 py-3">
                    <div class="text-2xl font-bold {{ $classes->isEmpty() ? 'text-corail-700' : 'text-ogar-700' }}">{{ $classes->count() }}</div>
                    <div class="text-[10px] font-semibold uppercase {{ $classes->isEmpty() ? 'text-corail-600' : 'text-ogar-600' }}">classes</div>
                </div>
                <div class="rounded-lg bg-gris-50 px-4 py-3">
                    <div class="text-2xl font-bold text-gris-700">{{ $effectifTotal }}</div>
                    <div class="text-[10px] font-semibold uppercase text-gris-500">élèves</div>
                </div>
                <div class="rounded-lg bg-soleil-50 px-4 py-3">
                    <div class="text-2xl font-bold text-soleil-700">{{ $matieres->count() }}</div>
                    <div class="text-[10px] font-semibold uppercase text-soleil-600">matières</div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 border-t border-gris-100 pt-4">
            <a href="{{ route('series.edit', $series->id) }}" class="bouton-secondaire text-xs">Modifier la série</a>
            <a href="{{ route('classes.index', ['cycle' => 'lycee']) }}" class="bouton-secondaire text-xs">Classes de lycée</a>
            <a href="{{ route('subjects.index', ['cycle' => 'lycee', 'serie' => $lettre]) }}" class="bouton-secondaire text-xs">
                Matières de la série
            </a>

            <x-confirmation :action="route('series.destroy', $series->id)" methode="DELETE"
                            titre="Supprimer cette série ?"
                            :message="$classes->isNotEmpty()
                                ? $classes->count().' classe(s) sont rattachées à cette série : la suppression sera refusée. Désactivez-la plutôt.'
                                : 'La série '.$series->name.' sera définitivement retirée du référentiel.'"
                            confirmer="Supprimer"
                            bouton="bouton-secondaire ml-auto text-xs text-corail-600 hover:bg-corail-50">
                Supprimer la série
            </x-confirmation>
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         Onglets
         ------------------------------------------------------------------ --}}
    <div class="mb-4 flex flex-wrap gap-1 border-b border-gris-200">
        @foreach ($onglets as $cle => $libelle)
            <button type="button" @click="onglet = '{{ $cle }}'"
                    :class="onglet === '{{ $cle }}' ? 'border-ogar-700 text-ogar-700' : 'border-transparent text-gris-500 hover:text-gris-700'"
                    class="cursor-pointer border-b-2 px-4 py-2 text-sm font-semibold transition">{{ $libelle }}</button>
        @endforeach
    </div>

    {{-- Détail ------------------------------------------------------------ --}}
    <div x-show="onglet === 'detail'" class="grid gap-4 lg:grid-cols-3">
        <div class="carte lg:col-span-2">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Fiche de la série</h3>
            </div>
            <dl class="grid grid-cols-2 gap-y-2.5 p-5 text-sm">
                <dt class="text-gris-400">Code</dt>
                <dd class="font-mono font-medium text-gris-900">{{ $series->code }}</dd>

                <dt class="text-gris-400">Intitulé</dt>
                <dd class="font-medium text-gris-900">{{ $series->name }}</dd>

                <dt class="text-gris-400">Niveau</dt>
                <dd class="font-medium text-gris-900">{{ $series->level }}</dd>

                <dt class="text-gris-400">Rang d’affichage</dt>
                <dd class="font-medium text-gris-900">{{ $series->order }}</dd>

                <dt class="text-gris-400">Statut</dt>
                <dd><x-puce :couleur="$series->is_active ? 'emerald' : 'slate'">{{ $series->is_active ? 'Active' : 'Inactive' }}</x-puce></dd>
            </dl>

            @if ($series->description)
                <div class="border-t border-gris-100 p-5">
                    <p class="text-xs font-semibold uppercase text-gris-400">Description</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-gris-700">{{ $series->description }}</p>
                </div>
            @endif
        </div>

        <div class="carte">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Lettre de série</h3>
            </div>
            <div class="p-5">
                <p class="text-sm text-gris-600">
                    Le suffixe du code, <span class="font-mono font-semibold text-gris-900">{{ $lettre }}</span>,
                    fait le lien avec le programme : une matière de lycée déclare les lettres
                    de série auxquelles elle s’applique.
                </p>
                <p class="mt-3 text-sm text-gris-600">
                    {{ $matieres->count() }} matière(s) active(s) visent la série {{ $lettre }}.
                </p>
            </div>
        </div>
    </div>

    {{-- Classes ------------------------------------------------------------ --}}
    <div x-show="onglet === 'classes'" x-cloak class="carte overflow-hidden">
        <div class="carte-entete">
            <div>
                <h3 class="text-sm font-semibold text-gris-900">Classes rattachées</h3>
                <p class="mt-0.5 text-xs text-gris-400">{{ $effectifTotal }} élève(s) inscrit(s) au total</p>
            </div>
            <a href="{{ route('classes.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                Module Classes
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Classe</th>
                        <th>Niveau</th>
                        <th class="text-center">Effectif</th>
                        <th class="text-center">Capacité</th>
                        <th class="text-center">Statut</th>
                        <th class="text-right">Fiche</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $classe)
                        @php($effectif = (int) ($effectifs[$classe->id] ?? 0))
                        <tr>
                            <td>
                                <a href="{{ route('classes.show', $classe->id) }}"
                                   class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                    {{ $classe->name }}
                                </a>
                            </td>
                            <td class="text-gris-600">{{ $classe->getSafeLevelName() }}</td>
                            <td class="text-center font-medium text-gris-700">{{ $effectif }}</td>
                            <td class="text-center text-gris-500">{{ $classe->capacity ?: '—' }}</td>
                            <td class="text-center">
                                <x-puce :couleur="$classe->is_active ? 'emerald' : 'slate'">
                                    {{ $classe->is_active ? 'Ouverte' : 'Fermée' }}
                                </x-puce>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('classes.show', $classe->id) }}" class="bouton-mini">Consulter</a>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="6" message="Aucune classe n’est rattachée à cette série."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Programme ----------------------------------------------------------- --}}
    <div x-show="onglet === 'matieres'" x-cloak class="carte overflow-hidden">
        <div class="carte-entete">
            <div>
                <h3 class="text-sm font-semibold text-gris-900">Programme de la série</h3>
                <p class="mt-0.5 text-xs text-gris-400">
                    Matières actives de lycée déclarant la série {{ $lettre }}
                    &middot; {{ $coefficientTotal }} de coefficients cumulés
                </p>
            </div>
            <a href="{{ route('subjects.index', ['cycle' => 'lycee', 'serie' => $lettre]) }}"
               class="text-xs font-semibold text-ogar-600 hover:underline">
                Filtrer les matières
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Matière</th>
                        <th>Code</th>
                        <th class="text-center">Coefficient</th>
                        <th class="text-right">Fiche</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matieres as $matiere)
                        <tr>
                            <td>
                                <a href="{{ route('subjects.show', $matiere->id) }}"
                                   class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                    {{ $matiere->name }}
                                </a>
                            </td>
                            <td class="font-mono text-xs text-gris-500">{{ $matiere->code }}</td>
                            <td class="text-center font-semibold text-gris-700">
                                {{ (int) $matiere->coefficient }}
                            </td>
                            <td class="text-right">
                                <a href="{{ route('subjects.show', $matiere->id) }}" class="bouton-mini">Consulter</a>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="4" message="Aucune matière active ne vise cette série."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
