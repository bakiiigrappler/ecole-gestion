@extends('layouts.app')

@section('titre', 'Bulletins de compétences — '.$class->name)
@section('sous-titre', ($class->getSafeLevelName() ?? '—').' · '.$students->count().' élève(s) — palier '.$palier)

@section('actions-entete')
    <a href="{{ route('competency-evaluations.create', ['class_id' => $class->id]) }}" class="bouton-secondaire">
        Nouvelle évaluation
    </a>
    <a href="{{ route('competency-evaluations.index') }}" class="bouton-primaire">Toutes les classes</a>
@endsection

@section('contenu')

@php
    $libellesMaitrise = [
        'maximale' => 'Maximale', 'minimale' => 'Minimale',
        'partielle' => 'Partielle', 'non_maitrise' => 'Non maîtrisé',
    ];

    // Classes écrites en entier : Tailwind ne compile pas une teinte interpolée.
    $puceMaitrise = [
        'maximale' => 'emerald', 'minimale' => 'sky',
        'partielle' => 'amber', 'non_maitrise' => 'rose',
    ];

    $paliers = range(1, 5);

    // Chiffres par élève, pour le palier consulté.
    $bilanEleve = function ($eleveId) use ($evaluations) {
        $lot = $evaluations[$eleveId] ?? collect();

        if ($lot->isEmpty()) {
            return null;
        }

        $obtenu = $lot->sum('total_points_obtained');
        $max = $lot->sum('total_points_max');

        return [
            'competences' => $lot->count(),
            'obtenu' => $obtenu,
            'max' => $max,
            'part' => $max > 0 ? round($obtenu / $max * 100) : 0,
            'maitrise' => $lot->pluck('competency_mastery')->filter()->countBy()->sortDesc()->keys()->first(),
        ];
    };

    $evalues = $students->filter(fn ($e) => isset($evaluations[$e->id]))->count();
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Élèves" :valeur="$students->count()"
                       :detail="$class->getSafeLevelName() ?? '—'" couleur="ogar"/>
        <x-statistique libelle="Évalués au palier {{ $palier }}" :valeur="$evalues"
                       :detail="$students->count() > 0 ? round($evalues / $students->count() * 100).'% de la classe' : '—'"
                       :couleur="$evalues > 0 ? 'emerald' : 'rose'"/>
        <x-statistique libelle="Compétences" :valeur="$competencies->flatten()->count()"
                       detail="Référentiel du primaire" couleur="violet"/>
        <x-statistique libelle="Palier consulté" :valeur="$palier"
                       detail="sur 5 périodes" couleur="amber"/>
    </div>

    {{-- ----------------------------------------------------------------
         Choix du palier
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 flex flex-wrap items-center justify-between gap-3 p-4">
        <div class="flex items-center gap-2">
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Palier</span>
            <div class="flex gap-1">
                @foreach ($paliers as $p)
                    <a href="{{ route('competency-evaluations.bulletins', ['classId' => $class->id, 'palier' => $p]) }}"
                       class="bouton-mini {{ (int) $palier === $p ? 'border-ogar-600 bg-ogar-600 text-white' : '' }}">
                        {{ $p }}
                    </a>
                @endforeach
            </div>
        </div>

        <p class="text-xs text-gris-400">
            Le bulletin annuel reprend les cinq paliers ; il s’ouvre depuis la ligne de l’élève.
        </p>
    </div>

    {{-- ----------------------------------------------------------------
         Un élève par ligne, son bulletin à un clic
         ---------------------------------------------------------------- --}}
    <div class="carte mt-4 overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Élèves de la classe</h2>
                <p class="mt-0.5 text-xs text-gris-400">Ouvrez un bulletin pour le consulter ou le télécharger.</p>
            </div>
            <span class="text-xs text-gris-400">{{ $students->count() }} élève(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Matricule</th>
                        <th class="text-center">Compétences évaluées</th>
                        <th class="text-center">Points</th>
                        <th>Maîtrise dominante</th>
                        <th class="text-right">Bulletin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $eleve)
                        @php($bilan = $bilanEleve($eleve->id))
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-avatar :nom="$eleve->full_name" :photo="$eleve->photo ?? null" class="h-9 w-9 shrink-0 text-[11px]"/>
                                    <a href="{{ route('students.show', $eleve->id) }}"
                                       class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                        {{ $eleve->full_name }}
                                    </a>
                                </div>
                            </td>
                            <td class="font-mono text-[11px] text-gris-500">{{ $eleve->student_id }}</td>

                            @if (! $bilan)
                                <td colspan="3" class="text-xs text-gris-400">Aucune évaluation à ce palier</td>
                            @else
                                <td class="text-center text-gris-700">{{ $bilan['competences'] }}</td>
                                <td class="whitespace-nowrap text-center text-gris-700">
                                    {{ $bilan['obtenu'] }}<span class="text-gris-400">/{{ $bilan['max'] }}</span>
                                    <span class="ml-1 text-[11px] text-gris-400">({{ $bilan['part'] }}%)</span>
                                </td>
                                <td>
                                    <x-puce :couleur="$puceMaitrise[$bilan['maitrise']] ?? 'slate'">
                                        {{ $libellesMaitrise[$bilan['maitrise']] ?? '—' }}
                                    </x-puce>
                                </td>
                            @endif

                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('competency-evaluations.student-bulletin', ['student' => $eleve->id, 'palier' => $palier]) }}"
                                       class="bouton-mini {{ $bilan ? 'border-ogar-600 bg-ogar-600 text-white' : 'cursor-not-allowed opacity-50' }}">
                                        Palier {{ $palier }}
                                    </a>
                                    <a href="{{ route('competency-evaluations.student-bulletin', ['student' => $eleve->id, 'annual' => 'true']) }}"
                                       class="bouton-mini">Annuel</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="6" message="Aucun élève inscrit dans cette classe."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
