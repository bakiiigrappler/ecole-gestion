@extends('layouts.app')

@section('titre', 'Procès-verbal de délibération')
@section('sous-titre', $examen->intitule_complet.($centre ? ' · '.$centre->nom : ' · toute la session'))

@section('actions-entete')
    <button type="button"
            class="bouton-primaire"
            data-export-pdf="proces-verbal"
            data-nom-fichier="PV_{{ \Illuminate\Support\Str::slug($examen->intitule_complet) }}.pdf">
        Télécharger le procès-verbal
    </button>
    <a href="{{ route('examens.show', $examen) }}" class="bouton-secondaire">La session</a>
@endsection

@section('contenu')

@php
    $nombre = fn ($v) => $v === null ? '—' : number_format($v, 2, ',', ' ');
    $total = $candidats->count();
    $admis = $candidats->where('statut', 'admis')->count();
    $taux = $total > 0 ? round($admis / $total * 100, 2) : 0;
@endphp

{{-- Ce que le document ne dit pas de lui-même, dit à l'écran --}}
@if ($document)
    <div class="carte mb-6 p-5 sans-impression {{ $document->estIntact() ? 'border-emerald-300' : 'border-corail-300' }}">
        <h2 class="text-sm font-semibold {{ $document->estIntact() ? 'text-emerald-900' : 'text-corail-900' }}">
            {{ $document->estIntact() ? 'Document scellé et intact' : 'Les résultats ont changé depuis la signature' }}
        </h2>
        <p class="mt-1.5 text-sm leading-relaxed text-gris-700">
            Signé par {{ $document->signataire_nom }}, {{ $document->signataire_qualite }},
            le {{ optional($document->signe_le)->format('d/m/Y à H:i') }}.
            @if ($document->estIntact())
                L’empreinte enregistrée correspond toujours aux résultats affichés.
            @else
                L’empreinte enregistrée ne correspond plus : une note a été modifiée après la
                signature. Il faut signer de nouveau, ou revenir aux résultats signés.
            @endif
        </p>
        @if ($document->jeton_verification)
            <p class="mt-2 text-[11px] text-gris-500">
                Jeton de vérification : <span class="font-mono">{{ $document->jeton_verification }}</span>
            </p>
        @endif
    </div>
@else
    <div class="carte mb-6 border-soleil-300 bg-soleil-50 p-5 sans-impression">
        <h2 class="text-sm font-semibold text-soleil-900">Procès-verbal non signé</h2>
        <p class="mt-1.5 text-sm leading-relaxed text-gris-700">
            Voici ce que le signataire lira. Tant qu’il n’est pas signé, ce document n’a aucune
            valeur et les résultats ne peuvent pas être proclamés.
        </p>
    </div>
@endif

{{-- ----------------------------------------------------------------------
     Le document, taillé pour une feuille A4 portrait : 794 × 1123 px à 96 ppp.
     ---------------------------------------------------------------------- --}}
<div class="overflow-x-auto">
<div id="proces-verbal"
     class="mx-auto flex flex-col bg-white px-10 py-8 text-gris-900 ring-1 ring-gris-200"
     style="width: 794px; min-height: 1123px;">

    <div class="border-b-2 border-gris-800 pb-3 text-center">
        <p class="text-[11px] font-bold uppercase tracking-wide">République Gabonaise</p>
        <p class="text-[9px] italic text-gris-600">Union — Travail — Justice</p>
        <p class="mt-1 text-[9px] uppercase leading-snug text-gris-600">
            Ministère de l’Éducation Nationale<br>
            Direction générale des examens et concours
        </p>
    </div>

    <h1 class="mt-5 text-center text-base font-bold uppercase tracking-wide">
        Procès-verbal de délibération
    </h1>
    <p class="text-center text-sm text-gris-700">
        {{ $examen->intitule_complet }}
    </p>
    @if ($centre)
        <p class="text-center text-[11px] text-gris-600">
            Centre de {{ $centre->nom }}@if ($centre->ville), {{ $centre->ville }}@endif
        </p>
    @endif

    {{-- Le bilan --}}
    <table class="mt-5 w-full border-collapse text-[11px]">
        <tbody>
            <tr>
                <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-semibold">Candidats présentés</td>
                <td class="w-24 border border-gris-400 px-2 py-1 text-right tabular-nums">{{ $total }}</td>
                <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-semibold">Admis</td>
                <td class="w-24 border border-gris-400 px-2 py-1 text-right tabular-nums">{{ $admis }}</td>
            </tr>
            <tr>
                <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-semibold">Second tour</td>
                <td class="border border-gris-400 px-2 py-1 text-right tabular-nums">
                    {{ $candidats->where('statut', 'second_tour')->count() }}
                </td>
                <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-semibold">Ajournés</td>
                <td class="border border-gris-400 px-2 py-1 text-right tabular-nums">
                    {{ $candidats->where('statut', 'ajourne')->count() }}
                </td>
            </tr>
            <tr>
                <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-semibold">Absents</td>
                <td class="border border-gris-400 px-2 py-1 text-right tabular-nums">
                    {{ $candidats->where('statut', 'absent')->count() }}
                </td>
                <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-semibold">Taux de réussite</td>
                <td class="border border-gris-400 px-2 py-1 text-right font-bold tabular-nums">
                    {{ number_format($taux, 2, ',', ' ') }} %
                </td>
            </tr>
        </tbody>
    </table>

    {{-- La liste nominative : c'est elle que l'empreinte protège --}}
    <table class="mt-5 w-full border-collapse text-[9px]">
        <thead>
            <tr class="bg-gris-100">
                <th class="border border-gris-400 px-1.5 py-1 text-left">N° candidat</th>
                <th class="border border-gris-400 px-1.5 py-1 text-left">Nom et prénoms</th>
                <th class="w-16 border border-gris-400 px-1.5 py-1 text-center">Moyenne</th>
                <th class="w-24 border border-gris-400 px-1.5 py-1 text-center">Décision</th>
                <th class="w-20 border border-gris-400 px-1.5 py-1 text-center">Mention</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($candidats as $candidat)
                <tr>
                    <td class="border border-gris-400 px-1.5 py-0.5 font-mono">{{ $candidat->numero_candidat }}</td>
                    <td class="border border-gris-400 px-1.5 py-0.5">{{ $candidat->nom_complet }}</td>
                    <td class="border border-gris-400 px-1.5 py-0.5 text-center tabular-nums">
                        {{ $nombre($candidat->moyenne) }}
                    </td>
                    <td class="border border-gris-400 px-1.5 py-0.5 text-center
                               {{ $candidat->statut === 'admis' ? 'font-semibold' : '' }}">
                        {{ $candidat->libelle_statut }}
                    </td>
                    <td class="border border-gris-400 px-1.5 py-0.5 text-center">{{ $candidat->mention ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Le visa --}}
    <div class="mt-auto pt-8">
        <p class="text-[10px] leading-relaxed text-gris-700">
            Le jury, après avoir examiné les résultats de l’ensemble des candidats, arrête la présente
            liste à {{ $total }} candidat(s) présenté(s), dont {{ $admis }} admis.
        </p>

        <div class="mt-6 flex items-end justify-between gap-8 text-[10px]">
            <div class="max-w-xs leading-relaxed text-gris-600">
                @if ($document)
                    <p class="font-semibold text-gris-800">
                        {{ $document->libelle_mode }}
                    </p>
                    <p>
                        {{ $document->signataire_nom }} — {{ $document->signataire_qualite }},
                        le {{ optional($document->signe_le)->format('d/m/Y à H:i') }}.
                    </p>
                    @if ($document->jeton_verification)
                        <p class="mt-1">
                            Vérification : <span class="font-mono">{{ $document->jeton_verification }}</span>
                        </p>
                    @endif
                @else
                    <p class="italic text-gris-500">Document non signé — sans valeur en l’état.</p>
                @endif
            </div>

            <div class="text-center text-[10px]">
                <p class="text-gris-600">
                    {{ $document->signataire_qualite ?? ($centre->president_jury ?? 'Le Président du jury') }}
                </p>
                <p class="mt-12 w-48 border-t border-gris-400 pt-0.5 text-gris-400">Signature et cachet</p>
            </div>
        </div>

        <p class="mt-4 border-t border-gris-200 pt-2 text-[8px] leading-snug text-gris-500">
            Établi le {{ now()->locale('fr')->isoFormat('D MMMM YYYY à HH:mm') }}. Ce procès-verbal fait foi
            des résultats de la session ; toute contestation s’instruit à partir de lui.
        </p>
    </div>
</div>
</div>

@endsection
