@extends('layouts.app')

@section('titre', 'Mes enfants')
@section('sous-titre', $parent->first_name.' '.$parent->last_name.' · '.$children->count().' enfant(s) scolarisé(s)')

@section('actions-entete')
    <a href="{{ route('parent-portal.dashboard') }}" class="bouton-secondaire">Mon tableau de bord</a>
@endsection

@section('contenu')

@php
    $franc = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';

    $encre = fn ($m) => $m === null
        ? 'text-gris-400'
        : ($m >= 12 ? 'text-emerald-600' : ($m >= 10 ? 'text-soleil-600' : 'text-corail-600'));

    $libellesPaiement = [
        'completed' => 'Réglé',
        'pending' => 'En attente',
        'failed' => 'Échoué',
        'cancelled' => 'Annulé',
    ];
@endphp

    {{-- ----------------------------------------------------------------
         Une carte par enfant : c'est la porte d'entrée de son dossier.
         ---------------------------------------------------------------- --}}
    <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
        @forelse ($children as $enfant)
            @php($fiche = $fiches[$enfant->id] ?? null)

            <div class="carte flex flex-col overflow-hidden">
                <div class="flex items-start gap-3 border-b border-gris-100 px-5 py-4">
                    <x-avatar :nom="$enfant->first_name.' '.$enfant->last_name"
                              :photo="$enfant->photo ?? null" class="h-12 w-12 shrink-0 text-sm"/>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-base font-semibold text-gris-900">
                            {{ $enfant->first_name }} {{ $enfant->last_name }}
                        </p>
                        <p class="truncate text-xs text-gris-500">
                            {{ $fiche['classe'] ?? 'Sans classe' }}
                            @if ($fiche['niveau'] ?? null) &middot; {{ $fiche['niveau'] }} @endif
                        </p>
                        <p class="font-mono text-[11px] text-gris-400">{{ $enfant->student_id }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 divide-x divide-gris-100 border-b border-gris-100 text-center">
                    <div class="px-2 py-3">
                        <p class="text-lg font-bold tabular-nums {{ $encre($fiche['moyenne'] ?? null) }}">
                            {{ ($fiche['moyenne'] ?? null) !== null ? number_format($fiche['moyenne'], 2, ',', ' ') : '—' }}
                        </p>
                        <p class="text-[11px] uppercase tracking-wide text-gris-400">Moyenne</p>
                    </div>
                    <div class="px-2 py-3">
                        <p class="text-lg font-bold tabular-nums {{ ($fiche['absences'] ?? 0) > 0 ? 'text-corail-600' : 'text-gris-900' }}">
                            {{ $fiche['absences'] ?? 0 }}
                        </p>
                        <p class="text-[11px] uppercase tracking-wide text-gris-400">Absences</p>
                    </div>
                    <div class="px-2 py-3">
                        <p class="text-lg font-bold tabular-nums text-gris-900">{{ $fiche['notes'] ?? 0 }}</p>
                        <p class="text-[11px] uppercase tracking-wide text-gris-400">Notes</p>
                    </div>
                </div>

                <div class="flex-1 px-5 py-4 text-sm">
                    <div class="flex items-baseline justify-between gap-2">
                        <span class="text-gris-500">Scolarité réglée</span>
                        <span class="font-semibold tabular-nums text-gris-800">
                            {{ $franc($fiche['paye'] ?? 0) }} / {{ $franc($fiche['du'] ?? 0) }}
                        </span>
                    </div>

                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gris-100">
                        <div class="h-full rounded-full {{ ($fiche['reste'] ?? 0) > 0 ? 'bg-soleil-500' : 'bg-emerald-500' }}"
                             style="width: {{ ($fiche['du'] ?? 0) > 0 ? min(100, round(($fiche['paye'] ?? 0) / $fiche['du'] * 100)) : 100 }}%"></div>
                    </div>

                    @if (($fiche['reste'] ?? 0) > 0)
                        <p class="mt-1.5 text-xs text-soleil-700">
                            Reste {{ $franc($fiche['reste']) }} à régler.
                        </p>
                    @endif
                </div>

                <div class="flex flex-wrap gap-1.5 border-t border-gris-100 bg-gris-50 px-5 py-3">
                    <a href="{{ route('parent-portal.child-details', $enfant->id) }}" class="bouton-mini">Son dossier</a>
                    <a href="{{ route('parent-portal.child-details', [$enfant->id, 'onglet' => 'emploi']) }}" class="bouton-mini">Emploi du temps</a>
                    <a href="{{ route('parent-portal.child-details', [$enfant->id, 'onglet' => 'notes']) }}" class="bouton-mini">Notes</a>
                    @if (($fiche['notes'] ?? 0) > 0)
                        <a href="{{ route('grades.bulletin', $enfant->id) }}" class="bouton-mini">Bulletin</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="carte p-8 lg:col-span-2 xl:col-span-3">
                <x-vide message="Aucun enfant n’est rattaché à votre compte."/>
                <p class="mt-3 text-center text-xs text-gris-400">
                    Le secrétariat de l’établissement peut faire le rattachement depuis votre fiche parent.
                </p>
            </div>
        @endforelse
    </div>

@endsection
