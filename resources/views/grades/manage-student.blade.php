@extends('layouts.app')

@section('titre', 'Notes de '.$student->full_name)
@section('sous-titre', ($class->name ?? 'classe inconnue').' — '.($academicYear->name ?? 'année en cours'))

@section('actions-entete')
    <a href="{{ route('grades.bulletin', $student->id) }}" class="bouton-secondaire">Bulletin</a>
    <a href="{{ route('grades.create', ['student_id' => $student->id]) }}" class="bouton-primaire">Ajouter une note</a>
@endsection

@section('contenu')

@php
    $trimestres = ['1er trimestre', '2ème trimestre', '3ème trimestre'];

    // Une note peut être sur 10, 20 ou 100 : on la ramène sur 20 pour comparer.
    $sur20 = fn ($g) => $g->max_score > 0 ? $g->score / $g->max_score * 20 : null;

    $moyenne = fn ($lot) => $lot->isEmpty() ? null : $lot->map($sur20)->filter()->avg();
    $note = fn ($m) => $m === null ? '—' : number_format((float) $m, 2, ',', ' ');

    $classeMoyenne = fn ($m) => $m === null ? 'text-gris-300'
        : ($m >= 12 ? 'text-emerald-700' : ($m >= 10 ? 'text-soleil-700' : 'text-corail-700'));
    $teinteMoyenne = fn ($m) => $m === null ? 'slate'
        : ($m >= 12 ? 'emerald' : ($m >= 10 ? 'amber' : 'rose'));

    $parMatiere = $grades->groupBy(fn ($g) => $g->subject->name ?? 'Matière supprimée')->sortKeys();
    $moyenneGenerale = $moyenne($grades);

    $ongletInitial = in_array(request('trimestre'), $trimestres, true) ? request('trimestre') : 'tous';
@endphp

