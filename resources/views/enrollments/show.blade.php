@extends('layouts.app')

@section('titre', 'Inscription '.($enrollment->student->full_name ?? $enrollment->applicant_full_name ?: '#'.$enrollment->id))
@section('sous-titre', ($enrollment->schoolClass->name ?? 'Classe non définie').' — '.($enrollment->academicYear->name ?? 'année inconnue'))

@section('actions-entete')
    <a href="{{ route('enrollments.edit', $enrollment->id) }}" class="bouton-secondaire">Modifier</a>
    <a href="{{ route('enrollments.receipt', $enrollment->id) }}" class="bouton-primaire">Reçu</a>
@endsection

@section('contenu')

@php
    $libellesCycle = ['preprimaire' => 'Préprimaire', 'primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    $teintesCycle = ['preprimaire' => 'amber', 'primaire' => 'emerald', 'college' => 'sky', 'lycee' => 'violet'];

    $libellesPaiement = ['completed' => 'Soldé', 'partial' => 'Partiel', 'pending' => 'Impayé', 'overdue' => 'En retard'];
    $teintesPaiement = ['completed' => 'emerald', 'partial' => 'amber', 'pending' => 'rose', 'overdue' => 'rose'];

    $libellesStatut = ['active' => 'Active', 'completed' => 'Terminée', 'transferred' => 'Transférée', 'dropped' => 'Abandonnée'];
    $teintesStatut = ['active' => 'emerald', 'completed' => 'violet', 'transferred' => 'sky', 'dropped' => 'slate'];

    $libellesMethode = [
        'cash' => 'Espèces', 'bank_transfer' => 'Virement bancaire',
        'moov_money' => 'Moov Money', 'airtel_money' => 'Airtel Money',
        'mobile_money' => 'Mobile Money', 'check' => 'Chèque',
    ];

    $franc = fn ($m) => number_format((float) $m, 0, ',', ' ').' F';

    $eleve = $enrollment->student;
    $cycle = $enrollment->schoolClass?->getSafeCycle();
    $paiements = $enrollment->payments;

    $facture = (float) $enrollment->total_fees;
    $encaisse = (float) $enrollment->amount_paid;
    $reste = (float) $enrollment->balance_due;
    $tauxPaiement = $facture > 0 ? (int) round($encaisse / $facture * 100) : null;

    // Le montant stocké et la somme des paiements sont deux chiffres distincts :
    // on les rapproche pour signaler tout écart plutôt que d'en cacher un.
    $sommePaiements = (float) $paiements->where('status', 'completed')->sum('amount');
    $ecartPaiements = round($sommePaiements - $encaisse, 2);

    $onglets = [
        'detail' => 'Détail',
        'paiements' => 'Paiements ('.$paiements->count().')',
        'parcours' => 'Parcours ('.$autresInscriptions->count().')',
    ];

    $ongletInitial = request('onglet', 'detail');
    $ongletInitial = array_key_exists($ongletInitial, $onglets) ? $ongletInitial : 'detail';
@endphp

<div x-data="{ onglet: '{{ $ongletInitial }}' }">

    {{-- ------------------------------------------------------------------
         En-tête du dossier
         ------------------------------------------------------------------ --}}
    <div class="carte mb-4 p-5">
        <div class="flex flex-wrap items-start gap-5">
            @if ($eleve)
                <x-avatar :nom="$eleve->full_name" :photo="$eleve->photo ?? null" class="h-20 w-20 shrink-0 text-xl"/>
            @else
                <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-soleil-50 ring-1 ring-soleil-100">
                    <svg class="h-9 w-9 text-soleil-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                </span>
            @endif

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-bold text-gris-900">
                        {{ $eleve->full_name ?? ($enrollment->applicant_full_name ?: 'Candidat sans nom') }}
                    </h2>
                    <x-puce :couleur="$teintesStatut[$enrollment->status] ?? 'slate'">
                        {{ $libellesStatut[$enrollment->status] ?? $enrollment->status }}
                    </x-puce>
                    <x-puce :couleur="$teintesPaiement[$enrollment->payment_status] ?? 'slate'">
                        {{ $libellesPaiement[$enrollment->payment_status] ?? $enrollment->payment_status }}
                    </x-puce>
                    @if ($cycle)
                        <x-puce :couleur="$teintesCycle[$cycle] ?? 'slate'">{{ $libellesCycle[$cycle] ?? $cycle }}</x-puce>
                    @endif
                    @if (! $eleve)
                        <x-puce couleur="rose">Élève non créé</x-puce>
                    @endif
                </div>

                <p class="mt-1 text-sm text-gris-500">
                    @if ($eleve)
                        <span class="font-mono">{{ $eleve->student_id }}</span> &middot;
                    @endif
                    {{ $enrollment->schoolClass->name ?? 'classe non définie' }}
                    &middot; {{ $enrollment->academicYear->name ?? 'année inconnue' }}
                </p>

                <div class="mt-3 flex flex-wrap gap-x-8 gap-y-1.5 text-sm [&>div]:whitespace-nowrap">
                    <div>
                        <span class="text-gris-400">Inscrit le</span>
                        <span class="font-medium">
                            {{ $enrollment->enrollment_date ? \Carbon\Carbon::parse($enrollment->enrollment_date)->translatedFormat('j F Y') : '—' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gris-400">Situation</span>
                        <span class="font-medium">{{ ucfirst($enrollment->student_status ?? '—') }}</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Reçu</span>
                        <span class="font-mono font-medium">{{ $enrollment->receipt_number ?: '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-lg bg-gris-50 px-4 py-3">
                    <div class="text-lg font-bold text-gris-700">{{ $franc($facture) }}</div>
                    <div class="text-[10px] font-semibold uppercase text-gris-500">facturé</div>
                </div>
                <div class="rounded-lg bg-emerald-50 px-4 py-3">
                    <div class="text-lg font-bold text-emerald-700">{{ $franc($encaisse) }}</div>
                    <div class="text-[10px] font-semibold uppercase text-emerald-600">encaissé</div>
                </div>
                <div class="rounded-lg {{ $reste > 0 ? 'bg-corail-50' : 'bg-soleil-50' }} px-4 py-3">
                    <div class="text-lg font-bold {{ $reste > 0 ? 'text-corail-700' : 'text-soleil-700' }}">{{ $franc($reste) }}</div>
                    <div class="text-[10px] font-semibold uppercase {{ $reste > 0 ? 'text-corail-600' : 'text-soleil-600' }}">reste dû</div>
                </div>
            </div>
        </div>

        @if ($tauxPaiement !== null)
            <div class="mt-4">
                <div class="mb-1 flex items-baseline justify-between text-xs">
                    <span class="text-gris-400">Avancement du règlement</span>
                    <span class="font-semibold text-gris-700">{{ $tauxPaiement }}%</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-gris-100">
                    <div class="h-full rounded-full {{ $tauxPaiement >= 100 ? 'bg-emerald-500' : ($tauxPaiement >= 50 ? 'bg-soleil-500' : 'bg-corail-500') }}"
                         style="width: {{ min($tauxPaiement, 100) }}%"></div>
                </div>
            </div>
        @endif

        <div class="mt-4 flex flex-wrap gap-2 border-t border-gris-100 pt-4">
            <a href="{{ route('enrollments.edit', $enrollment->id) }}" class="bouton-secondaire text-xs">Modifier l’inscription</a>
            <a href="{{ route('enrollments.receipt', $enrollment->id) }}" class="bouton-secondaire text-xs">Reçu de paiement</a>
            <a href="{{ route('enrollments.download-entry-authorization', $enrollment->id) }}" class="bouton-secondaire text-xs">
                Autorisation d’entrée
            </a>
            @if ($eleve)
                <a href="{{ route('students.show', $eleve->id) }}" class="bouton-secondaire text-xs">Fiche de l’élève</a>
            @else
                <a href="{{ route('enrollments.create-student', $enrollment->id) }}" class="bouton-primaire text-xs">Créer l’élève</a>
            @endif

            <x-confirmation :action="route('enrollments.destroy', $enrollment->id)" methode="DELETE"
                            titre="Supprimer cette inscription ?"
                            :message="$paiements->isNotEmpty()
                                ? $paiements->count().' paiement(s) sont rattachés à ce dossier : la suppression sera refusée. Passez-le plutôt en « transférée » ou « inactive ».'
                                : 'Cette inscription sera définitivement supprimée.'"
                            confirmer="Supprimer"
                            bouton="bouton-secondaire ml-auto text-xs text-corail-600 hover:bg-corail-50">
                Supprimer
            </x-confirmation>
        </div>
    </div>

    @if ($ecartPaiements != 0)
        <div class="mb-4 flex items-start gap-3 rounded-xl border border-soleil-200 bg-soleil-50 px-4 py-3 text-sm text-soleil-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
            <span>
                <strong>Écart entre le montant enregistré et les paiements.</strong>
                Le dossier indique {{ $franc($encaisse) }} encaissés, la somme des paiements soldés vaut
                {{ $franc($sommePaiements) }} — soit {{ $franc(abs($ecartPaiements)) }} de
                {{ $ecartPaiements > 0 ? 'plus' : 'moins' }}.
            </span>
        </div>
    @endif

    {{-- ------------------------------------------------------------------
         Onglets
         ------------------------------------------------------------------ --}}
    <div class="mb-4 flex flex-wrap gap-1 border-b border-gris-200">
        @foreach ($onglets as $cle => $libelle)
            <button type="button" @click="onglet = '{{ $cle }}'"
                    :class="onglet === '{{ $cle }}' ? 'border-ogar-700 text-ogar-700' : 'border-transparent text-gris-500 hover:text-gris-700'"
                    class="cursor-pointer border-b-2 px-4 py-2 text-sm font-semibold transition">{{ $libelle }}</button>
        @endforeach
    </div>

    {{-- Détail ------------------------------------------------------------ --}}
    <div x-show="onglet === 'detail'" class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="carte">
                <div class="carte-entete">
                    <h3 class="text-sm font-semibold text-gris-900">Dossier d’inscription</h3>
                </div>
                <dl class="grid grid-cols-2 gap-y-2.5 p-5 text-sm">
                    <dt class="text-gris-400">Classe</dt>
                    <dd class="font-medium text-gris-900">
                        @if ($enrollment->schoolClass)
                            <a href="{{ route('classes.show', $enrollment->schoolClass->id) }}" class="hover:text-ogar-700 hover:underline">
                                {{ $enrollment->schoolClass->name }}
                            </a>
                            <span class="text-gris-400">({{ $enrollment->schoolClass->getSafeLevelName() }})</span>
                        @else
                            —
                        @endif
                    </dd>

                    <dt class="text-gris-400">Année scolaire</dt>
                    <dd class="font-medium text-gris-900">{{ $enrollment->academicYear->name ?? '—' }}</dd>

                    <dt class="text-gris-400">Date d’inscription</dt>
                    <dd class="font-medium text-gris-900">
                        {{ $enrollment->enrollment_date ? \Carbon\Carbon::parse($enrollment->enrollment_date)->translatedFormat('j F Y') : '—' }}
                    </dd>

                    <dt class="text-gris-400">Statut du dossier</dt>
                    <dd><x-puce :couleur="$teintesStatut[$enrollment->status] ?? 'slate'">{{ $libellesStatut[$enrollment->status] ?? $enrollment->status }}</x-puce></dd>

                    <dt class="text-gris-400">Situation de l’élève</dt>
                    <dd class="font-medium text-gris-900">{{ ucfirst($enrollment->student_status ?? '—') }}</dd>

                    <dt class="text-gris-400">Type</dt>
                    <dd class="font-medium text-gris-900">
                        @if ($enrollment->is_reinscription)
                            Réinscription
                        @elseif ($enrollment->is_new_enrollment)
                            Première inscription
                        @else
                            <span class="text-gris-400">non précisé</span>
                        @endif
                    </dd>

                    <dt class="text-gris-400">Code du dossier</dt>
                    <dd class="font-mono font-medium text-gris-900">{{ $enrollment->enrollment_code ?: '—' }}</dd>
                </dl>

                @if ($enrollment->notes)
                    <div class="border-t border-gris-100 p-5">
                        <p class="text-xs font-semibold uppercase text-gris-400">Notes</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-gris-700">{{ $enrollment->notes }}</p>
                    </div>
                @endif
            </div>

            @if ($classePrecedente || $anneePrecedente || $enrollment->previous_year_average !== null)
                <div class="carte">
                    <div class="carte-entete">
                        <h3 class="text-sm font-semibold text-gris-900">Année précédente</h3>
                    </div>
                    <dl class="grid grid-cols-2 gap-y-2.5 p-5 text-sm">
                        <dt class="text-gris-400">Classe</dt>
                        <dd class="font-medium text-gris-900">{{ $classePrecedente->name ?? '—' }}</dd>

                        <dt class="text-gris-400">Année</dt>
                        <dd class="font-medium text-gris-900">{{ $anneePrecedente->name ?? '—' }}</dd>

                        <dt class="text-gris-400">Résultat</dt>
                        <dd class="font-medium text-gris-900">
                            {{ $enrollment->previous_year_result === 'non_applicable' ? 'Non applicable' : ucfirst(str_replace('_', ' ', (string) $enrollment->previous_year_result)) }}
                        </dd>

                        <dt class="text-gris-400">Moyenne</dt>
                        <dd class="font-medium text-gris-900">
                            {{ $enrollment->previous_year_average !== null ? number_format($enrollment->previous_year_average, 2, ',', ' ').'/20' : '—' }}
                        </dd>
                    </dl>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="carte">
                <div class="carte-entete">
                    <h3 class="text-sm font-semibold text-gris-900">Responsables</h3>
                </div>
                <div class="p-5">
                    @if ($eleve && $eleve->parents->isNotEmpty())
                        <div class="space-y-3">
                            @foreach ($eleve->parents as $parent)
                                <div class="flex items-start gap-3">
                                    <x-avatar :nom="$parent->full_name" class="h-9 w-9 shrink-0 text-[11px]"/>
                                    <div class="min-w-0">
                                        <a href="{{ route('parents.show', $parent->id) }}"
                                           class="block truncate text-sm font-medium text-gris-900 hover:text-ogar-700 hover:underline">
                                            {{ $parent->full_name }}
                                        </a>
                                        <div class="text-xs text-gris-400">
                                            {{ ucfirst($parent->pivot->relationship_type ?? 'responsable') }}
                                            @if ($parent->pivot->is_primary_contact) &middot; contact principal @endif
                                        </div>
                                        <div class="text-xs text-gris-500">{{ $parent->phone ?: '—' }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($enrollment->parent_first_name || $enrollment->parent_last_name)
                        {{-- Dossier sans élève créé : seules les coordonnées saisies au dépôt existent. --}}
                        <p class="text-xs font-semibold uppercase text-gris-400">Déclaré au dépôt du dossier</p>
                        <p class="mt-2 text-sm font-medium text-gris-900">
                            {{ trim($enrollment->parent_first_name.' '.$enrollment->parent_last_name) }}
                        </p>
                        <p class="text-xs text-gris-500">
                            {{ $enrollment->parent_relationship_label ?? $enrollment->parent_relationship ?? '—' }}
                            &middot; {{ $enrollment->parent_phone ?: '—' }}
                        </p>
                    @else
                        <p class="text-sm text-gris-400">Aucun responsable rattaché.</p>
                    @endif
                </div>
            </div>

            <div class="carte">
                <div class="carte-entete">
                    <h3 class="text-sm font-semibold text-gris-900">Règlement</h3>
                </div>
                <dl class="grid grid-cols-2 gap-y-2.5 p-5 text-sm">
                    <dt class="text-gris-400">Montant facturé</dt>
                    <dd class="text-right font-medium text-gris-900">{{ $franc($facture) }}</dd>

                    <dt class="text-gris-400">Encaissé</dt>
                    <dd class="text-right font-medium text-emerald-700">{{ $franc($encaisse) }}</dd>

                    <dt class="text-gris-400">Reste dû</dt>
                    <dd class="text-right font-semibold {{ $reste > 0 ? 'text-corail-700' : 'text-gris-400' }}">{{ $franc($reste) }}</dd>

                    <dt class="text-gris-400">Échéance</dt>
                    <dd class="text-right font-medium text-gris-900">
                        {{ $enrollment->payment_due_date ? \Carbon\Carbon::parse($enrollment->payment_due_date)->format('d/m/Y') : '—' }}
                    </dd>

                    <dt class="text-gris-400">Moyen</dt>
                    <dd class="text-right font-medium text-gris-900">
                        {{ $libellesMethode[$enrollment->payment_method] ?? ($enrollment->payment_method ?: '—') }}
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Paiements ---------------------------------------------------------- --}}
    <div x-show="onglet === 'paiements'" x-cloak class="carte overflow-hidden">
        <div class="carte-entete">
            <div>
                <h3 class="text-sm font-semibold text-gris-900">Paiements rattachés</h3>
                <p class="mt-0.5 text-xs text-gris-400">
                    {{ $franc($sommePaiements) }} soldés sur {{ $franc($facture) }} facturés
                </p>
            </div>
            <a href="{{ route('payments.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">Module Paiements</a>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>N° de reçu</th>
                        <th>Moyen</th>
                        <th class="text-right">Montant</th>
                        <th class="text-center">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paiements as $paiement)
                        <tr>
                            <td class="whitespace-nowrap text-gris-700">
                                {{ $paiement->paid_at ? \Carbon\Carbon::parse($paiement->paid_at)->format('d/m/Y') : $paiement->created_at?->format('d/m/Y') }}
                            </td>
                            <td class="font-mono text-xs text-gris-500">{{ $paiement->receipt_number ?: '—' }}</td>
                            <td class="text-gris-600">{{ $libellesMethode[$paiement->payment_method] ?? ($paiement->payment_method ?: '—') }}</td>
                            <td class="whitespace-nowrap text-right font-semibold text-gris-800">{{ $franc($paiement->amount) }}</td>
                            <td class="text-center">
                                <x-puce :couleur="$paiement->status === 'completed' ? 'emerald' : ($paiement->status === 'pending' ? 'amber' : 'rose')">
                                    {{ $paiement->status === 'completed' ? 'Soldé' : ucfirst($paiement->status) }}
                                </x-puce>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="5" message="Aucun paiement n’a encore été enregistré pour ce dossier."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Parcours ----------------------------------------------------------- --}}
    <div x-show="onglet === 'parcours'" x-cloak class="carte overflow-hidden">
        <div class="carte-entete">
            <div>
                <h3 class="text-sm font-semibold text-gris-900">Autres inscriptions de l’élève</h3>
                <p class="mt-0.5 text-xs text-gris-400">Toutes années confondues, hors dossier courant</p>
            </div>
            @if ($eleve)
                <a href="{{ route('students.show', $eleve->id) }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                    Fiche de l’élève
                </a>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Année</th>
                        <th>Classe</th>
                        <th class="whitespace-nowrap">Inscrit le</th>
                        <th class="text-right">Facturé</th>
                        <th class="text-center">Paiement</th>
                        <th class="text-right">Fiche</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($autresInscriptions as $autre)
                        <tr>
                            <td class="whitespace-nowrap font-medium text-gris-800">{{ $autre->academicYear->name ?? '—' }}</td>
                            <td class="text-gris-700">{{ $autre->schoolClass->name ?? '—' }}</td>
                            <td class="whitespace-nowrap text-gris-600">
                                {{ $autre->enrollment_date ? \Carbon\Carbon::parse($autre->enrollment_date)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="whitespace-nowrap text-right text-gris-700">{{ $franc($autre->total_fees) }}</td>
                            <td class="text-center">
                                <x-puce :couleur="$teintesPaiement[$autre->payment_status] ?? 'slate'">
                                    {{ $libellesPaiement[$autre->payment_status] ?? $autre->payment_status }}
                                </x-puce>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('enrollments.show', $autre->id) }}" class="bouton-mini">Consulter</a>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="6" message="C’est la seule inscription de cet élève."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
