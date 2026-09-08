@extends('layouts.app')

@section('titre', 'Tableau de bord')
@section('sous-titre', $parent->first_name.' '.$parent->last_name.' · '.$children->count().' enfant(s) — '.($annee->name ?? ''))

@section('actions-entete')
    <a href="{{ route('parent-portal.payment-history') }}" class="bouton-secondaire">Mes paiements</a>
    <a href="{{ route('parent-portal.children') }}" class="bouton-primaire">Mes enfants</a>
@endsection

@section('contenu')

@php
    $franc = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';

    $encre = fn ($m) => $m === null
        ? 'text-gris-400'
        : ($m >= 12 ? 'text-emerald-600' : ($m >= 10 ? 'text-soleil-600' : 'text-corail-600'));

    $barre = fn ($m) => $m >= 12 ? 'bg-emerald-500' : ($m >= 10 ? 'bg-soleil-500' : 'bg-corail-500');

    $libellesStatut = ['absent' => 'Absent', 'late' => 'Retard', 'excused' => 'Excusé'];
    $puceStatut = ['absent' => 'corail', 'late' => 'soleil', 'excused' => 'ogar'];

    $libellesPaiement = [
        'completed' => 'Réglé', 'pending' => 'En attente',
        'failed' => 'Échoué', 'cancelled' => 'Annulé',
    ];
