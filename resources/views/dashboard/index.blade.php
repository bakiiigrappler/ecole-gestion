@extends('layouts.app')

@section('titre', 'Tableau de bord')
@section('sous-titre', 'Vue d\'ensemble de l\'établissement — année ' . ($currentYear->name ?? '—'))

@section('actions-entete')
    <div x-data="{ ouvert: false }" class="relative">
        <button @click="ouvert = ! ouvert" class="bouton-secondaire">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            <span class="hidden sm:inline">Rapports</span>
        </button>

        <div x-show="ouvert" x-cloak @click.outside="ouvert = false"
             class="absolute right-0 z-30 mt-2 w-64 overflow-hidden rounded-xl border border-gris-200 bg-white py-1 shadow-lg">
            @foreach ([
                ['route' => 'dashboard.export.general',    'libelle' => 'Rapport général (PDF)'],
                ['route' => 'dashboard.export.financial',  'libelle' => 'Rapport financier (PDF)'],
                ['route' => 'dashboard.export.enrollment', 'libelle' => 'Rapport inscriptions (PDF)'],
            ] as $rapport)
                <a href="{{ route($rapport['route']) }}" target="_blank"
                   class="block cursor-pointer px-4 py-2 text-sm text-gris-700 hover:bg-gris-50">{{ $rapport['libelle'] }}</a>
            @endforeach
            <div class="my-1 border-t border-gris-100"></div>
            <a href="{{ route('dashboard.export.excel') }}" target="_blank"
               class="block cursor-pointer px-4 py-2 text-sm text-gris-700 hover:bg-gris-50">Exporter en Excel</a>
        </div>
    </div>
@endsection

