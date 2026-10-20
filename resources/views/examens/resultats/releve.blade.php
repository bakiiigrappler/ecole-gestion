@extends('layouts.app')

@section('titre', 'Relevé de notes d’examen')
@section('sous-titre', $examen->intitule_complet.' · '.$candidat->numero_candidat)

@section('actions-entete')
    <button type="button"
            class="bouton-primaire"
            data-export-pdf="releve-examen"
            data-page-unique
            data-nom-fichier="Releve_{{ $candidat->numero_candidat }}.pdf">
        Télécharger le relevé
    </button>
    <a href="{{ route('examens.mes-examens') }}" class="bouton-secondaire">Mes examens</a>
@endsection

@section('contenu')

@php
    $nombre = fn ($v) => $v === null ? '—' : number_format($v, 2, ',', ' ');
    $admis = $candidat->statut === 'admis';
    $total = 0;
@endphp

{{-- ----------------------------------------------------------------------
     Le document, taillé pour une feuille A4 portrait : 794 × 1123 px à 96 ppp.
     C'est ce bloc que html2canvas photographie — le PDF est exactement ce qui
     s'affiche ici.
     ---------------------------------------------------------------------- --}}
<div class="overflow-x-auto">
<div id="releve-examen"
     class="mx-auto flex flex-col bg-white px-10 py-8 text-gris-900 ring-1 ring-gris-200"
     style="width: 794px; min-height: 1123px;">

    {{-- En-tête officiel --}}
    <div class="border-b-2 border-gris-800 pb-3 text-center">
        <p class="text-[11px] font-bold uppercase tracking-wide">République Gabonaise</p>
        <p class="text-[9px] italic text-gris-600">Union — Travail — Justice</p>
        <p class="mt-1 text-[9px] uppercase leading-snug text-gris-600">
            Ministère de l’Éducation Nationale<br>
            Direction générale des examens et concours
        </p>
    </div>

    <h1 class="mt-5 text-center text-base font-bold uppercase tracking-wide">
        Relevé de notes
    </h1>
    <p class="text-center text-sm text-gris-700">{{ $examen->intitule_complet }} — session {{ $examen->annee }}</p>

    {{-- Le candidat --}}
    <dl class="mt-5 grid grid-cols-2 gap-x-8 gap-y-1.5 text-[11px]">
        <div class="flex justify-between gap-2 border-b border-dotted border-gris-300 pb-1">
            <dt class="text-gris-500">Nom et prénoms</dt>
            <dd class="text-right font-semibold">{{ $candidat->nom_complet }}</dd>
        </div>
        <div class="flex justify-between gap-2 border-b border-dotted border-gris-300 pb-1">
            <dt class="text-gris-500">Numéro de candidat</dt>
            <dd class="font-mono font-semibold">{{ $candidat->numero_candidat }}</dd>
        </div>
        <div class="flex justify-between gap-2 border-b border-dotted border-gris-300 pb-1">
            <dt class="text-gris-500">Né(e) le</dt>
            <dd class="font-medium tabular-nums">
                {{ optional($candidat->date_naissance)->format('d/m/Y') ?: '—' }}
                @if ($candidat->lieu_naissance) à {{ $candidat->lieu_naissance }} @endif
            </dd>
        </div>
        <div class="flex justify-between gap-2 border-b border-dotted border-gris-300 pb-1">
            <dt class="text-gris-500">Série</dt>
            <dd class="font-medium">{{ $candidat->serie ?: '—' }}</dd>
        </div>
        <div class="flex justify-between gap-2 border-b border-dotted border-gris-300 pb-1">
            <dt class="text-gris-500">Centre</dt>
            <dd class="text-right font-medium">{{ $candidat->centre->nom ?? '—' }}</dd>
        </div>
        <div class="flex justify-between gap-2 border-b border-dotted border-gris-300 pb-1">
            <dt class="text-gris-500">Établissement</dt>
            <dd class="text-right font-medium">
                {{ $candidat->etablissement_origine ?: ($candidat->school->name ?? 'Candidat libre') }}
            </dd>
        </div>
    </dl>

    {{-- Les notes, épreuve par épreuve --}}
    <table class="mt-6 w-full border-collapse text-[11px]">
        <thead>
            <tr class="bg-gris-100">
                <th class="border border-gris-400 px-2 py-1.5 text-left">Épreuve</th>
                <th class="w-16 border border-gris-400 px-2 py-1.5 text-center">Coef.</th>
                <th class="w-20 border border-gris-400 px-2 py-1.5 text-center">Note</th>
                <th class="w-20 border border-gris-400 px-2 py-1.5 text-center">Sur 20</th>
                <th class="w-20 border border-gris-400 px-2 py-1.5 text-center">Points</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($epreuves as $epreuve)
                @php($note = $notes->get($epreuve->id))
                @php($sur20 = $note && ! $note->absent ? $epreuve->sur20($note->note) : ($note?->absent ? 0 : null))
                @php($points = $sur20 === null ? null : ($epreuve->facultative ? max(0, $sur20 - 10) * $epreuve->coefficient : $sur20 * $epreuve->coefficient))
                @php($total += $points ?? 0)

                <tr>
                    <td class="border border-gris-400 px-2 py-1">
                        {{ $epreuve->intitule }}
                        @if ($epreuve->facultative)
                            <span class="text-[9px] italic text-gris-500">(facultative)</span>
                        @endif
                    </td>
                    <td class="border border-gris-400 px-2 py-1 text-center tabular-nums">
                        {{ $epreuve->facultative ? '—' : $epreuve->coefficient }}
                    </td>
                    <td class="border border-gris-400 px-2 py-1 text-center tabular-nums">
                        @if ($note?->absent)
                            <span class="italic text-corail-600">Absent</span>
                        @else
                            {{ $note ? $nombre($note->note).' / '.$epreuve->bareme : '—' }}
                        @endif
                    </td>
                    <td class="border border-gris-400 px-2 py-1 text-center font-semibold tabular-nums">
                        {{ $nombre($sur20) }}
                    </td>
                    <td class="border border-gris-400 px-2 py-1 text-center tabular-nums">
                        {{ $nombre($points) }}
                    </td>
                </tr>
            @endforeach

            <tr class="bg-gris-100 font-bold">
                <td class="border border-gris-400 px-2 py-1.5 uppercase">Total</td>
                <td class="border border-gris-400 px-2 py-1.5 text-center tabular-nums">
                    {{ rtrim(rtrim(number_format($diviseur, 1, ',', ' '), '0'), ',') }}
                </td>
                <td class="border border-gris-400 px-2 py-1.5"></td>
                <td class="border border-gris-400 px-2 py-1.5"></td>
                <td class="border border-gris-400 px-2 py-1.5 text-center tabular-nums">
                    {{ $nombre($candidat->total_points ?? $total) }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- La décision --}}
    <div class="mt-5 border-y-2 border-gris-800 py-3">
        <div class="flex items-baseline justify-between gap-6">
            <div>
                <p class="text-[9px] uppercase tracking-wide text-gris-500">Moyenne générale</p>
                <p class="text-2xl font-bold tabular-nums leading-none">
                    {{ $nombre($candidat->moyenne) }}<span class="text-sm font-normal text-gris-500">/20</span>
                </p>
            </div>

            <div class="text-right">
                <p class="text-[9px] uppercase tracking-wide text-gris-500">Décision du jury</p>
                <p class="text-lg font-bold uppercase {{ $admis ? 'text-emerald-700' : 'text-corail-700' }}">
                    {{ $candidat->libelle_statut }}
                </p>
                @if ($candidat->mention)
                    <p class="text-[11px] font-semibold text-gris-700">Mention {{ $candidat->mention }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Le visa, et la vérification --}}
    <div class="mt-auto pt-8">
        @if ($document)
            <div class="flex items-end justify-between gap-8 text-[10px]">
                <div class="max-w-xs leading-relaxed text-gris-600">
                    <p class="font-semibold text-gris-800">Document scellé</p>
                    <p>
                        {{ $document->signataire_nom }} — {{ $document->signataire_qualite }},
                        le {{ optional($document->signe_le)->format('d/m/Y à H:i') }}.
                    </p>
                    @if ($document->jeton_verification)
                        <p class="mt-1">
                            Vérification : <span class="font-mono">{{ $document->jeton_verification }}</span>
                        </p>
                    @endif
                </div>

                <div class="text-center text-[10px]">
                    <p class="text-gris-600">{{ $document->signataire_qualite }}</p>
                    <p class="mt-10 border-t border-gris-400 pt-0.5 text-gris-400">Signature et cachet</p>
                </div>
            </div>
        @else
            <div class="text-right text-[10px]">
                <p class="text-gris-600">Le Président du jury</p>
                <p class="ml-auto mt-10 w-48 border-t border-gris-400 pt-0.5 text-center text-gris-400">
                    Signature et cachet
                </p>
            </div>
        @endif

        <p class="mt-4 border-t border-gris-200 pt-2 text-[8px] leading-snug text-gris-500">
            Ce relevé est délivré à l’intéressé pour servir et valoir ce que de droit. Il reprend les
            notes portées au procès-verbal de délibération, qui seul fait foi en cas de contestation.
            Édité le {{ now()->locale('fr')->isoFormat('D MMMM YYYY à HH:mm') }}.
        </p>
    </div>
</div>
</div>

@endsection
