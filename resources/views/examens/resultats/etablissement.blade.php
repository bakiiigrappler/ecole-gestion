@extends('layouts.app')

@section('titre', 'Résultats — '.$examen->intitule_complet)
@section('sous-titre', $chiffres['candidats'].' candidat(s) de l’établissement')

@section('actions-entete')
    <a href="{{ route('examens.etablissement') }}" class="bouton-secondaire">Toutes les sessions</a>
@endsection

@section('contenu')

@php
    $taux = $chiffres['candidats'] > 0
        ? round($chiffres['admis'] / $chiffres['candidats'] * 100, 1)
        : 0;

    $puce = [
        'admis' => 'emerald', 'second_tour' => 'amber', 'ajourne' => 'rose',
        'absent' => 'slate', 'present' => 'sky', 'inscrit' => 'slate', 'exclu' => 'rose',
    ];
@endphp

    @unless ($examen->estProclame())
        <div class="carte mb-4 border-soleil-300 bg-soleil-50 p-5">
            <h2 class="text-sm font-semibold text-soleil-900">Session non proclamée</h2>
            <p class="mt-1.5 text-sm leading-relaxed text-gris-700">
                Cette session est en « {{ $examen->libelle_statut }} ». Vous voyez vos candidats et leur
                centre ; les résultats paraîtront à la proclamation.
            </p>
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Candidats" :valeur="$chiffres['candidats']"
                       detail="Inscrits par l’établissement" couleur="ogar"/>
        <x-statistique libelle="Admis" :valeur="$chiffres['admis']"
                       :detail="$taux.' % de réussite'" couleur="emerald"/>
        <x-statistique libelle="Second tour" :valeur="$chiffres['second_tour']"
                       detail="À repasser" couleur="amber"/>
        <x-statistique libelle="Ajournés" :valeur="$chiffres['ajournes']"
                       :detail="$chiffres['absents'].' absent(s)'" couleur="rose"/>
    </div>

<div class="mt-4 grid gap-6 lg:grid-cols-3">

    <div class="space-y-4 lg:col-span-2">

        {{-- Les candidats, groupés par centre : c'est la question du parent --}}
        @forelse ($parCentre as $nomDuCentre => $candidatsDuCentre)
            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <div>
                        <h2 class="text-sm font-semibold text-gris-900">{{ $nomDuCentre }}</h2>
                        <p class="mt-0.5 text-xs text-gris-400">
                            {{ $candidatsDuCentre->count() }} candidat(s)
                            @if ($candidatsDuCentre->first()?->centre?->ville)
                                · {{ $candidatsDuCentre->first()->centre->ville }}
                            @endif
                        </p>
                    </div>

                    @if ($examen->estProclame())
                        <span class="text-xs text-gris-500">
                            {{ $candidatsDuCentre->where('statut', 'admis')->count() }} admis
                        </span>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="tableau">
                        <thead>
                            <tr>
                                <th>Numéro</th>
                                <th>Candidat</th>
                                @if ($examen->estProclame())
                                    <th class="text-center">Moyenne</th>
                                    <th class="text-center">Décision</th>
                                @else
                                    <th class="text-center">État</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($candidatsDuCentre as $candidat)
                                <tr>
                                    <td class="font-mono text-xs text-gris-600">{{ $candidat->numero_candidat }}</td>
                                    <td class="font-medium text-gris-800">{{ $candidat->nom_complet }}</td>

                                    @if ($examen->estProclame())
                                        <td class="text-center tabular-nums text-gris-800">
                                            {{ $candidat->moyenne !== null ? number_format($candidat->moyenne, 2, ',', ' ') : '—' }}
                                        </td>
                                        <td class="text-center">
                                            <x-puce :couleur="$puce[$candidat->statut] ?? 'slate'">
                                                {{ $candidat->libelle_statut }}
                                            </x-puce>
                                            @if ($candidat->mention)
                                                <span class="block text-[11px] text-gris-500">{{ $candidat->mention }}</span>
                                            @endif
                                        </td>
                                    @else
                                        <td class="text-center">
                                            <x-puce :couleur="$puce[$candidat->statut] ?? 'slate'">
                                                {{ $candidat->libelle_statut }}
                                            </x-puce>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="carte p-8 text-center text-sm text-gris-500">
                Aucun candidat de l’établissement dans cette session.
            </div>
        @endforelse
    </div>

    {{-- Nos professeurs appelés à corriger --}}
    <div class="space-y-4">
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Nos correcteurs</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        Les professeurs de la maison convoqués pour cette session.
                    </p>
                </div>
            </div>

            <ul class="divide-y divide-gris-100">
                @forelse ($correcteurs as $correcteur)
                    <li class="px-5 py-3">
                        <p class="text-sm font-medium text-gris-900">{{ $correcteur->nom_affiche }}</p>
                        <p class="text-[11px] text-gris-500">
                            {{ $correcteur->epreuve->intitule ?? 'Jury' }}
                            @if ($correcteur->centre) · {{ $correcteur->centre->nom }} @endif
                            · {{ $correcteur->libelle_role }}
                        </p>
                        <x-puce :couleur="match ($correcteur->statut) {
                            'termine' => 'emerald', 'accepte' => 'sky', 'excuse' => 'rose', default => 'slate',
                        }">{{ $correcteur->libelle_statut }}</x-puce>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-gris-500">
                        Aucun de vos enseignants n’a été convoqué pour cette session.
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

@endsection
