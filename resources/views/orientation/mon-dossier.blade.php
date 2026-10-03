@extends('layouts.app')

@section('titre', 'Mon orientation')
@section('sous-titre', $niveau
    ? \App\Models\OrientationDossier::NIVEAUX[$niveau]
    : 'Ce qui vient après cette classe')

@section('contenu')

@php
    $note = fn ($v) => \App\Support\Orientation\ProfilEleve::nombre($v);
    $estParent = auth()->user()?->role === 'parent';
    $decide = $dossier?->estDecide();
    $transmis = $dossier?->estSoumis();
    $fige = $decide || $transmis;
@endphp

{{-- ----------------------------------------------------------------------
     Le parent choisit l'enfant dont il regarde l'orientation
     ---------------------------------------------------------------------- --}}
@if ($estParent && $enfants->count() > 1)
    <div class="carte mb-6 flex flex-wrap items-center gap-2 p-4">
        <span class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Enfant</span>
        @foreach ($enfants as $enfant)
            <a href="{{ route('orientation.mon-dossier', ['eleve' => $enfant->id]) }}"
               class="rounded-full border px-3 py-1 text-sm transition
                      {{ $enfant->id === $eleve->id
                            ? 'border-ogar-500 bg-ogar-50 font-semibold text-ogar-800'
                            : 'border-gris-200 text-gris-600 hover:border-gris-300' }}">
                {{ $enfant->first_name }} {{ $enfant->last_name }}
            </a>
        @endforeach
    </div>
@endif

@if (! $niveau)

    {{-- L'orientation ne concerne que deux classes : le dire, et ne pas
         afficher un formulaire qui n'aurait pas d'objet. --}}
    <div class="carte p-8 text-center">
        <h2 class="text-base font-semibold text-gris-900">L’orientation ne s’ouvre pas encore</h2>
        <p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed text-gris-600">
            Elle concerne deux classes : la <strong class="text-gris-800">3ème</strong>, pour choisir sa seconde
            et son lycée, et la <strong class="text-gris-800">terminale</strong>, pour préparer l’entrée dans le
            supérieur. {{ $estParent ? 'Votre enfant' : 'Vous' }} n’{{ $estParent ? 'est' : 'êtes' }} dans
            aucune des deux cette année.
        </p>
        <p class="mx-auto mt-3 max-w-xl text-sm text-gris-500">
            D’ici là, les notes s’accumulent — et ce sont elles qui diront, le moment venu, quelles filières
            sont à portée.
        </p>
    </div>

