@extends('layouts.app')

@section('titre', 'Reçu d’inscription')
@section('sous-titre', $enrollment->receipt_number.' · '.trim(($eleve['prenom'] ?? '').' '.($eleve['nom'] ?? '')))

@section('actions-entete')
    <button type="button"
            class="bouton-primaire"
            data-export-pdf="recu-inscription"
            data-format="a5"
            data-orientation="paysage"
            data-page-unique
            data-nom-fichier="Recu_{{ $enrollment->receipt_number }}.pdf">
        Télécharger le reçu
    </button>
    <a href="{{ route('enrollments.show', $enrollment->id) }}" class="bouton-secondaire">L’inscription</a>
@endsection

@section('contenu')

@php
    $montant = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '—';

    $du = (float) $enrollment->total_fees;
    $paye = (float) $enrollment->amount_paid;
    $reste = max(0, $du - $paye);
    $solde = $reste <= 0;

    $moyens = [
        'cash' => 'Espèces', 'bank_transfer' => 'Virement bancaire', 'check' => 'Chèque',
        'mobile_money' => 'Mobile money', 'card' => 'Carte bancaire',
    ];

    $statuts = [
        'paid' => 'Soldé', 'partial' => 'Partiel',
        'pending' => 'En attente', 'unpaid' => 'Impayé',
    ];

    $statut = $statuts[$enrollment->payment_status] ?? ucfirst((string) $enrollment->payment_status);
