@props([
    'creneaux',                 // collection de Schedule
    'titre' => 'Emploi du temps',
    'sousTitre' => null,        // la classe, l'élève, l'enseignant…
    'annee' => null,            // AcademicYear
    'complement' => 'enseignant', // ce qu'on lit sous la matière dans la grille
    'fichier' => 'Emploi_du_temps.pdf',
    'id' => null,
])

@php
    /*
     * L'emploi du temps, dans ses deux lectures.
     *
     * La grille colorée d'abord : c'est celle qu'on consulte, et elle se lit
     * d'un coup d'œil. L'aperçu ensuite : le document administratif en noir et
     * blanc, avec l'en-tête officiel, celui qui s'imprime et se garde.
     *
     * Le même composant sert à l'élève, au parent et à quiconque consulte un
     * emploi du temps sans le composer.
     */
    $id = $id ?: 'edt-'.substr(md5(uniqid('', true)), 0, 8);

    $etablissement = \App\Models\SchoolSettings::getSettings();

    $heure = fn ($v) => substr((string) $v, 0, 5);

    $jours = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'];
    $jours = array_filter($jours, fn ($n, $j) => $creneaux->contains('day_of_week', $j), ARRAY_FILTER_USE_BOTH)
        ?: [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi'];

    $plages = $creneaux
        ->map(fn ($c) => ['debut' => $heure($c->start_time), 'fin' => $heure($c->end_time)])
        ->unique('debut')
        ->sortBy('debut')
        ->values();

    $grille = $creneaux->keyBy(fn ($c) => $c->day_of_week.'-'.$heure($c->start_time));

    $heuresParJour = collect($jours)->mapWithKeys(fn ($nom, $jour) => [
        $jour => $creneaux->where('day_of_week', $jour)->where('type', 'course')->count(),
    ]);
@endphp

@if ($creneaux->isEmpty())
    <div class="carte p-8">
        <x-vide message="Aucun emploi du temps n’est encore établi."/>
    </div>
@else

<div x-data="{ vue: 'grille' }" {{ $attributes }}>

    <div class="carte overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gris-100 px-4 py-3">
            <div class="inline-flex rounded-xl bg-gris-100 p-1">
                <button type="button" @click="vue = 'grille'"
                        class="rounded-lg px-4 py-1.5 text-sm font-medium transition"
                        :class="vue === 'grille' ? 'bg-white text-ogar-700 shadow-sm' : 'text-gris-500 hover:text-gris-800'">
                    Grille de la semaine
                </button>
                <button type="button" @click="vue = 'apercu'"
                        class="rounded-lg px-4 py-1.5 text-sm font-medium transition"
                        :class="vue === 'apercu' ? 'bg-white text-ogar-700 shadow-sm' : 'text-gris-500 hover:text-gris-800'">
                    Aperçu du document
                </button>
            </div>

            <div class="flex items-center gap-3">
                <p class="hidden text-xs text-gris-400 sm:block"
                   x-text="vue === 'grille'
                       ? 'Une couleur par matière.'
                       : 'Le document officiel, tel qu’il s’imprime.'"></p>

                <button type="button"
                        x-show="vue === 'apercu'"
                        class="bouton-secondaire"
                        data-export-pdf="{{ $id }}"
                        data-orientation="paysage"
                        data-page-unique
                        data-nom-fichier="{{ $fichier }}">
                    Télécharger
                </button>
            </div>
        </div>

        <div x-show="vue === 'grille'" class="p-4">
            <x-grille-emploi-du-temps :creneaux="$creneaux" :complement="$complement"/>
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         Le document administratif. Ce bloc est celui que html2canvas
         photographie : le PDF est exactement ce qui s'affiche ici.
         ------------------------------------------------------------------ --}}
    <div x-show="vue === 'apercu'" x-cloak id="{{ $id }}" class="carte bg-white p-6 text-gris-900">

        <div class="flex items-start justify-between gap-4 border-b-2 border-gris-800 pb-3">
            <div class="flex items-start gap-3">
                @if ($etablissement->logo_url ?? null)
                    <img src="{{ $etablissement->logo_url }}" alt="Logo de l’établissement"
                         class="h-12 w-12 shrink-0 object-contain">
                @endif
                <div class="leading-tight">
                    <p class="text-[10px] text-gris-600">Ministère de l’Éducation Nationale</p>
                    <p class="text-sm font-bold uppercase">{{ $etablissement->school_name ?? 'Établissement scolaire' }}</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="text-right leading-tight">
                    <p class="text-[10px] uppercase tracking-wide text-gris-500">Année scolaire</p>
                    <p class="text-sm font-semibold">{{ $annee->name ?? '—' }}</p>
                </div>
                @if ($etablissement->seal_url ?? null)
                    <img src="{{ $etablissement->seal_url }}" alt="Sceau de la République"
                         class="h-12 w-16 shrink-0 object-contain">
                @endif
            </div>
        </div>

        <div class="mt-4 text-center">
            <h1 class="text-lg font-bold uppercase tracking-wide">{{ $titre }}</h1>
            @if ($sousTitre)
                <p class="mt-0.5 text-sm text-gris-600">{{ $sousTitre }}</p>
            @endif
        </div>

        <table class="mt-4 w-full table-fixed border-collapse text-[10px]">
            <thead>
                <tr>
                    <th class="w-24 border border-gris-400 bg-gris-200 px-1 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-700">
                        Horaire
                    </th>
                    @foreach ($jours as $nom)
                        <th class="border border-gris-400 bg-gris-200 px-1 py-1.5 font-semibold uppercase tracking-wide text-gris-800">
                            {{ $nom }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($plages as $plage)
                    <tr>
                        <td class="border border-gris-400 bg-gris-50 px-1.5 py-1 align-middle">
                            <div class="font-semibold tabular-nums text-gris-800">{{ $plage['debut'] }}</div>
                            <div class="tabular-nums text-gris-500">{{ $plage['fin'] }}</div>
                        </td>

                        @foreach ($jours as $jour => $nom)
                            @php($cours = $grille[$jour.'-'.$plage['debut']] ?? null)

                            @if (! $cours)
                                <td class="border border-gris-400 px-1 py-1.5 align-top"></td>
                            @elseif ($cours->type === 'break')
                                <td class="border border-gris-400 bg-gris-100 px-1 py-1.5 text-center align-middle text-[9px] uppercase tracking-wide text-gris-500">
                                    {{ $cours->title ?: 'Récréation' }}
                                </td>
                            @else
                                <td class="border border-gris-400 bg-white px-1.5 py-1 align-top text-gris-900">
                                    <div class="font-semibold leading-tight">{{ $cours->subject->name ?? '—' }}</div>

                                    @if ($complement === 'enseignant' && $cours->teacher)
                                        <div class="mt-0.5 text-[9px] text-gris-600">
                                            {{ $cours->teacher->last_name }} {{ mb_substr($cours->teacher->first_name, 0, 1) }}.
                                        </div>
                                    @elseif ($complement === 'classe' && $cours->schoolClass)
                                        <div class="mt-0.5 text-[9px] text-gris-600">{{ $cours->schoolClass->name }}</div>
                                    @endif

                                    @if ($cours->room)
                                        <div class="text-[9px] text-gris-500">Salle {{ $cours->room }}</div>
                                    @endif
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td class="border border-gris-400 bg-gris-200 px-1.5 py-1 text-[9px] font-semibold uppercase tracking-wide text-gris-700">
                        Heures / jour
                    </td>
                    @foreach ($jours as $jour => $nom)
                        <td class="border border-gris-400 bg-gris-200 px-1 py-1 text-center font-semibold text-gris-800">
                            {{ $heuresParJour[$jour] ?: '—' }}
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        </table>

        <p class="mt-4 text-[9px] text-gris-500">Édité le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
</div>

@endif
