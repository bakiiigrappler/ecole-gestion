@extends('layouts.app')

@section('titre', 'Évaluer '.$class->name)
@section('sous-titre', 'Livret de compétences — trimestre '.$trimester)

@section('actions-entete')
    <a href="{{ route('pre-primary-evaluations.bulletins', $class->id) }}" class="bouton-secondaire">Bulletins</a>
    <a href="{{ route('pre-primary-evaluations.index') }}" class="bouton-secondaire">Toutes les classes</a>
@endsection

@section('contenu')

@php
    $teintesCode = ['MAX' => 'emerald', 'MIN' => 'amber', 'PART' => 'rose', 'NM' => 'slate'];

    // Classes ecrites en entier : Tailwind ne compile pas une teinte interpolee.
    $styleCode = [
        'MAX'  => 'peer-checked:border-emerald-400 peer-checked:bg-emerald-50 peer-checked:text-emerald-700',
        'MIN'  => 'peer-checked:border-amber-400 peer-checked:bg-amber-50 peer-checked:text-amber-700',
        'PART' => 'peer-checked:border-rose-400 peer-checked:bg-rose-50 peer-checked:text-rose-700',
        'NM'   => 'peer-checked:border-slate-400 peer-checked:bg-slate-100 peer-checked:text-slate-700',
    ];
    $trimestres = [1 => '1er trimestre', 2 => '2ème trimestre', 3 => '3ème trimestre'];
    $totalCompetences = collect($competencies)->flatten(1)->count();
@endphp

