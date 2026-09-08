@extends('layouts.app')

@section('titre', 'Bulletins — '.$level->name)
@section('sous-titre', $classes->count().' classe(s) · '.$classes->sum('effectif').' élève(s) — '.($annee->name ?? 'année en cours'))

@section('actions-entete')
    <a href="{{ route('bulletins.byCycle', $level->cycle) }}" class="bouton-secondaire">Tout le cycle</a>
    <a href="{{ route('bulletins.index') }}" class="bouton-primaire">Tous les bulletins</a>
@endsection

@section('contenu')

@php
    $libellesCycle = ['preprimaire' => 'Préprimaire', 'primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    $teintesCycle = ['preprimaire' => 'amber', 'primaire' => 'emerald', 'college' => 'sky', 'lycee' => 'violet'];

    $effectif = $classes->sum('effectif');
    // Ne pas ecraser $notes : la table s'en sert ligne par ligne.
    $totalNotes = $classes->sum(fn ($c) => $notes[$c->id] ?? 0);
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Classes" :valeur="$classes->count()"
                       :detail="$level->name" couleur="ogar"/>
        <x-statistique libelle="Élèves" :valeur="$effectif"
                       :detail="($annee->name ?? '—')" couleur="emerald"/>
        <x-statistique libelle="Élèves notés" :valeur="$totalNotes"
                       :detail="$effectif > 0 ? round($totalNotes / $effectif * 100).'% de l’effectif' : '—'"
                       :couleur="$totalNotes > 0 ? 'violet' : 'rose'"/>
        <x-statistique libelle="Cycle" :valeur="$libellesCycle[$level->cycle] ?? $level->cycle"
                       :detail="$level->code ?? '—'" couleur="amber"/>
    </div>

    {{-- ----------------------------------------------------------------
         Le niveau, en une carte
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-semibold text-gris-900">{{ $level->name }}</h2>
                    <x-puce :couleur="$teintesCycle[$level->cycle] ?? 'slate'">
                        {{ $libellesCycle[$level->cycle] ?? $level->cycle }}
                    </x-puce>
                    <x-puce :couleur="$level->is_active ? 'emerald' : 'slate'">
                        {{ $level->is_active ? 'Actif' : 'Inactif' }}
                    </x-puce>
                </div>
                @if ($level->description)
                    <p class="mt-1 text-sm text-gris-500">{{ $level->description }}</p>
                @endif
            </div>

            <dl class="grid grid-cols-2 gap-x-8 gap-y-1 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-[11px] uppercase tracking-wide text-gris-400">Code</dt>
                    <dd class="font-medium text-gris-800">{{ $level->code ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wide text-gris-400">Ordre</dt>
                    <dd class="font-medium text-gris-800">{{ $level->order ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wide text-gris-400">Classes</dt>
                    <dd class="font-medium text-gris-800">{{ $classes->count() }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Les classes du niveau
         ---------------------------------------------------------------- --}}
    <div class="carte mt-4 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Classes de ce niveau</h2>
            <span class="text-xs text-gris-400">{{ $classes->count() }} classe(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Classe</th>
                        <th class="text-center">Élèves</th>
                        <th class="text-center">Élèves notés</th>
                        <th>Bulletins</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $classe)
                        @php($notesClasse = $notes[$classe->id] ?? 0)
                        <tr>
                            <td>
                                <a href="{{ route('bulletins.class', $classe->id) }}"
                                   class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                    {{ $classe->name }}
                                </a>
                            </td>
                            <td class="text-center {{ $classe->effectif > 0 ? 'font-medium text-gris-700' : 'text-corail-600' }}">
                                {{ $classe->effectif }}
                            </td>
                            <td class="text-center text-gris-600">{{ $notesClasse }}</td>
                            <td>
                                @if ($classe->effectif === 0)
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
                                <a href="{{ route('bulletins.class', $classe->id) }}" class="bouton-mini">
                                    Ouvrir la classe
                                </a>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="5" message="Aucune classe rattachée à ce niveau."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
