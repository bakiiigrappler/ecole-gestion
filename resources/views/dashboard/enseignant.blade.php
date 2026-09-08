@extends('layouts.app')

@section('titre', 'Tableau de bord')
@section('sous-titre', ($enseignant ? $enseignant->first_name.' '.$enseignant->last_name : 'Enseignant').' — '.$aujourdHui->locale('fr')->isoFormat('dddd D MMMM YYYY'))

@section('actions-entete')
    <a href="{{ route('schedules.index') }}" class="bouton-primaire">Mon emploi du temps</a>
@endsection

@section('contenu')

@php
    $heure = fn ($v) => substr((string) $v, 0, 5);
    $restants = $appels->reject(fn ($a) => $a['fait'])->count();
@endphp

    @unless ($enseignant)
        <div class="carte p-8">
            <x-vide message="Ce compte n’est rattaché à aucune fiche enseignant."/>
            <p class="mt-3 text-center text-xs text-gris-400">
                L’administration de l’établissement peut faire le rattachement depuis la fiche de l’enseignant.
            </p>
        </div>
    @else

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Mes élèves" :valeur="$eleves"
                       :detail="$classes->count().' classe(s)'" couleur="ogar"/>
        <x-statistique libelle="Heures par semaine" :valeur="$heuresParSemaine"
                       detail="Cours effectifs" couleur="emerald"/>
        <x-statistique libelle="Mes matières" :valeur="$matieres"
                       detail="Enseignées" couleur="violet"/>
        <x-statistique libelle="Appels du jour"
                       :valeur="($appels->count() - $restants).' / '.$appels->count()"
                       :detail="$restants > 0 ? $restants.' à faire' : 'Journée complète'"
                       :couleur="$restants > 0 ? 'amber' : 'emerald'"/>
    </div>

    @if ($restants > 0)
        <div class="mt-4 flex items-start gap-3 rounded-xl border border-soleil-200 bg-soleil-50 px-4 py-3 text-sm text-soleil-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span>
                <strong>{{ $restants }} appel(s) restent à faire aujourd’hui.</strong>
                L’administration suit les feuilles non rendues.
            </span>
        </div>
    @endif

    <div class="mt-6 grid items-start gap-4 lg:grid-cols-3">

        {{-- ------------------------------------------------------------
             Ma journée
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden lg:col-span-2">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Ma journée</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        {{ $aujourdHui->locale('fr')->isoFormat('dddd D MMMM') }} — vos cours, dans l’ordre.
                    </p>
                </div>
                <span class="text-xs text-gris-400">{{ $duJour->count() }} heure(s)</span>
            </div>

            <div class="divide-y divide-gris-100">
                @forelse ($duJour as $cours)
                    <div class="flex items-center gap-4 px-5 py-3">
                        <span class="w-24 shrink-0 text-sm font-semibold tabular-nums text-gris-800">
                            {{ $heure($cours->start_time) }}
                            <span class="block text-[11px] font-normal text-gris-400">{{ $heure($cours->end_time) }}</span>
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-gris-800">{{ $cours->subject->name ?? '—' }}</p>
                            <p class="truncate text-[11px] text-gris-500">
                                {{ $cours->schoolClass->name ?? '—' }}
                                @if ($cours->room) &middot; salle {{ $cours->room }} @endif
                            </p>
                        </div>

                        <a href="{{ route('attendances.manage', ['class' => $cours->class_id, 'date' => $aujourdHui->toDateString()]) }}"
                           class="bouton-mini shrink-0">Faire l’appel</a>
                    </div>
                @empty
                    <p class="p-5 text-sm text-gris-400">Aucun cours ne vous est attribué aujourd’hui.</p>
                @endforelse
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Mes appels du jour
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Feuilles d’appel</h2>
                <span class="text-xs text-gris-400">{{ $appels->count() }} classe(s)</span>
            </div>

            <div class="divide-y divide-gris-100">
                @forelse ($appels as $appel)
                    <div class="flex items-center gap-3 px-5 py-2.5">
                        <span class="min-w-0 flex-1 truncate text-sm text-gris-800">{{ $appel['classe']->name }}</span>

                        @if ($appel['fait'])
                            <x-puce couleur="emerald">Fait</x-puce>
                        @else
                            <a href="{{ route('attendances.manage', ['class' => $appel['classe']->id, 'date' => $aujourdHui->toDateString()]) }}"
                               class="bouton-mini border-ogar-600 bg-ogar-600 text-white">À faire</a>
                        @endif
                    </div>
                @empty
                    <p class="p-5 text-sm text-gris-400">Aucun appel prévu aujourd’hui.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Mes classes
         ---------------------------------------------------------------- --}}
    <div class="carte mt-4 overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Mes classes</h2>
                <p class="mt-0.5 text-xs text-gris-400">Celles où vous intervenez cette année.</p>
            </div>
            <a href="{{ route('students.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                Mes élèves
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Classe</th>
                        <th class="text-center">Élèves</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $classe)
                        <tr>
                            <td class="font-medium text-gris-800">{{ $classe->name }}</td>
                            <td class="text-center text-gris-600">{{ $classe->effectif }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('students.index', ['class' => $classe->id]) }}" class="bouton-mini">Élèves</a>
                                    <a href="{{ route('classes.fiche', $classe->id) }}" class="bouton-mini">Fiche de classe</a>
                                    <a href="{{ route('attendances.reports', $classe->id) }}" class="bouton-mini">Assiduité</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="3" message="Aucune classe ne vous est attribuée."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @endunless

@endsection
