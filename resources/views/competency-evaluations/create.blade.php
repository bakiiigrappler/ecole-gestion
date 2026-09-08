@extends('layouts.app')

@section('titre', 'Évaluer par compétences')
@section('sous-titre', $class->name.' · '.($class->getSafeLevelName() ?? '—').' — palier '.$palier)

@section('actions-entete')
    <a href="{{ route('competency-evaluations.bulletins', $class->id) }}" class="bouton-secondaire">Bulletins de la classe</a>
    <a href="{{ route('competency-evaluations.index') }}" class="bouton-primaire">Toutes les classes</a>
@endsection

@section('contenu')

@php
    // Barème du référentiel : chaque critère porte son propre maximum.
    $matieres = $competencies->map(fn ($liste) => $liste->map(fn ($c) => [
        'id' => $c->id,
        'nom' => $c->name,
        'criteres' => $c->criteria->take(3)->values()->map(fn ($cr) => [
            'nom' => $cr->name,
            'max' => (int) $cr->max_points,
        ])->all(),
    ])->values());

    // Ce qui est déjà saisi, pour préremplir la grille.
    $deja = collect($existingEvaluations ?? [])->flatten(1)->groupBy('student_id')->map(
        fn ($lot) => $lot->keyBy('competency_id')->map(fn ($e) => [
            'c1' => (int) $e->c1_points,
            'c2' => (int) $e->c2_points,
            'c3' => (int) $e->c3_points,
        ])
    );
