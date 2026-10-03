@extends('layouts.app')

@section('titre', ($dossier->student->first_name ?? '').' '.($dossier->student->last_name ?? ''))
@section('sous-titre', (\App\Models\OrientationDossier::NIVEAUX[$dossier->niveau] ?? $dossier->niveau)
    .' · '.$dossier->libelle_statut)

@section('actions-entete')
    <a href="{{ route('orientation.index') }}" class="bouton-secondaire">Tous les dossiers</a>
    @if ($dossier->student)
        <a href="{{ route('students.show', $dossier->student) }}" class="bouton-secondaire">La fiche élève</a>
    @endif
@endsection

@section('contenu')

@php
    $note = fn ($v) => \App\Support\Orientation\ProfilEleve::nombre($v);
    $fige = $dossier->moyennes ?? [];
    $ecart = ($fige['generale'] ?? null) !== null && ($profilActuel['generale'] ?? null) !== null
        ? round($profilActuel['generale'] - $fige['generale'], 2)
        : null;
@endphp

<div class="grid gap-6 lg:grid-cols-3">

    <div class="space-y-4 lg:col-span-2">

        {{-- Ce que l'élève demande --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Le vœu</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        Transmis le {{ optional($dossier->soumis_le)->format('d/m/Y à H:i') ?: '—' }}
                    </p>
                </div>
                <x-puce :couleur="match ($dossier->statut) {
                    'accorde' => 'emerald', 'refuse' => 'rose', 'soumis' => 'amber', default => 'slate',
                }">{{ $dossier->libelle_statut }}</x-puce>
            </div>

            <dl class="grid gap-px border-b border-gris-100 bg-gris-100 sm:grid-cols-2">
                <div class="bg-white px-5 py-3">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Voie</dt>
                    <dd class="mt-0.5 text-sm font-medium text-gris-900">
                        {{ $dossier->niveau === 'terminale'
                            ? 'Enseignement supérieur'
                            : \App\Support\Orientation\VoiesApresTroisieme::libelle($dossier->voie) }}
                    </dd>
                </div>
                <div class="bg-white px-5 py-3">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Filière demandée</dt>
                    <dd class="mt-0.5 text-sm font-medium text-gris-900">{{ $dossier->filiere ?: '—' }}</dd>
                    @if ($dossier->mode_admission)
                        <dd class="text-[11px] text-gris-500">
                            {{ \App\Support\Orientation\Conseil::libelleModeAdmission($dossier->mode_admission) }}
                        </dd>
                    @endif
                </div>
            </dl>

            <ol class="divide-y divide-gris-100">
                @forelse ($voeux as $voeu)
                    <li class="flex items-start gap-3 px-5 py-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gris-100
                                     text-[11px] font-bold text-gris-600">{{ $voeu['rang'] }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gris-900">{{ $voeu['nom'] }}</p>
                            @if ($voeu['etablissement'])
                                <p class="text-[11px] text-gris-500">
                                    {{ $voeu['etablissement']->libelle_type }}
                                    @if ($voeu['etablissement']->ville) · {{ $voeu['etablissement']->ville }} @endif
                                    @if ($voeu['etablissement']->places_libres !== null)
                                        · {{ $voeu['etablissement']->est_complet
                                                ? 'complet'
                                                : $voeu['etablissement']->places_libres.' place(s)' }}
                                    @endif
                                </p>
                            @endif
                            @if ($voeu['filiere'])
                                <p class="text-[11px] text-gris-500">Filière : {{ $voeu['filiere'] }}</p>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-gris-500">Aucun établissement demandé.</li>
                @endforelse
            </ol>

            @if ($dossier->commentaire_eleve)
                <div class="border-t border-gris-100 bg-gris-50 px-5 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Mot de l’élève</p>
                    <p class="mt-1 text-sm leading-relaxed text-gris-700">{{ $dossier->commentaire_eleve }}</p>
                </div>
            @endif
        </div>

        {{-- Le profil retenu, et celui d'aujourd'hui --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Les résultats</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        Figés à la transmission — un dossier se relit tel qu’il a été étudié.
                    </p>
                </div>
            </div>

            <div class="grid gap-px border-b border-gris-100 bg-gris-100 sm:grid-cols-3">
                @foreach ([
                    ['Moyenne générale', $fige['generale'] ?? null],
                    ['Sciences', $fige['sciences'] ?? null],
                    ['Lettres', $fige['lettres'] ?? null],
                ] as [$libelle, $valeur])
                    <div class="bg-white px-5 py-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">{{ $libelle }}</p>
                        <p class="mt-1 text-2xl font-bold tabular-nums text-gris-900">
                            {{ $note($valeur) }}<span class="text-sm font-normal text-gris-400">/20</span>
                        </p>
                    </div>
                @endforeach
            </div>

            @if ($fige['lecture'] ?? null)
                <p class="px-5 py-3 text-sm leading-relaxed text-gris-700">{{ $fige['lecture'] }}</p>
            @endif

            @if ($ecart !== null && abs($ecart) >= 0.3)
                <p class="border-t border-gris-100 bg-soleil-50 px-5 py-3 text-[11px] leading-relaxed text-gris-700">
                    Les notes ont bougé depuis la transmission : la moyenne générale est aujourd’hui de
                    <strong>{{ $note($profilActuel['generale']) }}/20</strong>
                    ({{ $ecart > 0 ? '+' : '' }}{{ $note($ecart) }} point).
                </p>
            @endif

            @if ($fige['par_matiere'] ?? null)
                <details class="border-t border-gris-100">
                    <summary class="cursor-pointer px-5 py-3 text-sm font-medium text-gris-700 hover:bg-gris-50">
                        Le détail par discipline
                    </summary>
                    <ul class="divide-y divide-gris-100">
                        @foreach ($fige['par_matiere'] as $matiere)
                            <li class="flex items-center justify-between gap-3 px-5 py-2 text-sm">
                                <span class="text-gris-700">{{ $matiere['matiere'] }}</span>
                                <span class="font-semibold tabular-nums {{ $matiere['moyenne'] >= 10 ? 'text-gris-800' : 'text-corail-600' }}">
                                    {{ $note($matiere['moyenne']) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         La décision
         ------------------------------------------------------------------ --}}
    <div class="space-y-4">
        @if ($dossier->estDecide())
            <div class="carte overflow-hidden {{ $dossier->statut === 'accorde' ? 'border-emerald-300' : 'border-corail-300' }}">
                <div class="border-b px-5 py-3 {{ $dossier->statut === 'accorde'
                        ? 'border-emerald-200 bg-emerald-50' : 'border-corail-200 bg-corail-50' }}">
                    <h2 class="text-sm font-semibold {{ $dossier->statut === 'accorde' ? 'text-emerald-900' : 'text-corail-900' }}">
                        {{ $dossier->statut === 'accorde' ? 'Accord' : 'Désaccord' }}
                    </h2>
                </div>

                <div class="p-5 text-sm leading-relaxed text-gris-700">
                    @if ($dossier->statut === 'refuse')
                        <p class="font-semibold text-gris-900">
                            {{ \App\Support\Orientation\MotifsOrientation::libelle($dossier->motif_code) }}
                        </p>
                        @if ($dossier->motif_precision)
                            <p class="mt-1">{{ $dossier->motif_precision }}</p>
                        @endif
                    @elseif ($dossier->motif_precision)
                        <p>{{ $dossier->motif_precision }}</p>
                    @endif

                    <p class="mt-3 text-[11px] text-gris-500">
                        Le {{ optional($dossier->decide_le)->format('d/m/Y à H:i') }}
                        @if ($dossier->decideur) par {{ $dossier->decideur->name }} @endif.
                        {{ $dossier->decision_vue_le
                            ? 'L’élève en a pris connaissance le '.$dossier->decision_vue_le->format('d/m/Y').'.'
                            : 'L’élève n’en a pas encore pris connaissance.' }}
                    </p>
                </div>
            </div>
        @elseif ($dossier->estSoumis())
            <form method="POST" action="{{ route('orientation.decider', $dossier) }}"
                  {{-- Aucun motif coché d'avance : refuser doit être un geste
                       choisi, pas le premier de la liste par inadvertance. --}}
                  x-data="{ decision: 'accorde', motif: '' }"
                  class="carte overflow-hidden">
                @csrf

                <div class="carte-entete">
                    <div>
                        <h2 class="text-sm font-semibold text-gris-900">Trancher</h2>
                        <p class="mt-0.5 text-xs text-gris-400">
                            Le désaccord exige un motif : l’élève n’aura que lui pour comprendre.
                        </p>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    {{-- Classes écrites en entier : Tailwind ne compile pas une
                         teinte interpolée. --}}
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="decision" value="accorde" class="peer sr-only" x-model="decision">
                            <span class="block rounded-xl border border-gris-200 px-4 py-3 text-center text-sm
                                         font-semibold text-gris-700 transition
                                         peer-checked:border-emerald-400 peer-checked:bg-emerald-50">
                                Accorder
                            </span>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="decision" value="refuse" class="peer sr-only" x-model="decision">
                            <span class="block rounded-xl border border-gris-200 px-4 py-3 text-center text-sm
                                         font-semibold text-gris-700 transition
                                         peer-checked:border-corail-400 peer-checked:bg-corail-50">
                                Désaccord
                            </span>
                        </label>
                    </div>

                    <div x-show="decision === 'refuse'" x-cloak class="space-y-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Motif <span class="text-corail-600">*</span>
                        </p>

                        @foreach (\App\Support\Orientation\MotifsOrientation::CATALOGUE as $cle => $motif)
                            <label class="block cursor-pointer">
                                <input type="radio" name="motif_code" value="{{ $cle }}" class="peer sr-only"
                                       x-model="motif">
                                <span class="block rounded-xl border border-gris-200 px-4 py-2.5 transition
                                             peer-checked:border-corail-400 peer-checked:bg-corail-50">
                                    <span class="block text-sm font-medium text-gris-800">{{ $motif['libelle'] }}</span>
                                    <span class="mt-0.5 block text-[11px] leading-snug text-gris-500">
                                        L’élève lira : « {{ $motif['consigne'] }} »
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div>
                        <label for="motif_precision"
                               class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Précision
                            <span class="font-normal normal-case tracking-normal text-gris-400">
                                — s’ajoute au motif, sur l’écran de l’élève
                            </span>
                        </label>
                        <textarea name="motif_precision" id="motif_precision" rows="3" class="champ w-full text-sm"
                                  :required="decision === 'refuse' && motif === 'autre'"
                                  placeholder="Ce que l’élève doit savoir ou faire."></textarea>
                    </div>
                </div>

                <div class="border-t border-gris-100 p-5">
                    <button type="submit" class="bouton-primaire w-full justify-center"
                            x-text="decision === 'accorde' ? 'Accorder ce vœu' : 'Marquer le désaccord'">
                        Accorder ce vœu
                    </button>
                </div>
            </form>
        @else
            <div class="carte p-5">
                <h2 class="text-sm font-semibold text-gris-900">Dossier en cours</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-gris-600">
                    L’élève n’a pas encore transmis ses vœux : il peut encore les modifier. Rien à trancher
                    pour l’instant.
                </p>
            </div>
        @endif

        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">L’élève</h2>
            </div>
            <dl class="divide-y divide-gris-100 text-sm">
                <div class="flex items-center justify-between gap-3 px-5 py-3">
                    <dt class="text-gris-600">Matricule</dt>
                    <dd class="font-mono text-gris-800">{{ $dossier->student->student_id ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 px-5 py-3">
                    <dt class="text-gris-600">Classe</dt>
                    <dd class="text-gris-800">
                        {{ $dossier->student?->enrollments?->first()?->schoolClass?->name ?? '—' }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-3 px-5 py-3">
                    <dt class="text-gris-600">Série</dt>
                    <dd class="text-gris-800">{{ $dossier->serie_actuelle ?: '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>

@endsection
