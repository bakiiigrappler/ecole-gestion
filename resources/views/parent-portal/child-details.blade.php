@extends('layouts.app')

@section('titre', $student->first_name.' '.$student->last_name)
@section('sous-titre', (optional($currentEnrollment?->schoolClass)->name ?? 'Sans classe').' — '.($annee->name ?? ''))

@section('actions-entete')
    <a href="{{ route('parent-portal.dashboard') }}" class="bouton-secondaire">Mes enfants</a>
    @if ($notes->isNotEmpty())
        <a href="{{ route('grades.bulletin', $student->id) }}" class="bouton-primaire">Son bulletin</a>
    @endif
@endsection

@section('contenu')

@php
    $onglets = [
        'scolarite' => 'Scolarité',
        'emploi' => 'Emploi du temps',
        'notes' => 'Notes',
        'assiduite' => 'Assiduité',
        'paiements' => 'Paiements',
    ];

    $onglet = array_key_exists($onglet, $onglets) ? $onglet : 'scolarite';

    $statuts = [
        'absent' => ['libelle' => 'Absent', 'couleur' => 'corail'],
        'late' => ['libelle' => 'Retard', 'couleur' => 'soleil'],
        'excused' => ['libelle' => 'Excusé', 'couleur' => 'ogar'],
        'present' => ['libelle' => 'Présent', 'couleur' => 'emerald'],
    ];

    $du = (float) ($currentEnrollment->total_fees ?? 0);
    $paye = (float) ($currentEnrollment->amount_paid ?? 0);
    $franc = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';
