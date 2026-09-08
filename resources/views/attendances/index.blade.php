@extends('layouts.app')

@section('titre', 'Présences')
@section('sous-titre', $jour->locale('fr')->isoFormat('dddd D MMMM YYYY').' — '.($currentAcademicYear->name ?? 'année en cours'))

@section('actions-entete')
    <form method="GET" action="{{ route('attendances.index') }}">
        <input type="hidden" name="date" value="{{ $jour->toDateString() }}">
        <select name="academic_year_id" onchange="this.form.submit()" class="champ text-sm">
            @foreach ($academicYears as $annee)
                <option value="{{ $annee->id }}" @selected((string) $academicYearId === (string) $annee->id)>
                    {{ $annee->name }}{{ $annee->is_current ? ' (courante)' : '' }}
                </option>
            @endforeach
        </select>
    </form>
@endsection

@section('contenu')

@php
    $libellesCycle = ['preprimaire' => 'Préprimaire', 'primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    $teintesCycle = ['preprimaire' => 'amber', 'primaire' => 'emerald', 'college' => 'sky', 'lycee' => 'violet'];

    $parCycle = $classes->groupBy(fn ($c) => $c->getSafeCycle());
    $aAppeler = $classes->where('students_count', '>', 0);
    $restantes = $aAppeler->reject(fn ($c) => isset($pointages[$c->id]))->count();

    $suivis = $bilanDuJour['pointes'];
    $taux = $suivis > 0 ? round($bilanDuJour['present'] / $suivis * 100) : 0;

    $veille = $jour->copy()->subDay()->toDateString();
    $lendemain = $jour->copy()->addDay()->toDateString();
    $lien = fn ($date) => route('attendances.index', ['date' => $date, 'academic_year_id' => $academicYearId]);
@endphp

    {{-- ----------------------------------------------------------------
         Barre de journée : l'appel se fait pour un jour, pas dans l'absolu
         ---------------------------------------------------------------- --}}
    <div class="carte p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <a href="{{ $lien($veille) }}" class="bouton-mini" title="Jour précédent">&larr;</a>

                <form method="GET" action="{{ route('attendances.index') }}" class="flex items-center gap-2">
                    <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
                    <input type="date" name="date" value="{{ $jour->toDateString() }}"
                           onchange="this.form.submit()" class="champ w-44 text-sm">
                </form>

                <a href="{{ $lien($lendemain) }}" class="bouton-mini" title="Jour suivant">&rarr;</a>

                @unless ($jour->isToday())
                    <a href="{{ $lien(now()->toDateString()) }}" class="bouton-mini">Aujourd’hui</a>
                @endunless
            </div>

            <div class="flex items-center gap-6 text-sm">
                <div>
                    <span class="font-semibold text-gris-900">{{ $bilanDuJour['classes_pointees'] }}</span>
                    <span class="text-gris-500">/ {{ $aAppeler->count() }} classes appelées</span>
                </div>
                @if ($restantes > 0)
                    <x-puce couleur="amber">{{ $restantes }} appel(s) à faire</x-puce>
                @elseif ($aAppeler->isNotEmpty())
                    <x-puce couleur="emerald">Journée complète</x-puce>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Présents"
                       :valeur="$bilanDuJour['present']"
                       :detail="$suivis > 0 ? $taux.'% des élèves pointés' : 'Aucun appel ce jour'"
                       couleur="emerald"/>
        <x-statistique libelle="Absents"
                       :valeur="$bilanDuJour['absent'] + $bilanDuJour['excused']"
                       :detail="$bilanDuJour['excused'].' justifié(s)'"
                       :couleur="$bilanDuJour['absent'] > 0 ? 'rose' : 'ogar'"/>
        <x-statistique libelle="Retards"
                       :valeur="$bilanDuJour['late']"
                       detail="Arrivées tardives"
                       couleur="amber"/>
        <x-statistique libelle="Élèves pointés"
                       :valeur="$suivis"
                       :detail="'sur '.$aAppeler->sum('students_count').' inscrits'"
                       couleur="violet"/>
    </div>

    {{-- ----------------------------------------------------------------
         Classes, groupées par cycle — filtrage au clavier côté navigateur
         ---------------------------------------------------------------- --}}
    <div x-data="{ recherche: '', etat: 'toutes' }" class="mt-6">
        <div class="carte mb-4 p-4">
            <div class="flex flex-wrap items-center gap-3">
                <input x-model="recherche" type="search" placeholder="Rechercher une classe…"
                       class="champ w-64 text-sm">

                <div class="flex gap-1">
                    @foreach (['toutes' => 'Toutes', 'a-faire' => 'À faire', 'faites' => 'Appel fait'] as $cle => $libelle)
                        <button type="button" @click="etat = '{{ $cle }}'"
                                class="bouton-mini"
                                :class="etat === '{{ $cle }}' ? 'bg-ogar-600 text-white border-ogar-600' : ''">
                            {{ $libelle }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        @foreach (['preprimaire', 'primaire', 'college', 'lycee'] as $cycle)
            @php($classesDuCycle = $parCycle[$cycle] ?? collect())
            @continue($classesDuCycle->isEmpty())

            <div class="carte mt-4 overflow-hidden">
                <div class="carte-entete">
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-semibold text-gris-900">{{ $libellesCycle[$cycle] }}</h2>
                        <x-puce :couleur="$teintesCycle[$cycle]">{{ $classesDuCycle->count() }} classe(s)</x-puce>
                    </div>
                    <span class="text-xs text-gris-400">
                        {{ $classesDuCycle->sum('students_count') }} élève(s)
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="tableau">
                        <thead>
                            <tr>
                                <th>Classe</th>
                                <th>Niveau</th>
                                <th class="text-center">Élèves</th>
                                <th>Appel du jour</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classesDuCycle as $classe)
                                @php($releve = $pointages[$classe->id] ?? null)
                                @php($fait = $releve !== null)
                                @php($vide = $classe->students_count === 0)

                                <tr x-show="(recherche === '' || '{{ Str::lower($classe->name.' '.$classe->getSafeLevelName()) }}'.includes(recherche.toLowerCase()))
                                            && (etat === 'toutes' || etat === '{{ $fait ? 'faites' : 'a-faire' }}')">
                                    <td>
                                        <a href="{{ route('classes.show', ['class' => $classe->id, 'onglet' => 'presences']) }}"
                                           class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                            {{ $classe->name }}
                                        </a>
                                    </td>
                                    <td class="text-gris-600">{{ $classe->getSafeLevelName() }}</td>
                                    <td class="text-center {{ $classe->students_count > 0 ? 'font-medium text-gris-700' : 'text-corail-600' }}">
                                        {{ $classe->students_count }}
                                    </td>
                                    <td>
                                        @if ($vide)
                                            <span class="text-xs text-gris-400">Aucun élève</span>
                                        @elseif (! $fait)
                                            {{-- L'administration doit savoir à qui s'adresser :
                                                 l'enseignant qui avait cours ce jour-là. --}}
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-puce couleur="amber">À faire</x-puce>
                                                @if (! empty($enseignantsDuJour[$classe->id]))
                                                    <span class="text-[11px] text-gris-500">
                                                        à relancer : {{ implode(', ', array_slice($enseignantsDuJour[$classe->id], 0, 2)) }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2">
                                                <x-puce couleur="emerald">Appel fait</x-puce>
                                                <span class="text-xs text-gris-500">
                                                    {{ $releve['present'] }} présent(s)
                                                    @if ($releve['absent'] + $releve['excused'] > 0)
                                                        · <span class="text-corail-600">{{ $releve['absent'] + $releve['excused'] }} absent(s)</span>
                                                    @endif
                                                    @if ($releve['late'] > 0)
                                                        · {{ $releve['late'] }} retard(s)
                                                    @endif
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('attendances.manage', ['class' => $classe->id, 'date' => $jour->toDateString()]) }}"
                                               class="bouton-mini {{ $vide ? 'pointer-events-none opacity-50' : ($fait ? '' : 'bg-ogar-600 text-white border-ogar-600') }}">
                                                {{ $fait ? 'Modifier' : 'Faire l’appel' }}
                                            </a>
                                            <a href="{{ route('attendances.reports', $classe->id) }}" class="bouton-mini">Rapports</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>

    @if ($classes->isEmpty())
        <div class="carte mt-6">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Classes</h2>
            </div>
            <div class="p-6">
                <x-vide message="Aucune classe ouverte sur cette année scolaire."/>
            </div>
        </div>
    @endif

@endsection
