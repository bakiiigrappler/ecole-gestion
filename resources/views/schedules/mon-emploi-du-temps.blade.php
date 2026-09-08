@extends('layouts.app')

@section('titre', 'Mon emploi du temps')
@section('sous-titre', $enseignant
    ? $lignes->where('type', 'course')->count().' heure(s) de cours · '.$classes->count().' classe(s) — '.($academicYear->name ?? '')
    : 'Aucune fiche enseignant rattachée à ce compte')

@section('actions-entete')
    <a href="{{ route('classes.index') }}" class="bouton-secondaire">Mes classes</a>
@endsection

@section('contenu')

@php
    $jours = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'];
    $etablissement = \App\Models\SchoolSettings::getSettings();

    $heure = fn ($v) => substr((string) $v, 0, 5);

    // Ne pas imprimer une colonne de samedi vide.
    $joursUtiles = array_filter($jours, fn ($n, $j) => $lignes->contains('day_of_week', $j), ARRAY_FILTER_USE_BOTH);
    $jours = $joursUtiles ?: [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi'];

    $heuresParJour = collect($jours)->mapWithKeys(fn ($nom, $jour) => [
        $jour => $lignes->where('day_of_week', $jour)->where('type', 'course')->count(),
    ]);
@endphp

    @unless ($enseignant)
        <div class="carte p-8">
            <x-vide message="Ce compte n’est rattaché à aucune fiche enseignant : son emploi du temps ne peut pas être établi."/>
            <p class="mt-3 text-center text-xs text-gris-400">
                L’administration de l’établissement peut faire le rattachement depuis la fiche de l’enseignant.
            </p>
        </div>
    @else

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Heures par semaine" :valeur="$lignes->where('type', 'course')->count()"
                       detail="Cours effectifs" couleur="ogar"/>
        <x-statistique libelle="Classes" :valeur="$classes->count()"
                       detail="Où vous intervenez" couleur="emerald"/>
        <x-statistique libelle="Matières" :valeur="$volumes->count()"
                       detail="Enseignées" couleur="violet"/>
        <x-statistique libelle="Jours de présence" :valeur="collect($heuresParJour)->filter()->count()"
                       detail="Dans la semaine" couleur="amber"/>
    </div>

    {{-- ----------------------------------------------------------------
         Filtrer sur une classe — sans jamais voir celles des autres
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 flex flex-wrap items-center justify-between gap-3 p-4">
        <form method="GET" action="{{ route('schedules.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Classe</label>
                <select name="classe" onchange="this.form.submit()" class="champ w-56 text-sm">
                    <option value="">Toutes mes classes</option>
                    @foreach ($classes as $classe)
                        <option value="{{ $classe->id }}" @selected((string) $classeChoisie === (string) $classe->id)>
                            {{ $classe->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($classeChoisie)
                <a href="{{ route('schedules.index') }}" class="bouton-secondaire mb-0.5">Tout afficher</a>
            @endif
        </form>

        <p class="text-xs text-gris-400">
            Vous ne voyez que vos propres heures.
        </p>
    </div>

    @if ($creneaux->isEmpty())
        <div class="carte mt-4 p-8">
            <x-vide message="Aucune heure ne vous est attribuée sur cette sélection."/>
        </div>
    @else

    {{-- ------------------------------------------------------------------
         Deux lectures de la même semaine : la grille colorée, qui se lit d'un
         coup d'œil et sert au quotidien, et l'aperçu — le document officiel
         en noir et blanc, celui qui s'imprime et se dépose au dossier.
         ------------------------------------------------------------------ --}}
    <div x-data="{ vue: 'grille' }" class="mt-4">

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
                            data-export-pdf="mon-emploi-du-temps"
                            data-orientation="paysage"
                            data-page-unique
                            data-nom-fichier="Mon_emploi_du_temps.pdf">
                        Télécharger
                    </button>
                </div>
            </div>

            <div x-show="vue === 'grille'" class="p-4">
                <x-grille-emploi-du-temps :creneaux="$lignes" complement="classe"/>
            </div>
        </div>

    <div x-show="vue === 'apercu'" x-cloak
         id="mon-emploi-du-temps" class="carte mt-4 bg-white p-6 text-gris-900">

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
                    <p class="text-sm font-semibold">{{ $academicYear->name ?? '—' }}</p>
                </div>
                @if ($etablissement->seal_url ?? null)
                    <img src="{{ $etablissement->seal_url }}" alt="Sceau de la République"
                         class="h-12 w-16 shrink-0 object-contain">
                @endif
            </div>
        </div>

        <div class="mt-4 text-center">
            <h1 class="text-lg font-bold uppercase tracking-wide">Emploi du temps</h1>
            <p class="mt-0.5 text-sm text-gris-600">
                {{ $enseignant->first_name }} {{ $enseignant->last_name }}
                @if ($classeChoisie)
                    &middot; {{ optional($classes->firstWhere('id', (int) $classeChoisie))->name }}
                @endif
            </p>
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
                @foreach ($creneaux as $creneau)
                    <tr>
                        <td class="border border-gris-400 bg-gris-50 px-1.5 py-1 align-middle">
                            <div class="font-semibold tabular-nums text-gris-800">{{ $creneau['debut'] }}</div>
                            <div class="tabular-nums text-gris-500">{{ $creneau['fin'] }}</div>
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
                                    <div class="mt-0.5 text-[9px] text-gris-600">{{ $cours->schoolClass->name ?? '—' }}</div>
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
                                @if ($volume['classes'])
                                    <span class="text-gris-400">— {{ implode(', ', $volume['classes']) }}</span>
                                @endif
                            </span>
                            <span class="shrink-0 font-semibold tabular-nums">{{ $volume['heures'] }} h</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <p class="mt-4 text-[9px] text-gris-500">Édité le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
    </div>

    @endif
    @endunless

@endsection
