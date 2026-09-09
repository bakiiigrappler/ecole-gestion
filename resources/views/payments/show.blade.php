@extends('layouts.app')

@section('titre', 'Paiement '.($payment->transaction_id ?? '#'.$payment->id))
@section('sous-titre', number_format($payment->amount, 0, ',', ' ').' FCFA — '.optional($payment->paid_at ?? $payment->created_at)->format('d/m/Y à H:i'))

@section('actions-entete')
    <a href="{{ route('payments.receipt', $payment) }}" class="bouton-secondaire">Reçu</a>
    <a href="{{ route('payments.index') }}" class="bouton-primaire">Tous les paiements</a>
@endsection

@section('contenu')

@php
    $montant = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';

    $libellesStatut = [
        'pending' => 'En attente', 'processing' => 'En cours', 'completed' => 'Terminé',
        'failed' => 'Échoué', 'cancelled' => 'Annulé', 'refunded' => 'Remboursé',
        'partially_refunded' => 'Partiellement remboursé',
    ];

    // Classes écrites en entier : Tailwind ne compile pas une teinte interpolée.
    $puceStatut = [
        'completed' => 'emerald', 'processing' => 'sky', 'pending' => 'amber',
        'failed' => 'rose', 'cancelled' => 'slate', 'refunded' => 'violet',
        'partially_refunded' => 'violet',
    ];

    $libellesMethode = [
        'moov_money' => 'Moov Money', 'airtel_money' => 'Airtel Money', 'card' => 'Carte bancaire',
        'bank_transfer' => 'Virement bancaire', 'cash' => 'Espèces', 'check' => 'Chèque',
    ];

    $libellesType = [
        'enrollment' => 'Inscription', 're_enrollment' => 'Réinscription', 'tuition' => 'Frais de scolarité',
        'transport' => 'Transport', 'canteen' => 'Cantine', 'uniform' => 'Uniforme', 'other' => 'Autre',
    ];

    $inscription = $payment->enrollment;
    $eleve = $payment->student ?? optional($inscription)->student;
    $remboursements = $payment->refunds ?? collect();
    $enAttente = in_array($payment->status, ['pending', 'processing'], true);
