@extends('layouts.app')

@section('titre', 'Bulletins — '.$class->name)
@section('sous-titre', ($class->getSafeLevelName() ?? '—').' · '.$students->count().' enfant(s) — '.($academicYear->name ?? ''))

@section('actions-entete')
    <a href="{{ route('pre-primary-evaluations.create', ['class_id' => $class->id]) }}" class="bouton-secondaire">
        Nouvelle évaluation
    </a>
    <a href="{{ route('pre-primary-evaluations.index') }}" class="bouton-primaire">Toutes les classes</a>
@endsection

@section('contenu')

@php
    $libellesCode = ['MAX' => 'Maximale', 'MIN' => 'Minimale', 'PART' => 'Partielle', 'NM' => 'Non maîtrisé'];

    // Classes écrites en entier : Tailwind ne compile pas une teinte interpolée.
    $puceCode = ['MAX' => 'emerald', 'MIN' => 'sky', 'PART' => 'amber', 'NM' => 'rose'];

    $trimestres = [1 => '1er trimestre', 2 => '2e trimestre', 3 => '3e trimestre'];

    // Ce que chaque enfant a d'évalué, trimestre par trimestre.
    $bilanEnfant = function ($enfantId) use ($evaluations) {
        $lot = $evaluations[$enfantId] ?? collect();

        if ($lot->isEmpty()) {
            return null;
        }

        $parTrimestre = [];

        foreach ([1, 2, 3] as $trimestre) {
            $codes = $lot->pluck("trimester_{$trimestre}_code")->filter();

            $parTrimestre[$trimestre] = [
                'evaluees' => $codes->count(),
                'dominante' => $codes->countBy()->sortDesc()->keys()->first(),
            ];
        }

        return ['competences' => $lot->count(), 'trimestres' => $parTrimestre];
    };

    $evalues = $students->filter(fn ($e) => isset($evaluations[$e->id]))->count();
    $totalCompetences = $competencies->flatten()->count();
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Enfants" :valeur="$students->count()"
                       :detail="$class->getSafeLevelName() ?? '—'" couleur="ogar"/>
        <x-statistique libelle="Évalués" :valeur="$evalues"
                       :detail="$students->count() > 0 ? round($evalues / $students->count() * 100).'% de la classe' : '—'"
                       :couleur="$evalues > 0 ? 'emerald' : 'rose'"/>
        <x-statistique libelle="Compétences" :valeur="$totalCompetences"
                       :detail="$competencies->count().' domaine(s)'" couleur="violet"/>
        <x-statistique libelle="Année" :valeur="$academicYear->name ?? '—'"
                       detail="Évaluation par compétences" couleur="amber"/>
    </div>

    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Enfants de la classe</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    Ouvrez un bulletin pour le consulter, puis téléchargez-le en PDF.
                </p>
            </div>
            <span class="text-xs text-gris-400">{{ $students->count() }} enfant(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Enfant</th>
                        <th>Matricule</th>
                        <th class="text-center">Compétences suivies</th>
                        @foreach ($trimestres as $libelle)
                            <th class="text-center">{{ $libelle }}</th>
                        @endforeach
                        <th class="text-right">Bulletin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $enfant)
                        @php($bilan = $bilanEnfant($enfant->id))
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-avatar :nom="$enfant->full_name" :photo="$enfant->photo ?? null" class="h-9 w-9 shrink-0 text-[11px]"/>
                                    <a href="{{ route('students.show', $enfant->id) }}"
                                       class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                        {{ $enfant->full_name }}
                                    </a>
                                </div>
                            </td>
                            <td class="font-mono text-[11px] text-gris-500">{{ $enfant->student_id }}</td>

                            @if (! $bilan)
                                <td colspan="4" class="text-xs text-gris-400">Aucune évaluation saisie</td>
                            @else
                                <td class="text-center text-gris-700">{{ $bilan['competences'] }}</td>

                                @foreach ([1, 2, 3] as $trimestre)
                                    @php($chiffres = $bilan['trimestres'][$trimestre])
                                    <td class="text-center">
                                        @if ($chiffres['evaluees'] === 0)
                                            <span class="text-xs text-gris-300">—</span>
                                        @else
                                            <x-puce :couleur="$puceCode[$chiffres['dominante']] ?? 'slate'">
                                                {{ $libellesCode[$chiffres['dominante']] ?? '—' }}
                                            </x-puce>
                                        @endif
                                    </td>
                                @endforeach
                            @endif

                            <td class="text-right">
                                {{-- La page ne proposait aucun acces au bulletin :
                                     il fallait connaitre l'URL par coeur. --}}
                                <a href="{{ route('pre-primary-evaluations.student-bulletin', $enfant->id) }}"
                                   class="bouton-mini {{ $bilan ? 'border-ogar-600 bg-ogar-600 text-white' : 'cursor-not-allowed opacity-50' }}">
                                    Ouvrir le bulletin
                                </a>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="7" message="Aucun enfant inscrit dans cette classe."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
