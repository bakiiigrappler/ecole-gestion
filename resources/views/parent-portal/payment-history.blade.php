@extends('layouts.app')

@section('titre', 'Mes paiements')
@section('sous-titre', $bilan['nombre'].' versement(s) pour '.$enfants->count().' enfant(s)')

@section('actions-entete')
    <a href="{{ route('parent-portal.dashboard') }}" class="bouton-secondaire">Mes enfants</a>
    <a href="{{ route('parent-portal.paiement') }}" class="bouton-primaire">Régler la scolarité</a>
@endsection

@section('contenu')

{{-- Ce que l'école a refusé, en tête : c'est ce qui appelle une action. --}}
<x-alerte-rejets :rejets="$rejets"/>

@php
    $franc = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';

    $libelles = [
        'completed' => 'Réglé',
        'pending' => 'En attente',
        'failed' => 'Échoué',
        'cancelled' => 'Annulé',
        'refunded' => 'Remboursé',
    ];

    $methodes = [
        'cash' => 'Espèces',
        'bank_transfer' => 'Virement',
        'check' => 'Chèque',
        'mobile_money' => 'Mobile money',
        'card' => 'Carte',
    ];
@endphp

    <div class="grid gap-4 sm:grid-cols-3">
        <x-statistique libelle="Total réglé" :valeur="$franc($bilan['total'])"
                       detail="Tous mes enfants" couleur="emerald"/>
        <x-statistique libelle="Versements" :valeur="$bilan['nombre']"
                       detail="Depuis l’ouverture du dossier" couleur="ogar"/>
        <x-statistique libelle="Dont en ligne" :valeur="$bilan['en_ligne']"
                       detail="Payés depuis ce portail" couleur="violet"/>
    </div>

    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Historique des versements</h2>
            <span class="text-xs text-gris-400">{{ $payments->total() }} au total</span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Enfant</th>
                        <th class="text-right">Montant</th>
                        <th>Moyen</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">Date</th>
                        <th class="text-right">Reçu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $paiement)
                        <tr>
                            <td class="font-mono text-xs text-gris-600">
                                {{ $paiement->transaction_id ?? '#'.$paiement->id }}
                            </td>
                            <td class="text-gris-700">
                                @if ($paiement->student)
                                    {{ $paiement->student->first_name }} {{ $paiement->student->last_name }}
                                    <span class="block font-mono text-[11px] text-gris-400">
                                        {{ $paiement->student->student_id }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-right font-semibold tabular-nums text-gris-800">
                                {{ $franc($paiement->amount) }}
                            </td>
                            <td class="text-gris-600">
                                {{ $methodes[$paiement->payment_method] ?? ($paiement->payment_method ?: '—') }}
                            </td>
                            <td class="text-center">
                                @php($rejet = $paiement->metadata['rejet'] ?? null)

                                <x-puce :couleur="match ($paiement->status) {
                                    'completed' => 'emerald',
                                    'cancelled', 'failed' => 'rose',
                                    default => 'soleil',
                                }">
                                    {{ $paiement->status === 'cancelled' && $rejet
                                        ? 'Refusé'
                                        : ($libelles[$paiement->status] ?? ucfirst((string) $paiement->status)) }}
                                </x-puce>

                                {{-- Le motif suit le statut : « refusé » tout seul
                                     n'apprend rien à qui a payé. --}}
                                @if ($rejet)
                                    <span class="mt-1 block text-[11px] leading-snug text-gris-500">
                                        {{ $rejet['libelle'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center tabular-nums text-gris-500">
                                {{ optional($paiement->paid_at ?? $paiement->created_at)->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="text-right">
                                <a href="{{ route('payments.receipt', $paiement->id) }}" class="bouton-mini">
                                    {{ $paiement->status === 'completed' ? 'Reçu' : 'Voir' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="7" message="Aucun versement enregistré pour vos enfants."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($payments->hasPages())
            <div class="border-t border-gris-100 px-5 py-3">
                {{ $payments->links() }}
            </div>
        @endif
    </div>

@endsection
