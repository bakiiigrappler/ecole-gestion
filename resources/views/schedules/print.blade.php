@extends('layouts.app')

@section('titre', 'Emploi du temps — '.$class->name)
@section('sous-titre', ($class->getSafeLevelName() ?? '—').' · '.$lignes->where('type', 'course')->count().' heures de cours — '.($academicYear->name ?? ''))

@section('actions-entete')
    <button type="button"
            class="bouton-secondaire"
            data-export-pdf="emploi-du-temps"
            data-orientation="paysage"
            data-page-unique
            data-nom-fichier="Emploi_du_temps_{{ \Illuminate\Support\Str::slug($class->name) }}.pdf">
        Exporter en PDF
    </button>
    <a href="{{ route('schedules.create', ['class_id' => $class->id, 'academic_year_id' => $academicYearId]) }}"
       class="bouton-primaire">Modifier</a>
@endsection

@section('contenu')

@php
    $etablissement = \App\Models\SchoolSettings::first();

    // Le document imprime se tient en noir et blanc : les couleurs par matiere
    // restent a la composition, ou elles servent a se reperer, mais une
    // photocopie ou une impression monochrome les rendrait illisibles.

    $duree = function ($debut, $fin) {
        [$hd, $md] = array_map('intval', explode(':', $debut));
        [$hf, $mf] = array_map('intval', explode(':', $fin));
        $minutes = ($hf * 60 + $mf) - ($hd * 60 + $md);

        if ($minutes <= 0) return '';
        if ($minutes < 60) return $minutes.' min';

        $reste = $minutes % 60;

        return intdiv($minutes, 60).' h'.($reste ? ' '.$reste : '');
    };

    $heuresParJour = collect($jours)->mapWithKeys(fn ($nom, $jour) => [
        $jour => $lignes->where('day_of_week', $jour)->where('type', 'course')->count(),
    ]);
