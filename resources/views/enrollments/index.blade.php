@extends('layouts.app')

@section('titre', 'Inscriptions')
@section('sous-titre', $currentYearEnrollments.' inscription(s) sur l’année en cours — '.$totalEnrollments.' au total')

@section('actions-entete')
    @if ($pendingCount > 0)
        <a href="{{ route('enrollments.pending-students') }}" class="bouton-secondaire">
            {{ $pendingCount }} en attente
        </a>
    @endif
    <a href="{{ route('enrollments.create') }}" class="bouton-primaire">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        <span class="hidden sm:inline">Nouvelle inscription</span>
    </a>
@endsection

@section('contenu')

@php
    $libellesCycle = ['preprimaire' => 'Préprimaire', 'primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    $teintesCycle = ['preprimaire' => 'amber', 'primaire' => 'emerald', 'college' => 'sky', 'lycee' => 'violet'];

    $libellesPaiement = ['completed' => 'Soldé', 'partial' => 'Partiel', 'pending' => 'Impayé', 'overdue' => 'En retard'];
    $teintesPaiement = ['completed' => 'emerald', 'partial' => 'amber', 'pending' => 'rose', 'overdue' => 'rose'];

    $libellesStatut = ['active' => 'Active', 'completed' => 'Terminée', 'transferred' => 'Transférée', 'dropped' => 'Abandonnée'];
    $teintesStatut = ['active' => 'emerald', 'completed' => 'violet', 'transferred' => 'sky', 'dropped' => 'slate'];

    $franc = fn ($montant) => number_format((float) $montant, 0, ',', ' ').' F';
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Inscriptions"
                       :valeur="number_format($currentYearEnrollments, 0, ',', ' ')"
                       :detail="$activeEnrollments.' active(s) toutes années'"
                       couleur="ogar"/>
        <x-statistique libelle="Encaissé"
                       :valeur="$franc($recouvrement['encaisse'])"
                       :detail="$recouvrement['taux'] !== null ? $recouvrement['taux'].'% du facturé' : 'Aucun montant facturé'"
                       couleur="emerald"/>
        <x-statistique libelle="Reste à encaisser"
                       :valeur="$franc($recouvrement['reste'])"
                       :detail="'sur '.$franc($recouvrement['facture']).' facturés'"
                       :couleur="$recouvrement['reste'] > 0 ? 'amber' : 'emerald'"/>
        <x-statistique libelle="Dossiers non soldés"
                       :valeur="$recouvrement['partiels'] + $recouvrement['impayes'] + $recouvrement['retards']"
                       :detail="$recouvrement['partiels'].' partiels · '.$recouvrement['impayes'].' impayés'"
                       :couleur="($recouvrement['partiels'] + $recouvrement['impayes']) > 0 ? 'rose' : 'violet'"/>
    </div>

    {{-- Répartition par cycle : quatre raccourcis de filtre plutôt qu'un simple compteur --}}
    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($libellesCycle as $cle => $libelle)
            <a href="{{ route('enrollments.index', ['cycle' => $cle]) }}"
               class="carte flex items-center justify-between px-4 py-3 transition-colors hover:bg-gris-50 {{ request('cycle') === $cle ? 'ring-2 ring-ogar-300' : '' }}">
                <span class="text-sm font-medium text-gris-700">{{ $libelle }}</span>
                <span class="flex items-center gap-2">
                    <span class="text-lg font-bold text-gris-900">{{ $enrollmentsByCycle[$cle] ?? 0 }}</span>
                    <x-puce :couleur="$teintesCycle[$cle]">inscrits</x-puce>
                </span>
            </a>
        @endforeach
    </div>

    {{-- ----------------------------------------------------------------
         Filtres — formulaire GET, filtrage côté serveur
         ---------------------------------------------------------------- --}}
    <form method="GET" action="{{ route('enrollments.index') }}" class="carte mt-6 p-4">
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label for="search" class="etiquette">Rechercher</label>
                <input type="search" name="search" id="search" value="{{ request('search') }}"
                       class="champ" placeholder="Nom, matricule, n° de reçu…">
            </div>

            <div>
                <label for="academic_year" class="etiquette">Année scolaire</label>
                <select name="academic_year" id="academic_year" class="champ">
                    <option value="">Toutes les années</option>
                    @foreach ($academicYears as $annee)
                        <option value="{{ $annee->id }}" @selected((string) request('academic_year') === (string) $annee->id)>
                            {{ $annee->name }}{{ $annee->is_current ? ' (courante)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="cycle" class="etiquette">Cycle</label>
                <select name="cycle" id="cycle" class="champ">
                    <option value="">Tous les cycles</option>
                    @foreach ($libellesCycle as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(request('cycle') === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="class" class="etiquette">Classe</label>
                <select name="class" id="class" class="champ">
                    <option value="">Toutes les classes</option>
                    @foreach ($classes as $classe)
                        <option value="{{ $classe->id }}" @selected((string) request('class') === (string) $classe->id)>
                            {{ $classe->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="payment_status" class="etiquette">Paiement</label>
                <select name="payment_status" id="payment_status" class="champ">
                    <option value="">Toutes les situations</option>
                    @foreach ($libellesPaiement as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(request('payment_status') === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3 grid gap-3 md:grid-cols-2 lg:grid-cols-6">
            <div>
                <label for="status" class="etiquette">Statut</label>
                <select name="status" id="status" class="champ">
                    <option value="">Tous les statuts</option>
                    @foreach ($libellesStatut as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(request('status') === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="student_status" class="etiquette">Situation de l’élève</label>
                <select name="student_status" id="student_status" class="champ">
                    <option value="">Toutes</option>
                    <option value="nouveau" @selected(request('student_status') === 'nouveau')>Nouveau</option>
                    <option value="redoublant" @selected(request('student_status') === 'redoublant')>Redoublant</option>
                    <option value="passant" @selected(request('student_status') === 'passant')>Passant</option>
                </select>
            </div>

            <div>
                <label for="enrollment_type" class="etiquette">Type</label>
                <select name="enrollment_type" id="enrollment_type" class="champ">
                    <option value="">Tous les types</option>
                    <option value="new" @selected(request('enrollment_type') === 'new')>Première inscription</option>
                    <option value="reinscription" @selected(request('enrollment_type') === 'reinscription')>Réinscription</option>
                </select>
            </div>

            <div>
                <label for="per_page" class="etiquette">Par page</label>
                <select name="per_page" id="per_page" class="champ">
                    @foreach (\App\Support\ParametresPlateforme::PAGINATIONS as $n)
                        <option value="{{ $n }}" @selected(\App\Support\ParametresPlateforme::pagination(request('per_page')) === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2 lg:col-span-2">
                <button type="submit" class="bouton-primaire">Filtrer</button>
                <a href="{{ route('enrollments.index') }}" class="bouton-secondaire">Réinitialiser</a>
            </div>
        </div>
    </form>

    {{-- ----------------------------------------------------------------
         Liste
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Dossiers d’inscription</h2>
            <span class="text-xs text-gris-400">{{ $enrollments->total() }} résultat(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Classe</th>
                        <th>Année</th>
                        <th class="whitespace-nowrap">Inscrit le</th>
                        <th class="text-right">Facturé</th>
                        <th class="text-right">Reste dû</th>
                        <th class="text-center">Paiement</th>
                        <th class="text-center">Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $inscription)
                        <tr>
                            <td>
                                @if ($inscription->student)
                                    <a href="{{ route('students.show', $inscription->student->id) }}"
                                       class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                        {{ $inscription->student->full_name }}
                                    </a>
                                    <div class="font-mono text-[11px] text-gris-400">{{ $inscription->student->student_id }}</div>
                                @else
                                    <span class="font-medium text-gris-800">{{ $inscription->applicant_full_name ?: 'Candidat sans nom' }}</span>
                                    <div class="text-[11px] italic text-soleil-700">Élève non encore créé</div>
                                @endif
                            </td>
                            <td>
                                @if ($inscription->schoolClass)
                                    <a href="{{ route('classes.show', $inscription->schoolClass->id) }}"
                                       class="text-gris-700 hover:text-ogar-700 hover:underline">
                                        {{ $inscription->schoolClass->name }}
                                    </a>
                                    <div class="text-[11px] text-gris-400">
                                        {{ $libellesCycle[$inscription->schoolClass->getSafeCycle()] ?? '—' }}
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
                            <td class="whitespace-nowrap text-right {{ $inscription->balance_due > 0 ? 'font-semibold text-corail-700' : 'text-gris-300' }}">
                                {{ $inscription->balance_due > 0 ? $franc($inscription->balance_due) : '—' }}
                            </td>
                            <td class="text-center">
                                <x-puce :couleur="$teintesPaiement[$inscription->payment_status] ?? 'slate'">
                                    {{ $libellesPaiement[$inscription->payment_status] ?? $inscription->payment_status }}
                                </x-puce>
                            </td>
                            <td class="text-center">
                                <x-puce :couleur="$teintesStatut[$inscription->status] ?? 'slate'">
                                    {{ $libellesStatut[$inscription->status] ?? $inscription->status }}
                                </x-puce>
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('enrollments.show', $inscription->id) }}" class="bouton-mini">Consulter</a>
                                    <a href="{{ route('enrollments.edit', $inscription->id) }}" class="bouton-mini">Modifier</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="9" message="Aucune inscription ne correspond à ces critères."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($enrollments->hasPages())
            <div class="border-t border-gris-100 px-5 py-4">
                {{ $enrollments->links() }}
            </div>
        @endif
    </div>

@endsection