@endphp

    {{-- ----------------------------------------------------------------
         L'essentiel, avant le détail
         ---------------------------------------------------------------- --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Moyenne générale"
                       :valeur="$moyenneGenerale !== null ? number_format($moyenneGenerale, 2, ',', ' ').' / 20' : '—'"
                       :detail="$notes->count().' note(s) cette année'" couleur="ogar"/>
        <x-statistique libelle="Taux de présence"
                       :valeur="$assiduite['taux'] !== null ? $assiduite['taux'].' %' : '—'"
                       :detail="$assiduite['total'].' journée(s) pointée(s)'" couleur="emerald"/>
        <x-statistique libelle="Absences" :valeur="$assiduite['absent']"
                       :detail="$assiduite['late'].' retard(s)'" couleur="corail"/>
        <x-statistique libelle="Reste à payer" :valeur="$franc(max(0, $du - $paye))"
                       :detail="$franc($paye).' réglés'" :couleur="$du - $paye > 0 ? 'soleil' : 'emerald'"/>
    </div>

    {{-- ----------------------------------------------------------------
         La fiche de l'enfant, onglet par onglet
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 overflow-hidden">
        <div class="flex flex-wrap gap-1 border-b border-gris-100 bg-gris-50 px-3 pt-3">
            @foreach ($onglets as $cle => $libelle)
                <a href="{{ route('parent-portal.child-details', [$student->id, 'onglet' => $cle]) }}"
                   class="rounded-t-lg border border-b-0 px-4 py-2 text-sm font-medium transition
                          {{ $onglet === $cle
                             ? 'border-gris-200 bg-white text-ogar-700'
                             : 'border-transparent text-gris-500 hover:text-gris-800' }}">
                    {{ $libelle }}
                </a>
            @endforeach
        </div>

        {{-- ---------------------------------------------------------- --}}
        @if ($onglet === 'scolarite')
            <div class="grid gap-6 p-5 lg:grid-cols-3">
                <div>
                    <div class="flex items-center gap-3">
                        <x-avatar :nom="$student->first_name.' '.$student->last_name"
                                  :photo="$student->photo ?? null" class="h-14 w-14 shrink-0 text-sm"/>
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold text-gris-900">
                                {{ $student->first_name }} {{ $student->last_name }}
                            </p>
                            <p class="font-mono text-[11px] text-gris-400">{{ $student->student_id }}</p>
                        </div>
                    </div>

                    <dl class="mt-4 space-y-2 border-t border-gris-100 pt-4 text-sm">
                        <div class="flex justify-between gap-2">
                            <dt class="text-gris-500">Classe</dt>
                            <dd class="font-medium text-gris-800">{{ optional($currentEnrollment?->schoolClass)->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gris-500">Niveau</dt>
                            <dd class="font-medium text-gris-800">{{ optional($currentEnrollment?->schoolClass?->level)->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gris-500">Né(e) le</dt>
                            <dd class="font-medium text-gris-800">
                                {{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gris-500">Lieu de naissance</dt>
                            <dd class="font-medium text-gris-800">{{ $student->place_of_birth ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gris-500">Statut</dt>
                            <dd class="font-medium text-gris-800">{{ $student->current_status ?: $student->status }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="lg:col-span-2">
                    <h3 class="text-sm font-semibold text-gris-900">Frais de scolarité</h3>

                    <div class="mt-3 grid grid-cols-3 gap-px overflow-hidden rounded-xl border border-gris-200 bg-gris-200 text-center">
                        <div class="bg-white px-3 py-4">
                            <p class="text-[11px] uppercase tracking-wide text-gris-400">Dû</p>
                            <p class="mt-1 text-base font-bold tabular-nums text-gris-900">{{ $franc($du) }}</p>
                        </div>
                        <div class="bg-white px-3 py-4">
                            <p class="text-[11px] uppercase tracking-wide text-gris-400">Réglé</p>
                            <p class="mt-1 text-base font-bold tabular-nums text-emerald-600">{{ $franc($paye) }}</p>
                        </div>
                        <div class="bg-white px-3 py-4">
                            <p class="text-[11px] uppercase tracking-wide text-gris-400">Reste</p>
                            <p class="mt-1 text-base font-bold tabular-nums {{ $du - $paye > 0 ? 'text-corail-600' : 'text-gris-900' }}">
                                {{ $franc(max(0, $du - $paye)) }}
                            </p>
                        </div>
                    </div>

                    <h3 class="mt-6 text-sm font-semibold text-gris-900">Moyennes par matière</h3>

                    <div class="mt-3 space-y-2">
                        @forelse ($parMatiere as $ligne)
                            <div class="flex items-center gap-3">
                                <span class="w-44 shrink-0 truncate text-sm text-gris-700">{{ $ligne['matiere'] }}</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-gris-100">
                                    <div class="h-full rounded-full {{ $ligne['moyenne'] >= 10 ? 'bg-emerald-500' : 'bg-corail-500' }}"
                                         style="width: {{ min(100, $ligne['moyenne'] / 20 * 100) }}%"></div>
                                </div>
                                <span class="w-16 shrink-0 text-right text-sm font-semibold tabular-nums text-gris-800">
                                    {{ number_format($ligne['moyenne'], 2, ',', ' ') }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-gris-400">Aucune note enregistrée cette année.</p>
                        @endforelse
                    </div>
                </div>
            </div>

        {{-- ---------------------------------------------------------- --}}
        @elseif ($onglet === 'emploi')
            <div class="p-5">
                <x-emploi-du-temps :creneaux="$creneaux"
                                   titre="Emploi du temps"
                                   :sous-titre="$student->first_name.' '.$student->last_name.' · '.(optional($currentEnrollment?->schoolClass)->name ?? '')"
                                   :annee="$annee"
                                   complement="enseignant"
                                   :fichier="'Emploi_du_temps_'.\Illuminate\Support\Str::slug($student->last_name).'.pdf'"/>
            </div>

        {{-- ---------------------------------------------------------- --}}
        @elseif ($onglet === 'notes')
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Matière</th>
                            <th>Évaluation</th>
                            <th class="text-center">Trimestre</th>
                            <th class="text-center">Note</th>
                            <th class="text-center">Sur 20</th>
                            <th>Enseignant</th>
                            <th class="text-center">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($notes as $note)
                            @php($sur20 = $note->max_score > 0 ? $note->score / $note->max_score * 20 : null)

                            <tr>
                                <td class="font-medium text-gris-800">{{ $note->subject->name ?? '—' }}</td>
                                <td class="text-gris-600">{{ $note->evaluation_type ?: $note->title ?: '—' }}</td>
                                <td class="text-center text-gris-600">{{ $note->term ?? $note->trimester ?? '—' }}</td>
                                <td class="text-center tabular-nums text-gris-700">
                                    {{ rtrim(rtrim(number_format((float) $note->score, 2, ',', ' '), '0'), ',') }}
                                    <span class="text-gris-400">/ {{ (int) $note->max_score }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="font-semibold tabular-nums {{ $sur20 !== null && $sur20 < 10 ? 'text-corail-600' : 'text-emerald-600' }}">
                                        {{ $sur20 !== null ? number_format($sur20, 2, ',', ' ') : '—' }}
                                    </span>
                                </td>
                                <td class="text-gris-600">
                                    {{ $note->teacher ? $note->teacher->last_name.' '.$note->teacher->first_name : '—' }}
                                </td>
                                <td class="text-center tabular-nums text-gris-500">
                                    {{ $note->created_at?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="7" message="Aucune note enregistrée cette année."/>
                        @endforelse
                    </tbody>
                </table>
            </div>

        {{-- ---------------------------------------------------------- --}}
        @elseif ($onglet === 'assiduite')
            <div class="grid grid-cols-2 gap-px border-b border-gris-100 bg-gris-100 sm:grid-cols-4">
                @foreach (['present' => 'Présences', 'absent' => 'Absences', 'late' => 'Retards', 'excused' => 'Excusées'] as $cle => $libelle)
                    <div class="bg-white px-4 py-3 text-center">
                        <p class="text-lg font-bold tabular-nums text-gris-900">{{ $assiduite[$cle] }}</p>
                        <p class="text-[11px] uppercase tracking-wide text-gris-400">{{ $libelle }}</p>
                    </div>
                @endforeach
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Justifié</th>
                            <th>Motif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($absences as $ligne)
                            @php($statut = $statuts[$ligne->status] ?? ['libelle' => $ligne->status, 'couleur' => 'gris'])

                            <tr>
                                <td class="tabular-nums text-gris-700">
                                    {{ \Carbon\Carbon::parse($ligne->attendance_date)->locale('fr')->isoFormat('ddd D MMM YYYY') }}
                                </td>
                                <td class="text-center">
                                    <x-puce :couleur="$statut['couleur']">{{ $statut['libelle'] }}</x-puce>
                                </td>
                                <td class="text-center text-gris-600">{{ $ligne->justified ? 'Oui' : 'Non' }}</td>
                                <td class="text-gris-600">{{ $ligne->reason ?: '—' }}</td>
                            </tr>
                        @empty
                            <x-vide :colonnes="4" message="Aucune absence ni retard : assiduité parfaite."/>
                        @endforelse
                    </tbody>
                </table>
            </div>

        {{-- ---------------------------------------------------------- --}}
        @else
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th class="text-right">Montant</th>
                            <th>Moyen</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Date</th>
                            <th class="text-right">Reçu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paiements as $paiement)
                            <tr>
                                <td class="font-mono text-xs text-gris-600">{{ $paiement->transaction_id ?? $paiement->id }}</td>
                                <td class="text-right font-semibold tabular-nums text-gris-800">{{ $franc($paiement->amount) }}</td>
                                <td class="text-gris-600">{{ $paiement->payment_method ?: '—' }}</td>
                                <td class="text-center">
                                    <x-puce :couleur="$paiement->status === 'completed' ? 'emerald' : 'soleil'">
                                        {{ $paiement->status === 'completed' ? 'Réglé' : ucfirst((string) $paiement->status) }}
                                    </x-puce>
                                </td>
                                <td class="text-center tabular-nums text-gris-500">
                                    {{ optional($paiement->paid_at ?? $paiement->created_at)->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('payments.receipt', $paiement->id) }}" class="bouton-mini">Reçu</a>
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="6" message="Aucun paiement enregistré pour cet enfant."/>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($enLigne->isNotEmpty())
                <div class="border-t border-gris-100 px-5 py-3 text-xs text-gris-400">
                    {{ $enLigne->count() }} paiement(s) en ligne également enregistrés.
                </div>
            @endif
        @endif
    </div>

@endsection
