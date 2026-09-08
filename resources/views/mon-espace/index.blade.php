@extends('layouts.app')

@section('titre', 'Tableau de bord')
@section('sous-titre', $eleve->first_name.' '.$eleve->last_name.' · '.(optional($inscription?->schoolClass)->name ?? 'sans classe').' — '.($annee->name ?? ''))

@section('actions-entete')
    <a href="{{ route('mon-espace.fiche') }}" class="bouton-secondaire">Ma fiche élève</a>
    <a href="{{ route('mon-espace.notes') }}" class="bouton-primaire">Mes notes</a>
@endsection

@section('contenu')

@php
    $montant = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';
    $heure = fn ($v) => substr((string) $v, 0, 5);

    $encre = fn ($m) => $m === null
        ? 'text-gris-400'
        : ($m >= 12 ? 'text-emerald-600' : ($m >= 10 ? 'text-soleil-600' : 'text-corail-600'));

    $barre = fn ($m) => $m >= 12 ? 'bg-emerald-500' : ($m >= 10 ? 'bg-soleil-500' : 'bg-corail-500');

    $libellesStatut = ['absent' => 'Absent', 'late' => 'Retard', 'excused' => 'Excusé'];
    $puceStatut = ['absent' => 'corail', 'late' => 'soleil', 'excused' => 'ogar'];
