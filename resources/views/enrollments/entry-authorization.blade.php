@extends('layouts.app')

@section('titre', 'Autorisation d’entrée')
@section('sous-titre', $enrollment->enrollment_code.' · '.trim(($eleve['prenom'] ?? '').' '.($eleve['nom'] ?? '')))

@section('actions-entete')
    <button type="button"
            class="bouton-primaire"
            data-export-pdf="autorisation-entree"
            data-format="a5"
            data-orientation="paysage"
            data-page-unique
            data-nom-fichier="Autorisation_{{ $enrollment->enrollment_code }}.pdf">
        Télécharger l’autorisation
    </button>
    <a href="{{ route('enrollments.receipt', $enrollment->id) }}" class="bouton-secondaire">Le reçu</a>
@endsection

@section('contenu')

@php
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '—';

    $sexe = match (mb_strtolower((string) ($eleve['sexe'] ?? ''))) {
        'm', 'male', 'masculin' => 'Masculin',
        'f', 'female', 'feminin', 'féminin' => 'Féminin',
        default => '—',
    };

    $annee = $enrollment->academicYear;

    /*
     * Le code de l'inscription en QR : c'est lui que le surveillant scanne à
     * la grille. Le code reste lisible en clair juste dessous — le QR n'est
     * qu'un raccourci, jamais la seule source.
     *
     * L'API d'Endroid a changé en version 6 : `Builder::create()` n'existe
     * plus. On passe par le writer, stable d'une version à l'autre.
     */
    $qr = null;

    if (class_exists(\Endroid\QrCode\QrCode::class)) {
        try {
            $qr = (new \Endroid\QrCode\Writer\PngWriter())
                ->write(new \Endroid\QrCode\QrCode(
                    data: $enrollment->enrollment_code,
                    size: 220,
                    margin: 0,
                ))
                ->getDataUri();
        } catch (\Throwable $e) {
            // Un document sans QR reste valable : le code écrit fait foi.
            $qr = null;
        }
    }
