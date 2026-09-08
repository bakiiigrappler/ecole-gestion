@extends('layouts.app')

@section('titre', 'Assiduité — '.$class->name)
@section('sous-titre', $libellePeriode.' · du '.$debut->format('d/m/Y').' au '.$fin->format('d/m/Y'))

@section('actions-entete')
    <a href="{{ route('attendances.manage', $class->id) }}" class="bouton-secondaire">Faire l’appel</a>
    <a href="{{ route('attendances.index') }}" class="bouton-primaire">Toutes les classes</a>
@endsection

@section('contenu')

@php
    // Classes écrites en entier : Tailwind ne compile pas une teinte interpolée.
    $barre = fn ($t) => $t === null ? 'bg-gris-300' : ($t >= 95 ? 'bg-emerald-500' : ($t >= 85 ? 'bg-soleil-500' : 'bg-corail-500'));
    $encre = fn ($t) => $t === null ? 'text-gris-400' : ($t >= 95 ? 'text-emerald-600' : ($t >= 85 ? 'text-soleil-600' : 'text-corail-600'));
    $puce = fn ($t) => $t === null ? 'slate' : ($t >= 95 ? 'emerald' : ($t >= 85 ? 'amber' : 'rose'));

    $periodes = [
        'semaine' => 'Cette semaine',
        'mois' => 'Ce mois-ci',
        'trimestre' => 'Ce trimestre',
        'annee' => 'Cette année',
    ];

    $lien = fn ($cle) => route('attendances.reports', [
        'class' => $class->id,
        'periode' => $cle,
        'academic_year_id' => $academicYearId,
    ]);

    $absencesTotales = $bilan['absent'] + $bilan['excused'];
    $joursPointes = $parJour->count();

    // Effectif moyen present par jour : plus parlant qu'un pourcentage seul.
    $presentsParJour = $joursPointes > 0 ? round($bilan['present'] / $joursPointes) : 0;