@endphp

    {{-- ----------------------------------------------------------------
         Ce qu'un parent veut savoir en ouvrant l'application
         ---------------------------------------------------------------- --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Mes enfants" :valeur="$stats['total_children']"
                       :detail="$stats['active_enrollments'].' scolarisé(s) cette année'" couleur="ogar"/>
        <x-statistique libelle="Moyenne de la fratrie"
                       :valeur="$bilan['moyenne'] !== null ? number_format($bilan['moyenne'], 2, ',', ' ') : '—'"
                       detail="Toutes matières, sur 20"
                       :couleur="($bilan['moyenne'] ?? 0) >= 10 ? 'emerald' : 'corail'"/>
        <x-statistique libelle="Absences du mois" :valeur="$bilan['absences']"
                       detail="Tous mes enfants"
                       :couleur="$bilan['absences'] > 0 ? 'corail' : 'emerald'"/>
        <x-statistique libelle="Reste à payer" :valeur="$franc($bilan['reste'])"
                       :detail="$franc($bilan['paye']).' déjà réglés'"
                       :couleur="$bilan['reste'] > 0 ? 'soleil' : 'emerald'"/>
    </div>

    @if ($bilan['reste'] > 0)
        <div class="mt-4 flex items-start gap-3 rounded-xl border border-soleil-200 bg-soleil-50 px-4 py-3 text-sm text-soleil-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span>
                <strong>{{ $franc($bilan['reste']) }} restent à régler</strong>
                sur la scolarité de vos enfants. Le détail figure sur la fiche de chacun.
            </span>
        </div>
    @endif

    <div class="mt-6 grid items-start gap-4 lg:grid-cols-3">

        {{-- ------------------------------------------------------------
             Mes enfants, en un coup d'œil
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden lg:col-span-2">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Mes enfants</h2>
                    <p class="mt-0.5 text-xs text-gris-400">Leur classe, leur moyenne et leur assiduité.</p>
                </div>
                <a href="{{ route('parent-portal.children') }}"
                   class="text-xs font-semibold text-ogar-600 hover:underline">Voir les fiches</a>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Enfant</th>
                            <th>Classe</th>
                            <th class="text-center">Moyenne</th>
                            <th class="text-center">Absences</th>
                            <th class="text-right">Reste à payer</th>
                            <th class="text-right">Dossier</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($children as $enfant)
                            @php($fiche = $fiches[$enfant->id] ?? [])

                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-avatar :nom="$enfant->first_name.' '.$enfant->last_name"
                                                  :photo="$enfant->photo ?? null" taille="h-8 w-8"/>
                                        <span class="font-medium text-gris-800">
                                            {{ $enfant->first_name }} {{ $enfant->last_name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-gris-600">{{ $fiche['classe'] ?? '—' }}</td>
                                <td class="text-center font-semibold tabular-nums {{ $encre($fiche['moyenne'] ?? null) }}">
                                    {{ ($fiche['moyenne'] ?? null) !== null ? number_format($fiche['moyenne'], 2, ',', ' ') : '—' }}
                                </td>
                                <td class="text-center tabular-nums {{ ($fiche['absences'] ?? 0) > 0 ? 'text-corail-600' : 'text-gris-500' }}">
                                    {{ $fiche['absences'] ?? 0 }}
                                </td>
                                <td class="text-right tabular-nums {{ ($fiche['reste'] ?? 0) > 0 ? 'font-semibold text-soleil-700' : 'text-gris-500' }}">
                                    {{ $franc($fiche['reste'] ?? 0) }}
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('parent-portal.child-details', $enfant->id) }}"
                                       class="bouton-mini">Ouvrir</a>
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="6" message="Aucun enfant n’est rattaché à votre compte."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Les dernières absences
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Dernières absences</h2>
                <span class="text-xs text-gris-400">{{ $dernieresAbsences->count() }}</span>
            </div>

            <div class="divide-y divide-gris-100">
                @forelse ($dernieresAbsences as $ligne)
                    <div class="flex items-center gap-3 px-5 py-2.5">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gris-800">
                                {{ optional($children->firstWhere('id', $ligne->student_id))->first_name ?? 'Élève' }}
                            </p>
                            <p class="text-[11px] text-gris-400">
                                {{ \Carbon\Carbon::parse($ligne->attendance_date)->locale('fr')->isoFormat('ddd D MMM') }}
                                @if ($ligne->reason) &middot; {{ $ligne->reason }} @endif
                            </p>
                        </div>
                        <x-puce :couleur="$puceStatut[$ligne->status] ?? 'slate'">
                            {{ $libellesStatut[$ligne->status] ?? $ligne->status }}
                        </x-puce>
                    </div>
                @empty
                    <p class="p-5 text-sm text-gris-400">
                        Aucune absence ni retard cette année : rien à signaler.
                    </p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-4 grid items-start gap-4 lg:grid-cols-2">

        {{-- ------------------------------------------------------------
             Les dernières notes
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Dernières notes</h2>
                <span class="text-xs text-gris-400">{{ $dernieresNotes->count() }} récente(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Enfant</th>
                            <th>Matière</th>
                            <th class="text-center">Note</th>
                            <th class="text-center">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dernieresNotes as $note)
                            @php($sur20 = $note->max_score > 0 ? $note->score / $note->max_score * 20 : null)

                            <tr>
                                <td class="text-gris-700">
                                    {{ optional($children->firstWhere('id', $note->student_id))->first_name ?? '—' }}
                                </td>
                                <td class="font-medium text-gris-800">{{ $note->subject->name ?? '—' }}</td>
                                <td class="text-center font-semibold tabular-nums {{ $encre($sur20) }}">
                                    {{ $sur20 !== null ? number_format($sur20, 2, ',', ' ') : '—' }}
                                </td>
                                <td class="text-center tabular-nums text-gris-500">
                                    {{ $note->created_at?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="4" message="Aucune note n’a encore été saisie."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Mes derniers paiements
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Mes derniers paiements</h2>
                <a href="{{ route('parent-portal.payment-history') }}"
                   class="text-xs font-semibold text-ogar-600 hover:underline">Tout l’historique</a>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Enfant</th>
                            <th class="text-right">Montant</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentPayments as $paiement)
                            <tr>
                                <td class="text-gris-700">
                                    {{ optional($children->firstWhere('id', $paiement->student_id))->first_name ?? '—' }}
                                </td>
                                <td class="text-right font-semibold tabular-nums text-gris-800">
                                    {{ $franc($paiement->amount) }}
                                </td>
                                <td class="text-center">
                                    <x-puce :couleur="$paiement->status === 'completed' ? 'emerald' : 'soleil'">
                                        {{ $libellesPaiement[$paiement->status] ?? ucfirst((string) $paiement->status) }}
                                    </x-puce>
                                </td>
                                <td class="text-center tabular-nums text-gris-500">
                                    {{ $paiement->created_at?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="4" message="Aucun versement enregistré."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
