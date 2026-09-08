@extends('layouts.app')

@section('titre', 'Autorisation d’entrée')
@section('sous-titre', $enrollment->enrollment_code.' · '.trim(($eleve['prenom'] ?? '').' '.($eleve['nom'] ?? '')))

@section('actions-entete')
    <button type="button"
            class="bouton-primaire"
            data-export-pdf="autorisation-entree"
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
     * la grille. Le code est déjà lisible en clair juste dessous — le QR n'est
     * qu'un raccourci, jamais la seule source.
     */
    $qr = null;

    /*
     * L'API d'Endroid a change en version 6 : `Builder::create()` n'existe
     * plus. On passe par le writer, qui est stable d'une version a l'autre.
     */
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
         Le document. Ce bloc est celui que html2canvas photographie :
         le PDF est exactement ce qui s'affiche ici.
         ------------------------------------------------------------------ --}}
    <div id="autorisation-entree" class="mx-auto max-w-3xl bg-white p-8 text-gris-900 ring-1 ring-gris-200">

        {{-- En-tête officiel --}}
        <div class="flex items-start justify-between gap-4 border-b-2 border-gris-800 pb-3">
            <div class="flex items-start gap-3">
                @if ($schoolSettings->logo_url ?? null)
                    <img src="{{ $schoolSettings->logo_url }}" alt="Logo de l’établissement"
                         class="h-14 w-14 shrink-0 object-contain">
                @else
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                        Logo<br>établissement
                    </span>
                @endif
                <div class="leading-tight">
                    <p class="text-[10px] text-gris-600">Ministère de l’Éducation Nationale</p>
                    <p class="text-sm font-bold uppercase">{{ $schoolName }}</p>
                    <p class="text-[9px] text-gris-500">
                        @if ($schoolSettings->school_bp ?? null) {{ $schoolSettings->school_bp }} @endif
                        @if ($schoolSettings->school_phone ?? null) &middot; Tél : {{ $schoolSettings->school_phone }} @endif
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="text-right leading-tight">
                    <p class="text-[10px] uppercase tracking-wide text-gris-500">Année scolaire</p>
                    <p class="text-sm font-semibold">{{ $annee->name ?? '—' }}</p>
                </div>

                @if ($schoolSettings->seal_url ?? null)
                    <img src="{{ $schoolSettings->seal_url }}" alt="Sceau de la République"
                         class="h-14 w-16 shrink-0 object-contain">
                @else
                    <span class="flex h-14 w-16 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                        Sceau de la<br>République
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-5 text-center">
            <h1 class="text-lg font-bold uppercase tracking-wide">Autorisation d’entrée</h1>
            <p class="mt-0.5 text-[11px] text-gris-600">
                Titre d’accès à l’établissement pour l’année scolaire {{ $annee->name ?? '—' }}
            </p>
        </div>

        {{-- Le porteur, sa photo, et le code de contrôle --}}
        <div class="mt-5 flex items-start gap-6 border-y-2 border-gris-800 py-5">

            {{-- Photographie --}}
            <div class="shrink-0">
                @if ($eleve['photo'])
                    <img src="{{ asset('storage/'.$eleve['photo']) }}" alt="Photographie de l’élève"
                         class="h-32 w-26 border border-gris-400 object-cover" style="width: 6.5rem;">
                @else
                    <span class="flex h-32 items-center justify-center border border-dashed border-gris-400 text-center text-[8px] leading-tight text-gris-400"
                          style="width: 6.5rem;">
                        Photographie
                    </span>
                @endif
            </div>

            {{-- Identité --}}
            <div class="min-w-0 flex-1">
                <p class="text-[10px] uppercase tracking-wide text-gris-500">Titulaire</p>
                <p class="text-xl font-bold uppercase leading-tight">
                    {{ $eleve['nom'] }} <span class="font-semibold normal-case">{{ $eleve['prenom'] }}</span>
                </p>

                <dl class="mt-3 grid grid-cols-2 gap-x-6 gap-y-1.5 text-[11px]">
                    @if ($eleve['matricule'])
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                            <dt class="text-gris-500">Matricule</dt>
                            <dd class="font-mono font-semibold">{{ $eleve['matricule'] }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Classe</dt>
                        <dd class="font-semibold">{{ $enrollment->schoolClass->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Né(e) le</dt>
                        <dd class="font-medium tabular-nums">{{ $date($eleve['naissance']) }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Sexe</dt>
                        <dd class="font-medium">{{ $sexe }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Niveau</dt>
                        <dd class="font-medium">{{ $enrollment->schoolClass->level->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Inscrit le</dt>
                        <dd class="font-medium tabular-nums">{{ $date($enrollment->enrollment_date) }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Code de contrôle --}}
            <div class="shrink-0 text-center">
                @if ($qr)
                    <img src="{{ $qr }}" alt="Code de vérification"
                         class="h-28 w-28 border border-gris-300 p-1">
                @else
                    <span class="flex h-28 w-28 items-center justify-center border border-dashed border-gris-400 text-center text-[8px] leading-tight text-gris-400">
                        Code de<br>vérification
                    </span>
                @endif

                <p class="mt-1.5 text-[9px] uppercase tracking-wide text-gris-500">Code d’inscription</p>
                <p class="font-mono text-sm font-bold tracking-wider">{{ $enrollment->enrollment_code }}</p>
            </div>
        </div>

        {{-- Le responsable, qui vient chercher l'enfant --}}
        <div class="mt-5 grid grid-cols-2 gap-6">
            <div>
                <h2 class="mb-2 border-b border-gris-300 pb-1 text-[11px] font-bold uppercase tracking-wide text-gris-600">
                    Personne à prévenir
                </h2>

                @if ($responsable)
                    <dl class="space-y-1.5 text-[11px]">
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                            <dt class="text-gris-500">Nom et prénoms</dt>
                            <dd class="text-right font-semibold">{{ $responsable['nom'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                            <dt class="text-gris-500">Lien de parenté</dt>
                            <dd class="font-medium">{{ $responsable['lien'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                            <dt class="text-gris-500">Téléphone</dt>
                            <dd class="font-semibold tabular-nums">{{ $responsable['telephone'] ?: '—' }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-[11px] italic text-gris-400">
                        Aucun parent ni tuteur n’est rattaché à ce dossier.
                    </p>
                @endif
            </div>

            <div>
                <h2 class="mb-2 border-b border-gris-300 pb-1 text-[11px] font-bold uppercase tracking-wide text-gris-600">
                    Validité
                </h2>

                <dl class="space-y-1.5 text-[11px]">
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Du</dt>
                        <dd class="font-medium tabular-nums">{{ $date($annee->start_date ?? null) }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Au</dt>
                        <dd class="font-medium tabular-nums">{{ $date($annee->end_date ?? null) }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Statut de l’inscription</dt>
                        <dd class="font-semibold">
                            {{ $enrollment->status === 'active' ? 'Active' : ucfirst((string) $enrollment->status) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Ce que le porteur s'engage à respecter --}}
        <div class="mt-5 border border-gris-300 bg-gris-50 px-4 py-3 text-[10px] leading-relaxed text-gris-700">
            <p class="mb-1 font-bold uppercase tracking-wide text-gris-600">Conditions</p>
            <p>
                Cette autorisation est strictement personnelle et doit être présentée à toute réquisition
                du personnel de l’établissement. Elle est valable pour la seule année scolaire mentionnée.
                Toute perte ou détérioration est à signaler sans délai au secrétariat, qui en délivrera un
                duplicata. L’usage par un tiers entraîne son retrait immédiat.
            </p>
        </div>

        {{-- Signature --}}
        <div class="mt-8 flex items-end justify-between text-[10px]">
            <p class="text-gris-500">
                Délivrée à {{ $schoolSettings->city ?? 'Libreville' }},
                le {{ now()->locale('fr')->isoFormat('D MMMM YYYY') }}
            </p>

            <div class="text-center">
                <p class="text-gris-600">{{ $schoolSettings->principal_title ?? 'Le Chef d’établissement' }}</p>
                <p class="mt-12 border-t border-gris-400 px-10 pt-1 text-gris-400">Signature et cachet</p>
            </div>
        </div>
    </div>

@endsection
