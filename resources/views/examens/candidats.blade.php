@extends('layouts.app')

@section('titre', 'Candidats — '.$examen->intitule_complet)
@section('sous-titre', $candidats->total().' candidat(s)')

@section('actions-entete')
    <a href="{{ route('examens.show', $examen) }}" class="bouton-secondaire">La session</a>
@endsection

@section('contenu')

@php
    $puce = [
        'inscrit' => 'slate', 'present' => 'sky', 'absent' => 'amber', 'exclu' => 'rose',
        'admis' => 'emerald', 'second_tour' => 'amber', 'ajourne' => 'rose',
    ];
@endphp

<div class="grid gap-6 lg:grid-cols-4">

    <div class="space-y-4 lg:col-span-3">

        <form method="GET" action="{{ route('examens.candidats', $examen) }}"
              class="carte flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-48 flex-1">
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Recherche</label>
                <input type="search" name="recherche" value="{{ request('recherche') }}"
                       class="champ w-full text-sm" placeholder="Nom, prénom ou numéro…">
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Centre</label>
                <select name="centre" class="champ w-48 text-sm">
                    <option value="">Tous</option>
                    @foreach ($centres as $centre)
                        <option value="{{ $centre->id }}" @selected(request('centre') == $centre->id)>
                            {{ $centre->nom }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Statut</label>
                <select name="statut" class="champ w-40 text-sm">
                    <option value="">Tous</option>
                    @foreach (\App\Models\ExamenCandidat::STATUTS as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(request('statut') === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="bouton-primaire">Filtrer</button>
        </form>

        <div class="carte overflow-hidden">
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Candidat</th>
                            <th>Établissement</th>
                            <th>Centre</th>
                            <th class="text-center">Anonymat</th>
                            <th class="text-center">Moyenne</th>
                            <th class="text-center">Décision</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($candidats as $candidat)
                            <tr>
                                <td class="font-mono text-xs text-gris-600">{{ $candidat->numero_candidat }}</td>

                                <td>
                                    <span class="font-medium text-gris-800">{{ $candidat->nom_complet }}</span>
                                    @if ($candidat->serie)
                                        <span class="block text-[11px] text-gris-400">série {{ $candidat->serie }}</span>
                                    @endif
                                </td>

                                <td class="text-[11px] text-gris-600">
                                    {{ $candidat->etablissement_origine ?: ($candidat->school->name ?? 'Candidat libre') }}
                                </td>

                                <td class="text-[11px] text-gris-600">{{ $candidat->centre->nom ?? '—' }}</td>

                                <td class="text-center font-mono text-[11px] text-gris-500">
                                    {{ $candidat->numero_anonymat ?: '—' }}
                                </td>

                                <td class="text-center tabular-nums text-gris-800">
                                    {{ $candidat->moyenne !== null ? number_format($candidat->moyenne, 2, ',', ' ') : '—' }}
                                </td>

                                <td class="text-center">
                                    <x-puce :couleur="$puce[$candidat->statut] ?? 'slate'">
                                        {{ $candidat->libelle_statut }}
                                    </x-puce>
                                    @if ($candidat->mention)
                                        <span class="block text-[11px] text-gris-500">{{ $candidat->mention }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="7" message="Aucun candidat ne correspond à ces filtres."/>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($candidats->hasPages())
                <div class="border-t border-gris-200 px-4 py-3">{{ $candidats->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Inscrire une promotion entière --}}
    <div class="space-y-4">
        <form method="POST" action="{{ route('examens.inscrire', $examen) }}" class="carte overflow-hidden">
            @csrf

            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Inscrire un établissement</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        Les candidats viennent des inscriptions de l’année : personne ne ressaisit trois cents noms.
                    </p>
                </div>
            </div>

            <div class="space-y-3 p-5">
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Établissement <span class="text-corail-600">*</span>
                    </label>
                    <select name="school_id" required class="champ w-full text-sm">
                        @foreach ($ecoles as $ecole)
                            <option value="{{ $ecole->id }}">{{ $ecole->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Niveau <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="niveau" required class="champ w-full text-sm"
                           value="{{ $examen->type === 'bepc' ? '3' : ($examen->type === 'cep' ? 'CM2' : 'erminale') }}">
                    <p class="mt-1 text-[11px] text-gris-400">
                        Le nom du niveau, ou un fragment : « erminale », « 3 », « CM2 ».
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Centre affecté
                    </label>
                    <select name="centre_id" class="champ w-full text-sm">
                        <option value="">À affecter plus tard</option>
                        @foreach ($centres as $centre)
                            <option value="{{ $centre->id }}">{{ $centre->nom }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="border-t border-gris-100 p-5">
                <button type="submit" class="bouton-primaire w-full justify-center">Inscrire la promotion</button>
            </div>
        </form>
    </div>
</div>

@endsection