@endphp

    {{-- ----------------------------------------------------------------
         Les quatre chiffres qui résument mon année
         ---------------------------------------------------------------- --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Ma moyenne générale"
                       :valeur="$moyenneGenerale !== null ? number_format($moyenneGenerale, 2, ',', ' ') : '—'"
                       :detail="$nombreDeNotes.' note(s) sur l’année'" couleur="ogar"/>
        <x-statistique libelle="Taux de présence"
                       :valeur="$assiduite['taux'] !== null ? $assiduite['taux'].' %' : '—'"
                       :detail="$assiduite['total'].' jour(s) pointé(s)'" couleur="emerald"/>
        <x-statistique libelle="Mes absences" :valeur="$assiduite['absent']"
                       :detail="$assiduite['late'].' retard(s)'"
                       :couleur="$assiduite['absent'] > 0 ? 'corail' : 'emerald'"/>
        <x-statistique libelle="Reste à payer" :valeur="$montant($scolarite['reste'])"
                       :detail="$montant($scolarite['paye']).' réglés'"
                       :couleur="$scolarite['reste'] > 0 ? 'soleil' : 'emerald'"/>
    </div>

    <div class="mt-6 grid items-start gap-4 lg:grid-cols-3">

        {{-- ------------------------------------------------------------
             Ma journée
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden lg:col-span-2">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Ma journée</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        {{ $aujourdHui->locale('fr')->isoFormat('dddd D MMMM') }} — mes cours, dans l’ordre.
                    </p>
                </div>
                <a href="{{ route('mon-espace.emploi-du-temps') }}"
                   class="text-xs font-semibold text-ogar-600 hover:underline">Ma semaine</a>
            </div>

            <div class="divide-y divide-gris-100">
                @forelse ($duJour as $cours)
                    <div class="flex items-center gap-4 px-5 py-3">
                        <span class="w-20 shrink-0 text-sm font-semibold tabular-nums text-gris-800">
                            {{ $heure($cours->start_time) }}
                            <span class="block text-[11px] font-normal text-gris-400">{{ $heure($cours->end_time) }}</span>
                        </span>

                        <div class="min-w-0 flex-1">
                            @if ($cours->type === 'break')
                                <p class="truncate text-sm text-gris-500">{{ $cours->title ?: 'Récréation' }}</p>
                            @else
                                <p class="truncate font-medium text-gris-800">{{ $cours->subject->name ?? '—' }}</p>
                                <p class="truncate text-[11px] text-gris-500">
                                    @if ($cours->teacher)
                                        {{ $cours->teacher->last_name }} {{ $cours->teacher->first_name }}
                                    @endif
                                    @if ($cours->room) &middot; salle {{ $cours->room }} @endif
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="p-5 text-sm text-gris-400">Aucun cours ne figure à votre emploi du temps aujourd’hui.</p>
                @endforelse
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Ma fiche
             ------------------------------------------------------------ --}}
        <div class="carte p-5">
            <div class="flex items-center gap-3">
                <x-avatar :nom="$eleve->first_name.' '.$eleve->last_name" :photo="$eleve->photo ?? null"
                          class="h-14 w-14 shrink-0 text-sm"/>
                <div class="min-w-0">
                    <p class="truncate text-base font-semibold text-gris-900">
                        {{ $eleve->first_name }} {{ $eleve->last_name }}
                    </p>
                    <p class="font-mono text-[11px] text-gris-400">{{ $eleve->student_id }}</p>
                </div>
            </div>

            <dl class="mt-4 space-y-2 border-t border-gris-100 pt-4 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Classe</dt>
                    <dd class="font-medium text-gris-800">{{ optional($inscription?->schoolClass)->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Niveau</dt>
                    <dd class="font-medium text-gris-800">
                        {{ optional($inscription?->schoolClass?->level)->name ?? '—' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Né(e) le</dt>
                    <dd class="font-medium text-gris-800">
                        {{ $eleve->date_of_birth ? \Carbon\Carbon::parse($eleve->date_of_birth)->format('d/m/Y') : '—' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Année scolaire</dt>
                    <dd class="font-medium text-gris-800">{{ $annee->name ?? '—' }}</dd>
                </div>
            </dl>

            <a href="{{ route('mon-espace.fiche') }}" class="bouton-secondaire mt-4 w-full justify-center">
                Voir et télécharger ma fiche
            </a>
        </div>
    </div>

    <div class="mt-4 grid items-start gap-4 lg:grid-cols-2">

        {{-- ------------------------------------------------------------
             Mes meilleures matières
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Où j’en suis, par matière</h2>
                    <p class="mt-0.5 text-xs text-gris-400">Notes ramenées sur 20.</p>
                </div>
                <a href="{{ route('mon-espace.notes') }}"
                   class="text-xs font-semibold text-ogar-600 hover:underline">Toutes mes notes</a>
            </div>

            <div class="space-y-3 p-5">
                @forelse ($parMatiere as $ligne)
                    <div class="flex items-center gap-3">
                        <span class="w-40 shrink-0 truncate text-sm text-gris-700">{{ $ligne['matiere'] }}</span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-gris-100">
                            <div class="h-full rounded-full {{ $barre($ligne['moyenne']) }}"
                                 style="width: {{ min(100, round($ligne['moyenne'] / 20 * 100)) }}%"></div>
                        </div>
                        <span class="w-14 shrink-0 text-right text-sm font-semibold tabular-nums {{ $encre($ligne['moyenne']) }}">
                            {{ number_format($ligne['moyenne'], 2, ',', ' ') }}
                        </span>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-gris-400">
                        Aucune note n’a encore été saisie pour vous.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Mes dernières absences
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Mes dernières absences</h2>
                <a href="{{ route('mon-espace.absences') }}"
                   class="text-xs font-semibold text-ogar-600 hover:underline">Tout voir</a>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Situation</th>
                            <th>Motif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dernieresAbsences as $ligne)
                            <tr>
                                <td class="whitespace-nowrap text-gris-700">
                                    {{ \Carbon\Carbon::parse($ligne->attendance_date)->locale('fr')->isoFormat('ddd D MMM') }}
                                </td>
                                <td>
                                    <x-puce :couleur="$puceStatut[$ligne->status] ?? 'slate'">
                                        {{ $libellesStatut[$ligne->status] ?? $ligne->status }}
                                    </x-puce>
                                </td>
                                <td class="text-gris-600">{{ $ligne->reason ?: '—' }}</td>
                            </tr>
                        @empty
                            <x-vide :colonnes="3" message="Aucune absence ni retard : continuez ainsi."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
