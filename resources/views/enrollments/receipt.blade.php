@extends('layouts.app')

@section('titre', 'Reçu d’inscription')
@section('sous-titre', $enrollment->receipt_number.' · '.trim(($eleve['prenom'] ?? '').' '.($eleve['nom'] ?? '')))

@section('actions-entete')
    <button type="button"
            class="bouton-primaire"
            data-export-pdf="recu-inscription"
            data-page-unique
            data-nom-fichier="Recu_{{ $enrollment->receipt_number }}.pdf">
        Télécharger le reçu
    </button>
    <a href="{{ route('enrollments.show', $enrollment->id) }}" class="bouton-secondaire">L’inscription</a>
@endsection

@section('contenu')

@php
    $montant = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '—';

    $du = (float) $enrollment->total_fees;
    $paye = (float) $enrollment->amount_paid;
    $reste = max(0, $du - $paye);
    $solde = $reste <= 0;

    $moyens = [
        'cash' => 'Espèces', 'bank_transfer' => 'Virement bancaire', 'check' => 'Chèque',
        'mobile_money' => 'Mobile money', 'card' => 'Carte bancaire',
    ];

    $statuts = [
        'paid' => ['libelle' => 'Soldé', 'ton' => 'emerald'],
        'partial' => ['libelle' => 'Partiel', 'ton' => 'soleil'],
        'pending' => ['libelle' => 'En attente', 'ton' => 'corail'],
        'unpaid' => ['libelle' => 'Impayé', 'ton' => 'corail'],
    ];

    $statut = $statuts[$enrollment->payment_status] ?? ['libelle' => ucfirst((string) $enrollment->payment_status), 'ton' => 'gris'];

