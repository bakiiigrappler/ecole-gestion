@extends('layouts.app')

@section('titre', 'Emplois du temps')
@section('sous-titre', $bilan['planifiees'].' classe(s) planifiée(s) sur '.$bilan['classes'].' — '.($currentAcademicYear->name ?? 'année en cours'))

@section('actions-entete')
    <form method="GET" action="{{ route('schedules.index') }}" class="hidden sm:block">
        <select name="academic_year_id" onchange="this.form.submit()" class="champ text-sm">
            @foreach ($academicYears as $annee)
                <option value="{{ $annee->id }}" @selected(optional($currentAcademicYear)->id === $annee->id)>
                    {{ $annee->name }}{{ $annee->is_current ? ' (courante)' : '' }}
                </option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('schedules.create') }}" class="bouton-primaire">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        <span class="hidden sm:inline">Construire un emploi du temps</span>
    </a>
@endsection

@section('contenu')

@php
    $libellesCycle = ['preprimaire' => 'Préprimaire', 'primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    $teintesCycle = ['preprimaire' => 'amber', 'primaire' => 'emerald', 'college' => 'sky', 'lycee' => 'violet'];
    $joursCourts = [1 => 'L', 2 => 'M', 3 => 'Me', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];

    $classesParCycle = $listeClasses->groupBy(fn ($c) => $c->level->cycle ?? 'primaire');
    $filtres = request()->only(['recherche', 'classe', 'cycle', 'etat']);
    $filtreActif = collect($filtres)->filter()->isNotEmpty();
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Créneaux"
                       :valeur="number_format($bilan['creneaux'], 0, ',', ' ')"
                       :detail="($currentAcademicYear->name ?? '—')"
                       couleur="ogar"/>
        <x-statistique libelle="Classes planifiées"
                       :valeur="$bilan['planifiees']"
                       :detail="'sur '.$bilan['classes'].' classe(s)'"
                       couleur="emerald"/>
        <x-statistique libelle="À planifier"
                       :valeur="$bilan['a_planifier']"
                       detail="Aucun créneau saisi"
                       :couleur="$bilan['a_planifier'] > 0 ? 'rose' : 'violet'"/>
        <x-statistique libelle="Heures sans enseignant"
                       :valeur="$bilan['sans_enseignant']"
                       detail="Cours à pourvoir"
                       :couleur="$bilan['sans_enseignant'] > 0 ? 'amber' : 'violet'"/>
    </div>

    {{-- ----------------------------------------------------------------
         Filtres — la classe d'abord, c'est par elle qu'on cherche
         ---------------------------------------------------------------- --}}
    <form method="GET" action="{{ route('schedules.index') }}"
          data-filtre-dynamique="emplois"
          class="carte mt-6 flex flex-wrap items-end gap-3 p-4">
        <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Classe</label>
            <select name="classe" class="champ w-56 text-sm">
                <option value="">Toutes les classes</option>
                @foreach ($classesParCycle as $cycle => $duCycle)
                    <optgroup label="{{ $libellesCycle[$cycle] ?? ucfirst($cycle) }}">
                        @foreach ($duCycle as $c)
                            <option value="{{ $c->id }}" @selected((string) request('classe') === (string) $c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Cycle</label>
            <select name="cycle" class="champ w-40 text-sm">
                <option value="">Tous</option>
                @foreach ($libellesCycle as $cle => $libelle)
                    <option value="{{ $cle }}" @selected(request('cycle') === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">État</label>
            <select name="etat" class="champ w-40 text-sm">
                <option value="">Tous</option>
                <option value="planifie" @selected(request('etat') === 'planifie')>Planifié</option>
                <option value="a-planifier" @selected(request('etat') === 'a-planifier')>À planifier</option>
            </select>
        </div>

        <div class="flex-1">
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Recherche</label>
            <input type="search" name="recherche" value="{{ request('recherche') }}"
                   placeholder="Nom de la classe…" class="champ w-full min-w-48 text-sm">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bouton-primaire">Filtrer</button>
            @if ($filtreActif)
                <a href="{{ route('schedules.index', ['academic_year_id' => $academicYearId]) }}"
                   class="bouton-secondaire">Réinitialiser</a>
            @endif
        </div>
    </form>

    {{-- ----------------------------------------------------------------
         Une classe par ligne, dix par page
         ---------------------------------------------------------------- --}}
    <div class="carte relative mt-4 overflow-hidden" data-liste-dynamique="emplois">
        <x-chargement data-voile-chargement hidden/>

        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Emplois du temps par classe</h2>
            <span class="text-xs text-gris-400">
                {{ $classes->total() }} classe(s){{ $filtreActif ? ' après filtrage' : '' }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Classe</th>
                        <th>Niveau</th>
                        <th class="text-center">Heures de cours</th>
                        <th>Jours couverts</th>
                        <th>État</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $classe)
                        @php($lignes = $couverture[$classe->id] ?? collect())
                        @php($jours = $lignes->pluck('day_of_week')->unique()->sort())
                        @php($aPourvoir = $lignes->where('type', 'course')->whereNull('teacher_id')->count())
                        @php($cycle = $classe->level->cycle ?? 'primaire')

                        <tr>
                            <td>
                                <a href="{{ route('schedules.create', ['class_id' => $classe->id, 'academic_year_id' => $academicYearId]) }}"
                                   class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                    {{ $classe->name }}
                                </a>
                            </td>
                            <td>
                                <div class="text-gris-600">{{ $classe->level->name ?? '—' }}</div>
                                <x-puce :couleur="$teintesCycle[$cycle] ?? 'slate'">{{ $libellesCycle[$cycle] ?? $cycle }}</x-puce>
                            </td>
                            <td class="text-center {{ $classe->cours_count > 0 ? 'font-medium text-gris-700' : 'text-gris-400' }}">
                                {{ $classe->cours_count }}
                            </td>
                            <td>
                                @if ($jours->isEmpty())
                                    <span class="text-xs text-gris-400">—</span>
                                @else
                                    <div class="flex gap-1">
                                        @foreach ([1, 2, 3, 4, 5, 6] as $jour)
                                            <span class="flex h-5 w-5 items-center justify-center rounded text-[10px] font-semibold
                                                         {{ $jours->contains($jour) ? 'bg-ogar-100 text-ogar-700' : 'bg-gris-100 text-gris-300' }}">
                                                {{ $joursCourts[$jour] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($classe->creneaux_count === 0)
                                    <x-puce couleur="rose">À planifier</x-puce>
                                @elseif ($aPourvoir > 0)
                                    <div class="flex items-center gap-2">
                                        <x-puce couleur="emerald">Planifié</x-puce>
                                        <span class="text-[11px] text-soleil-700">{{ $aPourvoir }} h sans enseignant</span>
                                    </div>
                                @else
                                    <x-puce couleur="emerald">Complet</x-puce>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('schedules.create', ['class_id' => $classe->id, 'academic_year_id' => $academicYearId]) }}"
                                       class="bouton-mini {{ $classe->creneaux_count === 0 ? 'border-ogar-600 bg-ogar-600 text-white' : '' }}">
                                        {{ $classe->creneaux_count === 0 ? 'Planifier' : 'Modifier' }}
                                    </a>
                                    @if ($classe->creneaux_count > 0)
                                        <a href="{{ route('schedules.print', $classe->id) }}" class="bouton-mini">Imprimer</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="6" message="Aucune classe ne correspond à ces filtres."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($classes->hasPages())
            <div class="border-t border-gris-200 px-4 py-3" data-pagination>
                {{ $classes->links() }}
            </div>
        @endif
    </div>

@endsection