@else

    {{-- ------------------------------------------------------------------
         La décision, quand elle est tombée
         ------------------------------------------------------------------ --}}
    @if ($decide)
        <div class="carte mb-6 overflow-hidden {{ $dossier->statut === 'accorde' ? 'border-emerald-300' : 'border-corail-300' }}">
            <div class="border-b px-5 py-3 {{ $dossier->statut === 'accorde'
                    ? 'border-emerald-200 bg-emerald-50' : 'border-corail-200 bg-corail-50' }}">
                <h2 class="text-sm font-semibold {{ $dossier->statut === 'accorde' ? 'text-emerald-900' : 'text-corail-900' }}">
                    {{ $dossier->statut === 'accorde'
                        ? 'Accord du service d’orientation'
                        : 'Désaccord du service d’orientation' }}
                </h2>
                <p class="mt-0.5 text-[11px] text-gris-600">
                    Décision rendue le {{ optional($dossier->decide_le)->format('d/m/Y') }}
                    @if ($dossier->decideur) par {{ $dossier->decideur->name }} @endif
                </p>
            </div>

            <div class="p-5">
                @if ($dossier->statut === 'accorde')
                    <p class="text-sm leading-relaxed text-gris-700">
                        L’établissement suit {{ $estParent ? 'le vœu de votre enfant' : 'votre vœu' }} :
                        <strong class="text-gris-900">{{ $dossier->filiere ?: \App\Support\Orientation\VoiesApresTroisieme::libelle($dossier->voie) }}</strong>.
                        Le dossier d’inscription se retire au secrétariat.
                    </p>
                @else
                    <p class="text-sm font-semibold text-gris-900">
                        {{ \App\Support\Orientation\MotifsOrientation::libelle($dossier->motif_code) }}
                    </p>
                    @if ($dossier->motif_precision)
                        <p class="mt-1 text-sm leading-relaxed text-gris-700">{{ $dossier->motif_precision }}</p>
                    @endif
                    <p class="mt-2 text-sm leading-relaxed text-gris-600">
                        {{ \App\Support\Orientation\MotifsOrientation::consigne($dossier->motif_code) }}
                    </p>
                @endif

                @unless ($dossier->decision_vue_le)
                    <form method="POST" action="{{ route('orientation.decision-lue', $dossier) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="bouton-secondaire">J’ai pris connaissance</button>
                    </form>
                @endunless
            </div>
        </div>
    @elseif ($transmis)
        <div class="carte mb-6 border-soleil-300 bg-soleil-50 p-5">
            <h2 class="text-sm font-semibold text-soleil-900">Dossier transmis, en cours d’étude</h2>
            <p class="mt-1.5 text-sm leading-relaxed text-gris-700">
                Transmis le {{ optional($dossier->soumis_le)->format('d/m/Y à H:i') }}. Le service d’orientation
                de l’établissement l’étudie ; la décision paraîtra ici même. D’ici là les choix ne se modifient
                plus — adressez-vous au secrétariat s’il faut les revoir.
            </p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- --------------------------------------------------------------
             Colonne de gauche : le profil, puis les choix
             -------------------------------------------------------------- --}}
        <div class="space-y-4 lg:col-span-2">

            {{-- Le profil, calculé sur les notes réelles --}}
            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <div>
                        <h2 class="text-sm font-semibold text-gris-900">{{ $profil['libelle'] }}</h2>
                        <p class="mt-0.5 text-xs text-gris-400">
                            Établi sur {{ $profil['notes'] }} note(s) de l’année — rien n’est saisi à la main.
                        </p>
                    </div>
                </div>

                <div class="grid gap-px border-b border-gris-100 bg-gris-100 sm:grid-cols-3">
                    @foreach ([
                        ['Moyenne générale', $profil['generale'], 'text-gris-900'],
                        ['Disciplines scientifiques', $profil['sciences'], 'text-ogar-700'],
                        ['Disciplines littéraires', $profil['lettres'], 'text-violet-700'],
                    ] as [$libelle, $valeur, $encre])
                        <div class="bg-white px-5 py-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">{{ $libelle }}</p>
                            <p class="mt-1 text-2xl font-bold tabular-nums {{ $encre }}">
                                {{ $note($valeur) }}<span class="text-sm font-normal text-gris-400">/20</span>
                            </p>
                        </div>
                    @endforeach
                </div>

                <p class="p-5 text-sm leading-relaxed text-gris-700">{{ $profil['lecture'] }}</p>

                @if ($profil['par_matiere'])
                    <details class="border-t border-gris-100">
                        <summary class="cursor-pointer px-5 py-3 text-sm font-medium text-gris-700 hover:bg-gris-50">
                            Le détail par discipline
                        </summary>
                        <ul class="divide-y divide-gris-100">
                            @foreach ($profil['par_matiere'] as $matiere)
                                <li class="flex items-center justify-between gap-3 px-5 py-2 text-sm">
                                    <span class="text-gris-700">{{ $matiere['matiere'] }}</span>
                                    <span class="tabular-nums font-semibold {{ $matiere['moyenne'] >= 10 ? 'text-gris-800' : 'text-corail-600' }}">
                                        {{ $note($matiere['moyenne']) }}
                                        <span class="text-[11px] font-normal text-gris-400">
                                            · {{ $matiere['notes'] }} note(s)
                                        </span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>

            <form method="POST" action="{{ route('orientation.enregistrer') }}" class="space-y-4">
                @csrf
                @if ($estParent)
                    <input type="hidden" name="eleve" value="{{ $eleve->id }}">
                @endif

                @if ($niveau === 'troisieme')
                    @include('orientation.partials.troisieme')
                @else
                    @include('orientation.partials.terminale')
                @endif

                {{-- Les vœux --}}
                <div class="carte overflow-hidden">
                    <div class="carte-entete">
                        <div>
                            <h2 class="text-sm font-semibold text-gris-900">
                                {{ $niveau === 'troisieme' ? 'Où poursuivre' : 'Où s’inscrire' }}
                            </h2>
                            <p class="mt-0.5 text-xs text-gris-400">
                                {{ $maxVoeux }} établissements au plus, dans l’ordre de préférence.
                            </p>
                        </div>
                    </div>

                    @php
                        $dejaChoisis = collect($dossier?->voeux ?? [])->pluck('etablissement_id')->all();
                    @endphp

                    @if ($etablissements->isEmpty())
                        <div class="p-8 text-center text-sm text-gris-500">
                            Aucun établissement de ce type ne figure encore au répertoire.
                            Le secrétariat peut en ajouter depuis « Orientation › Répertoire ».
                        </div>
                    @else
                        <div class="relative max-h-[28rem] divide-y divide-gris-100 overflow-y-auto">
                            @foreach ($etablissements as $etablissement)
                                @php($choisi = in_array($etablissement->id, $dejaChoisis, true))

                                <label class="flex cursor-pointer items-start gap-3 px-5 py-3 transition hover:bg-gris-50
                                              {{ $choisi ? 'bg-ogar-50' : '' }}">
                                    <input type="checkbox" name="voeux[]" value="{{ $etablissement->id }}"
                                           class="mt-1 shrink-0" @checked($choisi) @disabled($fige)>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gris-900">{{ $etablissement->nom_complet }}</p>
                                        <p class="text-[11px] text-gris-500">
                                            {{ $etablissement->libelle_type }}
                                            @if ($etablissement->ville) · {{ $etablissement->ville }} @endif
                                            @if ($etablissement->quartier) · {{ $etablissement->quartier }} @endif
                                        </p>

                                        @if ($etablissement->filieres)
                                            <p class="mt-1 text-[11px] leading-snug text-gris-500">
                                                {{ implode(' · ', array_slice($etablissement->filieres, 0, 6)) }}
                                            </p>
                                        @endif
                                    </div>

                                    @if ($etablissement->places_libres !== null)
                                        <span class="shrink-0 text-right">
                                            <x-puce :couleur="$etablissement->est_complet ? 'rose' : 'emerald'">
                                                {{ $etablissement->est_complet
                                                    ? 'Complet'
                                                    : $etablissement->places_libres.' place(s)' }}
                                            </x-puce>
                                        </span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Un mot de l'élève --}}
                <div class="carte overflow-hidden">
                    <div class="carte-entete">
                        <h2 class="text-sm font-semibold text-gris-900">Ce que vous voulez ajouter</h2>
                    </div>
                    <div class="p-5">
                        <textarea name="commentaire_eleve" rows="3" class="champ w-full text-sm"
                                  placeholder="Un projet, une contrainte de transport, une vocation : ce que les notes ne disent pas."
                                  @disabled($fige)>{{ old('commentaire_eleve', $dossier?->commentaire_eleve) }}</textarea>
                    </div>
                </div>

                @unless ($fige)
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="bouton-secondaire">Enregistrer sans transmettre</button>
                    </div>
                @endunless
            </form>

            @unless ($fige)
                <form method="POST" action="{{ route('orientation.soumettre') }}">
                    @csrf
                    @if ($estParent)
                        <input type="hidden" name="eleve" value="{{ $eleve->id }}">
                    @endif
                    <button type="submit" class="bouton-primaire w-full justify-center">
                        Transmettre au service d’orientation
                    </button>
                    <p class="mt-2 text-[11px] leading-relaxed text-gris-500">
                        Une fois transmis, les choix ne se modifient plus. Le conseiller rend une décision motivée,
                        qui paraîtra sur cette page.
                    </p>
                </form>
            @endunless
        </div>

        {{-- --------------------------------------------------------------
             Colonne de droite : l'état du dossier
             -------------------------------------------------------------- --}}
        <div class="space-y-4">
            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Mon dossier</h2>
                </div>

                <dl class="divide-y divide-gris-100 text-sm">
                    <div class="flex items-center justify-between gap-3 px-5 py-3">
                        <dt class="text-gris-600">État</dt>
                        <dd>
                            <x-puce :couleur="match ($dossier?->statut) {
                                'accorde' => 'emerald', 'refuse' => 'rose', 'soumis' => 'amber', default => 'slate',
                            }">{{ $dossier?->libelle_statut ?? 'Brouillon' }}</x-puce>
                        </dd>
                    </div>

                    @if ($dossier?->voie && $niveau === 'troisieme')
                        <div class="px-5 py-3">
                            <dt class="text-[11px] uppercase tracking-wide text-gris-500">Voie choisie</dt>
                            <dd class="mt-0.5 font-medium text-gris-800">
                                {{ \App\Support\Orientation\VoiesApresTroisieme::libelle($dossier->voie) }}
                            </dd>
                        </div>
                    @endif

                    @if ($dossier?->filiere)
                        <div class="px-5 py-3">
                            <dt class="text-[11px] uppercase tracking-wide text-gris-500">Filière demandée</dt>
                            <dd class="mt-0.5 font-medium text-gris-800">{{ $dossier->filiere }}</dd>
                            @if ($dossier->mode_admission)
                                <dd class="mt-0.5 text-[11px] text-gris-500">
                                    {{ \App\Support\Orientation\Conseil::libelleModeAdmission($dossier->mode_admission) }}
                                </dd>
                            @endif
                        </div>
                    @endif

                    @if ($dossier && $dossier->voeux)
                        <div class="px-5 py-3">
                            <dt class="text-[11px] uppercase tracking-wide text-gris-500">Vœux</dt>
                            <dd class="mt-1 space-y-1">
                                @foreach ($dossier->voeuxDetailles() as $voeu)
                                    <p class="text-sm text-gris-800">
                                        <span class="font-mono text-[11px] text-gris-400">{{ $voeu['rang'] }}.</span>
                                        {{ $voeu['nom'] }}
                                    </p>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="carte p-5">
                <h2 class="text-sm font-semibold text-gris-900">Comment cela se décide</h2>
                <ol class="mt-2 space-y-2 text-sm leading-relaxed text-gris-600">
                    <li>1. Le profil se calcule sur les notes de l’année — il ne se saisit pas.</li>
                    <li>2. {{ $niveau === 'troisieme'
                            ? 'Vous choisissez une voie, puis une filière : chacune dit la moyenne qu’elle demande.'
                            : 'Votre série ouvre des filières : chacune dit à quels métiers elle mène.' }}</li>
                    <li>3. Vous classez jusqu’à {{ $maxVoeux }} établissements.</li>
                    <li>4. Le conseiller tranche en motivant, et la décision paraît ici.</li>
                </ol>
            </div>
        </div>
    </div>

@endif

@endsection
