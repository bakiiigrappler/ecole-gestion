@extends('layouts.app')

@section('titre', 'Modifier le paiement')
@section('sous-titre', ($payment->transaction_id ?? '#'.$payment->id).' — '.number_format($payment->amount, 0, ',', ' ').' FCFA')

@section('actions-entete')
    <a href="{{ route('payments.show', $payment) }}" class="bouton-secondaire">Annuler</a>
@endsection

@section('contenu')

@php
    $statutsModifiables = [
        'pending' => 'En attente',
        'completed' => 'Terminé',
        'failed' => 'Échoué',
        'cancelled' => 'Annulé',
        'refunded' => 'Remboursé',
    ];
@endphp

<form method="POST" action="{{ route('payments.update', $payment) }}" class="grid gap-6 lg:grid-cols-3">
    @csrf
    @method('PUT')

    <div class="space-y-4 lg:col-span-2">

        {{-- ------------------------------------------------------------
             À quelle inscription ce paiement se rapporte
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Inscription concernée</h2>
            </div>

            <div class="p-5">
                <label for="enrollment_id" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Élève et classe <span class="text-corail-600">*</span>
                </label>
                <select name="enrollment_id" id="enrollment_id" required class="champ w-full text-sm">
                    @foreach ($enrollments as $inscription)
                        <option value="{{ $inscription->id }}"
                                @selected(old('enrollment_id', $payment->enrollment_id) == $inscription->id)>
                            {{ optional($inscription->student)->first_name }} {{ optional($inscription->student)->last_name }}
                            — {{ optional($inscription->schoolClass)->name ?? 'Sans classe' }}
                            ({{ optional($inscription->academicYear)->name }})
                        </option>
                    @endforeach
                </select>
                @error('enrollment_id')
                    <p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-[11px] text-gris-400">
                    Changer d’inscription réaffecte le paiement à un autre élève.
                </p>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             La transaction
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Transaction</h2>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-3">
                <div>
                    <label for="amount" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Montant (FCFA) <span class="text-corail-600">*</span>
                    </label>
                    <input type="number" name="amount" id="amount" min="0" step="1" required
                           value="{{ old('amount', (int) $payment->amount) }}" class="champ w-full text-sm">
                    @error('amount')
                        <p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="payment_method" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Méthode <span class="text-corail-600">*</span>
                    </label>
                    <select name="payment_method" id="payment_method" required class="champ w-full text-sm">
                        @foreach ($paymentMethods as $cle => $libelle)
                            <option value="{{ $cle }}" @selected(old('payment_method', $payment->payment_method) === $cle)>
                                {{ $libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="payment_type" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Type <span class="text-corail-600">*</span>
                    </label>
                    <select name="payment_type" id="payment_type" required class="champ w-full text-sm">
                        @foreach ($paymentTypes as $cle => $libelle)
                            <option value="{{ $cle }}" @selected(old('payment_type', $payment->payment_type) === $cle)>
                                {{ $libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Statut <span class="text-corail-600">*</span>
                    </label>
                    {{-- Le contrôleur n'accepte que ces cinq valeurs. --}}
                    <select name="status" id="status" required class="champ w-full text-sm">
                        @foreach ($statutsModifiables as $cle => $libelle)
                            <option value="{{ $cle }}" @selected(old('status', $payment->status) === $cle)>
                                {{ $libelle }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="paid_at" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Date de paiement
                    </label>
                    <input type="datetime-local" name="paid_at" id="paid_at"
                           value="{{ old('paid_at', optional($payment->paid_at)->format('Y-m-d\TH:i')) }}"
                           class="champ w-full text-sm">
                    <p class="mt-1 text-[11px] text-gris-400">Laisser vide si le paiement n’est pas encore encaissé.</p>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Le payeur
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Payeur</h2>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-3">
                <div>
                    <label for="payer_name" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Nom <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="payer_name" id="payer_name" required
                           value="{{ old('payer_name', $payment->payer_name) }}" class="champ w-full text-sm">
                    @error('payer_name')
                        <p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="payer_phone" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Téléphone <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="payer_phone" id="payer_phone" required
                           value="{{ old('payer_phone', $payment->payer_phone) }}" class="champ w-full text-sm">
                    @error('payer_phone')
                        <p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="payer_email" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Courriel
                    </label>
                    <input type="email" name="payer_email" id="payer_email"
                           value="{{ old('payer_email', $payment->payer_email) }}" class="champ w-full text-sm">
                    @error('payer_email')
                        <p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-3">
                    <label for="notes" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Notes
                    </label>
                    <textarea name="notes" id="notes" rows="3" class="champ w-full text-sm">{{ old('notes', $payment->notes) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Rappel de la transaction et enregistrement
         ---------------------------------------------------------------- --}}
    <div class="space-y-4">
        <div class="carte p-5">
            <h2 class="mb-3 text-sm font-semibold text-gris-900">Transaction d’origine</h2>

            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Référence</dt>
                    <dd class="font-mono text-[11px] font-medium text-gris-800">
                        {{ $payment->transaction_id ?? '#'.$payment->id }}
                    </dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Montant</dt>
                    <dd class="font-medium text-gris-800">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Enregistré le</dt>
                    <dd class="font-medium text-gris-800">{{ optional($payment->created_at)->format('d/m/Y') }}</dd>
                </div>
            </dl>

            <div class="mt-5 space-y-2">
                <button type="submit" class="bouton-primaire w-full justify-center">Enregistrer</button>
                <a href="{{ route('payments.show', $payment) }}" class="bouton-secondaire w-full justify-center">Annuler</a>
            </div>
        </div>
    </div>
</form>

@endsection