@endphp

    {{-- ------------------------------------------------------------------
         Le document, taillé pour une demi-feuille en paysage : 210 × 148 mm.
         C'est ce bloc que html2canvas photographie — le PDF est exactement ce
         qui s'affiche ici, d'où une hauteur tenue au plus juste.
         ------------------------------------------------------------------ --}}
    <div id="recu-inscription"
         class="mx-auto flex flex-col bg-white px-7 py-5 text-gris-900 ring-1 ring-gris-200"
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
                    <p class="text-[13px] font-semibold">{{ $enrollment->academicYear->name ?? '—' }}</p>
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

        {{-- Titre et référence, sur une seule ligne --}}
        <div class="mt-2.5 flex items-baseline justify-between gap-4">
            <h1 class="text-base font-bold uppercase tracking-wide">Reçu d’inscription</h1>
            <p class="text-[10px] text-gris-600">
                N° <span class="font-mono font-semibold text-gris-900">{{ $enrollment->receipt_number }}</span>
                &middot; délivré le <span class="font-semibold tabular-nums text-gris-900">{{ $date($enrollment->enrollment_date) }}</span>
            </p>
        </div>

        {{-- Le corps : le versement à gauche, les personnes à droite --}}
        <div class="mt-2.5 grid grid-cols-[1.15fr_1fr] gap-6">

            {{-- ---------------------------------------------------- --}}
            <div>
                <div class="border-y-2 border-gris-800 py-2.5">
                    <p class="text-[8px] uppercase tracking-wide text-gris-500">Montant reçu</p>
                    <div class="flex items-baseline justify-between gap-3">
                        <p class="text-2xl font-bold tabular-nums leading-none">{{ $montant($paye) }}</p>
                        <span class="shrink-0 rounded-full border px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide
                                     {{ $solde ? 'border-emerald-600 text-emerald-700' : 'border-soleil-600 text-soleil-700' }}">
                            {{ $statut }}
                        </span>
                    </div>
                    <p class="mt-1 text-[9px] italic leading-snug text-gris-600">
                        {{ \App\Support\SommeEnLettres::francs($paye) }}
                    </p>
                    <p class="mt-0.5 text-[9px] text-gris-500">
                        Réglé en {{ mb_strtolower($moyens[$enrollment->payment_method] ?? ($enrollment->payment_method ?: 'moyen non précisé')) }}
                        @if ($enrollment->payment_reference)
                            &middot; réf. <span class="font-mono">{{ $enrollment->payment_reference }}</span>
                        @endif
                    </p>
                </div>

                <table class="mt-2.5 w-full border-collapse text-[9px]">
                    <tbody>
                        <tr>
                            <td class="border border-gris-400 px-2 py-1">Frais de scolarité de l’année</td>
                            <td class="w-28 border border-gris-400 px-2 py-1 text-right tabular-nums">{{ $montant($du) }}</td>
                        </tr>
                        <tr>
                            <td class="border border-gris-400 px-2 py-1 font-semibold">Versé à ce jour</td>
                            <td class="border border-gris-400 px-2 py-1 text-right font-semibold tabular-nums">{{ $montant($paye) }}</td>
                        </tr>
                        <tr>
                            <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-bold uppercase tracking-wide">Reste à percevoir</td>
                            <td class="border border-gris-400 bg-gris-100 px-2 py-1 text-right text-[11px] font-bold tabular-nums">{{ $montant($reste) }}</td>
                        </tr>
                    </tbody>
                </table>

                @unless ($solde)
                    <p class="mt-1 text-[8px] text-gris-600">
                        Le solde de <span class="font-semibold">{{ $montant($reste) }}</span> reste dû
                        @if ($enrollment->payment_due_date)
                            au plus tard le <span class="font-semibold tabular-nums">{{ $date($enrollment->payment_due_date) }}</span>.
                        @else
                            auprès de la comptabilité.
                        @endif
                    </p>
                @endunless
            </div>

            {{-- ---------------------------------------------------- --}}
            <div>
                <h2 class="mb-1 border-b border-gris-300 pb-0.5 text-[8px] font-bold uppercase tracking-wide text-gris-600">
                    Élève inscrit
                </h2>

                <dl class="space-y-0.5 text-[9px]">
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                        <dt class="text-gris-500">Nom et prénoms</dt>
                        <dd class="text-right font-semibold">{{ $eleve['nom'] }} {{ $eleve['prenom'] }}</dd>
                    </div>
                    @if ($eleve['matricule'])
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                            <dt class="text-gris-500">Matricule</dt>
                            <dd class="font-mono font-medium">{{ $eleve['matricule'] }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                        <dt class="text-gris-500">Né(e) le</dt>
                        <dd class="font-medium tabular-nums">{{ $date($eleve['naissance']) }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                        <dt class="text-gris-500">Classe</dt>
                        <dd class="font-semibold">{{ $enrollment->schoolClass->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                        <dt class="text-gris-500">Niveau</dt>
                        <dd class="font-medium">{{ $enrollment->schoolClass->level->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                        <dt class="text-gris-500">Type</dt>
                        <dd class="font-medium">{{ $enrollment->is_reinscription ? 'Réinscription' : 'Nouvelle inscription' }}</dd>
                    </div>
                </dl>

                <h2 class="mb-1 mt-2.5 border-b border-gris-300 pb-0.5 text-[8px] font-bold uppercase tracking-wide text-gris-600">
                    Parent ou tuteur
                </h2>

                @if ($responsable)
                    <dl class="space-y-0.5 text-[9px]">
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                            <dt class="text-gris-500">Nom et prénoms</dt>
                            <dd class="text-right font-semibold">{{ $responsable['nom'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                            <dt class="text-gris-500">Lien de parenté</dt>
                            <dd class="font-medium">{{ $responsable['lien'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                            <dt class="text-gris-500">Téléphone</dt>
                            <dd class="font-medium tabular-nums">{{ $responsable['telephone'] ?: '—' }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-[9px] italic text-gris-400">Aucun parent ni tuteur rattaché à ce dossier.</p>
                @endif
            </div>
        </div>

        {{-- Signatures et mention légale --}}
        <div class="mt-auto grid grid-cols-2 gap-10 pt-6 text-[8px]">
            <div class="text-center">
                <p class="text-gris-600">Le parent ou tuteur</p>
                <p class="mt-7 border-t border-gris-400 pt-0.5 text-gris-400">Signature</p>
            </div>
            <div class="text-center">
                <p class="text-gris-600">{{ $schoolSettings->principal_title ?? 'Le Chef d’établissement' }}</p>
                <p class="mt-7 border-t border-gris-400 pt-0.5 text-gris-400">Signature et cachet</p>
            </div>
        </div>

        <p class="mt-2.5 border-t border-gris-200 pt-1.5 text-[7px] leading-snug text-gris-500">
            Ce reçu atteste du versement porté ci-dessus pour l’année scolaire
            {{ $enrollment->academicYear->name ?? '—' }}. Il est à conserver : sa présentation peut être exigée
            à tout moment de la scolarité. Édité le {{ now()->locale('fr')->isoFormat('D MMMM YYYY à HH:mm') }}.
        </p>
    </div>

@endsection