@section('contenu')

    {{-- ----------------------------------------------------------------
         Bandeau d'accueil
         ---------------------------------------------------------------- --}}
    <div class="carte mb-6 flex flex-wrap items-center gap-5 p-5">
        <x-mascotte pose="repos" taille="h-20" class="hidden sm:flex"/>

        <div class="min-w-0 flex-1">
            <h2 class="text-lg font-semibold text-gris-900">
                Bonjour {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}.
            </h2>
            <p class="mt-1 text-sm text-gris-500">
                {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }} &middot;
                {{ number_format($totalStudents ?? 0, 0, ',', ' ') }} élèves suivis sur
                l’année {{ $currentYear->name ?? '—' }}.
            </p>
        </div>

        <a href="{{ route('students.create') }}" class="bouton-secondaire">Inscrire un élève</a>
    </div>

    {{-- ----------------------------------------------------------------
         Chiffres cles
         ---------------------------------------------------------------- --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique
            libelle="Total élèves"
            :valeur="number_format($totalStudents ?? 0, 0, ',', ' ')"
            :detail="($studentStats['actifs'] ?? 0).' actifs · '.($studentStats['anciens'] ?? 0).' anciens'"
            couleur="ogar"
            :lien="route('students.index')"/>

        <x-statistique
            libelle="Inscriptions {{ $currentYear->name ?? '' }}"
            :valeur="number_format($enrollmentStats['total'] ?? 0, 0, ',', ' ')"
            :detail="($enrollmentStats['reinscription'] ?? 0).' réinscriptions'"
            couleur="emerald"
            :lien="route('enrollments.index')"/>

        <x-statistique
            libelle="Revenus de l'année"
            :valeur="number_format($financialStats['yearly_revenue'] ?? 0, 0, ',', ' ').' F'"
            :detail="($financialStats['collection_rate'] ?? 0).'% collecté'"
            couleur="amber"
            :lien="route('payments.index')"/>

        <x-statistique
            libelle="Solde à percevoir"
            :valeur="number_format($financialStats['balance_due'] ?? 0, 0, ',', ' ').' F'"
            :detail="(($financialStats['partial_count'] ?? 0) + ($financialStats['unpaid_count'] ?? 0)).' dossiers impayés'"
            couleur="rose"
            :lien="route('fees.index')"/>
    </div>

    {{-- ----------------------------------------------------------------
         Repartition des inscriptions
         ---------------------------------------------------------------- --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="carte">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Inscriptions par cycle</h2>
                <span class="text-xs text-gris-400">{{ $currentYear->name ?? '' }}</span>
            </div>
            <div class="h-64 p-4">
                <canvas data-graphique="doughnut" data-donnees="{{ json_encode([
                    'labels' => ['Préprimaire', 'Primaire', 'Collège', 'Lycée'],
                    'datasets' => [[
                        'data' => [
                            $enrollmentsByCycle['preprimaire'] ?? 0,
                            $enrollmentsByCycle['primaire'] ?? 0,
                            $enrollmentsByCycle['college'] ?? 0,
                            $enrollmentsByCycle['lycee'] ?? 0,
                        ],
                    ]],
                ]) }}"></canvas>
            </div>
        </div>

        <div class="carte">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Statut des élèves</h2>
                <span class="text-xs text-gris-400">Année en cours</span>
            </div>
            <div class="h-64 p-4">
                <canvas data-graphique="bar" data-donnees="{{ json_encode([
                    'labels' => ['Nouveaux', 'Passants', 'Redoublants'],
                    'legende' => false,
                    'datasets' => [[
                        'data' => [
                            $enrollmentStats['nouveau'] ?? 0,
                            $enrollmentStats['passant'] ?? 0,
                            $enrollmentStats['redoublant'] ?? 0,
                        ],
                    ]],
                ]) }}"></canvas>
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Evolution et situation financiere
         ---------------------------------------------------------------- --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <div class="carte lg:col-span-2">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Évolution des inscriptions</h2>
                <span class="text-xs text-gris-400">12 derniers mois</span>
            </div>
            <div class="h-64 p-4">
                <canvas data-graphique="line" data-donnees="{{ json_encode([
                    'labels' => collect($enrollmentTrend ?? [])->pluck('month'),
                    'legende' => false,
                    'datasets' => [[
                        'data' => collect($enrollmentTrend ?? [])->pluck('count'),
                        'couleurs' => 'rgba(28, 117, 188, 0.12)',
                        'bordure' => '#1c75bc',
                    ]],
                ]) }}"></canvas>
            </div>
        </div>

        <div class="carte">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Situation financière</h2>
            </div>
            <div class="space-y-4 p-5">
                @php
                    $tauxCollecte = (int) ($financialStats['collection_rate'] ?? 0);
                @endphp

                <dl class="space-y-2 text-sm">
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-gris-500">Frais attendus</dt>
                        <dd class="font-semibold text-gris-900">{{ number_format($financialStats['total_fees'] ?? 0, 0, ',', ' ') }} F</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-gris-500">Encaissé</dt>
                        <dd class="font-semibold text-emerald-700">{{ number_format($financialStats['yearly_revenue'] ?? 0, 0, ',', ' ') }} F</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-gris-500">Reste à percevoir</dt>
                        <dd class="font-semibold text-corail-700">{{ number_format($financialStats['balance_due'] ?? 0, 0, ',', ' ') }} F</dd>
                    </div>
                </dl>

                <div>
                    <div class="mb-1 flex items-baseline justify-between text-xs">
                        <span class="font-semibold uppercase tracking-wide text-gris-500">Taux de recouvrement</span>
                        <span class="font-bold text-gris-900">{{ $tauxCollecte }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-gris-100"
                         role="progressbar" aria-valuenow="{{ $tauxCollecte }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full bg-emerald-600" style="width: {{ min($tauxCollecte, 100) }}%"></div>
                    </div>
                </div>

                <ul class="space-y-1.5 border-t border-gris-100 pt-4 text-sm">
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-gris-600">Dossiers soldés</span>
                        <x-puce couleur="emerald">{{ $financialStats['paid_count'] ?? 0 }}</x-puce>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-gris-600">Paiements partiels</span>
                        <x-puce couleur="amber">{{ $financialStats['partial_count'] ?? 0 }}</x-puce>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-gris-600">Impayés</span>
                        <x-puce couleur="rose">{{ $financialStats['unpaid_count'] ?? 0 }}</x-puce>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Derniers mouvements
         ---------------------------------------------------------------- --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Inscriptions récentes</h2>
                <a href="{{ route('enrollments.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">Voir tout</a>
            </div>
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Classe</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentEnrollments as $inscription)
                            <tr>
                                <td>
                                    <div class="font-semibold text-gris-900">
                                        @if ($inscription->applicant_first_name && $inscription->applicant_last_name)
                                            {{ $inscription->applicant_first_name }} {{ $inscription->applicant_last_name }}
                                        @elseif ($inscription->student)
                                            {{ $inscription->student->first_name }} {{ $inscription->student->last_name }}
                                        @else
                                            &mdash;
                                        @endif
                                    </div>
                                    <div class="text-xs text-gris-400">{{ $inscription->enrollment_code }}</div>
                                </td>
                                <td>{{ $inscription->schoolClass->name ?? '—' }}</td>
                                <td>
                                    @switch($inscription->student_status)
                                        @case('nouveau')    <x-puce couleur="sky">Nouveau</x-puce> @break
                                        @case('passant')    <x-puce couleur="emerald">Passant</x-puce> @break
                                        @case('redoublant') <x-puce couleur="amber">Redoublant</x-puce> @break
                                        @default <x-puce>—</x-puce>
                                    @endswitch
                                </td>
                                <td class="whitespace-nowrap text-gris-500">
                                    {{ $inscription->enrollment_date?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="4" message="Aucune inscription récente."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Paiements récents</h2>
                <a href="{{ route('payments.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">Voir tout</a>
            </div>
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Montant</th>
                            <th>Reçu</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentPayments as $paiement)
                            <tr>
                                <td>
                                    <div class="font-semibold text-gris-900">
                                        @if ($paiement->applicant_first_name && $paiement->applicant_last_name)
                                            {{ $paiement->applicant_first_name }} {{ $paiement->applicant_last_name }}
                                        @elseif ($paiement->student)
                                            {{ $paiement->student->first_name }} {{ $paiement->student->last_name }}
                                        @else
                                            &mdash;
                                        @endif
                                    </div>
                                    <div class="text-xs text-gris-400">{{ $paiement->schoolClass->name ?? '—' }}</div>
                                </td>
                                <td class="whitespace-nowrap font-semibold text-emerald-700">
                                    {{ number_format($paiement->amount_paid, 0, ',', ' ') }} F
                                </td>
                                <td><span class="font-mono text-xs text-gris-500">{{ $paiement->receipt_number ?? '—' }}</span></td>
                                <td class="whitespace-nowrap text-gris-500">
                                    {{ $paiement->enrollment_date?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="4" message="Aucun paiement récent."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Classes les plus chargees
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Classes les plus chargées</h2>
            <a href="{{ route('classes.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">Toutes les classes</a>
        </div>
        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th class="w-12">#</th>
                        <th>Classe</th>
                        <th>Niveau</th>
                        <th>Cycle</th>
                        <th>Effectif</th>
                        <th>Capacité</th>
                        <th class="w-48">Taux d'occupation</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classesByEnrollment as $rang => $classe)
                        @php
                            $capacite = $classe->capacity ?: 0;
                            $taux = $capacite > 0 ? (int) round(($classe->enrollments_count / $capacite) * 100) : 0;
                            $teinte = $taux >= 90 ? 'bg-corail-600' : ($taux >= 70 ? 'bg-soleil-500' : 'bg-emerald-600');
                        @endphp
                        <tr>
                            <td class="text-gris-400">{{ $rang + 1 }}</td>
                            <td class="font-semibold text-gris-900">{{ $classe->name }}</td>
                            <td>{{ $classe->level->name ?? '—' }}</td>
                            <td class="capitalize">{{ $classe->level->cycle ?? '—' }}</td>
                            <td><x-puce couleur="sky">{{ $classe->enrollments_count }}</x-puce></td>
                            <td class="text-gris-500">{{ $capacite ?: '—' }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gris-100"
                                         role="progressbar" aria-valuenow="{{ $taux }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="h-full rounded-full {{ $teinte }}" style="width: {{ min($taux, 100) }}%"></div>
                                    </div>
                                    <span class="w-10 shrink-0 text-right text-xs font-semibold text-gris-600">{{ $taux }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="7" message="Aucune classe enregistrée."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Actions rapides
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Actions rapides</h2>
        </div>
        <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['route' => 'students.create', 'libelle' => 'Nouvel élève',      'trace' => 'M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z'],
                ['route' => 'teachers.create', 'libelle' => 'Nouvel enseignant', 'trace' => 'M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5'],
                ['route' => 'grades.create',   'libelle' => 'Saisir des notes',  'trace' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10'],
                ['route' => 'payments.index',  'libelle' => 'Gérer les paiements', 'trace' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z'],
            ] as $action)
                <a href="{{ route($action['route']) }}"
                   class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-gris-200 px-4 py-6 text-center transition-colors hover:border-ogar-300 hover:bg-ogar-50
                          focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ogar-600">
                    <svg class="h-7 w-7 text-ogar-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $action['trace'] }}"/>
                    </svg>
                    <span class="text-sm font-semibold text-gris-700">{{ $action['libelle'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

@endsection
