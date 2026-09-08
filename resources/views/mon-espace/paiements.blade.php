@extends('layouts.app')

@section('titre', 'Mes reçus')
@section('sous-titre', $paiements->count().' versement(s) — '.($annee->name ?? ''))

@section('actions-entete')
    <a href="{{ route('mon-espace') }}" class="bouton-secondaire">Mon tableau de bord</a>
@endsection

@section('contenu')

@php
    $montant = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';

    $libelles = [
        'completed' => 'Réglé', 'pending' => 'En attente',
        'failed' => 'Échoué', 'cancelled' => 'Annulé', 'refunded' => 'Remboursé',
    ];

    $methodes = [
        'cash' => 'Espèces', 'bank_transfer' => 'Virement', 'check' => 'Chèque',
        'mobile_money' => 'Mobile money', 'card' => 'Carte',
    ];
@endphp

    <div class="grid gap-4 sm:grid-cols-3">
        <x-statistique libelle="Frais de l’année" :valeur="$montant($scolarite['du'])"
                       detail="Montant dû" couleur="ogar"/>
        <x-statistique libelle="Déjà réglé" :valeur="$montant($scolarite['paye'])"
                       :detail="$paiements->count().' versement(s)'" couleur="emerald"/>
        <x-statistique libelle="Reste à payer" :valeur="$montant($scolarite['reste'])"
                       :detail="$scolarite['reste'] > 0 ? 'À régler auprès du secrétariat' : 'Scolarité soldée'"
                       :couleur="$scolarite['reste'] > 0 ? 'soleil' : 'emerald'"/>
    </div>

    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Mes versements</h2>
            <span class="text-xs text-gris-400">{{ $paiements->count() }} au total</span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th class="text-right">Montant</th>
                        <th>Moyen</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">Date</th>
                        <th class="text-right">Reçu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paiements as $paiement)
                        <tr>
                            <td class="font-mono text-xs text-gris-600">
                                {{ $paiement->transaction_id ?? '#'.$paiement->id }}
                            </td>
                            <td class="text-right font-semibold tabular-nums text-gris-800">
                                {{ $montant($paiement->amount) }}
                            </td>
                            <td class="text-gris-600">
                                {{ $methodes[$paiement->payment_method] ?? ($paiement->payment_method ?: '—') }}
                            </td>
                            <td class="text-center">
                                <x-puce :couleur="$paiement->status === 'completed' ? 'emerald' : 'soleil'">
                                    {{ $libelles[$paiement->status] ?? ucfirst((string) $paiement->status) }}
                                </x-puce>
                            </td>
                            <td class="text-center tabular-nums text-gris-500">
                                {{ optional($paiement->paid_at ?? $paiement->created_at)->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="text-right">
                                <a href="{{ route('payments.receipt', $paiement->id) }}" class="bouton-mini">Reçu</a>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="6" message="Aucun versement n’est encore enregistré à votre nom."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
