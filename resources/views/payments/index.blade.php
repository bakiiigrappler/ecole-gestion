@extends('layouts.app')

@section('titre', 'Paiements')
@section('sous-titre', number_format($bilan['transactions'], 0, ',', ' ').' transaction(s) — '.number_format($bilan['encaisse'], 0, ',', ' ').' FCFA encaissés')

@section('actions-entete')
    <a href="{{ route('payments.export', request()->query()) }}" class="bouton-secondaire">Exporter</a>
    <a href="{{ route('enrollments.index') }}" class="bouton-primaire">Inscriptions</a>
@endsection

@section('contenu')

@php
    $montant = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';

    // Classes écrites en entier : Tailwind ne compile pas une teinte interpolée.
    $puceStatut = [
        'completed' => 'emerald',
        'processing' => 'sky',
        'pending' => 'amber',
        'failed' => 'rose',
        'cancelled' => 'slate',
        'refunded' => 'violet',
        'partially_refunded' => 'violet',
    ];

    $filtres = request()->only(['search', 'status', 'payment_method', 'payment_type', 'class_id', 'date_from', 'date_to']);
    $filtreActif = collect($filtres)->filter()->isNotEmpty();

    $tauxRecouvrement = $recouvrement['du'] > 0
        ? round($recouvrement['paye'] / $recouvrement['du'] * 100)
        : 0;
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Encaissé"
                       :valeur="number_format($bilan['encaisse'], 0, ',', ' ')"
                       detail="FCFA — transactions terminées"
                       couleur="emerald"/>
        <x-statistique libelle="Transactions"
                       :valeur="number_format($bilan['transactions'], 0, ',', ' ')"
                       :detail="$filtreActif ? 'après filtrage' : 'toutes périodes'"
                       couleur="ogar"/>
        <x-statistique libelle="En attente"
                       :valeur="$bilan['en_attente']"
                       detail="À confirmer"
                       :couleur="$bilan['en_attente'] > 0 ? 'amber' : 'violet'"/>
        <x-statistique libelle="Échouées ou annulées"
                       :valeur="$bilan['echouees']"
                       detail="Sans encaissement"
                       :couleur="$bilan['echouees'] > 0 ? 'rose' : 'violet'"/>
    </div>

    {{-- ----------------------------------------------------------------
         Ce qui reste dû ne se lit pas dans les transactions mais dans les
         inscriptions : c'est la seule source du montant attendu.
         ---------------------------------------------------------------- --}}
    <div class="carte mt-4 p-5">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div class="min-w-64 flex-1">
                <h2 class="text-sm font-semibold text-gris-900">Recouvrement des frais</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    Calculé sur les inscriptions actives, pas sur le journal des transactions.
                </p>

                <div class="mt-3 h-2 overflow-hidden rounded-full bg-gris-100">
                    <div class="h-full rounded-full {{ $tauxRecouvrement >= 80 ? 'bg-emerald-500' : ($tauxRecouvrement >= 50 ? 'bg-soleil-500' : 'bg-corail-500') }}"
                         style="width: {{ min(100, $tauxRecouvrement) }}%"></div>
                </div>
                <p class="mt-1.5 text-xs text-gris-500">
                    <span class="font-semibold text-gris-900">{{ $tauxRecouvrement }}%</span> des frais attendus ont été encaissés.
                </p>
            </div>

            <dl class="grid grid-cols-3 gap-x-8 gap-y-1 text-sm">
                <div>
                    <dt class="text-[11px] uppercase tracking-wide text-gris-400">Attendu</dt>
                    <dd class="font-semibold text-gris-800">{{ $montant($recouvrement['du']) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wide text-gris-400">Encaissé</dt>
                    <dd class="font-semibold text-emerald-700">{{ $montant($recouvrement['paye']) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wide text-gris-400">Reste dû</dt>
                    <dd class="font-semibold text-corail-700">{{ $montant($recouvrement['reste']) }}</dd>
                </div>
            </dl>

            <a href="{{ route('enrollments.index', ['payment_status' => 'pending']) }}" class="bouton-secondaire">
                {{ $recouvrement['en_retard'] }} inscription(s) à relancer
            </a>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Filtres
         ---------------------------------------------------------------- --}}
    <form method="GET" action="{{ route('payments.index') }}"
          data-filtre-dynamique="paiements"
          class="carte mt-4 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-48 flex-1">
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Recherche</label>
            <input type="search" name="search" value="{{ request('search') }}"
                   placeholder="Transaction, élève, payeur, téléphone…" class="champ w-full text-sm">
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Statut</label>
            <select name="status" class="champ w-40 text-sm">
                <option value="">Tous</option>
                @foreach ($statuses as $cle => $libelle)
                    <option value="{{ $cle }}" @selected(request('status') === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Méthode</label>
            <select name="payment_method" class="champ w-40 text-sm">
                <option value="">Toutes</option>
                @foreach ($paymentMethods as $cle => $libelle)
                    <option value="{{ $cle }}" @selected(request('payment_method') === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Classe</label>
            <select name="class_id" class="champ w-40 text-sm">
                <option value="">Toutes</option>
                @foreach ($classes as $classe)
                    <option value="{{ $classe->id }}" @selected((string) request('class_id') === (string) $classe->id)>
                        {{ $classe->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Du</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="champ w-36 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Au</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="champ w-36 text-sm">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bouton-primaire">Filtrer</button>
            @if ($filtreActif)
                <a href="{{ route('payments.index') }}" class="bouton-secondaire">Réinitialiser</a>
            @endif
        </div>
    </form>

    {{-- ----------------------------------------------------------------
         Le journal des transactions
         ---------------------------------------------------------------- --}}
    <div class="carte relative mt-4 overflow-hidden" data-liste-dynamique="paiements">
        <x-chargement data-voile-chargement hidden/>

        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Journal des transactions</h2>
            <span class="text-xs text-gris-400">
                {{ number_format($payments->total(), 0, ',', ' ') }} transaction(s){{ $filtreActif ? ' après filtrage' : '' }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Transaction</th>
                        <th>Élève</th>
                        <th class="text-right">Montant</th>
                        <th>Méthode</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $paiement)
                        <tr>
                            <td>
                                <a href="{{ route('payments.show', $paiement) }}"
                                   class="font-mono text-[11px] font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                    {{ $paiement->transaction_id ?? '#'.$paiement->id }}
                                </a>
                                <div class="text-[11px] text-gris-400">
                                    {{ $paymentTypes[$paiement->payment_type] ?? $paiement->payment_type }}
                                </div>
                            </td>
                            <td>
                                @if ($paiement->student)
                                    <a href="{{ route('students.show', $paiement->student->id) }}"
                                       class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                        {{ $paiement->student->first_name }} {{ $paiement->student->last_name }}
                                    </a>
                                    <div class="text-[11px] text-gris-400">
                                        {{ $paiement->enrollment->schoolClass->name ?? '—' }}
                                    </div>
                                @else
                                    <span class="text-xs italic text-gris-400">{{ $paiement->payer_name ?? 'Payeur inconnu' }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right font-semibold text-gris-800">
                                {{ $montant($paiement->amount) }}
                            </td>
                            <td class="text-gris-600">
                                {{ $paymentMethods[$paiement->payment_method] ?? $paiement->payment_method }}
                            </td>
                            <td>
                                <x-puce :couleur="$puceStatut[$paiement->status] ?? 'slate'">
                                    {{ $statuses[$paiement->status] ?? $paiement->status }}
                                </x-puce>
                            </td>
                            <td class="whitespace-nowrap text-gris-600">
                                {{ optional($paiement->paid_at ?? $paiement->created_at)->format('d/m/Y') }}
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('payments.show', $paiement) }}" class="bouton-mini">Voir</a>
                                    <a href="{{ route('payments.receipt', $paiement) }}" class="bouton-mini">Reçu</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="7" message="Aucune transaction ne correspond à ces filtres."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($payments->hasPages())
            <div class="border-t border-gris-200 px-4 py-3" data-pagination>
                {{ $payments->links() }}
            </div>
        @endif
    </div>

@endsection