<div x-data="{ eleve: '{{ $students->first()->id ?? '' }}' }">

    {{-- ------------------------------------------------------------------
         Contexte
         ------------------------------------------------------------------ --}}
    <div class="carte mb-4 p-5">
        <div class="flex flex-wrap items-start gap-5">
            <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-ogar-50 ring-1 ring-ogar-100">
                <svg class="h-8 w-8 text-ogar-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-xl font-bold text-gris-900">{{ $class->name }}</h2>
                <p class="mt-1 text-sm text-gris-500">
                    {{ $class->getSafeLevelName() }}
                    &middot; {{ $students->count() }} enfant(s)
                    &middot; {{ $totalCompetences }} compétence(s)
                </p>
            </div>

            {{-- Changer de trimestre sans repasser par la liste --}}
            <div class="flex flex-wrap gap-1">
                @foreach ($trimestres as $numero => $libelle)
                    <a href="{{ route('pre-primary-evaluations.create', ['class_id' => $class->id, 'trimester' => $numero]) }}"
                       class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors
                              {{ (int) $trimester === $numero ? 'bg-ogar-600 text-white' : 'bg-gris-100 text-gris-600 hover:bg-gris-200' }}">
                        {{ $libelle }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Légende des codes --}}
        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-gris-100 pt-4">
            <span class="text-xs font-semibold uppercase text-gris-400">Codes</span>
            @foreach ($codes as $code => $libelle)
                <span class="flex items-center gap-1.5">
                    <x-puce :couleur="$teintesCode[$code] ?? 'slate'">{{ $code }}</x-puce>
                    <span class="text-xs text-gris-500">{{ $libelle }}</span>
                </span>
            @endforeach
        </div>
    </div>

    @if ($students->isEmpty() || $totalCompetences === 0)
        <div class="carte">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Évaluation impossible</h3>
            </div>
            <div class="p-6">
                <x-vide :message="$students->isEmpty()
                    ? 'Aucun enfant n’est inscrit dans cette classe.'
                    : 'Aucune compétence n’est définie dans le référentiel du préprimaire.'"/>
            </div>
        </div>
    @else

    <form method="POST" action="{{ route('pre-primary-evaluations.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="class_id" value="{{ $class->id }}">
        <input type="hidden" name="trimester" value="{{ $trimester }}">

        <div class="grid gap-4 lg:grid-cols-4 lg:items-start">

            {{-- Choix de l'enfant : on évalue un enfant à la fois, sinon la
                 grille devient illisible dès la deuxième compétence. --}}
            <div class="lg:sticky lg:top-20">
                <div class="carte overflow-hidden">
                    <div class="carte-entete">
                        <h3 class="text-sm font-semibold text-gris-900">Enfants</h3>
                        <span class="text-xs text-gris-400">{{ $students->count() }}</span>
                    </div>
                    <ul class="max-h-[28rem] divide-y divide-gris-100 overflow-y-auto">
                        @foreach ($students as $enfant)
                            <li>
                                <button type="button" @click="eleve = '{{ $enfant->id }}'"
                                        class="flex w-full cursor-pointer items-center gap-3 px-4 py-2.5 text-left transition-colors"
                                        :class="eleve === '{{ $enfant->id }}' ? 'bg-ogar-50' : 'hover:bg-gris-50'">
                                    <x-avatar :nom="$enfant->full_name" class="h-8 w-8 shrink-0 text-[10px]"/>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-gris-800">{{ $enfant->full_name }}</span>
                                        <span class="block truncate font-mono text-[11px] text-gris-400">{{ $enfant->student_id }}</span>
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Grille de compétences de l'enfant sélectionné --}}
            <div class="space-y-4 lg:col-span-3">
                @foreach ($students as $enfant)
                    <div x-show="eleve === '{{ $enfant->id }}'" x-cloak class="space-y-4">
                        @foreach ($competencies as $domaine => $listeDuDomaine)
                            <div class="carte overflow-hidden">
                                <div class="carte-entete">
                                    <h3 class="text-sm font-semibold text-gris-900">{{ $domaine ?: 'Sans domaine' }}</h3>
                                    <span class="text-xs text-gris-400">{{ $listeDuDomaine->count() }} compétence(s)</span>
                                </div>

                                <div class="divide-y divide-gris-100">
                                    @foreach ($listeDuDomaine as $competence)
                                        @php($cle = $enfant->id.'_'.$competence->id)
                                        @php($existante = $existingEvaluations[$cle] ?? null)
                                        @php($codeActuel = $existante?->{'trimester_'.$trimester.'_code'})
                                        @php($commentaire = $existante?->{'trimester_'.$trimester.'_comment'})

                                        <div class="px-5 py-3">
                                            <div class="flex flex-wrap items-start justify-between gap-3">
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm font-medium text-gris-800">{{ $competence->name }}</p>
                                                    @if ($competence->description)
                                                        <p class="mt-0.5 text-xs text-gris-400">{{ $competence->description }}</p>
                                                    @endif
                                                </div>

                                                <div class="flex flex-wrap gap-1">
                                                    @foreach ($codes as $code => $libelle)
                                                        <label class="cursor-pointer" title="{{ $libelle }}">
                                                            <input type="radio" class="peer sr-only"
                                                                   name="evaluations[{{ $enfant->id }}][{{ $competence->id }}][code]"
                                                                   value="{{ $code }}"
                                                                   @checked($codeActuel === $code)>
                                                            <span class="block rounded-lg border border-gris-200 px-2.5 py-1 text-xs font-semibold text-gris-500 transition-colors hover:bg-gris-50 {{ $styleCode[$code] ?? '' }}">
                                                                {{ $code }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <input type="text"
                                                   name="evaluations[{{ $enfant->id }}][{{ $competence->id }}][comment]"
                                                   value="{{ $commentaire }}"
                                                   placeholder="Observation (facultative)"
                                                   class="champ mt-2 text-xs">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                <div class="carte flex flex-wrap items-center gap-3 p-4">
                    <button type="submit" class="bouton-primaire">Enregistrer le trimestre {{ $trimester }}</button>
                    <a href="{{ route('pre-primary-evaluations.index') }}" class="bouton-secondaire">Annuler</a>
                    <span class="ml-auto text-xs text-gris-400">
                        Les enfants non renseignés restent simplement vides.
                    </span>
                </div>
            </div>
        </div>
    </form>
    @endif
</div>

@endsection
