@extends('layouts.app')

@section('titre', 'Reçu de paiement')
@section('sous-titre', ($payment->receipt_number ?: $payment->transaction_id).' · '
    .trim(($payment->student->first_name ?? '').' '.($payment->student->last_name ?? '')))

@section('actions-entete')
    <button type="button"
            class="bouton-primaire"
            data-export-pdf="recu-paiement"
            data-format="a5"
            data-orientation="paysage"
            data-page-unique
            data-nom-fichier="Recu_{{ $payment->receipt_number ?: $payment->transaction_id }}.pdf">
        Télécharger le reçu
    </button>

    @if (auth()->user()?->role === 'parent')
        <a href="{{ route('parent-portal.payment-history') }}" class="bouton-secondaire">Mes paiements</a>
    @else
        <a href="{{ route('payments.show', $payment) }}" class="bouton-secondaire">Le versement</a>
    @endif
@endsection

@section('contenu')

@php
    $franc = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '—';

    $regle = $payment->status === 'completed';
    $enAttente = in_array($payment->status, ['pending', 'processing'], true);

    $statuts = [
        'completed' => 'Réglé',
        'pending' => 'En attente de vérification',
        'processing' => 'En cours de vérification',
        'failed' => 'Échoué',
        'cancelled' => 'Annulé',
        'refunded' => 'Remboursé',
        'partially_refunded' => 'Partiellement remboursé',
    ];

    $moyens = [
        'cash' => 'espèces', 'bank_transfer' => 'virement bancaire', 'check' => 'chèque',
        'card' => 'carte bancaire', 'airtel_money' => 'Airtel Money', 'moov_money' => 'Moov Money',
    ];

    $types = [
        'enrollment' => 'Frais d’inscription', 're_enrollment' => 'Frais de réinscription',
        'tuition' => 'Frais de scolarité', 'transport' => 'Transport',
        'canteen' => 'Cantine', 'uniform' => 'Tenue scolaire', 'other' => 'Autre versement',
    ];

    $inscription = $payment->enrollment;
    $du = (float) ($inscription->total_fees ?? 0);
    $verse = (float) ($inscription->amount_paid ?? 0);
    $reste = max(0, $du - $verse);
@endphp