@endphp

    <div x-data="saisieDesCompetences({
            matieres: {{ Js::from($matieres) }},
            deja: {{ Js::from($deja) }},
            eleves: {{ Js::from($students->map(fn ($e) => ['id' => $e->id, 'nom' => $e->full_name, 'matricule' => $e->student_id])->values()) }}
         })">

        {{-- ------------------------------------------------------------
             Qui évalue-t-on, et sur quel palier
             ------------------------------------------------------------ --}}
        <div class="carte p-4">

            {{-- Les deux champs occupent toute la largeur, cote a cote ; le
                 rappel de ce qu'on evalue vient en dessous, sur sa ligne. --}}
            <div class="grid gap-4 sm:grid-cols-2">

                {{-- Champ filtrable : une classe de trente noms se cherche,
                     elle ne se déroule pas. --}}
                <div class="relative" @click.outside="listeOuverte = false">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Élève</label>

                    <div class="relative">
                        <input type="text"
                               x-model="recherche"
                               @focus="listeOuverte = true"
                               @input="listeOuverte = true"
                               @keydown.escape="listeOuverte = false"
                               placeholder="Rechercher un élève par nom ou matricule…"
                               class="champ w-full pr-8 text-sm">

                        <button type="button" x-show="eleveId" x-cloak
                                @click="vider()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gris-400 hover:text-corail-600"
                                title="Effacer">&times;</button>
                    </div>

                    <div x-show="listeOuverte" x-cloak
                         class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gris-200 bg-white shadow-lg">
                        <template x-for="e in elevesFiltres()" :key="e.id">
                            <button type="button" @click="choisir(e)"
                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-ogar-50"
                                    :class="e.id === eleveId ? 'bg-ogar-50 font-semibold text-ogar-800' : 'text-gris-700'">
                                <span class="min-w-0 flex-1 truncate" x-text="e.nom"></span>
                                <span class="shrink-0 font-mono text-[11px] text-gris-400" x-text="e.matricule"></span>
                            </button>
                        </template>

                        <p x-show="elevesFiltres().length === 0" x-cloak class="px-3 py-2 text-sm text-gris-400">
                            Aucun élève ne correspond.
                        </p>
                    </div>
                </div>

                <form method="GET" action="{{ route('competency-evaluations.create') }}">
                    <input type="hidden" name="class_id" value="{{ $class->id }}">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Palier</label>
                    <select name="palier" onchange="this.form.submit()" class="champ w-full text-sm">
                        @foreach (range(1, 5) as $p)
                            <option value="{{ $p }}" @selected((int) $palier === $p)>Palier {{ $p }} sur 5</option>
                        @endforeach
                    </select>
                </form>
            </div>

            {{-- Ce qu'on est en train d'évaluer, affiché en toutes lettres. --}}
            <div class="mt-4 border-t border-gris-100 pt-4">
                <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Évaluation en cours</span>

                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">

                    <template x-if="eleveId">
                        <span class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                            <span class="text-base font-semibold text-gris-900" x-text="nomDeLEleve()"></span>
                            <span class="font-mono text-[11px] text-gris-400" x-text="matriculeDeLEleve()"></span>
                            <span class="text-gris-300">·</span>
                            <span class="font-semibold text-ogar-700">Palier {{ $palier }} sur 5</span>
                        </span>
                    </template>

                    <template x-if="! eleveId">
                        <span class="text-sm text-gris-400">Aucun élève sélectionné</span>
                    </template>
                </div>

                <p class="mt-1 text-[11px] text-gris-400">
                    Changer de palier recharge la grille depuis le serveur ; changer d’élève reprend ses points déjà saisis.
                </p>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             La grille de saisie
             ------------------------------------------------------------ --}}
        <template x-if="! eleveId">
            <div class="carte mt-4 p-8">
                <x-vide message="Choisissez un élève pour saisir son évaluation."/>
            </div>
        </template>

        <form method="POST" action="{{ route('competency-evaluations.store-single-student') }}" x-show="eleveId" x-cloak class="mt-4">
            @csrf
            <input type="hidden" name="class_id" value="{{ $class->id }}">
            <input type="hidden" name="palier" value="{{ $palier }}">
            <input type="hidden" name="student_id" :value="eleveId">

            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <div>
                        <h2 class="text-sm font-semibold text-gris-900">
                            Points par critère — <span x-text="nomDeLEleve()"></span>
                        </h2>
                        <p class="mt-0.5 text-xs text-gris-400">
                            Chaque critère se note sur son barème propre ; le niveau de maîtrise s’en déduit.
                        </p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gris-500">
                        <span><span class="font-semibold text-gris-800" x-text="totalObtenu()"></span> / <span x-text="totalMax()"></span> points</span>
                        <span class="rounded-full bg-gris-100 px-2 py-0.5 font-semibold" x-text="maitrise()"></span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="tableau">
                        <thead>
                            <tr>
                                <th>Compétence</th>
                                <th class="w-28 text-center">Critère 1</th>
                                <th class="w-28 text-center">Critère 2</th>
                                <th class="w-28 text-center">Critère 3</th>
                                <th class="w-24 text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($rang = 0)
                            @foreach ($matieres as $matiere => $liste)
                                <tr class="bg-gris-50">
                                    <td colspan="5" class="text-[11px] font-bold uppercase tracking-wide text-gris-600">
                                        {{ $matiere }}
                                    </td>
                                </tr>

                                @foreach ($liste as $competence)
                                    <tr>
                                        <td class="text-gris-800">{{ $competence['nom'] }}</td>

                                        <input type="hidden" name="evaluations[{{ $rang }}][competency_id]" value="{{ $competence['id'] }}">

                                        @foreach ([0, 1, 2] as $i)
                                            @php($critere = $competence['criteres'][$i] ?? null)
                                            @php($max = $critere['max'] ?? 3)
                                            <td class="text-center">
                                                <input type="hidden" name="evaluations[{{ $rang }}][c{{ $i + 1 }}_max_points]" value="{{ $max }}">
                                                <div class="flex items-center justify-center gap-1">
                                                    <input type="number" min="0" max="{{ $max }}"
                                                           name="evaluations[{{ $rang }}][c{{ $i + 1 }}_points]"
                                                           x-model.number="points[{{ $competence['id'] }}].c{{ $i + 1 }}"
                                                           class="w-14 rounded border border-gris-300 px-1 py-1 text-center text-sm focus:border-ogar-400">
                                                    <span class="text-[11px] text-gris-400">/ {{ $max }}</span>
                                                </div>
                                                @if ($critere)
                                                    <span class="mt-0.5 block truncate text-[10px] text-gris-400" title="{{ $critere['nom'] }}">
                                                        {{ $critere['nom'] }}
                                                    </span>
                                                @endif
                                            </td>
                                        @endforeach

                                        <td class="text-center font-semibold text-gris-800">
                                            <span x-text="totalDeLaCompetence({{ $competence['id'] }})"></span>
                                            <span class="text-gris-400">/ {{ collect($competence['criteres'])->sum('max') ?: 9 }}</span>
                                        </td>
                                    </tr>
                                    @php($rang++)
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center gap-3 border-t border-gris-200 p-4">
                    <button type="submit" class="bouton-primaire">Enregistrer l’évaluation</button>
                    <a href="{{ route('competency-evaluations.bulletins', $class->id) }}" class="bouton-secondaire">Annuler</a>
                    <span class="ml-auto text-xs text-gris-400">
                        Palier {{ $palier }} — les points déjà saisis sont repris.
                    </span>
                </div>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
