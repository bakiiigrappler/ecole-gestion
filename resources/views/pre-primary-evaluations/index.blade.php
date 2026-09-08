@extends('layouts.app')

@section('titre', 'Évaluations du préprimaire')
@section('sous-titre', $classes->count().' classe(s) — évaluation par compétences, sans note chiffrée')

@section('actions-entete')
    <a href="{{ route('competency-evaluations.index') }}" class="bouton-secondaire">Compétences du primaire</a>
@endsection

@section('contenu')

@php
    $effectifTotal = $classes->sum(fn ($c) => (int) ($effectifs[$c->id] ?? 0));

    // Les quatre codes du livret, du plus acquis au moins abordé.
    $codes = [
        ['CA',  'Compétence acquise',   'Maîtrise maximale',  'emerald'],
        ['AR',  'À renforcer',          'Maîtrise minimale',  'amber'],
        ['NA',  'Non acquise',          'Maîtrise partielle', 'rose'],
        ['ENA', 'Encore non abordée',   'Non maîtrisée',      'slate'],
    ];

    $trimestres = [1 => '1er trimestre', 2 => '2ème trimestre', 3 => '3ème trimestre'];
@endphp

    @if ($nombreCompetences === 0)
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-corail-200 bg-corail-50 px-4 py-3 text-sm text-corail-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
            <span>
                <strong>Aucune compétence n’est définie pour le préprimaire.</strong>
                Le formulaire d’évaluation s’ouvrira sans aucun domaine à renseigner tant que
                le référentiel reste vide.
            </span>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Classes"
                       :valeur="$classes->count()"
                       detail="Préprimaire, ouvertes"
                       couleur="ogar"/>
        <x-statistique libelle="Enfants"
                       :valeur="number_format($effectifTotal, 0, ',', ' ')"
                       detail="Inscrits et actifs"
                       couleur="emerald"/>
        <x-statistique libelle="Compétences"
                       :valeur="$nombreCompetences"
                       detail="Référentiel du préprimaire"
                       :couleur="$nombreCompetences > 0 ? 'violet' : 'rose'"/>
        <x-statistique libelle="Trimestres"
                       valeur="3"
                       detail="Un livret par année"
                       couleur="amber"/>
    </div>

    {{-- ----------------------------------------------------------------
         Classes et avancement par trimestre
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Classes du préprimaire</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    Chaque trimestre s’évalue séparément ; le bulletin reprend les trois.
                </p>
            </div>
            <a href="{{ route('classes.index', ['cycle' => 'preprimaire']) }}"
               class="text-xs font-semibold text-ogar-600 hover:underline">Module Classes</a>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Classe</th>
                        <th>Niveau</th>
                        <th class="text-center">Enfants</th>
                        <th class="text-center">1<sup>er</sup> trim.</th>
                        <th class="text-center">2<sup>e</sup> trim.</th>
                        <th class="text-center">3<sup>e</sup> trim.</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $classe)
                        @php($effectif = (int) ($effectifs[$classe->id] ?? 0))
                        @php($suivi = $avancement[$classe->id] ?? null)
                        <tr>
                            <td>
                                <a href="{{ route('classes.show', $classe->id) }}"
                                   class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                    {{ $classe->name }}
                                </a>
                            </td>
                            <td class="text-gris-600">{{ $classe->getSafeLevelName() }}</td>
                            <td class="text-center font-medium text-gris-700">{{ $effectif }}</td>

                            @foreach ([1, 2, 3] as $numero)
                                @php($evalues = (int) ($suivi->{'t'.$numero} ?? 0))
                                <td class="text-center">
                                    @if ($effectif === 0)
                                        <span class="text-xs text-gris-400">—</span>
                                    @else
                                        <a href="{{ route('pre-primary-evaluations.create', ['class_id' => $classe->id, 'trimester' => $numero]) }}"
                                           class="inline-block">
                                            <x-puce :couleur="$evalues >= $effectif ? 'emerald' : ($evalues > 0 ? 'amber' : 'slate')">
                                                {{ $evalues }} / {{ $effectif }}
                                            </x-puce>
                                        </a>
                                    @endif
                                </td>
                            @endforeach

                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('pre-primary-evaluations.create', ['class_id' => $classe->id, 'trimester' => 1]) }}"
                                       class="bouton-mini {{ $nombreCompetences === 0 || $effectif === 0 ? 'cursor-not-allowed opacity-50' : '' }}">
                                        Évaluer
                                    </a>
                                    <a href="{{ route('pre-primary-evaluations.bulletins', $classe->id) }}"
                                       class="bouton-mini">Bulletins</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="7" message="Aucune classe de préprimaire n’est ouverte cette année."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Repères : codes du livret et fonctionnement
         ---------------------------------------------------------------- --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="carte">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Codes d’évaluation</h3>
            </div>
            <div class="divide-y divide-gris-100">
                @foreach ($codes as [$code, $libelle, $detail, $teinte])
                    <div class="flex items-center gap-3 px-5 py-3">
                        <x-puce :couleur="$teinte">{{ $code }}</x-puce>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-medium text-gris-800">{{ $libelle }}</span>
                            <span class="block text-xs text-gris-400">{{ $detail }}</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="carte">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Fonctionnement</h3>
            </div>
            <ul class="space-y-2.5 p-5 text-sm text-gris-600">
                <li class="flex gap-2">
                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-ogar-400"></span>
                    Évaluation trimestrielle : les trois trimestres se saisissent séparément.
                </li>
                <li class="flex gap-2">
                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-ogar-400"></span>
                    Évaluation par domaines de compétences, sans note chiffrée.
                </li>
                <li class="flex gap-2">
                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-ogar-400"></span>
                    Un bulletin individuel par enfant, reprenant les trois trimestres.
                </li>
                <li class="flex gap-2">
                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-ogar-400"></span>
                    Suivi de l’évolution sur l’année, d’un trimestre à l’autre.
                </li>
            </ul>
        </div>
    </div>

@endsection
