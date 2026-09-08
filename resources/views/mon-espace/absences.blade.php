@extends('layouts.app')

@section('titre', 'Mes absences')
@section('sous-titre', ($assiduite['taux'] !== null ? $assiduite['taux'].' % de présence' : 'Aucun pointage').' — '.($annee->name ?? ''))

@section('actions-entete')
    <a href="{{ route('mon-espace') }}" class="bouton-secondaire">Mon tableau de bord</a>
@endsection

@section('contenu')

@php
    $libelles = ['absent' => 'Absent', 'late' => 'Retard', 'excused' => 'Excusé', 'present' => 'Présent'];
    $puces = ['absent' => 'corail', 'late' => 'soleil', 'excused' => 'ogar', 'present' => 'emerald'];
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Taux de présence"
                       :valeur="$assiduite['taux'] !== null ? $assiduite['taux'].' %' : '—'"
                       :detail="$assiduite['total'].' jour(s) pointé(s)'" couleur="emerald"/>
        <x-statistique libelle="Absences" :valeur="$assiduite['absent']"
                       detail="Non justifiées comprises"
                       :couleur="$assiduite['absent'] > 0 ? 'corail' : 'emerald'"/>
        <x-statistique libelle="Retards" :valeur="$assiduite['late']"
                       detail="Sur l’année"
                       :couleur="$assiduite['late'] > 0 ? 'soleil' : 'emerald'"/>
        <x-statistique libelle="Absences excusées" :valeur="$assiduite['excused']"
                       detail="Motif accepté" couleur="ogar"/>
    </div>

    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Le détail</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    Seules vos absences et vos retards figurent ici : les jours de présence n’apprendraient rien.
                </p>
            </div>
            <span class="text-xs text-gris-400">{{ $absences->count() }} ligne(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-center">Situation</th>
                        <th class="text-center">Justifiée</th>
                        <th>Motif</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($absences as $ligne)
                        <tr>
                            <td class="whitespace-nowrap text-gris-700">
                                {{ \Carbon\Carbon::parse($ligne->attendance_date)->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
                            </td>
                            <td class="text-center">
                                <x-puce :couleur="$puces[$ligne->status] ?? 'slate'">
                                    {{ $libelles[$ligne->status] ?? $ligne->status }}
                                </x-puce>
                            </td>
                            <td class="text-center text-gris-600">{{ $ligne->justified ? 'Oui' : 'Non' }}</td>
                            <td class="text-gris-600">{{ $ligne->reason ?: '—' }}</td>
                        </tr>
                    @empty
                        <x-vide :colonnes="4" message="Aucune absence ni retard : continuez ainsi."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
