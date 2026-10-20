@extends('layouts.app')

@section('titre', 'Examens de l’établissement')
@section('sous-titre', $examens->count().' session(s) où vos élèves sont inscrits')

@section('contenu')

    @if ($examens->isEmpty())
        <div class="carte p-8 text-center">
            <h2 class="text-base font-semibold text-gris-900">Aucun candidat inscrit</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed text-gris-600">
                Aucun de vos élèves ne figure dans une session d’examen. Les inscriptions sont faites
                par la direction des examens, à partir de vos classes.
            </p>
        </div>
    @else
        <div class="carte overflow-hidden">
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Session</th>
                            <th>Étape</th>
                            <th class="text-center">Nos candidats</th>
                            <th>Épreuves</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($examens as $examen)
                            <tr>
                                <td>
                                    <span class="font-medium text-gris-800">{{ $examen->intitule_complet }}</span>
                                    <span class="block text-[11px] text-gris-400">
                                        {{ $examen->libelle_type }} · {{ $examen->annee }}
                                    </span>
                                </td>

                                <td>
                                    <x-puce :couleur="$examen->estProclame() ? 'emerald' : 'slate'">
                                        {{ $examen->libelle_statut }}
                                    </x-puce>
                                </td>

                                <td class="text-center tabular-nums text-gris-800">{{ $examen->mes_candidats }}</td>

                                <td class="text-[11px] text-gris-500">
                                    @if ($examen->debut_epreuves)
                                        du {{ $examen->debut_epreuves->format('d/m/Y') }}
                                        @if ($examen->fin_epreuves) au {{ $examen->fin_epreuves->format('d/m/Y') }} @endif
                                    @else
                                        dates à fixer
                                    @endif
                                </td>

                                <td class="text-right">
                                    <a href="{{ route('examens.etablissement.session', $examen) }}" class="bouton-mini">
                                        {{ $examen->estProclame() ? 'Les résultats' : 'Nos candidats' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@endsection
