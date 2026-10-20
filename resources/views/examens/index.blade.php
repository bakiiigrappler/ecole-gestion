@extends('layouts.app')

@section('titre', 'Examens nationaux')
@section('sous-titre', $examens->total().' session(s)')

@section('actions-entete')
    <a href="{{ route('resultats.accueil') }}" class="bouton-secondaire" target="_blank" rel="noopener">
        Page publique
    </a>
    <a href="{{ route('examens.create') }}" class="bouton-primaire">Ouvrir une session</a>
@endsection

@section('contenu')

@php
    $puce = [
        'preparation' => 'slate', 'inscriptions' => 'sky', 'epreuves' => 'violet',
        'correction' => 'amber', 'deliberation' => 'amber', 'signe' => 'ogar', 'proclame' => 'emerald',
    ];
@endphp

    <div class="carte overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Session</th>
                        <th>Étape</th>
                        <th class="text-center">Candidats</th>
                        <th class="text-center">Centres</th>
                        <th class="text-center">Correcteurs</th>
                        <th>Épreuves</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($examens as $examen)
                        <tr>
                            <td>
                                <a href="{{ route('examens.show', $examen) }}"
                                   class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                    {{ $examen->intitule_complet }}
                                </a>
                                <span class="block text-[11px] text-gris-400">
                                    {{ $examen->libelle_type }} · {{ $examen->annee }}
                                </span>
                            </td>

                            <td>
                                <x-puce :couleur="$puce[$examen->statut] ?? 'slate'">
                                    {{ $examen->libelle_statut }}
                                </x-puce>
                            </td>

                            <td class="text-center tabular-nums text-gris-700">{{ $examen->candidats_count }}</td>
                            <td class="text-center tabular-nums text-gris-700">{{ $examen->centres_count }}</td>
                            <td class="text-center tabular-nums text-gris-700">{{ $examen->correcteurs_count }}</td>

                            <td class="text-[11px] text-gris-500">
                                @if ($examen->debut_epreuves)
                                    du {{ $examen->debut_epreuves->format('d/m/Y') }}
                                    @if ($examen->fin_epreuves) au {{ $examen->fin_epreuves->format('d/m/Y') }} @endif
                                @else
                                    dates à fixer
                                @endif
                            </td>

                            <td class="text-right">
                                <a href="{{ route('examens.show', $examen) }}" class="bouton-mini">Conduire</a>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="7" message="Aucune session d’examen. Ouvrez-en une pour commencer."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($examens->hasPages())
            <div class="border-t border-gris-200 px-4 py-3">{{ $examens->links() }}</div>
        @endif
    </div>

@endsection