@endphp

    @if ($creneaux->isEmpty())
        <div class="carte p-8">
            <x-vide message="Cette classe n’a aucun créneau enregistré pour cette année."/>
            <div class="mt-4 text-center">
                <a href="{{ route('schedules.create', ['class_id' => $class->id, 'academic_year_id' => $academicYearId]) }}"
                   class="bouton-primaire">Construire l’emploi du temps</a>
            </div>
        </div>
    @else

    {{-- ------------------------------------------------------------------
         Le document. Ce bloc est celui que html2canvas photographie :
         le PDF est exactement ce qui s'affiche ici.
         ------------------------------------------------------------------ --}}
    <div id="emploi-du-temps" class="mx-auto max-w-6xl bg-white p-6 text-gris-900 ring-1 ring-gris-200">

        {{-- En-tête : logo de l'établissement à gauche, sceau de la
             République à droite, comme sur le bulletin. --}}
        <div class="flex items-start justify-between gap-4 border-b-2 border-gris-800 pb-3">
            <div class="flex items-start gap-3">
                @if ($etablissement->logo_url ?? null)
                    <img src="{{ $etablissement->logo_url }}" alt="Logo de l’établissement"
                         class="h-12 w-12 shrink-0 object-contain">
                @else
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                        Logo<br>établissement
                    </span>
                @endif
                <div class="leading-tight">
                    <p class="text-[10px] text-gris-600">Ministère de l’Éducation Nationale</p>
                    <p class="text-sm font-bold uppercase">{{ $etablissement->school_name ?? 'Établissement scolaire' }}</p>
                    <p class="text-[9px] text-gris-500">
                        @if ($etablissement->school_bp ?? null) {{ $etablissement->school_bp }} @endif
                        @if ($etablissement->school_phone ?? null) Tél/fax : {{ $etablissement->school_phone }} @endif
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="text-right leading-tight">
                    <p class="text-[10px] uppercase tracking-wide text-gris-500">Année scolaire</p>
                    <p class="text-sm font-semibold">{{ $academicYear->name ?? '—' }}</p>
                </div>

                @if ($etablissement->seal_url ?? null)
                    <img src="{{ $etablissement->seal_url }}" alt="Sceau de la République"
                         class="h-12 w-16 shrink-0 object-contain">
                @else
                    <span class="flex h-12 w-16 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                        Sceau de la<br>République
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-4 text-center">
            <h1 class="text-lg font-bold uppercase tracking-wide">Emploi du temps</h1>
            <p class="mt-0.5 text-sm text-gris-600">
                Classe <span class="font-semibold text-gris-900">{{ $class->name }}</span>
                @if ($class->getSafeLevelName())
                    &middot; {{ $class->getSafeLevelName() }}
                @endif
            </p>
        </div>

        {{-- La grille hebdomadaire --}}
        <table class="mt-4 w-full table-fixed border-collapse text-[10px]">
            <thead>
                <tr>
                    <th class="w-24 border border-gris-400 bg-gris-200 px-1 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-600">
                        Horaire
                    </th>
                    @foreach ($jours as $nom)
                        <th class="border border-gris-400 bg-gris-200 px-1 py-1.5 font-semibold uppercase tracking-wide text-gris-700">
                            {{ $nom }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($creneaux as $creneau)
                    <tr>
                        <td class="border border-gris-400 bg-gris-50 px-1.5 py-1 align-middle">
                            <div class="font-semibold tabular-nums text-gris-800">{{ $creneau['debut'] }}</div>
                            <div class="tabular-nums text-gris-500">{{ $creneau['fin'] }}</div>
                            <div class="mt-0.5 text-[9px] text-gris-400">{{ $duree($creneau['debut'], $creneau['fin']) }}</div>
                        </td>

                        @foreach ($jours as $jour => $nom)
                            @php($cours = $grille[$jour.'-'.$creneau['debut']] ?? null)

                            @if (! $cours)
                                <td class="border border-gris-400 px-1 py-1.5 align-top"></td>
                            @elseif ($cours->type === 'break')
                                <td class="border border-gris-400 bg-gris-100 px-1 py-1.5 text-center align-middle text-[9px] uppercase tracking-wide text-gris-500">
                                    {{ $cours->title ?: 'Récréation' }}
                                </td>
                            @else
                                <td class="border border-gris-400 bg-white px-1.5 py-1 align-top text-gris-900">
                                    <div class="font-semibold leading-tight">{{ $cours->subject->name ?? '—' }}</div>
                                    @if ($cours->teacher)
                                        <div class="mt-0.5 text-[9px] text-gris-600">
                                            {{ $cours->teacher->first_name }} {{ $cours->teacher->last_name }}
                                        </div>
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
                    <td class="border border-gris-400 bg-gris-200 px-1.5 py-1 text-[9px] font-semibold uppercase tracking-wide text-gris-600">
                        Heures / jour
                    </td>
                    @foreach ($jours as $jour => $nom)
                        <td class="border border-gris-400 bg-gris-200 px-1 py-1 text-center font-semibold text-gris-700">
                            {{ $heuresParJour[$jour] ?: '—' }}
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        </table>

        {{-- Récapitulatif horaire par matière --}}
        @if ($volumes->isNotEmpty())
            <div class="mt-4">
                <h2 class="mb-1.5 text-[10px] font-bold uppercase tracking-wide text-gris-600">
                    Volume horaire hebdomadaire
                </h2>

                <div class="grid grid-cols-2 gap-x-6 gap-y-0.5 text-[10px] sm:grid-cols-3">
                    @foreach ($volumes as $volume)
                        <div class="flex items-baseline justify-between gap-2 border-b border-gris-100 py-0.5">
                            <span class="truncate">
                                {{ $volume['matiere'] }}
                                @if ($volume['enseignants'])
                                    <span class="text-gris-400">— {{ implode(', ', $volume['enseignants']) }}</span>
                                @endif
                            </span>
                            <span class="shrink-0 font-semibold tabular-nums">{{ $volume['heures'] }} h</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-5 flex items-end justify-between text-[9px] text-gris-500">
            <span>Édité le {{ now()->format('d/m/Y à H:i') }}</span>
            <span class="text-right">
                <span class="block">{{ $etablissement->principal_title ?? 'Le Chef d’établissement' }}</span>
                <span class="mt-6 block w-40 border-t border-gris-400"></span>
            </span>
        </div>
    </div>

    @endif

@endsection