{{-- Ce que le document ne dit pas de lui-même, dit à l'écran --}}
@if ($enAttente)
    <div class="carte mb-6 border-soleil-300 bg-soleil-50 p-5 sans-impression">
        <h2 class="text-sm font-semibold text-soleil-900">Versement déclaré, pas encore vérifié</h2>
        <p class="mt-1.5 text-sm leading-relaxed text-gris-700">
            Ce reçu existe déjà, mais il ne vaut pas quittance : le secrétariat doit d’abord retrouver
            l’opération {{ $payment->gateway_transaction_id ? 'n° '.$payment->gateway_transaction_id : '' }}
            chez {{ $moyens[$payment->payment_method] ?? 'l’opérateur' }}. Le document portera « réglé »
            dès cette vérification faite.
        </p>

        @if (auth()->user() && auth()->user()->role !== 'parent')
            <div class="mt-4 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('payments.complete', $payment) }}">
                    @csrf
                    <button type="submit" class="bouton-primaire">Valider ce versement</button>
                </form>

                <x-rejet-paiement :payment="$payment">Refuser…</x-rejet-paiement>
            </div>
        @endif
    </div>
@endif

@php($rejet = $payment->metadata['rejet'] ?? null)

@if ($payment->status === 'cancelled' && $rejet)
    <div class="carte mb-6 border-corail-300 bg-corail-50 p-5 sans-impression">
        <h2 class="text-sm font-semibold text-corail-900">Versement refusé</h2>
        <p class="mt-1.5 text-sm font-medium text-gris-800">{{ $rejet['libelle'] }}</p>

        @if (! empty($rejet['precision']))
            <p class="mt-1 text-sm leading-relaxed text-gris-700">{{ $rejet['precision'] }}</p>
        @endif

        <p class="mt-2 text-sm leading-relaxed text-gris-600">
            {{ \App\Support\MotifsDeRejet::consigne($rejet['motif'] ?? null) }}
        </p>

        <p class="mt-2 text-[11px] text-gris-500">
            Refusé le {{ \Carbon\Carbon::parse($rejet['le'])->format('d/m/Y à H:i') }}
            @if (! empty($rejet['par'])) par {{ $rejet['par'] }} @endif.
            Aucun montant n’a été porté au dossier de l’élève.
        </p>
    </div>
@endif

{{-- ----------------------------------------------------------------------
     Le document, taillé pour une demi-feuille en paysage : 210 × 148 mm.
     C'est ce bloc que html2canvas photographie — le PDF est exactement ce qui
     s'affiche ici.
     ---------------------------------------------------------------------- --}}
<div class="overflow-x-auto">
<div id="recu-paiement"
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
                <p class="text-[13px] font-semibold">
                    {{ $inscription->academicYear->name ?? ($schoolSettings->academic_year ?? '—') }}
                </p>
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

    {{-- Titre et référence --}}
    <div class="mt-2.5 flex items-baseline justify-between gap-4">
        <h1 class="text-base font-bold uppercase tracking-wide">Reçu de paiement</h1>
        <p class="text-[10px] text-gris-600">
            @if ($payment->receipt_number)
                N° <span class="font-mono font-semibold text-gris-900">{{ $payment->receipt_number }}</span>
            @else
                Réf. <span class="font-mono font-semibold text-gris-900">{{ $payment->transaction_id }}</span>
            @endif
            &middot; {{ $regle ? 'encaissé le' : 'déclaré le' }}
            <span class="font-semibold tabular-nums text-gris-900">
                {{ $date($payment->paid_at ?? $payment->created_at) }}
            </span>
        </p>
    </div>

    {{-- Le corps : le versement à gauche, les personnes à droite --}}
    <div class="mt-2.5 grid grid-cols-[1.15fr_1fr] gap-6">

        <div>
            <div class="border-y-2 border-gris-800 py-2.5">
                <p class="text-[8px] uppercase tracking-wide text-gris-500">
                    {{ $regle ? 'Montant reçu' : 'Montant déclaré' }}
                </p>
                <div class="flex items-baseline justify-between gap-3">
                    <p class="text-2xl font-bold tabular-nums leading-none">{{ $franc($payment->amount) }}</p>
                    <span class="shrink-0 rounded-full border px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide
                                 {{ $regle ? 'border-emerald-600 text-emerald-700'
                                    : ($enAttente ? 'border-soleil-600 text-soleil-700' : 'border-corail-600 text-corail-700') }}">
                        {{ $statuts[$payment->status] ?? $payment->status }}
                    </span>
                </div>
                <p class="mt-1 text-[9px] italic leading-snug text-gris-600">
                    {{ \App\Support\SommeEnLettres::francs((float) $payment->amount) }}
                </p>
                <p class="mt-0.5 text-[9px] text-gris-500">
                    {{ $types[$payment->payment_type] ?? 'Versement' }}
                    &middot; réglé par {{ $moyens[$payment->payment_method] ?? ($payment->payment_method ?: 'moyen non précisé') }}
                    @if ($payment->gateway_transaction_id)
                        &middot; transaction <span class="font-mono">{{ $payment->gateway_transaction_id }}</span>
                    @endif
                </p>
            </div>

            @if ($inscription)
                <table class="mt-2.5 w-full border-collapse text-[9px]">
                    <tbody>
                        <tr>
                            <td class="border border-gris-400 px-2 py-1">Frais de scolarité de l’année</td>
                            <td class="w-28 border border-gris-400 px-2 py-1 text-right tabular-nums">{{ $franc($du) }}</td>
                        </tr>
                        <tr>
                            <td class="border border-gris-400 px-2 py-1 font-semibold">Versé à ce jour</td>
                            <td class="border border-gris-400 px-2 py-1 text-right font-semibold tabular-nums">{{ $franc($verse) }}</td>
                        </tr>
                        <tr>
                            <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-bold uppercase tracking-wide">Reste à percevoir</td>
                            <td class="border border-gris-400 bg-gris-100 px-2 py-1 text-right text-[11px] font-bold tabular-nums">{{ $franc($reste) }}</td>
                        </tr>
                    </tbody>
                </table>

                @if ($enAttente)
                    <p class="mt-1 text-[8px] leading-snug text-gris-600">
                        Le solde ci-dessus ne tient pas encore compte de ce versement : il y sera porté
                        à sa vérification.
                    </p>
                @endif
            @else
                <p class="mt-2.5 text-[9px] italic text-gris-500">
                    Versement non rattaché à une inscription.
                </p>
            @endif
        </div>

        <div>
            <h2 class="mb-1 border-b border-gris-300 pb-0.5 text-[8px] font-bold uppercase tracking-wide text-gris-600">
                Élève concerné
            </h2>

            @if ($payment->student)
                <dl class="space-y-0.5 text-[9px]">
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                        <dt class="text-gris-500">Nom et prénoms</dt>
                        <dd class="text-right font-semibold">
                            {{ $payment->student->last_name }} {{ $payment->student->first_name }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                        <dt class="text-gris-500">Matricule</dt>
                        <dd class="font-mono font-medium">{{ $payment->student->student_id ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                        <dt class="text-gris-500">Classe</dt>
                        <dd class="font-semibold">{{ $inscription->schoolClass->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                        <dt class="text-gris-500">Niveau</dt>
                        <dd class="font-medium">{{ $inscription->schoolClass->level->name ?? '—' }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-[9px] italic text-gris-400">Aucun élève rattaché à ce versement.</p>
            @endif

            <h2 class="mb-1 mt-2.5 border-b border-gris-300 pb-0.5 text-[8px] font-bold uppercase tracking-wide text-gris-600">
                Payeur
            </h2>

            <dl class="space-y-0.5 text-[9px]">
                <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                    <dt class="text-gris-500">Nom et prénoms</dt>
                    <dd class="text-right font-semibold">
                        {{ $payment->payer_name
                            ?: trim(($payment->parent->first_name ?? '').' '.($payment->parent->last_name ?? '')) ?: '—' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                    <dt class="text-gris-500">Téléphone</dt>
                    <dd class="font-medium tabular-nums">
                        {{ $payment->payer_phone ?: ($payment->parent->phone ?? '—') }}
                    </dd>
                </div>
                <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-0.5">
                    <dt class="text-gris-500">Canal</dt>
                    <dd class="font-medium">
                        {{ ($payment->metadata['canal'] ?? null) === 'portail_parent' ? 'Portail parent' : 'Guichet' }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Signatures --}}
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
        @if ($regle)
            Ce reçu atteste du versement porté ci-dessus. Il est à conserver : sa présentation peut être
            exigée à tout moment de la scolarité.
        @else
            Ce document constate une déclaration de versement et
            <strong class="text-gris-700">ne vaut pas quittance</strong> tant que l’établissement n’a pas
            vérifié l’opération auprès de l’opérateur.
        @endif
        Édité le {{ now()->locale('fr')->isoFormat('D MMMM YYYY à HH:mm') }}.
    </p>
</div>
</div>

@endsection
