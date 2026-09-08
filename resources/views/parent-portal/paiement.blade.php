@extends('layouts.app')

@section('titre', 'Régler la scolarité')
{{-- Le sous-titre nomme les opérateurs réellement ouverts : annoncer Moov
     Money à une école qui n'y a pas de compte enverrait le parent nulle part. --}}
@section('sous-titre', $operateurs
    ? 'Payer par '.collect($operateurs)->pluck('libelle')->join(' ou ').', puis déclarer le versement'
    : 'Le paiement en ligne n’est pas ouvert dans cet établissement')

@section('actions-entete')
    <a href="{{ route('parent-portal.payment-history') }}" class="bouton-secondaire">Mes paiements</a>
    <a href="{{ route('parent-portal.dashboard') }}" class="bouton-primaire">Mes enfants</a>
@endsection

@section('contenu')

@php
    $franc = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';

    // Le dossier mis en avant : celui choisi, sinon le premier qui doit encore.
    $dossierChoisi = $dossiers->firstWhere(fn ($d) => $d['eleve']->id === $choisi) ?? $dossiers->first();
@endphp

@if ($operateurs === [])

    {{-- L'établissement n'a pas ouvert le paiement par téléphone : le dire, et
         ne pas afficher un formulaire qui ne mènerait nulle part. --}}
    <div class="carte p-8 text-center">
        <h2 class="text-base font-semibold text-gris-900">Le paiement en ligne n’est pas encore ouvert</h2>
        <p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed text-gris-600">
            {{ $reglages->school_name ?? 'L’établissement' }} n’a pas encore publié ses coordonnées de paiement
            par téléphone. La scolarité se règle au secrétariat
            @if ($reglages->school_phone ?? null)
                — vous pouvez l’appeler au
                <span class="font-semibold tabular-nums text-gris-800">{{ $reglages->school_phone }}</span>
            @endif.
        </p>
        <a href="{{ route('parent-portal.payment-history') }}" class="bouton-secondaire mt-5">
            Voir mes versements
        </a>
    </div>

@elseif ($dossiers->isEmpty())

    <div class="carte p-8 text-center">
        <h2 class="text-base font-semibold text-gris-900">Aucune inscription en cours</h2>
        <p class="mx-auto mt-2 max-w-xl text-sm text-gris-600">
            Aucun de vos enfants n’a d’inscription active cette année : il n’y a rien à régler pour l’instant.
        </p>
    </div>

@else

<div x-data="{
        enfant: {{ $dossierChoisi['eleve']->id }},
        operateur: '{{ array_key_first($operateurs) }}',
        montant: '{{ $dossierChoisi['reste'] > 0 ? (int) $dossierChoisi['reste'] : '' }}',
        copie: null,
        copier(quoi, valeur) {
            const fini = () => { this.copie = quoi; setTimeout(() => (this.copie = null), 2000); };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(valeur).then(fini);
                return;
            }

            const zone = document.createElement('textarea');
            zone.value = valeur;
            zone.style.position = 'fixed';
            zone.style.opacity = '0';
            document.body.appendChild(zone);
            zone.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(zone);
            fini();
        }
     }"
     class="grid gap-6 lg:grid-cols-3">

    {{-- ------------------------------------------------------------------
         Colonne de gauche : ce qui est dû, où payer, comment
         ------------------------------------------------------------------ --}}
    <div class="space-y-4 lg:col-span-2">

        {{-- Les dossiers : un onglet par enfant --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Ce qui reste à régler</h2>
                    <p class="mt-0.5 text-xs text-gris-400">Année scolaire en cours.</p>
                </div>
            </div>

            <div class="max-h-96 divide-y divide-gris-100 overflow-y-auto">
                @foreach ($dossiers as $dossier)
                    <label class="flex cursor-pointer flex-wrap items-center gap-4 px-5 py-4 transition"
                           :class="enfant === {{ $dossier['eleve']->id }} ? 'bg-ogar-50' : 'hover:bg-gris-50'">
                        <input type="radio" name="choix_enfant" class="sr-only"
                               value="{{ $dossier['eleve']->id }}" x-model.number="enfant"
                               @change="montant = '{{ $dossier['reste'] > 0 ? (int) $dossier['reste'] : '' }}'">

                        <x-avatar :nom="$dossier['eleve']->first_name.' '.$dossier['eleve']->last_name"
                                  class="h-10 w-10 shrink-0 text-[11px]"/>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gris-900">
                                {{ $dossier['eleve']->first_name }} {{ $dossier['eleve']->last_name }}
                            </p>
                            <p class="truncate text-[11px] text-gris-500">
                                {{ $dossier['inscription']->schoolClass->name ?? '—' }}
                                · <span class="font-mono">{{ $dossier['eleve']->student_id }}</span>
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="text-[11px] uppercase tracking-wide text-gris-500">Reste dû</p>
                            <p class="text-base font-bold tabular-nums {{ $dossier['reste'] > 0 ? 'text-gris-900' : 'text-emerald-700' }}">
                                {{ $franc($dossier['reste']) }}
                            </p>
                            <p class="text-[11px] text-gris-400">
                                sur {{ $franc($dossier['du']) }}
                            </p>
                        </div>

                        @if ($dossier['en_attente'] > 0)
                            <span class="w-full text-[11px] text-soleil-700">
                                {{ $franc($dossier['en_attente']) }} déjà déclarés, en attente de vérification par le secrétariat.
                            </span>
                        @endif
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Où payer : un panneau par opérateur --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Où payer</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        {{-- Marchand ou transfert selon ce que l'école a publié : la carte
                             de chaque opérateur le précise juste en dessous. --}}
                        Depuis votre téléphone. L’argent va directement à l’établissement.
                    </p>
                </div>
            </div>

            <div class="grid gap-2 p-5 sm:grid-cols-2">
                @foreach ($operateurs as $cle => $operateur)
                    <label class="cursor-pointer">
                        <input type="radio" class="peer sr-only" value="{{ $cle }}" x-model="operateur">
                        <span class="block rounded-xl border border-gris-200 px-4 py-3 transition
                                     peer-checked:border-ogar-500 peer-checked:bg-ogar-50">
                            <span class="block text-sm font-semibold text-gris-800">{{ $operateur['libelle'] }}</span>
                            <span class="mt-0.5 block font-mono text-[11px] text-gris-500">{{ $operateur['ussd'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            @foreach ($operateurs as $cle => $operateur)
                @php($consignes = \App\Support\MobileMoney::consignes($reglages, $operateur))

                <div x-show="operateur === '{{ $cle }}'" x-cloak class="border-t border-gris-100 p-5">

                    {{-- Où verser, en grand : c'est ce qu'on recopie sur le clavier du
                         téléphone. Le code marchand quand l'école en a un, son numéro
                         sinon — et les deux quand elle a les deux. --}}
                    <div class="grid gap-3 {{ $operateur['code'] && $operateur['numero'] ? 'sm:grid-cols-2' : '' }}">
                        @if ($operateur['code'])
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-ogar-300 bg-ogar-50 px-5 py-4">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-ogar-700">
                                        Code marchand {{ $operateur['libelle'] }}
                                    </p>
                                    <p class="mt-1 font-mono text-2xl font-bold tracking-[0.15em] text-ogar-900">
                                        {{ $operateur['code'] }}
                                    </p>
                                </div>

                                <button type="button" @click="copier('code-{{ $cle }}', @js($operateur['code']))"
                                        class="bouton-secondaire shrink-0">
                                    <span x-show="copie !== 'code-{{ $cle }}'">Copier</span>
                                    <span x-show="copie === 'code-{{ $cle }}'" x-cloak>Copié ✓</span>
                                </button>
                            </div>
                        @endif

                        @if ($operateur['numero'])
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gris-300 bg-gris-50 px-5 py-4">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-600">
                                        Numéro {{ $operateur['libelle'] }}
                                    </p>
                                    <p class="mt-1 font-mono text-2xl font-bold tracking-[0.1em] tabular-nums text-gris-900">
                                        {{ $operateur['numero'] }}
                                    </p>
                                </div>

                                <button type="button" @click="copier('num-{{ $cle }}', @js($operateur['numero']))"
                                        class="bouton-secondaire shrink-0">
                                    <span x-show="copie !== 'num-{{ $cle }}'">Copier</span>
                                    <span x-show="copie === 'num-{{ $cle }}'" x-cloak>Copié ✓</span>
                                </button>
                            </div>
                        @endif
                    </div>

                    @if ($operateur['nom'])
                        <p class="mt-2 text-[11px] text-gris-600">
                            Avant de valider, l’écran de votre téléphone doit afficher
                            <span class="font-semibold text-gris-800">{{ $operateur['nom'] }}</span>.
                            S’il affiche autre chose, interrompez l’opération.
                        </p>
                    @endif

                    {{-- La marche à suivre --}}
                    <p class="mb-2 mt-5 text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Marche à suivre
                        @if ($consignes['propres'])
                            <span class="ml-1 font-normal normal-case tracking-normal text-gris-400">
                                — consignes de l’établissement
                            </span>
                        @endif
                    </p>

                    <ol class="space-y-2">
                        @foreach ($consignes['lignes'] as $rang => $etape)
                            <li class="flex gap-3 text-sm leading-relaxed text-gris-700">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gris-100 text-[11px] font-bold text-gris-600">
                                    {{ $rang + 1 }}
                                </span>
                                <span>{{ $etape }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         Colonne de droite : déclarer le versement
         ------------------------------------------------------------------ --}}
    <div class="space-y-4">
        <form method="POST" action="{{ route('parent-portal.declarer-paiement') }}" class="carte overflow-hidden">
            @csrf
            <input type="hidden" name="student_id" :value="enfant">
            <input type="hidden" name="operateur" :value="operateur">

            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Déclarer le versement</h2>
                    <p class="mt-0.5 text-xs text-gris-400">Une fois le paiement effectué au téléphone.</p>
                </div>
            </div>

            <div class="space-y-4 p-5">
                <div>
                    <label for="montant" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Montant versé <span class="text-corail-600">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="montant" id="montant" required min="100" step="100"
                               x-model="montant" class="champ w-full pr-14 text-sm tabular-nums">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-[11px] text-gris-400">
                            FCFA
                        </span>
                    </div>
                    @error('montant')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="reference" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Identifiant de la transaction <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="reference" id="reference" required
                           value="{{ old('reference') }}" class="champ w-full font-mono text-sm"
                           placeholder="Celui du SMS de confirmation">
                    <p class="mt-1 text-[11px] text-gris-400">
                        C’est lui que le secrétariat confronte au relevé de l’opérateur.
                    </p>
                    @error('reference')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="telephone" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Numéro ayant payé
                    </label>
                    <input type="text" name="telephone" id="telephone"
                           value="{{ old('telephone', $parent->phone) }}" class="champ w-full text-sm tabular-nums">
                    @error('telephone')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="border-t border-gris-100 p-5">
                <button type="submit" class="bouton-primaire w-full justify-center">
                    Déclarer le versement
                </button>
                <p class="mt-3 text-[11px] leading-relaxed text-gris-500">
                    Votre reçu est établi aussitôt, avec la mention
                    <strong class="text-gris-700">« en attente de vérification »</strong>. Il portera
                    « réglé » dès que le secrétariat aura retrouvé votre versement chez l’opérateur.
                </p>
            </div>
        </form>

        <div class="carte p-5">
            <h2 class="text-sm font-semibold text-gris-900">En cas de difficulté</h2>
            <p class="mt-1.5 text-sm leading-relaxed text-gris-600">
                Si l’écran de votre téléphone n’affiche pas
                <span class="font-medium text-gris-800">{{ $reglages->school_name ?? 'le nom de l’établissement' }}</span>
                après le code marchand, interrompez l’opération et prévenez le secrétariat.
            </p>
            @if ($reglages->school_phone ?? null)
                <p class="mt-2 text-sm text-gris-700">
                    Secrétariat : <span class="font-semibold tabular-nums">{{ $reglages->school_phone }}</span>
                </p>
            @endif
        </div>
    </div>
</div>

@endif

@endsection
