@extends('layouts.app')

@section('titre', 'Appel — '.$class->name)
@section('sous-titre', \Carbon\Carbon::parse($today)->translatedFormat('l j F Y').' — '.$students->count().' élève(s)')

@section('actions-entete')
    <a href="{{ route('attendances.reports', $class->id) }}" class="bouton-secondaire">Rapports</a>
    <a href="{{ route('attendances.index') }}" class="bouton-secondaire">Toutes les classes</a>
@endsection

@section('contenu')

@php
    $statuts = [
        'present' => ['Présent', 'emerald'],
        'absent'  => ['Absent', 'corail'],
        'late'    => ['Retard', 'soleil'],
        'excused' => ['Justifié', 'gris'],
    ];

    // Classes écrites en entier : Tailwind ne compile pas une teinte interpolée.
    $styleStatut = [
        'present' => 'peer-checked:border-emerald-400 peer-checked:bg-emerald-50 peer-checked:text-emerald-700',
        'absent'  => 'peer-checked:border-corail-400 peer-checked:bg-corail-50 peer-checked:text-corail-700',
        'late'    => 'peer-checked:border-soleil-400 peer-checked:bg-soleil-50 peer-checked:text-soleil-700',
        'excused' => 'peer-checked:border-gris-400 peer-checked:bg-gris-100 peer-checked:text-gris-700',
    ];

    $dateDuJour = \Carbon\Carbon::parse($today)->format('Y-m-d');

    // Pointages déjà saisis pour cette date, indexés par élève.
    $dejaPointe = collect($todayAttendances)->keyBy('student_id');

    $totalSemaine = collect(['present', 'absent', 'late', 'excused'])->sum(fn ($c) => $weekStats->$c ?? 0);
@endphp

