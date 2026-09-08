@extends('layouts.app')

@section('titre', $fee->name)
@section('sous-titre', number_format($fee->amount, 0, ',', ' ').' FCFA — '.($fee->frequency === 'monthly' ? 'mensuel' : ($fee->frequency === 'quarterly' ? 'trimestriel' : ($fee->frequency === 'yearly' ? 'annuel' : 'paiement unique'))))

@section('actions-entete')
    <a href="{{ route('fees.edit', $fee) }}" class="bouton-secondaire">Modifier</a>
    <a href="{{ route('fees.index') }}" class="bouton-primaire">Tous les frais</a>
@endsection

@section('contenu')

@php
    $montant = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';

    $libellesType = [
        'tuition' => 'Scolarité', 'registration' => 'Inscription', 'uniform' => 'Uniforme',
        'transport' => 'Transport', 'meal' => 'Repas', 'other' => 'Autre',
    ];

    $libellesFrequence = [
        'monthly' => 'Mensuel', 'quarterly' => 'Trimestriel',
        'yearly' => 'Annuel', 'one_time' => 'Paiement unique',
    ];

    // Classes écrites en entier : Tailwind ne compile pas une teinte interpolée.
    $puceType = [
        'tuition' => 'ogar', 'registration' => 'emerald', 'uniform' => 'violet',
        'transport' => 'amber', 'meal' => 'sky', 'other' => 'slate',
    ];

    $echeances = ['monthly' => 10, 'quarterly' => 3, 'yearly' => 1, 'one_time' => 1];
    $nombreEcheances = $echeances[$fee->frequency] ?? 1;
@endphp

    <div class="grid gap-4 lg:grid-cols-3">

        <div class="space-y-4 lg:col-span-2">
            <div class="carte p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Montant</p>
                        <p class="mt-0.5 text-3xl font-bold text-gris-900">{{ $montant($fee->amount) }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <x-puce :couleur="$puceType[$fee->fee_type] ?? 'slate'">
                                {{ $libellesType[$fee->fee_type] ?? $fee->fee_type }}
                            </x-puce>
                            <x-puce :couleur="$fee->is_active ? 'emerald' : 'slate'">
                                {{ $fee->is_active ? 'Actif' : 'Désactivé' }}
                            </x-puce>
                            @if ($fee->is_mandatory)
                                <x-puce couleur="rose">Obligatoire</x-puce>
                            @endif
                        </div>
                    </div>

                    <div class="text-right">
                        <p class="text-[11px] uppercase tracking-wide text-gris-400">Coût sur l’année</p>
                        <p class="text-lg font-semibold text-gris-800">{{ $montant($fee->montantAnnuel()) }}</p>
                        <p class="text-[11px] text-gris-400">
                            {{ $nombreEcheances }} échéance(s) de {{ $montant($fee->amount) }}
                        </p>
                    </div>
                </div>

                @if ($fee->description)
                    <p class="mt-4 border-t border-gris-100 pt-4 text-sm leading-relaxed text-gris-600">
                        {{ $fee->description }}
                    </p>
                @endif
            </div>

            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Paramètres</h2>
                </div>

                <dl class="grid grid-cols-2 gap-x-8 gap-y-3 p-5 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gris-400">Périodicité</dt>
                        <dd class="font-medium text-gris-800">
                            {{ $libellesFrequence[$fee->frequency] ?? $fee->frequency }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gris-400">Échéance</dt>
                        <dd class="font-medium text-gris-800">
                            {{ optional($fee->due_date)->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gris-400">Année scolaire</dt>
                        <dd class="font-medium text-gris-800">{{ $fee->academicYear->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gris-400">Niveau</dt>
                        <dd class="font-medium text-gris-800">{{ $fee->niveau->name ?? 'Tous' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gris-400">Classe</dt>
                        <dd class="font-medium text-gris-800">{{ $fee->schoolClass->name ?? 'Toutes' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gris-400">Créé le</dt>
                        <dd class="font-medium text-gris-800">{{ optional($fee->created_at)->format('d/m/Y') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="space-y-4">
            <div class="carte p-5">
                <h2 class="mb-3 text-sm font-semibold text-gris-900">À qui s’applique ce frais</h2>

                <p class="text-sm leading-relaxed text-gris-600">
                    @if ($fee->schoolClass)
                        Aux élèves de <span class="font-semibold text-gris-900">{{ $fee->schoolClass->name }}</span>.
                    @elseif ($fee->niveau)
                        À tous les élèves du niveau <span class="font-semibold text-gris-900">{{ $fee->niveau->name }}</span>.
                    @else
                        À <span class="font-semibold text-gris-900">tous les élèves</span> de l’établissement.
                    @endif

                    @if ($fee->is_mandatory)
                        Ce frais est obligatoire.
                    @else
                        Ce frais est facultatif.
                    @endif
                </p>

                @unless ($fee->is_active)
                    <p class="mt-3 rounded-lg bg-gris-50 p-3 text-[11px] text-gris-500">
                        Ce frais est désactivé : il n’est plus facturé aux nouvelles inscriptions.
                    </p>
                @endunless
            </div>

            <div class="carte p-5">
                <h2 class="mb-3 text-sm font-semibold text-gris-900">Actions</h2>

                <div class="space-y-2">
                    <a href="{{ route('fees.edit', $fee) }}" class="bouton-primaire w-full justify-center">Modifier</a>

                    <x-confirmation :action="route('fees.destroy', $fee)"
                                    methode="DELETE"
                                    titre="Supprimer ce frais ?"
                                    :message="'« '.$fee->name.' » ('.$montant($fee->amount).') sera retiré de la grille tarifaire. Cette action est définitive.'"
                                    confirmer="Supprimer"
                                    bouton="bouton-danger w-full justify-center">
                        Supprimer
                    </x-confirmation>
                </div>
            </div>
        </div>
    </div>

@endsection