@endphp

    <div class="grid gap-4 lg:grid-cols-3">

        {{-- ------------------------------------------------------------
             La transaction
             ------------------------------------------------------------ --}}
        <div class="lg:col-span-2">
            <div class="carte p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Montant</p>
                        <p class="mt-0.5 text-3xl font-bold text-gris-900">{{ $montant($payment->amount) }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <x-puce :couleur="$puceStatut[$payment->status] ?? 'slate'">
                                {{ $libellesStatut[$payment->status] ?? $payment->status }}
                            </x-puce>
                            <x-puce couleur="sky">{{ $libellesType[$payment->payment_type] ?? $payment->payment_type }}</x-puce>
                        </div>
                    </div>

                    <div class="text-right">
                        <p class="text-[11px] uppercase tracking-wide text-gris-400">Référence</p>
                        <p class="font-mono text-sm font-semibold text-gris-800">
                            {{ $payment->transaction_id ?? '#'.$payment->id }}
                        </p>
                    </div>
                </div>

                <dl class="mt-5 grid grid-cols-2 gap-x-8 gap-y-3 border-t border-gris-100 pt-4 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gris-400">Méthode</dt>
                        <dd class="font-medium text-gris-800">
                            {{ $libellesMethode[$payment->payment_method] ?? $payment->payment_method ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gris-400">Payé le</dt>
                        <dd class="font-medium text-gris-800">
                            {{ optional($payment->paid_at)->format('d/m/Y à H:i') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gris-400">Enregistré le</dt>
                        <dd class="font-medium text-gris-800">
                            {{ optional($payment->created_at)->format('d/m/Y à H:i') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gris-400">Payeur</dt>
                        <dd class="font-medium text-gris-800">{{ $payment->payer_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gris-400">Téléphone</dt>
                        <dd class="font-medium text-gris-800">{{ $payment->payer_phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gris-400">Courriel</dt>
                        <dd class="truncate font-medium text-gris-800">{{ $payment->payer_email ?? '—' }}</dd>
                    </div>
                    @if ($payment->paymentGateway ?? null)
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide text-gris-400">Plateforme</dt>
                            <dd class="font-medium text-gris-800">{{ $payment->paymentGateway->name }}</dd>
                        </div>
                    @endif
                    @if ($payment->gateway_transaction_id)
                        <div class="sm:col-span-2">
                            <dt class="text-[11px] uppercase tracking-wide text-gris-400">Référence plateforme</dt>
                            <dd class="truncate font-mono text-[11px] text-gris-700">{{ $payment->gateway_transaction_id }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($payment->notes)
                    <div class="mt-4 rounded-lg bg-gris-50 p-3 text-sm text-gris-600">
                        {{ $payment->notes }}
                    </div>
                @endif
            </div>

            {{-- Remboursements --}}
            @if ($remboursements->isNotEmpty())
                <div class="carte mt-4 overflow-hidden">
                    <div class="carte-entete">
                        <h2 class="text-sm font-semibold text-gris-900">Remboursements</h2>
                        <span class="text-xs text-gris-400">{{ $remboursements->count() }}</span>
                    </div>
                    <table class="tableau">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Motif</th>
                                <th class="text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($remboursements as $remboursement)
                                <tr>
                                    <td class="text-gris-600">{{ optional($remboursement->created_at)->format('d/m/Y') }}</td>
                                    <td class="text-gris-700">{{ $remboursement->reason ?? '—' }}</td>
                                    <td class="text-right font-semibold text-corail-700">
                                        {{ $montant($remboursement->amount) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ------------------------------------------------------------
             L'élève, l'inscription, les actions
             ------------------------------------------------------------ --}}
        <div class="space-y-4">
            <div class="carte p-5">
                <h2 class="mb-3 text-sm font-semibold text-gris-900">Élève</h2>

                @if ($eleve)
                    <div class="flex items-center gap-3">
                        <x-avatar :nom="$eleve->full_name ?? ($eleve->first_name.' '.$eleve->last_name)"
                                  :photo="$eleve->photo ?? null" class="h-11 w-11 shrink-0 text-xs"/>
                        <div class="min-w-0">
                            <a href="{{ route('students.show', $eleve->id) }}"
                               class="block truncate font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                {{ $eleve->first_name }} {{ $eleve->last_name }}
                            </a>
                            <div class="font-mono text-[11px] text-gris-400">{{ $eleve->student_id }}</div>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gris-400">Aucun élève rattaché à cette transaction.</p>
                @endif

                @if ($inscription)
                    <dl class="mt-4 space-y-2 border-t border-gris-100 pt-3 text-sm">
                        <div class="flex justify-between gap-2">
                            <dt class="text-gris-500">Classe</dt>
                            <dd class="font-medium text-gris-800">{{ $inscription->schoolClass->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gris-500">Année</dt>
                            <dd class="font-medium text-gris-800">{{ $inscription->academicYear->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gris-500">Frais dus</dt>
                            <dd class="font-medium text-gris-800">{{ $montant($inscription->total_fees) }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gris-500">Déjà payé</dt>
                            <dd class="font-medium text-emerald-700">{{ $montant($inscription->amount_paid) }}</dd>
                        </div>
                        @php($reste = max(0, (float) $inscription->total_fees - (float) $inscription->amount_paid))
                        <div class="flex justify-between gap-2 border-t border-gris-100 pt-2">
                            <dt class="font-medium text-gris-700">Reste dû</dt>
                            <dd class="font-semibold {{ $reste > 0 ? 'text-corail-700' : 'text-emerald-700' }}">
                                {{ $montant($reste) }}
                            </dd>
                        </div>
                    </dl>

                    <a href="{{ route('enrollments.show', $inscription) }}"
                       class="bouton-secondaire mt-4 w-full justify-center">Voir l’inscription</a>
                @endif
            </div>

            {{-- Actions : seules celles que le contrôleur accepte réellement --}}
            <div class="carte p-5">
                <h2 class="mb-3 text-sm font-semibold text-gris-900">Actions</h2>

                <div class="space-y-2">
                    <a href="{{ route('payments.receipt', $payment) }}" class="bouton-secondaire w-full justify-center">
                        Imprimer le reçu
                    </a>

                    @if ($enAttente)
                        <form method="POST" action="{{ route('payments.complete', $payment) }}">
                            @csrf
                            <button type="submit" class="bouton-primaire w-full justify-center">
                                Marquer comme terminé
                            </button>
                        </form>

                        {{-- Le refus exige un motif : c'est la seule phrase que le
                             parent lira pour comprendre ce qu'il doit faire. --}}
                        <x-rejet-paiement :payment="$payment"
                                          bouton="bouton-danger w-full justify-center">
                            Refuser le versement…
                        </x-rejet-paiement>
                    @else
                        <p class="text-[11px] leading-relaxed text-gris-400">
                            Seuls les paiements en attente peuvent être terminés ou annulés.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