@endphp

    {{-- ------------------------------------------------------------------
         Le document, taillé pour une demi-feuille en paysage : 210 × 148 mm.
         ------------------------------------------------------------------ --}}
    <div id="autorisation-entree"
         class="mx-auto bg-white px-7 py-5 text-gris-900 ring-1 ring-gris-200"
         style="width: 794px; min-height: 559px;">

        {{-- En-tête officiel --}}
        <div class="flex items-start justify-between gap-4 border-b-2 border-gris-800 pb-2">
            <div class="flex items-start gap-2.5">
                @if ($schoolSettings->logo_url ?? null)
                    <img src="{{ $schoolSettings->logo_url }}" alt="" class="h-11 w-11 shrink-0 object-contain">
                @else
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[6px] leading-tight text-gris-500">
                        Logo
                    </span>
                @endif
                <div class="leading-tight">
                    <p class="text-[8px] text-gris-600">Ministère de l’Éducation Nationale</p>
                    <p class="text-[13px] font-bold uppercase leading-tight">{{ $schoolName }}</p>
                    <p class="text-[8px] text-gris-500">
                        @if ($schoolSettings->school_bp ?? null) {{ $schoolSettings->school_bp }} @endif
                        @if ($schoolSettings->school_phone ?? null) &middot; Tél : {{ $schoolSettings->school_phone }} @endif
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-2.5">
                <div class="text-right leading-tight">
                    <p class="text-[8px] uppercase tracking-wide text-gris-500">Année scolaire</p>
                    <p class="text-[13px] font-semibold">{{ $annee->name ?? '—' }}</p>
                </div>
                @if ($schoolSettings->seal_url ?? null)
                    <img src="{{ $schoolSettings->seal_url }}" alt="" class="h-11 w-14 shrink-0 object-contain">
                @else
                    <span class="flex h-11 w-14 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[6px] leading-tight text-gris-500">
                        Sceau
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-2 text-center">
            <h1 class="text-base font-bold uppercase tracking-wide">Autorisation d’entrée</h1>
            <p class="text-[9px] text-gris-600">
                Titre d’accès à l’établissement pour l’année scolaire {{ $annee->name ?? '—' }}
            </p>
        </div>

        {{-- Le porteur : photo, identité, code de contrôle --}}
        <div class="mt-2.5 flex items-start gap-5 border-y-2 border-gris-800 py-3">

            <div class="shrink-0">
                @if ($eleve['photo'])
                    <img src="{{ asset('storage/'.$eleve['photo']) }}" alt=""
                         class="border border-gris-400 object-cover" style="width: 78px; height: 100px;">
                @else
                    <span class="flex items-center justify-center border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-400"
                          style="width: 78px; height: 100px;">
                        Photographie
                    </span>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-[8px] uppercase tracking-wide text-gris-500">Titulaire</p>
                <p class="text-lg font-bold uppercase leading-tight">
                    {{ $eleve['nom'] }} <span class="font-semibold normal-case">{{ $eleve['prenom'] }}</span>
                </p>

                <dl class="mt-2 grid grid-cols-2 gap-x-5 gap-y-0.5 text-[9px]">
                    @if ($eleve['matricule'])
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                            <dt class="text-gris-500">Matricule</dt>
                            <dd class="font-mono font-semibold">{{ $eleve['matricule'] }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                        <dt class="text-gris-500">Classe</dt>
                        <dd class="font-semibold">{{ $enrollment->schoolClass->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                        <dt class="text-gris-500">Né(e) le</dt>
                        <dd class="font-medium tabular-nums">{{ $date($eleve['naissance']) }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                        <dt class="text-gris-500">Sexe</dt>
                        <dd class="font-medium">{{ $sexe }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                        <dt class="text-gris-500">Niveau</dt>
                        <dd class="font-medium">{{ $enrollment->schoolClass->level->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                        <dt class="text-gris-500">Valable jusqu’au</dt>
                        <dd class="font-medium tabular-nums">{{ $date($annee->end_date ?? null) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="shrink-0 text-center">
                @if ($qr)
                    <img src="{{ $qr }}" alt="Code de vérification"
                         class="border border-gris-300 p-0.5" style="width: 88px; height: 88px;">
                @else
                    <span class="flex items-center justify-center border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-400"
                          style="width: 88px; height: 88px;">
                        Code de<br>vérification
                    </span>
                @endif
                <p class="mt-1 text-[7px] uppercase tracking-wide text-gris-500">Code d’inscription</p>
                <p class="font-mono text-[10px] font-bold tracking-wider">{{ $enrollment->enrollment_code }}</p>
            </div>
        </div>

        {{-- Contact, validité, conditions --}}
        <div class="mt-2.5 grid grid-cols-[1fr_1.35fr] gap-5">
            <div>
                <h2 class="mb-1 border-b border-gris-300 pb-0.5 text-[8px] font-bold uppercase tracking-wide text-gris-600">
                    Personne à prévenir
                </h2>

                @if ($responsable)
                    <dl class="space-y-0.5 text-[9px]">
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                            <dt class="text-gris-500">Nom</dt>
                            <dd class="text-right font-semibold">{{ $responsable['nom'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                            <dt class="text-gris-500">Lien</dt>
                            <dd class="font-medium">{{ $responsable['lien'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                            <dt class="text-gris-500">Téléphone</dt>
                            <dd class="font-semibold tabular-nums">{{ $responsable['telephone'] ?: '—' }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-[9px] italic text-gris-400">Aucun parent ni tuteur rattaché.</p>
                @endif
            </div>

            <div class="border border-gris-300 bg-gris-50 px-3 py-2 text-[7.5px] leading-snug text-gris-700">
                <p class="mb-0.5 font-bold uppercase tracking-wide text-gris-600">Conditions</p>
                <p>
                    Cette autorisation est strictement personnelle et doit être présentée à toute réquisition
                    du personnel de l’établissement. Elle vaut pour la seule année scolaire mentionnée, du
                    {{ $date($annee->start_date ?? null) }} au {{ $date($annee->end_date ?? null) }}.
                    Toute perte est à signaler sans délai au secrétariat, qui en délivrera un duplicata.
                    L’usage par un tiers entraîne son retrait immédiat.
                </p>
            </div>
        </div>

        {{-- Signature --}}
        <div class="mt-3 flex items-end justify-between text-[8px]">
            <p class="text-gris-500">
                Délivrée à {{ $schoolSettings->city ?? 'Libreville' }},
                le {{ now()->locale('fr')->isoFormat('D MMMM YYYY') }}
            </p>

            <div class="text-center">
                <p class="text-gris-600">{{ $schoolSettings->principal_title ?? 'Le Chef d’établissement' }}</p>
                <p class="mt-7 border-t border-gris-400 px-8 pt-0.5 text-gris-400">Signature et cachet</p>
            </div>
        </div>
    </div>

@endsection
