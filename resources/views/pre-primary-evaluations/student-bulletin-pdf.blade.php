@extends('layouts.app')

@section('titre', 'Bulletin — '.$student->first_name.' '.$student->last_name)
@section('sous-titre', ($currentEnrollment->schoolClass->name ?? '—').' · '.($academicYear->name ?? ''))

@section('actions-entete')
    <button type="button"
            class="bouton-secondaire"
            data-export-pdf="bulletin-preprimaire"
            data-orientation="paysage"
            data-page-unique
            data-nom-fichier="Bulletin_{{ \Illuminate\Support\Str::slug($student->first_name.' '.$student->last_name) }}.pdf">
        Télécharger le bulletin
    </button>
    <a href="{{ route('pre-primary-evaluations.bulletins', $currentEnrollment->schoolClass->id) }}" class="bouton-primaire">
        Retour à la classe
    </a>
@endsection

@section('contenu')

@php
    // Le préprimaire s'évalue par codes, trimestre par trimestre.
    $libellesCode = ['MAX' => 'Maîtrise maximale', 'MIN' => 'Maîtrise minimale', 'PART' => 'Maîtrise partielle', 'NM' => 'Non maîtrisé'];

    $trimestres = [1 => '1er trimestre', 2 => '2e trimestre', 3 => '3e trimestre'];

    // Le document s'imprime : gris, noir et blanc uniquement.
    $fondCode = ['MAX' => '#ffffff', 'MIN' => '#f1f5f9', 'PART' => '#e2e8f0', 'NM' => '#cbd5e1'];

    $renseignes = $evaluations->filter(fn ($e) => $e->trimester_1_code || $e->trimester_2_code || $e->trimester_3_code);