<form method="POST" action="{{ route('attendances.store', $class->id) }}"
      x-data="{
          date: '{{ $dateDuJour }}',
          recherche: '',
          statuts: {{ Js::from($students->mapWithKeys(fn ($e) => [
              (string) $e->id => optional($dejaPointe[$e->id] ?? null)->status ?? 'present',
          ])) }},

          {{-- Marquer tout le monde d'un coup : le cas courant est « tout le
               monde est là », on ne corrige ensuite que les exceptions. --}}
          tout(statut) {
              Object.keys(this.statuts).forEach((id) => this.statuts[id] = statut);
          },
          compte(statut) {
              return Object.values(this.statuts).filter((s) => s === statut).length;
          },
          get total() { return Object.keys(this.statuts).length; },
          get taux() {
              return this.total ? Math.round(this.compte('present') / this.total * 100) : 0;
          },
      }"
      class="space-y-6">
    @csrf
    <input type="hidden" name="attendance_date" :value="date">

    {{-- ------------------------------------------------------------------
         Date et compteurs en direct
         ------------------------------------------------------------------ --}}
    <div class="carte p-5">
        <div class="flex flex-wrap items-end gap-5">
            <div>
                <label class="etiquette" for="date-appel">Date de l’appel</label>
                <input id="date-appel" type="date" x-model="date"
                       max="{{ now()->format('Y-m-d') }}"
                       class="champ sm:w-56">
                <p class="mt-1 text-xs text-gris-400">
                    Changer la date recharge la feuille depuis le serveur.
                </p>
            </div>

            <a :href="'{{ route('attendances.manage', $class->id) }}?date=' + date"
               class="bouton-secondaire mb-6">Charger cette date</a>

            <div class="mb-1 ml-auto grid grid-cols-4 gap-3 text-center">
                @foreach ($statuts as $cle => [$libelle, $teinte])
                    <div class="rounded-lg bg-gris-50 px-4 py-2">
                        <div class="text-lg font-bold text-gris-800" x-text="compte('{{ $cle }}')"></div>
                        <div class="text-[10px] font-semibold uppercase text-gris-500">{{ $libelle }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-4 border-t border-gris-100 pt-4">
            <div class="mb-1 flex items-baseline justify-between text-xs">
                <span class="text-gris-400">Taux de présence de la feuille</span>
                <span class="font-semibold text-gris-700"><span x-text="taux"></span>%</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-gris-100">
                <div class="h-full rounded-full transition-all"
                     :class="taux >= 90 ? 'bg-emerald-500' : (taux >= 75 ? 'bg-soleil-500' : 'bg-corail-500')"
                     :style="'width: ' + taux + '%'"></div>
            </div>
        </div>
    </div>

    @if ($students->isEmpty())
        <div class="carte">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Feuille d’appel</h2>
            </div>
            <div class="p-6">
                <x-vide message="Aucun élève inscrit dans cette classe : l’appel est impossible."/>
            </div>
        </div>
    @else

    {{-- ------------------------------------------------------------------
         Feuille d'appel
         ------------------------------------------------------------------ --}}
    <div class="carte overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Feuille d’appel</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    @if ($dejaPointe->isNotEmpty())
                        {{ $dejaPointe->count() }} pointage(s) déjà enregistré(s) pour cette date — ils seront remplacés.
                    @else
                        Aucun pointage pour cette date.
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-1">
                <input x-model="recherche" type="search" placeholder="Rechercher un élève…"
                       class="champ mr-2 w-48 text-xs">
                <span class="mr-1 self-center text-xs text-gris-400">Tout marquer</span>
                @foreach ($statuts as $cle => [$libelle, $teinte])
                    <button type="button" @click="tout('{{ $cle }}')" class="bouton-mini">{{ $libelle }}</button>
                @endforeach
            </div>
        </div>

        <div class="divide-y divide-gris-100">
            @foreach ($students as $index => $eleve)
                @php($pointage = $dejaPointe[$eleve->id] ?? null)
                {{-- La recherche masque la ligne sans la retirer du formulaire :
                     un eleve filtre reste pointe tel qu'il l'etait. --}}
                <div class="flex flex-wrap items-center gap-4 px-5 py-3"
                     x-show="recherche === '' || '{{ Str::lower($eleve->full_name.' '.$eleve->student_id) }}'.includes(recherche.toLowerCase())">
                    <span class="w-8 shrink-0 text-xs text-gris-400">{{ $index + 1 }}</span>

                    <x-avatar :nom="$eleve->full_name" :photo="$eleve->photo ?? null" class="h-10 w-10 shrink-0 text-xs"/>

                    <div class="min-w-0 flex-1">
                        <a href="{{ route('students.show', $eleve->id) }}"
                           class="block truncate font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                            {{ $eleve->full_name }}
                        </a>
                        <div class="font-mono text-[11px] text-gris-400">{{ $eleve->student_id }}</div>
                    </div>

                    {{-- Le formulaire attend un tableau indexé de pointages. --}}
                    <input type="hidden" name="attendances[{{ $index }}][student_id]" value="{{ $eleve->id }}">
                    <input type="hidden" name="attendances[{{ $index }}][time_slot]" value="journee">

                    <div class="flex flex-wrap gap-1">
                        @foreach ($statuts as $cle => [$libelle, $teinte])
                            <label class="cursor-pointer">
                                <input type="radio" class="peer sr-only"
                                       name="attendances[{{ $index }}][status]"
                                       value="{{ $cle }}"
                                       x-model="statuts['{{ $eleve->id }}']">
                                <span class="block rounded-lg border border-gris-200 px-3 py-1.5 text-xs font-semibold text-gris-500 transition-colors hover:bg-gris-50 {{ $styleStatut[$cle] }}">
                                    {{ $libelle }}
                                </span>
                            </label>
                        @endforeach
                    </div>

                    {{-- Le motif n'a de sens que si l'élève n'est pas présent. --}}
                    <input type="text" name="attendances[{{ $index }}][reason]"
                           value="{{ $pointage->reason ?? '' }}"
                           placeholder="Motif"
                           x-show="statuts['{{ $eleve->id }}'] !== 'present'" x-cloak
                           class="champ w-full text-xs sm:w-52">
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-gris-100 p-4">
            <button type="submit" class="bouton-primaire">Enregistrer l’appel</button>
            <a href="{{ route('attendances.index') }}" class="bouton-secondaire">Annuler</a>
            <span class="ml-auto text-xs text-gris-400">
                <span x-text="compte('present')"></span> présent(s) sur <span x-text="total"></span>
            </span>
        </div>
    </div>
    @endif
</form>

{{-- ------------------------------------------------------------------
     Rappel de la semaine
     ------------------------------------------------------------------ --}}
@if ($totalSemaine > 0)
    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Cette semaine</h2>
            <a href="{{ route('attendances.reports', $class->id) }}"
               class="text-xs font-semibold text-ogar-600 hover:underline">Rapport détaillé</a>
        </div>

        <div class="grid gap-4 p-5 sm:grid-cols-4">
            @foreach ($statuts as $cle => [$libelle, $teinte])
                <div>
                    <div class="flex items-baseline justify-between">
                        <span class="text-xs text-gris-500">{{ $libelle }}</span>
                        <span class="text-sm font-bold text-gris-800">{{ $weekStats->$cle ?? 0 }}</span>
                    </div>
                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-gris-100">
                        <div class="h-full rounded-full bg-ogar-400"
                             style="width: {{ $totalSemaine > 0 ? round(($weekStats->$cle ?? 0) / $totalSemaine * 100) : 0 }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@endsection