<script>
    function saisieDesCompetences(donnees) {
        return {
            matieres: donnees.matieres,
            eleves: donnees.eleves,
            deja: donnees.deja,
            eleveId: '',
            recherche: '',
            listeOuverte: false,
            points: {},

            init() {
                this.reinitialiser();
                this.$watch('eleveId', () => this.reinitialiser());
            },

            /* Filtrage sur le nom comme sur le matricule. Une fois l'eleve
               choisi, le champ porte son nom : on montre alors toute la liste
               plutot que de la reduire au seul nom deja selectionne. */
            elevesFiltres() {
                const terme = this.recherche.trim().toLowerCase();

                if (terme === '' || terme === this.nomDeLEleve().toLowerCase()) {
                    return this.eleves;
                }

                return this.eleves.filter((e) =>
                    `${e.nom} ${e.matricule}`.toLowerCase().includes(terme));
            },

            choisir(eleve) {
                this.eleveId = eleve.id;
                this.recherche = eleve.nom;
                this.listeOuverte = false;
            },

            vider() {
                this.eleveId = '';
                this.recherche = '';
                this.listeOuverte = false;
            },

            matriculeDeLEleve() {
                return this.eleves.find((e) => e.id === this.eleveId)?.matricule ?? '';
            },

            /* Repartir des points deja saisis pour l'eleve choisi, sinon de zero. */
            reinitialiser() {
                const saisis = this.deja[this.eleveId] ?? {};
                const points = {};

                Object.values(this.matieres).flat().forEach((competence) => {
                    const existant = saisis[competence.id];

                    points[competence.id] = {
                        c1: existant?.c1 ?? 0,
                        c2: existant?.c2 ?? 0,
                        c3: existant?.c3 ?? 0,
                    };
                });

                this.points = points;
            },

            nomDeLEleve() {
                return this.eleves.find((e) => e.id === this.eleveId)?.nom ?? '';
            },

            totalDeLaCompetence(id) {
                const p = this.points[id];

                return p ? (p.c1 || 0) + (p.c2 || 0) + (p.c3 || 0) : 0;
            },

            totalObtenu() {
                return Object.keys(this.points).reduce((n, id) => n + this.totalDeLaCompetence(Number(id)), 0);
            },

            totalMax() {
                return Object.values(this.matieres).flat().reduce(
                    (n, c) => n + (c.criteres.reduce((s, cr) => s + cr.max, 0) || 9), 0);
            },

            /* Memes seuils que le referentiel : 80 %, 60 %, 40 %. */
            maitrise() {
                const max = this.totalMax();

                if (max === 0) return '—';

                const part = this.totalObtenu() / max;

                if (part >= 0.8) return 'Maîtrise maximale';
                if (part >= 0.6) return 'Maîtrise minimale';
                if (part >= 0.4) return 'Maîtrise partielle';

                return 'Non maîtrisé';
            },
        };
    }
</script>
@endpush
