@extends('layouts.app')

@section('titre', 'Évaluations par compétences')
@section('sous-titre', 'Primaire — '.$classes->count().' classe(s)')

@section('actions-entete')
    <a href="{{ route('grades.index') }}" class="bouton-secondaire">Notes du secondaire</a>
@endsection

@section('contenu')

@php
    $effectifTotal = $classes->sum(fn ($c) => (int) ($effectifs[$c->id] ?? 0));
    $evaluesTotal = $evaluations->sum('eleves');
@endphp

    {{-- Sans référentiel de compétences, aucune évaluation n'est possible :
         on le dit avant de laisser cliquer sur une classe. --}}
    @if ($nombreCompetences === 0)
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-corail-200 bg-corail-50 px-4 py-3 text-sm text-corail-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
            <span>
                <strong>Aucune compétence n’est définie dans le référentiel.</strong>
                Tant que la table des compétences est vide, l’évaluation d’une classe s’ouvre
                sur un formulaire sans critère : rien ne peut être saisi ni imprimé.
            </span>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Classes du primaire"
                       :valeur="$classes->count()"
                       detail="Ouvertes cette année"
                       couleur="ogar"/>
        <x-statistique libelle="Élèves concernés"
                       :valeur="number_format($effectifTotal, 0, ',', ' ')"
                       detail="Inscrits et actifs"
                       couleur="emerald"/>
        <x-statistique libelle="Élèves évalués"
                       :valeur="number_format($evaluesTotal, 0, ',', ' ')"
                       :detail="$effectifTotal > 0 ? round($evaluesTotal / $effectifTotal * 100).'% de l’effectif' : 'Aucun effectif'"
                       :couleur="$evaluesTotal > 0 ? 'amber' : 'rose'"/>
        <x-statistique libelle="Compétences"
                       :valeur="$nombreCompetences"
                       detail="Référentiel du primaire"
                       :couleur="$nombreCompetences > 0 ? 'violet' : 'rose'"/>
    </div>

    {{-- ----------------------------------------------------------------
         Classes
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Classes du primaire</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    L’évaluation par compétences remplace la notation chiffrée au primaire.
                </p>
            </div>
            <a href="{{ route('classes.index', ['cycle' => 'primaire']) }}"
               class="text-xs font-semibold text-ogar-600 hover:underline">Module Classes</a>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Classe</th>
                        <th>Niveau</th>
                        <th class="text-center">Élèves</th>
                        <th class="text-center">Évalués</th>
                        <th class="text-center">Évaluations</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $classe)
                        @php($effectif = (int) ($effectifs[$classe->id] ?? 0))
                        @php($eval = $evaluations[$classe->id] ?? null)
                        @php($evalues = (int) ($eval->eleves ?? 0))
                        <tr>
                            <td>
                                <a href="{{ route('classes.show', $classe->id) }}"
                                   class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                    {{ $classe->name }}
                                </a>
                            </td>
                            <td class="text-gris-600">{{ $classe->getSafeLevelName() }}</td>
                            <td class="text-center font-medium text-gris-700">{{ $effectif }}</td>
                            <td class="text-center">
                                @if ($effectif === 0)
                                    <span class="text-xs text-gris-400">—</span>
                                @else
                                    <x-puce :couleur="$evalues >= $effectif ? 'emerald' : ($evalues > 0 ? 'amber' : 'slate')">
                                        {{ $evalues }} / {{ $effectif }}
                                    </x-puce>
                                @endif
                            </td>
                            <td class="text-center {{ ($eval->n ?? 0) > 0 ? 'text-gris-700' : 'text-gris-300' }}">
                                {{ $eval->n ?? 0 }}
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('competency-evaluations.create', ['class_id' => $classe->id]) }}"
                                       class="bouton-mini {{ $nombreCompetences === 0 || $effectif === 0 ? 'cursor-not-allowed opacity-50' : '' }}">
                                        Évaluer
                                    </a>
                                    <a href="{{ route('competency-evaluations.bulletins', $classe->id) }}"
                                       class="bouton-mini {{ $evalues === 0 ? 'cursor-not-allowed opacity-50' : '' }}">
                                        Bulletins
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="6" message="Aucune classe de primaire n’est ouverte cette année."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($classes->isEmpty())
        <div class="carte mt-4 p-5 text-center">
            <a href="{{ route('classes.create') }}" class="bouton-primaire">Créer une classe de primaire</a>
        </div>
    @endif

@endsection
