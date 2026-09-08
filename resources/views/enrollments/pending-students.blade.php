@extends('layouts.app')

@section('titre', 'Dossiers en attente')
@section('sous-titre', $pendingEnrollments->total().' inscription(s) sans élève créé')

@section('actions-entete')
    <a href="{{ route('enrollments.index') }}" class="bouton-secondaire">Toutes les inscriptions</a>
    <a href="{{ route('enrollments.create') }}" class="bouton-primaire">Nouvelle inscription</a>
@endsection

@section('contenu')

@php
    $libellesCycle = ['preprimaire' => 'Préprimaire', 'primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    $teintesCycle = ['preprimaire' => 'amber', 'primaire' => 'emerald', 'college' => 'sky', 'lycee' => 'violet'];
    $franc = fn ($m) => number_format((float) $m, 0, ',', ' ').' F';
@endphp

    <div class="flex items-start gap-3 rounded-xl border border-gris-200 bg-white px-4 py-3 text-sm text-gris-600">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-ogar-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <span>
            Ces dossiers ont été déposés mais aucun élève n’a encore été créé à partir d’eux.
            Tant que l’élève n’existe pas, l’inscription ne compte dans aucun effectif et
            aucune note ni présence ne peut être saisie.
        </span>
    </div>

    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">En attente de création d’élève</h2>
            <span class="text-xs text-gris-400">{{ $pendingEnrollments->total() }} dossier(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Candidat</th>
                        <th>Responsable déclaré</th>
                        <th>Classe visée</th>
                        <th>Année</th>
                        <th class="whitespace-nowrap">Déposé le</th>
                        <th class="text-right">Facturé</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendingEnrollments as $inscription)
                        <tr>
                            <td>
                                <div class="font-medium text-gris-800">
                                    {{ $inscription->applicant_full_name ?: 'Candidat sans nom' }}
                                </div>
                                <div class="text-[11px] text-gris-400">
                                    {{ $inscription->applicant_date_of_birth ? \Carbon\Carbon::parse($inscription->applicant_date_of_birth)->format('d/m/Y') : 'date de naissance inconnue' }}
                                    @if ($inscription->applicant_gender)
                                        &middot; {{ $inscription->applicant_gender === 'M' ? 'Garçon' : 'Fille' }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="text-gris-700">
                                    {{ trim($inscription->parent_first_name.' '.$inscription->parent_last_name) ?: '—' }}
                                </div>
                                <div class="text-[11px] text-gris-400">{{ $inscription->parent_phone ?: 'aucun téléphone' }}</div>
                            </td>
                            <td>
                                @if ($inscription->schoolClass)
                                    {{ $inscription->schoolClass->name }}
                                    <div class="text-[11px]">
                                        <x-puce :couleur="$teintesCycle[$inscription->schoolClass->getSafeCycle()] ?? 'slate'">
                                            {{ $libellesCycle[$inscription->schoolClass->getSafeCycle()] ?? '—' }}
                                        </x-puce>
                                    </div>
                                @else
                                    <span class="text-xs text-gris-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-gris-600">{{ $inscription->academicYear->name ?? '—' }}</td>
                            <td class="whitespace-nowrap text-gris-600">
                                {{ $inscription->enrollment_date ? \Carbon\Carbon::parse($inscription->enrollment_date)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="whitespace-nowrap text-right text-gris-700">{{ $franc($inscription->total_fees) }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('enrollments.show', $inscription->id) }}" class="bouton-mini">Consulter</a>
                                    <a href="{{ route('enrollments.create-student', $inscription->id) }}" class="bouton-mini">Créer l’élève</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="7" message="Aucun dossier n’attend la création d’un élève."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pendingEnrollments->hasPages())
            <div class="border-t border-gris-100 px-5 py-4">
                {{ $pendingEnrollments->links() }}
            </div>
        @endif
    </div>

@endsection
