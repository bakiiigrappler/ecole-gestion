@extends('layouts.app')

@section('titre', 'Versements déclarés')
@section('sous-titre', $compte['a-verifier'].' versement(s) à vérifier auprès de l’opérateur')

@section('actions-entete')
    <a href="{{ route('payments.index') }}" class="bouton-secondaire">Journal des paiements</a>
    <a href="{{ route('admin.school-settings.index') }}" class="bouton-secondaire">Coordonnées de paiement</a>
@endsection

@section('contenu')

@php
    $franc = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';

    $onglets = [
        'a-verifier' => ['libelle' => 'À vérifier', 'couleur' => 'soleil'],
        'refuses' => ['libelle' => 'Refusés', 'couleur' => 'corail'],
        'valides' => ['libelle' => 'Validés', 'couleur' => 'emerald'],
    ];
@endphp

    {{-- ----------------------------------------------------------------
         Ce que ces versements représentent
         ---------------------------------------------------------------- --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <x-statistique libelle="À vérifier" :valeur="$compte['a-verifier']"
                       detail="Déclarés par les parents" couleur="amber"/>
        <x-statistique libelle="En jeu" :valeur="$franc($attendu)"
                       detail="Non encore porté aux dossiers" couleur="ogar"/>
        <x-statistique libelle="Validés" :valeur="$compte['valides']"
                       detail="Retrouvés chez l’opérateur" couleur="emerald"/>
    </div>

    {{-- La marche à suivre, dite une fois --}}
    <div class="carte mt-4 p-5">
        <h2 class="text-sm font-semibold text-gris-900">Comment traiter un versement déclaré</h2>
        <ol class="mt-2 grid gap-2 text-sm leading-relaxed text-gris-600 md:grid-cols-3">
            <li class="flex gap-2">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gris-100 text-[11px] font-bold text-gris-600">1</span>
                <span>Ouvrez le relevé de l’opérateur et cherchez l’identifiant de transaction déclaré.</span>
            </li>
            <li class="flex gap-2">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gris-100 text-[11px] font-bold text-gris-600">2</span>
                <span>Comparez le montant et le numéro qui a payé.</span>
            </li>
            <li class="flex gap-2">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gris-100 text-[11px] font-bold text-gris-600">3</span>
                <span><strong class="text-gris-800">Valider</strong> porte le montant au dossier de l’élève ; <strong class="text-gris-800">Refuser</strong> exige un motif, que le parent lira.</span>
            </li>
        </ol>
    </div>

    {{-- ----------------------------------------------------------------
         Les trois états
         ---------------------------------------------------------------- --}}
    <div class="mt-6 flex flex-wrap items-center gap-1 border-b border-gris-200">
        @foreach ($onglets as $cle => $onglet_)
            <a href="{{ route('payments.declarations', ['onglet' => $cle]) }}"
               class="border-b-2 px-4 py-2 text-sm font-semibold transition
                      {{ $onglet === $cle
                            ? 'border-ogar-700 text-ogar-700'
                            : 'border-transparent text-gris-500 hover:text-gris-700' }}">
                {{ $onglet_['libelle'] }}
                <span class="ml-1 text-xs font-normal text-gris-400">{{ $compte[$cle] }}</span>
            </a>
        @endforeach
    </div>

    <div class="carte mt-4 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th class="text-right">Montant</th>
                        <th>Opérateur et transaction</th>
                        <th>Déclaré le</th>
                        <th>{{ $onglet === 'refuses' ? 'Motif du refus' : 'Payeur' }}</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($declarations as $declaration)
                        @php($rejet = $declaration->metadata['rejet'] ?? null)

                        <tr>
                            <td>
                                @if ($declaration->student)
                                    <span class="font-medium text-gris-800">
                                        {{ $declaration->student->first_name }} {{ $declaration->student->last_name }}
                                    </span>
                                    <span class="block text-[11px] text-gris-400">
                                        <span class="font-mono">{{ $declaration->student->student_id }}</span>
                                        @if ($declaration->enrollment?->schoolClass)
                                            · {{ $declaration->enrollment->schoolClass->name }}
                                        @endif
                                    </span>
                                @else
                                    <span class="text-gris-400">—</span>
                                @endif
                            </td>

                            <td class="text-right font-semibold tabular-nums text-gris-900">
                                {{ $franc($declaration->amount) }}
                            </td>

                            <td class="text-gris-600">
                                {{ \App\Support\MobileMoney::libelle($declaration->payment_method) }}
                                <span class="block font-mono text-[11px] text-gris-500">
                                    {{ $declaration->gateway_transaction_id ?: '—' }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap text-gris-600">
                                {{ optional($declaration->created_at)->format('d/m/Y') }}
                                <span class="block text-[11px] text-gris-400">
                                    {{ optional($declaration->created_at)->format('H:i') }}
                                </span>
                            </td>

                            <td class="text-gris-600">
                                @if ($onglet === 'refuses')
                                    @if ($rejet)
                                        <span class="text-gris-800">{{ $rejet['libelle'] }}</span>
                                        @if (! empty($rejet['precision']))
                                            <span class="block text-[11px] leading-snug text-gris-500">
                                                {{ \Illuminate\Support\Str::limit($rejet['precision'], 70) }}
                                            </span>
                                        @endif
                                        @if (! empty($rejet['par']))
                                            <span class="block text-[11px] text-gris-400">par {{ $rejet['par'] }}</span>
                                        @endif
                                    @else
                                        <span class="text-gris-400">Aucun motif enregistré</span>
                                    @endif
                                @else
                                    {{ $declaration->payer_name ?: '—' }}
                                    <span class="block text-[11px] tabular-nums text-gris-400">
                                        {{ $declaration->payer_phone ?: '' }}
                                    </span>
                                @endif
                            </td>

                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('payments.receipt', $declaration) }}" class="bouton-mini">Le reçu</a>

                                    @if (in_array($declaration->status, ['pending', 'processing'], true))
                                        <form method="POST" action="{{ route('payments.complete', $declaration) }}">
                                            @csrf
                                            <button type="submit" class="bouton-mini text-emerald-700">Valider</button>
                                        </form>

                                        <x-rejet-paiement :payment="$declaration" bouton="bouton-mini text-corail-600">
                                            Refuser…
                                        </x-rejet-paiement>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="6"
                                :message="$onglet === 'a-verifier'
                                    ? 'Aucun versement n’attend de vérification.'
                                    : 'Aucun versement dans cet état.'"/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($declarations->hasPages())
            <div class="border-t border-gris-200 px-4 py-3">
                {{ $declarations->links() }}
            </div>
        @endif
    </div>

@endsection