@endphp

    @if ($renseignes->isEmpty())
        <div class="carte p-8">
            <x-vide message="Aucune évaluation n’a encore été saisie pour cet enfant."/>
            <div class="mt-4 text-center">
                <a href="{{ route('pre-primary-evaluations.create', ['class_id' => $currentEnrollment->schoolClass->id]) }}"
                   class="bouton-primaire">Évaluer cet enfant</a>
            </div>
        </div>
    @else

    {{-- ------------------------------------------------------------------
         Le document. Ce bloc est celui que html2canvas photographie :
         le PDF est exactement ce qui s'affiche ici.
         ------------------------------------------------------------------ --}}
    <div id="bulletin-preprimaire" class="mx-auto max-w-6xl bg-white p-6 text-gris-900 ring-1 ring-gris-200">

        {{-- En-tête : logo de l'établissement à gauche, sceau à droite --}}
        <div class="flex items-start justify-between gap-4 border-b-2 border-gris-800 pb-3">
            <div class="flex items-start gap-3">
                @if ($schoolSettings->logo_url ?? null)
                    <img src="{{ $schoolSettings->logo_url }}" alt="Logo de l’établissement"
                         class="h-12 w-12 shrink-0 object-contain">
                @else
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                        Logo<br>établissement
                    </span>
                @endif
                <div class="leading-tight">
                    <p class="text-[10px] text-gris-600">Ministère de l’Éducation Nationale</p>
                    <p class="text-sm font-bold uppercase">{{ $schoolName ?? ($schoolSettings->school_name ?? 'Établissement scolaire') }}</p>
                    <p class="text-[9px] text-gris-500">
                        @if ($schoolSettings->school_bp ?? null) {{ $schoolSettings->school_bp }} @endif
                        @if ($schoolSettings->school_phone ?? null) Tél/fax : {{ $schoolSettings->school_phone }} @endif
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="text-right leading-tight">
                    <p class="text-[10px] uppercase tracking-wide text-gris-500">Année scolaire</p>
                    <p class="text-sm font-semibold">{{ $academicYear->name ?? '—' }}</p>
                </div>

                @if ($schoolSettings->seal_url ?? null)
                    <img src="{{ $schoolSettings->seal_url }}" alt="Sceau de la République"
                         class="h-12 w-16 shrink-0 object-contain">
                @else
                    <span class="flex h-12 w-16 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                        Sceau de la<br>République
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-4 text-center">
            <h1 class="text-lg font-bold uppercase tracking-wide">Bulletin d’évaluation — Préprimaire</h1>
        </div>

        {{-- Identité de l'enfant --}}
        <table class="mt-3 w-full border-collapse border border-gris-400 text-[11px]">
            <tr>
                <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-semibold uppercase tracking-wide text-gris-600">Enfant</td>
                <td class="border border-gris-400 px-2 py-1 font-semibold">
                    {{ $student->last_name }} {{ $student->first_name }}
                </td>
                <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-semibold uppercase tracking-wide text-gris-600">Matricule</td>
                <td class="border border-gris-400 px-2 py-1 font-mono">{{ $student->student_id }}</td>
                <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-semibold uppercase tracking-wide text-gris-600">Classe</td>
                <td class="border border-gris-400 px-2 py-1">{{ $currentEnrollment->schoolClass->name ?? '—' }}</td>
                <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-semibold uppercase tracking-wide text-gris-600">Né(e) le</td>
                <td class="border border-gris-400 px-2 py-1">
                    {{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : '—' }}
                </td>
            </tr>
        </table>

        {{-- Les compétences, domaine par domaine --}}
        <table class="mt-3 w-full border-collapse border border-gris-400 text-[10px]">
            <thead>
                <tr class="bg-gris-200">
                    <th class="border border-gris-400 px-2 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-700">
                        Compétence
                    </th>
                    @foreach ($trimestres as $libelle)
                        <th class="w-28 border border-gris-400 px-2 py-1.5 font-semibold uppercase tracking-wide text-gris-700">
                            {{ $libelle }}
                        </th>
                    @endforeach
                    <th class="w-64 border border-gris-400 px-2 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-700">
                        Appréciation
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($competencies as $domaine => $liste)
                    <tr>
                        <td colspan="5" class="border border-gris-400 bg-gris-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-gris-700">
                            {{ $domaine }}
                        </td>
                    </tr>

                    @foreach ($liste as $competence)
                        @php($evaluation = $evaluations[$competence->id] ?? null)
                        <tr>
                            <td class="border border-gris-400 px-2 py-1">{{ $competence->name }}</td>

                            @foreach ([1, 2, 3] as $trimestre)
                                @php($code = $evaluation?->{"trimester_{$trimestre}_code"})
                                <td class="border border-gris-400 px-1 py-1 text-center font-semibold"
                                    style="background:{{ $code ? ($fondCode[$code] ?? '#ffffff') : '#ffffff' }}">
                                    {{ $code ?: '—' }}
                                </td>
                            @endforeach

                            <td class="border border-gris-400 px-2 py-1 text-gris-600">
                                {{ $evaluation?->trimester_3_comment
                                    ?: ($evaluation?->trimester_2_comment
                                    ?: ($evaluation?->trimester_1_comment ?: '')) }}
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        {{-- Légende et signature --}}
        <div class="mt-3 flex items-start justify-between gap-6">
            <div>
                <p class="mb-1 text-[9px] font-bold uppercase tracking-wide text-gris-600">Légende</p>
                <div class="flex flex-wrap gap-3 text-[9px] text-gris-600">
                    @foreach ($libellesCode as $code => $libelle)
                        <span class="flex items-center gap-1">
                            <span class="inline-flex h-4 w-7 shrink-0 items-center justify-center border border-gris-400 font-semibold"
                                  style="background:{{ $fondCode[$code] }}">{{ $code }}</span>
                            {{ $libelle }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="text-right text-[9px] text-gris-500">
                <span class="block">{{ $schoolSettings->principal_title ?? 'Le Chef d’établissement' }}</span>
                <span class="ml-auto mt-8 block w-40 border-t border-gris-400"></span>
            </div>
        </div>

        <p class="mt-4 text-[9px] text-gris-500">Édité le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>

    @endif

@endsection