@endphp

    {{-- ----------------------------------------------------------------
         Une seule période gouverne toute la page
         ---------------------------------------------------------------- --}}
    <div class="carte flex flex-wrap items-center justify-between gap-3 p-3">
        <div class="flex gap-1">
            @foreach ($periodes as $cle => $libelle)
                <a href="{{ $lien($cle) }}"
                   class="bouton-mini {{ $periode === $cle ? 'border-ogar-600 bg-ogar-600 text-white' : '' }}">
                    {{ $libelle }}
                </a>
            @endforeach
        </div>
        <p class="text-xs text-gris-400">
            {{ $students->count() }} élève(s) · {{ $joursPointes }} jour(s) d’appel sur la période
        </p>
    </div>

    @if ($bilan['total'] === 0)
        <div class="carte mt-4 p-8">
            <x-vide message="Aucun appel n’a été fait dans cette classe sur cette période."/>
            <div class="mt-4 text-center">
                <a href="{{ route('attendances.manage', $class->id) }}" class="bouton-primaire">Faire l’appel</a>
            </div>
        </div>
    @else

    {{-- ----------------------------------------------------------------
         Le résultat en une phrase, puis le détail
         ---------------------------------------------------------------- --}}
    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="carte p-5 lg:col-span-1">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Taux de présence</p>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-4xl font-bold {{ $encre($bilan['taux']) }}">{{ $bilan['taux'] }}%</span>
                <x-puce :couleur="$puce($bilan['taux'])">
                    {{ $bilan['taux'] >= 95 ? 'Bonne assiduité' : ($bilan['taux'] >= 85 ? 'À surveiller' : 'Préoccupant') }}
                </x-puce>
            </div>

            <div class="mt-3 h-2 overflow-hidden rounded-full bg-gris-100">
                <div class="h-full rounded-full {{ $barre($bilan['taux']) }}" style="width: {{ $bilan['taux'] }}%"></div>
            </div>

            <p class="mt-3 text-sm leading-relaxed text-gris-600">
                En moyenne <span class="font-semibold text-gris-900">{{ $presentsParJour }}</span>
                élève(s) présent(s) par jour d’appel, sur {{ $students->count() }} inscrits.
                @if ($absencesTotales > 0)
                    <span class="text-corail-700">{{ $absencesTotales }} absence(s)</span> relevée(s)
                    dont {{ $bilan['excused'] }} justifiée(s).
                @else
                    Aucune absence sur la période.
                @endif
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
            <x-statistique libelle="Présences" :valeur="$bilan['present']"
                           :detail="$bilan['total'] > 0 ? round($bilan['present'] / $bilan['total'] * 100).'% des pointages' : '—'"
                           couleur="emerald"/>
            <x-statistique libelle="Absences" :valeur="$bilan['absent']"
                           detail="Non justifiées"
                           :couleur="$bilan['absent'] > 0 ? 'rose' : 'ogar'"/>
            <x-statistique libelle="Retards" :valeur="$bilan['late']"
                           detail="Arrivées tardives"
                           couleur="amber"/>
            <x-statistique libelle="Absences justifiées" :valeur="$bilan['excused']"
                           detail="Motif renseigné"
                           couleur="violet"/>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Jour par jour : voir quel jour a décroché, et l'ouvrir
         ---------------------------------------------------------------- --}}
    <div class="carte mt-4 overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Jour par jour</h2>
                <p class="mt-0.5 text-xs text-gris-400">Cliquez un jour pour ouvrir sa feuille d’appel.</p>
            </div>
            <div class="flex items-center gap-3 text-[11px] text-gris-500">
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> ≥ 95%</span>
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-soleil-500"></span> 85–94%</span>
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-corail-500"></span> &lt; 85%</span>
            </div>
        </div>

        <div class="flex gap-2 overflow-x-auto p-4">
            @foreach ($parJour as $date => $chiffres)
                @php($jour = \Carbon\Carbon::parse($date))
                <a href="{{ route('attendances.manage', ['class' => $class->id, 'date' => $date]) }}"
                   class="group w-24 shrink-0 rounded-lg border border-gris-200 p-2 text-center transition hover:border-ogar-400 hover:shadow-sm">
                    <div class="text-[10px] uppercase text-gris-400">{{ $jour->locale('fr')->isoFormat('ddd') }}</div>
                    <div class="text-sm font-semibold text-gris-800">{{ $jour->format('d/m') }}</div>

                    <div class="mx-auto mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gris-100">
                        <div class="h-full {{ $barre($chiffres['taux']) }}" style="width: {{ $chiffres['taux'] }}%"></div>
                    </div>

                    <div class="mt-1 text-[11px] font-semibold {{ $encre($chiffres['taux']) }}">
                        {{ $chiffres['taux'] }}%
                    </div>
                    <div class="text-[10px] text-gris-400">
                        {{ $chiffres['absent'] + $chiffres['excused'] }} abs · {{ $chiffres['late'] }} ret.
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Élèves à surveiller — l'information actionnable, en premier
         ---------------------------------------------------------------- --}}
    @if ($aSurveiller->isNotEmpty())
        <div class="carte mt-4 overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Élèves à surveiller</h2>
                    <p class="mt-0.5 text-xs text-gris-400">Taux de présence inférieur à 90% sur la période.</p>
                </div>
                <x-puce couleur="rose">{{ $aSurveiller->count() }}</x-puce>
            </div>

            <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($aSurveiller as $ligne)
                    <a href="{{ route('students.show', $ligne['eleve']->id) }}"
                       class="flex items-center gap-3 rounded-lg border border-gris-200 p-3 transition hover:border-corail-300 hover:bg-corail-50/40">
                        <x-avatar :nom="$ligne['eleve']->full_name" :photo="$ligne['eleve']->photo ?? null" class="h-9 w-9 shrink-0 text-[11px]"/>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-gris-800">{{ $ligne['eleve']->full_name }}</div>
                            <div class="truncate text-[11px] text-gris-500">
                                {{ $ligne['absent'] + $ligne['excused'] }} absence(s), {{ $ligne['late'] }} retard(s)
                                @if ($ligne['motifs'])
                                    · {{ implode(', ', $ligne['motifs']) }}
                                @endif
                            </div>
                        </div>
                        <span class="shrink-0 text-sm font-bold {{ $encre($ligne['taux']) }}">{{ $ligne['taux'] }}%</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ----------------------------------------------------------------
         Le détail complet, du plus fragile au plus assidu
         ---------------------------------------------------------------- --}}
    <div class="carte mt-4 overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Assiduité élève par élève</h2>
                <p class="mt-0.5 text-xs text-gris-400">{{ $libellePeriode }} — les taux les plus faibles en premier.</p>
            </div>
            <a href="{{ route('classes.show', ['class' => $class->id, 'onglet' => 'presences']) }}"
               class="text-xs font-semibold text-ogar-600 hover:underline">Fiche de la classe</a>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th class="text-center">Présent</th>
                        <th class="text-center">Absent</th>
                        <th class="text-center">Retard</th>
                        <th class="text-center">Justifié</th>
                        <th class="w-48">Taux de présence</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assiduite as $ligne)
                        <tr>
                            <td>
                                <a href="{{ route('students.show', $ligne['eleve']->id) }}"
                                   class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                    {{ $ligne['eleve']->full_name }}
                                </a>
                                <div class="font-mono text-[11px] text-gris-400">{{ $ligne['eleve']->student_id }}</div>
                            </td>
                            <td class="text-center text-gris-700">{{ $ligne['present'] }}</td>
                            <td class="text-center {{ $ligne['absent'] > 0 ? 'font-semibold text-corail-700' : 'text-gris-400' }}">
                                {{ $ligne['absent'] }}
                            </td>
                            <td class="text-center {{ $ligne['late'] > 0 ? 'font-semibold text-soleil-700' : 'text-gris-400' }}">
                                {{ $ligne['late'] }}
                            </td>
                            <td class="text-center text-gris-500">{{ $ligne['excused'] }}</td>
                            <td>
                                @if ($ligne['taux'] === null)
                                    <span class="text-xs text-gris-400">jamais pointé</span>
                                @else
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gris-100">
                                            <div class="h-full rounded-full {{ $barre($ligne['taux']) }}"
                                                 style="width: {{ $ligne['taux'] }}%"></div>
                                        </div>
                                        <span class="w-10 shrink-0 text-right text-xs font-semibold {{ $encre($ligne['taux']) }}">
                                            {{ $ligne['taux'] }}%
                                        </span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="6" message="Aucun élève inscrit dans cette classe."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @endif

@endsection
