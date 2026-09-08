@extends('layouts.app')

@section('titre', 'Fiche de classe — '.$class->name)
@section('sous-titre', ($class->getSafeLevelName() ?? '—').' · '.$eleves->count().' élève(s) — '.($annee->name ?? ''))

@section('actions-entete')
    <button type="button"
            class="bouton-secondaire"
            data-export-pdf="fiche-de-classe"
            data-orientation="paysage"
            data-nom-fichier="Fiche_classe_{{ \Illuminate\Support\Str::slug($class->name) }}.pdf">
        Télécharger la fiche
    </button>
    <a href="{{ route('students.index', ['class' => $class->id]) }}" class="bouton-primaire">Voir les élèves</a>
@endsection

@section('contenu')

@php
    $etablissement = \App\Models\SchoolSettings::first();

    // Une feuille A4 paysage tient une vingtaine de lignes en restant lisible :
    // au-delà le tableau est réduit à la photographie et devient illisible.
    $feuillets = $eleves->chunk(20);

    /*
     * Le sexe est stocke tantot « M »/« F », tantot « male »/« female » selon
     * l'ancienneté de la fiche : le document, lui, n'affiche qu'une lettre.
     */
    $sexe = fn ($eleve) => match (mb_strtolower((string) $eleve->gender)) {
        'm', 'male', 'masculin', 'garcon', 'garçon' => 'M',
        'f', 'female', 'feminin', 'féminin', 'fille' => 'F',
        default => '—',
    };

    $garcons = $eleves->filter(fn ($e) => $sexe($e) === 'M')->count();
    $filles = $eleves->filter(fn ($e) => $sexe($e) === 'F')->count();

    $tuteur = function ($eleve) {
        $parent = $eleve->parents->first();

        return $parent ? $parent->last_name.' '.$parent->first_name : '—';
    };

    $contact = function ($eleve) {
        $parent = $eleve->parents->first();

        return $parent?->phone ?: ($eleve->phone ?: ($eleve->emergency_contact ?: '—'));
    };