@endphp

    {{-- ------------------------------------------------------------------
         Le document. Ce bloc est celui que html2canvas photographie :
         le PDF est exactement ce qui s'affiche ici.
         ------------------------------------------------------------------ --}}
    <div id="recu-inscription" class="mx-auto max-w-3xl bg-white p-8 text-gris-900 ring-1 ring-gris-200">

        {{-- En-tête officiel --}}
        <div class="flex items-start justify-between gap-4 border-b-2 border-gris-800 pb-3">
            <div class="flex items-start gap-3">
                @if ($schoolSettings->logo_url ?? null)
                    <img src="{{ $schoolSettings->logo_url }}" alt="Logo de l’établissement"
                         class="h-14 w-14 shrink-0 object-contain">
                @else
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                        Logo<br>établissement
                    </span>
                @endif
                <div class="leading-tight">
                    <p class="text-[10px] text-gris-600">Ministère de l’Éducation Nationale</p>
                    <p class="text-sm font-bold uppercase">{{ $schoolName }}</p>
                    <p class="text-[9px] text-gris-500">
                        @if ($schoolSettings->school_bp ?? null) {{ $schoolSettings->school_bp }} @endif
                        @if ($schoolSettings->school_phone ?? null) &middot; Tél : {{ $schoolSettings->school_phone }} @endif
                        @if ($schoolSettings->school_email ?? null) &middot; {{ $schoolSettings->school_email }} @endif
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="text-right leading-tight">
                    <p class="text-[10px] uppercase tracking-wide text-gris-500">Année scolaire</p>
                    <p class="text-sm font-semibold">{{ $enrollment->academicYear->name ?? '—' }}</p>
                </div>

                @if ($schoolSettings->seal_url ?? null)
                    <img src="{{ $schoolSettings->seal_url }}" alt="Sceau de la République"
                         class="h-14 w-16 shrink-0 object-contain">
                @else
                    <span class="flex h-14 w-16 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                        Sceau de la<br>République
                    </span>
                @endif
            </div>
        </div>

        {{-- Titre et référence --}}
        <div class="mt-5 flex items-end justify-between gap-4">
            <div>
                <h1 class="text-lg font-bold uppercase tracking-wide">Reçu d’inscription</h1>
                <p class="mt-0.5 font-mono text-xs text-gris-500">N° {{ $enrollment->receipt_number }}</p>
            </div>

            <div class="text-right text-[11px] leading-tight">
                <p class="text-gris-500">Délivré le</p>
                <p class="font-semibold tabular-nums">{{ $date($enrollment->enrollment_date) }}</p>
            </div>
        </div>

        {{-- Le montant, en évidence : c'est ce qu'on vient vérifier --}}
        <div class="mt-4 border-y-2 border-gris-800 py-4">
            <div class="flex items-end justify-between gap-6">
                <div>
                    <p class="text-[10px] uppercase tracking-wide text-gris-500">Montant reçu</p>
                    <p class="text-3xl font-bold tabular-nums">{{ $montant($paye) }}</p>
                    <p class="mt-1 max-w-md text-[11px] italic leading-snug text-gris-600">
                        {{ \App\Support\SommeEnLettres::francs($paye) }}
                    </p>
                </div>

                <div class="shrink-0 text-right">
                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-wide
                                 {{ $solde
                                    ? 'border-emerald-600 text-emerald-700'
                                    : 'border-soleil-600 text-soleil-700' }}">
                        {{ $statut['libelle'] }}
                    </span>
                    <p class="mt-1.5 text-[10px] text-gris-500">
                        {{ $moyens[$enrollment->payment_method] ?? ($enrollment->payment_method ?: 'Non précisé') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Élève et responsable --}}
        <div class="mt-5 grid grid-cols-2 gap-6">
            <div>
                <h2 class="mb-2 border-b border-gris-300 pb-1 text-[11px] font-bold uppercase tracking-wide text-gris-600">
                    Élève inscrit
                </h2>

                <dl class="space-y-1.5 text-[11px]">
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Nom et prénoms</dt>
                        <dd class="text-right font-semibold">{{ $eleve['nom'] }} {{ $eleve['prenom'] }}</dd>
                    </div>
                    @if ($eleve['matricule'])
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                            <dt class="text-gris-500">Matricule</dt>
                            <dd class="font-mono font-medium">{{ $eleve['matricule'] }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Né(e) le</dt>
                        <dd class="font-medium tabular-nums">{{ $date($eleve['naissance']) }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Classe</dt>
                        <dd class="font-semibold">{{ $enrollment->schoolClass->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Niveau</dt>
                        <dd class="font-medium">{{ $enrollment->schoolClass->level->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Type</dt>
                        <dd class="font-medium">
                            {{ $enrollment->is_reinscription ? 'Réinscription' : 'Nouvelle inscription' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div>
                <h2 class="mb-2 border-b border-gris-300 pb-1 text-[11px] font-bold uppercase tracking-wide text-gris-600">
                    Parent ou tuteur
                </h2>

                @if ($responsable)
                    <dl class="space-y-1.5 text-[11px]">
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                            <dt class="text-gris-500">Nom et prénoms</dt>
                            <dd class="text-right font-semibold">{{ $responsable['nom'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                            <dt class="text-gris-500">Lien de parenté</dt>
                            <dd class="font-medium">{{ $responsable['lien'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                            <dt class="text-gris-500">Téléphone</dt>
                            <dd class="font-medium tabular-nums">{{ $responsable['telephone'] ?: '—' }}</dd>
                        </div>
                        @if ($responsable['email'])
                            <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                                <dt class="text-gris-500">Adresse e-mail</dt>
                                <dd class="truncate font-medium">{{ $responsable['email'] }}</dd>
                            </div>
                        @endif
                    </dl>
                @else
                    <p class="text-[11px] italic text-gris-400">
                        Aucun parent ni tuteur n’est rattaché à ce dossier.
                    </p>
                @endif
            </div>
        </div>

        {{-- Le compte des frais --}}
        <h2 class="mb-2 mt-6 border-b border-gris-300 pb-1 text-[11px] font-bold uppercase tracking-wide text-gris-600">
            Détail des frais
        </h2>

        <table class="w-full border-collapse text-[11px]">
            <tbody>
                <tr>
                    <td class="border border-gris-400 px-3 py-2">Frais de scolarité de l’année</td>
                    <td class="w-40 border border-gris-400 px-3 py-2 text-right tabular-nums">{{ $montant($du) }}</td>
                </tr>
                <tr>
                    <td class="border border-gris-400 px-3 py-2 font-semibold">Versé à ce jour</td>
                    <td class="border border-gris-400 px-3 py-2 text-right font-semibold tabular-nums">{{ $montant($paye) }}</td>
                </tr>
                <tr>
                    <td class="border border-gris-400 bg-gris-100 px-3 py-2 font-bold uppercase tracking-wide">
                        Reste à percevoir
                    </td>
                    <td class="border border-gris-400 bg-gris-100 px-3 py-2 text-right text-sm font-bold tabular-nums">
                        {{ $montant($reste) }}
                    </td>
                </tr>
            </tbody>
        </table>

        @unless ($solde)
            <p class="mt-2 text-[10px] text-gris-600">
                Le solde de <span class="font-semibold">{{ $montant($reste) }}</span> reste dû
                @if ($enrollment->payment_due_date)
                    au plus tard le <span class="font-semibold tabular-nums">{{ $date($enrollment->payment_due_date) }}</span>.
                @else
                    auprès du service de la comptabilité.
                @endif
            </p>
        @endunless

        @if ($enrollment->payment_reference)
            <p class="mt-2 text-[10px] text-gris-500">
                Référence du versement : <span class="font-mono">{{ $enrollment->payment_reference }}</span>
            </p>
        @endif

        {{-- Signatures --}}
        <div class="mt-10 grid grid-cols-2 gap-10 text-[10px]">
            <div class="text-center">
                <p class="text-gris-600">Le parent ou tuteur</p>
                <p class="mt-12 border-t border-gris-400 pt-1 text-gris-400">Signature</p>
            </div>
            <div class="text-center">
                <p class="text-gris-600">{{ $schoolSettings->principal_title ?? 'Le Chef d’établissement' }}</p>
                <p class="mt-12 border-t border-gris-400 pt-1 text-gris-400">Signature et cachet</p>
            </div>
        </div>

        {{-- Pied --}}
        <div class="mt-6 border-t border-gris-200 pt-2 text-[9px] leading-relaxed text-gris-500">
            <p>
                Ce reçu atteste du versement porté ci-dessus pour l’année scolaire
                {{ $enrollment->academicYear->name ?? '—' }}. Il est à conserver : sa présentation peut être
                exigée à tout moment de la scolarité.
            </p>
            <p class="mt-1">Édité le {{ now()->locale('fr')->isoFormat('D MMMM YYYY à HH:mm') }}.</p>
        </div>
    </div>

@endsection
