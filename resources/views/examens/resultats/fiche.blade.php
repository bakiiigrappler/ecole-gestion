@extends('layouts.public')

@section('titre', 'Résultat — '.$fiche['numero'])

@section('contenu')

@php
    $admis = $fiche['statut'] === 'admis';
    $secondTour = $fiche['statut'] === 'second_tour';

    $teinte = $admis ? 'emerald' : ($secondTour ? 'soleil' : 'corail');
@endphp

<div class="mx-auto max-w-2xl px-4 py-10">

    <div class="carte overflow-hidden">
        {{-- Classes écrites en entier : Tailwind ne compile pas une teinte
             interpolée. --}}
        <div class="px-6 py-5 text-center
                    {{ $admis ? 'bg-emerald-50 border-b border-emerald-200'
                        : ($secondTour ? 'bg-soleil-50 border-b border-soleil-200'
                        : 'bg-corail-50 border-b border-corail-200') }}">
            <p class="text-[11px] font-semibold uppercase tracking-wide
                      {{ $admis ? 'text-emerald-700' : ($secondTour ? 'text-soleil-700' : 'text-corail-700') }}">
                {{ $fiche['examen'] }}
            </p>

            <p class="mt-2 text-3xl font-bold
                      {{ $admis ? 'text-emerald-800' : ($secondTour ? 'text-soleil-800' : 'text-corail-800') }}">
                {{ $fiche['decision'] }}
            </p>

            @if ($admis && $fiche['mention'])
                <p class="mt-1 text-sm font-semibold text-emerald-700">Mention {{ $fiche['mention'] }}</p>
            @endif
        </div>

        <dl class="divide-y divide-gris-100 text-sm">
            <div class="flex items-center justify-between gap-3 px-6 py-3">
                <dt class="text-gris-600">Candidat</dt>
                <dd class="text-right font-semibold text-gris-900">
                    {{ $fiche['nom'] }} {{ $fiche['prenom'] }}
                </dd>
            </div>

            <div class="flex items-center justify-between gap-3 px-6 py-3">
                <dt class="text-gris-600">Numéro de candidat</dt>
                <dd class="font-mono font-semibold text-gris-900">{{ $fiche['numero'] }}</dd>
            </div>

            @if ($fiche['serie'])
                <div class="flex items-center justify-between gap-3 px-6 py-3">
                    <dt class="text-gris-600">Série</dt>
                    <dd class="font-medium text-gris-900">{{ $fiche['serie'] }}</dd>
                </div>
            @endif

            @if ($fiche['centre'])
                <div class="flex items-center justify-between gap-3 px-6 py-3">
                    <dt class="text-gris-600">Centre de composition</dt>
                    <dd class="text-right font-medium text-gris-900">
                        {{ $fiche['centre'] }}
                        @if ($fiche['ville'])
                            <span class="block text-[11px] font-normal text-gris-500">{{ $fiche['ville'] }}</span>
                        @endif
                    </dd>
                </div>
            @endif

            @if ($fiche['etablissement'])
                <div class="flex items-center justify-between gap-3 px-6 py-3">
                    <dt class="text-gris-600">Établissement d’origine</dt>
                    <dd class="text-right font-medium text-gris-900">{{ $fiche['etablissement'] }}</dd>
                </div>
            @endif
        </dl>

        <div class="border-t border-gris-100 bg-gris-50 px-6 py-4">
            <p class="text-[11px] leading-relaxed text-gris-600">
                @if ($admis)
                    Le relevé de notes détaillé est remis par votre établissement, et se consulte
                    depuis votre compte élève ou celui de vos parents. Il n’est pas public.
                @elseif ($secondTour)
                    Le second tour se présente aux dates fixées par votre centre. Rapprochez-vous
                    de votre établissement pour la convocation.
                @else
                    Le détail de vos notes se consulte depuis votre compte élève ou celui de vos
                    parents, et auprès de votre établissement.
                @endif
            </p>

            @if ($fiche['proclamation'])
                <p class="mt-2 text-[11px] text-gris-500">
                    Proclamation du {{ \Carbon\Carbon::parse($fiche['proclamation'])->format('d/m/Y') }}.
                    Seul le procès-verbal signé fait foi.
                </p>
            @endif
        </div>
    </div>

    <div class="mt-6 text-center">
        <a href="{{ route('resultats.accueil') }}" class="bouton-secondaire">Consulter un autre résultat</a>
    </div>
</div>

@endsection