@endphp

    @if ($eleves->isEmpty())
        <div class="carte p-8">
            <x-vide message="Aucun élève n’est inscrit dans cette classe cette année."/>
        </div>
    @else

    {{-- ------------------------------------------------------------------
         Le document. C'est ce bloc que html2canvas photographie : chaque
         feuillet devient une feuille du PDF.
         ------------------------------------------------------------------ --}}
    <div id="fiche-de-classe" class="space-y-6">

        @foreach ($feuillets as $rang => $feuillet)
            <div data-page-pdf class="mx-auto w-full max-w-6xl bg-white p-6 text-gris-900 ring-1 ring-gris-200">

                {{-- En-tête officiel : logo de l'établissement à gauche,
                     sceau de la République à droite. --}}
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
                                @if ($etablissement->school_phone ?? null) &middot; Tél : {{ $etablissement->school_phone }} @endif
                            </p>
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
                        @else
                            <span class="flex h-12 w-16 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                                Sceau de la<br>République
                            </span>
                        @endif
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <h1 class="text-lg font-bold uppercase tracking-wide">Fiche de classe</h1>
                    <p class="mt-0.5 text-sm text-gris-600">
                        Classe <span class="font-semibold text-gris-900">{{ $class->name }}</span>
                        @if ($class->getSafeLevelName())
                            &middot; {{ $class->getSafeLevelName() }}
                        @endif
                    </p>
                </div>

                {{-- Le récapitulatif ne figure qu'en tête du premier feuillet. --}}
                @if ($rang === 0)
                    <div class="mt-3 grid grid-cols-4 gap-px border border-gris-400 bg-gris-400 text-center text-[10px]">
                        <div class="bg-white px-2 py-1.5">
                            <p class="uppercase tracking-wide text-gris-500">Effectif</p>
                            <p class="text-sm font-bold">{{ $eleves->count() }}</p>
                        </div>
                        <div class="bg-white px-2 py-1.5">
                            <p class="uppercase tracking-wide text-gris-500">Garçons</p>
                            <p class="text-sm font-bold">{{ $garcons }}</p>
                        </div>
                        <div class="bg-white px-2 py-1.5">
                            <p class="uppercase tracking-wide text-gris-500">Filles</p>
                            <p class="text-sm font-bold">{{ $filles }}</p>
                        </div>
                        <div class="bg-white px-2 py-1.5">
                            <p class="uppercase tracking-wide text-gris-500">Professeur principal</p>
                            <p class="text-sm font-bold">
                                {{ $principal ? $principal->last_name.' '.$principal->first_name : '—' }}
                            </p>
                        </div>
                    </div>
                @endif

                <table class="mt-3 w-full border-collapse text-[10px]">
                    <thead>
                        <tr>
                            <th class="w-8 border border-gris-400 bg-gris-200 px-1 py-1.5 text-center font-semibold uppercase tracking-wide text-gris-700">N°</th>
                            <th class="w-24 border border-gris-400 bg-gris-200 px-2 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-700">Matricule</th>
                            <th class="border border-gris-400 bg-gris-200 px-2 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-700">Nom et prénoms</th>
                            <th class="w-10 border border-gris-400 bg-gris-200 px-1 py-1.5 text-center font-semibold uppercase tracking-wide text-gris-700">Sexe</th>
                            <th class="w-20 border border-gris-400 bg-gris-200 px-2 py-1.5 text-center font-semibold uppercase tracking-wide text-gris-700">Né(e) le</th>
                            <th class="w-28 border border-gris-400 bg-gris-200 px-2 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-700">Lieu de naissance</th>
                            <th class="border border-gris-400 bg-gris-200 px-2 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-700">Parent ou tuteur</th>
                            <th class="w-24 border border-gris-400 bg-gris-200 px-2 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-700">Contact</th>
                            <th class="w-20 border border-gris-400 bg-gris-200 px-1 py-1.5 text-center font-semibold uppercase tracking-wide text-gris-700">Observations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($feuillet as $eleve)
                            <tr>
                                <td class="border border-gris-400 px-1 py-1 text-center tabular-nums text-gris-600">
                                    {{ $rang * 20 + $loop->iteration }}
                                </td>
                                <td class="border border-gris-400 px-2 py-1 tabular-nums">{{ $eleve->student_id }}</td>
                                <td class="border border-gris-400 px-2 py-1 font-medium uppercase">
                                    {{ $eleve->last_name }} <span class="font-normal normal-case">{{ $eleve->first_name }}</span>
                                </td>
                                <td class="border border-gris-400 px-1 py-1 text-center">{{ $sexe($eleve) }}</td>
                                <td class="border border-gris-400 px-2 py-1 text-center tabular-nums">
                                    {{ $eleve->date_of_birth ? \Carbon\Carbon::parse($eleve->date_of_birth)->format('d/m/Y') : '—' }}
                                </td>
                                <td class="border border-gris-400 px-2 py-1">{{ $eleve->place_of_birth ?: '—' }}</td>
                                <td class="border border-gris-400 px-2 py-1">{{ $tuteur($eleve) }}</td>
                                <td class="border border-gris-400 px-2 py-1 tabular-nums">{{ $contact($eleve) }}</td>
                                <td class="border border-gris-400 px-1 py-1"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Pied : le feuillet se signe, et se numérote. --}}
                <div class="mt-4 flex items-end justify-between text-[9px] text-gris-500">
                    <p>Établi le {{ now()->locale('fr')->isoFormat('D MMMM YYYY') }}</p>

                    @if ($rang === $feuillets->count() - 1)
                        <div class="text-center">
                            <p class="text-gris-600">Le professeur principal</p>
                            <p class="mt-8 border-t border-gris-400 px-8 pt-1">
                                {{ $principal ? $principal->last_name.' '.$principal->first_name : '' }}
                            </p>
                        </div>
                    @endif

                    <p>Feuille {{ $rang + 1 }} / {{ $feuillets->count() }}</p>
                </div>
            </div>
        @endforeach
    </div>

    @endif

@endsection