<div x-data="{ trimestre: '{{ $ongletInitial }}' }">

    {{-- ------------------------------------------------------------------
         En-tête de l'élève
         ------------------------------------------------------------------ --}}
    <div class="carte mb-4 p-5">
        <div class="flex flex-wrap items-start gap-5">
            <x-avatar :nom="$student->full_name" :photo="$student->photo ?? null" class="h-20 w-20 shrink-0 text-xl"/>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-bold text-gris-900">{{ $student->full_name }}</h2>
                    @if (($student->fitness_status ?? 'apte') === 'inapte')
                        <x-puce couleur="rose">Inapte</x-puce>
                    @endif
                </div>

                <p class="mt-1 text-sm text-gris-500">
                    <span class="font-mono">{{ $student->student_id }}</span>
                    &middot; {{ $class->name ?? '—' }}
                    &middot; {{ $academicYear->name ?? '—' }}
                </p>

                <div class="mt-3 flex flex-wrap gap-x-8 gap-y-1.5 text-sm [&>div]:whitespace-nowrap">
                    <div>
                        <span class="text-gris-400">Notes</span>
                        <span class="font-medium">{{ $grades->count() }}</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Matières</span>
                        <span class="font-medium">{{ $parMatiere->count() }}</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Niveau</span>
                        <span class="font-medium">{{ $class?->getSafeLevelName() ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                @foreach ($trimestres as $i => $t)
                    @php($m = $moyenne($grades->where('term', $t)))
                    <div class="rounded-lg bg-gris-50 px-4 py-3">
                        <div class="text-lg font-bold {{ $classeMoyenne($m) }}">{{ $note($m) }}</div>
                        <div class="text-[10px] font-semibold uppercase text-gris-500">
                            {{ $i + 1 }}<sup>{{ $i === 0 ? 'er' : 'e' }}</sup> trim.
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gris-100 pt-4">
            <a href="{{ route('students.show', $student->id) }}" class="bouton-secondaire text-xs">Fiche de l’élève</a>
            <a href="{{ route('grades.bulletin', $student->id) }}" class="bouton-secondaire text-xs">Bulletin détaillé</a>
            <a href="{{ route('grades.index') }}" class="bouton-secondaire text-xs">Toutes les notes</a>

            <span class="ml-auto flex items-baseline gap-2">
                <span class="text-xs text-gris-400">Moyenne générale</span>
                <span class="text-xl font-bold {{ $classeMoyenne($moyenneGenerale) }}">{{ $note($moyenneGenerale) }}</span>
                <span class="text-xs text-gris-400">/20</span>
            </span>
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         Filtre par trimestre
         ------------------------------------------------------------------ --}}
    <div class="mb-4 flex flex-wrap gap-1 border-b border-gris-200">
        <button type="button" @click="trimestre = 'tous'"
                :class="trimestre === 'tous' ? 'border-ogar-700 text-ogar-700' : 'border-transparent text-gris-500 hover:text-gris-700'"
                class="cursor-pointer border-b-2 px-4 py-2 text-sm font-semibold transition">
            Toutes ({{ $grades->count() }})
        </button>
        @foreach ($trimestres as $t)
            <button type="button" @click="trimestre = '{{ $t }}'"
                    :class="trimestre === '{{ $t }}' ? 'border-ogar-700 text-ogar-700' : 'border-transparent text-gris-500 hover:text-gris-700'"
                    class="cursor-pointer border-b-2 px-4 py-2 text-sm font-semibold transition">
                {{ $t }} ({{ $grades->where('term', $t)->count() }})
            </button>
        @endforeach
    </div>

    {{-- ------------------------------------------------------------------
         Notes groupées par matière
         ------------------------------------------------------------------ --}}
    @forelse ($parMatiere as $matiere => $notesDeLaMatiere)
        @php($moyenneMatiere = $moyenne($notesDeLaMatiere))
        <div class="carte mb-4 overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h3 class="text-sm font-semibold text-gris-900">{{ $matiere }}</h3>
                    <p class="mt-0.5 text-xs text-gris-400">
                        {{ $notesDeLaMatiere->count() }} note(s)
                        @if ($notesDeLaMatiere->first()->subject?->coefficient)
                            &middot; coefficient {{ (int) $notesDeLaMatiere->first()->subject->coefficient }}
                        @endif
                    </p>
                </div>
                <x-puce :couleur="$teinteMoyenne($moyenneMatiere)">{{ $note($moyenneMatiere) }}/20</x-puce>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Trimestre</th>
                            <th>Enseignant</th>
                            <th class="text-center">Note</th>
                            <th class="text-center">Sur 20</th>
                            <th>Appréciation</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($notesDeLaMatiere->sortBy('term') as $note_)
                            <tr x-show="trimestre === 'tous' || trimestre === '{{ $note_->term }}'">
                                <td class="whitespace-nowrap font-medium text-gris-800">{{ $note_->term }}</td>
                                <td class="text-gris-600">{{ $note_->teacher?->full_name ?? '—' }}</td>
                                <td class="text-center font-semibold text-gris-800">
                                    {{ rtrim(rtrim(number_format($note_->score, 2, ',', ''), '0'), ',') }}
                                    <span class="text-xs font-normal text-gris-400">/ {{ (int) $note_->max_score }}</span>
                                </td>
                                <td class="text-center">
                                    @php($n = $sur20($note_))
                                    <span class="font-semibold {{ $classeMoyenne($n) }}">{{ $note($n) }}</span>
                                </td>
                                <td class="max-w-xs truncate text-gris-500">{{ $note_->comments ?: '—' }}</td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('grades.edit', $note_->id) }}" class="bouton-mini">Modifier</a>

                                        <x-confirmation :action="route('grades.destroy', $note_->id)" methode="DELETE"
                                                        titre="Supprimer cette note ?"
                                                        :message="'La note de '.$matiere.' ('.$note_->term.') sera définitivement supprimée.'"
                                                        confirmer="Supprimer"
                                                        bouton="bouton-mini text-corail-600 hover:bg-corail-50">
                                            Supprimer
                                        </x-confirmation>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="carte">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Aucune note</h3>
                <a href="{{ route('grades.create', ['student_id' => $student->id]) }}" class="bouton-primaire text-xs">
                    Ajouter une note
                </a>
            </div>
            <div class="p-6">
                <x-vide message="Aucune note n’a encore été saisie pour cet élève dans cette classe."/>
            </div>
        </div>
    @endforelse
</div>

@endsection
