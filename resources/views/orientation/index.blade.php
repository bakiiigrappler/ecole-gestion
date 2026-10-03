@extends('layouts.app')

@section('titre', 'Orientation')
@section('sous-titre', $compte['a-etudier'].' dossier(s) à étudier'.($annee ? ' · '.$annee->name : ''))

@section('actions-entete')
    <a href="{{ route('orientation.repertoire') }}" class="bouton-secondaire">Répertoire des établissements</a>
@endsection

@section('contenu')

@php
    $onglets = [
        'a-etudier' => 'À étudier',
        'accordes' => 'Accordés',
        'refuses' => 'Refusés',
        'brouillons' => 'En cours chez l’élève',
    ];

    $puce = [
        'soumis' => 'amber', 'accorde' => 'emerald', 'refuse' => 'rose', 'brouillon' => 'slate',
    ];
@endphp

    <div class="grid gap-4 sm:grid-cols-4">
        <x-statistique libelle="À étudier" :valeur="$compte['a-etudier']"
                       detail="Transmis par les élèves" couleur="amber"/>
        <x-statistique libelle="Accordés" :valeur="$compte['accordes']"
                       detail="Vœux suivis" couleur="emerald"/>
        <x-statistique libelle="Refusés" :valeur="$compte['refuses']"
                       detail="Avec motif transmis" couleur="rose"/>
        <x-statistique libelle="En cours" :valeur="$compte['brouillons']"
                       detail="Pas encore transmis" couleur="slate"/>
    </div>

    {{-- Où va la promotion : ce que le conseil de classe cherche à savoir --}}
    @if ($repartition)
        <div class="carte mt-4 p-5">
            <h2 class="text-sm font-semibold text-gris-900">Où va la promotion</h2>
            <p class="mt-0.5 text-xs text-gris-400">
                Vœux transmis ou accordés, cette année.
            </p>

            @php($total = collect($repartition)->sum('nombre'))

            <ul class="mt-3 space-y-2">
                @foreach ($repartition as $ligne)
                    @php($part = $total > 0 ? round($ligne['nombre'] / $total * 100) : 0)
                    <li>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="text-gris-700">{{ $ligne['libelle'] }}</span>
                            <span class="tabular-nums text-gris-500">
                                {{ $ligne['nombre'] }} <span class="text-[11px]">({{ $part }} %)</span>
                            </span>
                        </div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-gris-100">
                            <div class="h-full rounded-full bg-ogar-500" style="width: {{ max(2, $part) }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6 flex flex-wrap items-center gap-1 border-b border-gris-200">
        @foreach ($onglets as $cle => $libelle)
            <a href="{{ route('orientation.index', ['onglet' => $cle]) }}"
               class="border-b-2 px-4 py-2 text-sm font-semibold transition
                      {{ $onglet === $cle
                            ? 'border-ogar-700 text-ogar-700'
                            : 'border-transparent text-gris-500 hover:text-gris-700' }}">
                {{ $libelle }}
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
                        <th>Niveau</th>
                        <th>Vœu</th>
                        <th class="text-center">Moyenne</th>
                        <th>{{ $onglet === 'a-etudier' ? 'Transmis le' : 'Décidé le' }}</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dossiers as $dossier)
                        <tr>
                            <td>
                                <span class="font-medium text-gris-800">
                                    {{ $dossier->student->first_name ?? '—' }} {{ $dossier->student->last_name ?? '' }}
                                </span>
                                <span class="block font-mono text-[11px] text-gris-400">
                                    {{ $dossier->student->student_id ?? '' }}
                                </span>
                            </td>

                            <td class="text-gris-600">
                                {{ \App\Models\OrientationDossier::NIVEAUX[$dossier->niveau] ?? $dossier->niveau }}
                            </td>

                            <td class="text-gris-700">
                                {{ $dossier->filiere
                                    ?: ($dossier->niveau === 'terminale'
                                        ? 'Enseignement supérieur, filière à préciser'
                                        : \App\Support\Orientation\VoiesApresTroisieme::libelle($dossier->voie)) }}
                                <span class="block text-[11px] text-gris-400">
                                    {{ count($dossier->voeux ?? []) }} établissement(s) demandé(s)
                                </span>
                            </td>

                            <td class="text-center tabular-nums text-gris-700">
                                {{ \App\Support\Orientation\ProfilEleve::nombre($dossier->moyennes['generale'] ?? null) }}
                            </td>

                            <td class="whitespace-nowrap text-gris-600">
                                @php($date = $onglet === 'a-etudier' ? $dossier->soumis_le : $dossier->decide_le)
                                {{ $date ? $date->format('d/m/Y') : '—' }}
                                @if ($dossier->decideur)
                                    <span class="block text-[11px] text-gris-400">par {{ $dossier->decideur->name }}</span>
                                @endif
                            </td>

                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <x-puce :couleur="$puce[$dossier->statut] ?? 'slate'">
                                        {{ $dossier->libelle_statut }}
                                    </x-puce>
                                    <a href="{{ route('orientation.show', $dossier) }}" class="bouton-mini">
                                        {{ $dossier->estSoumis() ? 'Étudier' : 'Le dossier' }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="6" message="Aucun dossier dans cet état."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($dossiers->hasPages())
            <div class="border-t border-gris-200 px-4 py-3">{{ $dossiers->links() }}</div>
        @endif
    </div>

@endsection
