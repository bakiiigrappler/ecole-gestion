@extends('layouts.app')

@section('titre', 'Attestation de réussite')
@section('sous-titre', $examen->intitule_complet.' · '.$candidat->nom_complet)

@section('actions-entete')
    <button type="button"
            class="bouton-primaire"
            data-export-pdf="attestation"
            data-page-unique
            data-nom-fichier="Attestation_{{ $candidat->numero_candidat }}.pdf">
        Télécharger en PDF
    </button>
    <a href="{{ route('examens.releve', $candidat) }}" class="bouton-secondaire">Le relevé de notes</a>
@endsection

@section('contenu')

@php
    /*
     * Le genre tel qu'il est reellement stocke : « male » / « female ». Tester
     * « F » ne rendait jamais vrai, et toute bacheliere etait declaree « admis ».
     */
    $feminin = in_array(mb_strtolower((string) $candidat->sexe), ['female', 'f', 'feminin', 'féminin'], true);

    /*
     * Les centres de cette demonstration s'appellent « Centre de X ». Ecrit
     * apres « au centre de composition de », cela donnait « de Centre de X ».
     * On retire le prefixe quand il est la, et l'on garde le nom tel quel
     * sinon : un centre nomme autrement doit s'ecrire comme il se nomme.
     */
    $centre = $candidat->centre?->nom;
    $centre = $centre ? preg_replace('/^Centre\s+(de|du|des|d’|d\')\s*/iu', '', $centre) : null;

    // Le centre et sa ville d'un seul tenant : deux @if imbriques laissaient
    // une espace avant la virgule, que rien dans la vue ne rattrapait.
    $lieu = $centre
        ? $centre.($candidat->centre->ville ? ', '.$candidat->centre->ville : '')
        : null;
@endphp

{{-- Ce que le document ne dit pas de lui-même, dit à l'écran. Hors du
     document, donc hors du PDF : une attestation ne se commente pas. --}}
<div class="carte mb-6 border-emerald-300 p-5 sans-impression">
    <h2 class="text-sm font-semibold text-emerald-900">Document scellé</h2>
    <p class="mt-1.5 text-sm leading-relaxed text-gris-700">
        Cette attestation n’existe que parce que le procès-verbal de la session est signé et que
        son empreinte correspond toujours aux résultats. Signé par
        {{ $document->signataire_nom }}, {{ $document->signataire_qualite }},
        le {{ optional($document->signe_le)->format('d/m/Y à H:i') }}.
    </p>
    @if ($document->jeton_verification)
        <p class="mt-2 text-[11px] text-gris-500">
            Jeton de vérification : <span class="font-mono">{{ $document->jeton_verification }}</span> —
            il permet à qui reçoit ce document d’en vérifier l’origine.
        </p>
    @endif
</div>

{{-- ----------------------------------------------------------------------
     Le document, taillé pour une feuille A4 portrait : 794 × 1123 px à 96 ppp.
     Le PDF est exactement ce qui s'affiche ici.
     ---------------------------------------------------------------------- --}}
<div class="overflow-x-auto">
<div id="attestation"
     class="mx-auto flex flex-col bg-white px-12 py-10 text-gris-900 ring-1 ring-gris-200"
     style="width: 794px; min-height: 1123px;">

    {{-- En-tête officiel --}}
    <div class="border-b-2 border-gris-800 pb-3 text-center">
        <p class="text-[12px] font-bold uppercase tracking-wide">République Gabonaise</p>
        <p class="text-[9px] italic text-gris-600">Union — Travail — Justice</p>
        <p class="mt-1 text-[9px] uppercase leading-snug text-gris-600">
            Ministère de l’Éducation Nationale<br>
            Direction générale des examens et concours
        </p>
    </div>

    <h1 class="mt-10 text-center text-xl font-bold uppercase tracking-[0.18em]">
        Attestation de réussite
    </h1>
    <p class="mt-1 text-center text-sm text-gris-600">
        {{ $examen->intitule_complet }}
    </p>

    {{-- Le corps de l'acte. C'est le texte qui engage, et il se lit d'un trait. --}}
    <div class="mt-10 space-y-5 text-[13px] leading-loose">
        <p>
            Le Directeur général des examens et concours atteste que
        </p>

        <p class="text-center text-lg font-bold uppercase tracking-wide">
            {{ $candidat->nom_complet }}
        </p>

        <p>
            {{ $feminin ? 'née' : 'né' }} le
            <strong>{{ optional($candidat->date_naissance)->format('d/m/Y') ?: '—' }}</strong>@if ($candidat->lieu_naissance) à <strong>{{ $candidat->lieu_naissance }}</strong>@endif,
            {{ $feminin ? 'inscrite' : 'inscrit' }} sous le numéro de candidat
            <strong class="font-mono">{{ $candidat->numero_candidat }}</strong>,
            @if ($candidat->etablissement_origine)
                en provenance de <strong>{{ $candidat->etablissement_origine }}</strong>,
            @endif
            a subi avec succès les épreuves du
            <strong>{{ $examen->intitule_complet }}</strong>@if ($lieu), au centre de composition de
                <strong>{{ $lieu }}</strong>@endif,
            et est en conséquence {{ $feminin ? 'déclarée' : 'déclaré' }}
        </p>

        <p class="text-center">
            <span class="inline-block border-2 border-gris-800 px-8 py-2 text-lg font-bold uppercase tracking-wide">
                {{ $feminin ? 'Admise' : 'Admis' }}
            </span>
        </p>

        @if ($candidat->mention || $candidat->moyenne !== null)
            <p class="text-center text-[13px]">
                @if ($candidat->moyenne !== null)
                    avec une moyenne générale de
                    <strong class="tabular-nums">{{ number_format($candidat->moyenne, 2, ',', ' ') }}/20</strong>
                @endif
                @if ($candidat->mention)
                    @if ($candidat->moyenne !== null) et @endif
                    la mention <strong>{{ $candidat->mention }}</strong>@endif.
            </p>
        @endif

        <p>
            La présente attestation est délivrée à {{ $feminin ? 'l’intéressée' : 'l’intéressé' }}
            pour servir et valoir ce que de droit, en attendant la délivrance du diplôme définitif.
        </p>
    </div>

    {{-- Le visa, au bas de la feuille --}}
    <div class="mt-auto pt-10">
        <div class="flex items-end justify-between gap-8 text-[10px]">
            <div class="max-w-[17rem] leading-relaxed text-gris-600">
                <p class="font-semibold text-gris-800">{{ $document->libelle_mode }}</p>
                <p>
                    Procès-verbal signé le
                    {{ optional($document->signe_le)->format('d/m/Y à H:i') }}
                    par {{ $document->signataire_nom }}.
                </p>
                @if ($document->jeton_verification)
                    <p class="mt-1">
                        Vérification :
                        <span class="font-mono font-semibold">{{ $document->jeton_verification }}</span>
                    </p>
                @endif
            </div>

            <div class="text-center text-[10px]">
                <p class="text-gris-600">{{ $document->signataire_qualite }}</p>
                <p class="mt-14 w-52 border-t border-gris-400 pt-0.5 text-gris-400">Signature et cachet</p>
            </div>
        </div>

        <p class="mt-5 border-t border-gris-200 pt-2 text-[8px] leading-snug text-gris-500">
            Éditée le {{ now()->locale('fr')->isoFormat('D MMMM YYYY à HH:mm') }}. Ce document reprend la
            décision portée au procès-verbal de délibération, qui seul fait foi en cas de contestation.
            Toute altération le rend nul.
        </p>
    </div>
</div>
</div>

@endsection
