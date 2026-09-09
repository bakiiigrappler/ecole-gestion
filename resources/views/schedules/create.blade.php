@extends('layouts.app')

@section('titre', 'Emploi du temps')
@section('sous-titre', $classe ? $classe->name.' — '.($annee->name ?? '') : 'Choisir une classe à planifier')

@section('actions-entete')
    <div class="flex gap-2">
        @if ($classe)
            <a href="{{ route('schedules.print', $classe->id) }}" class="bouton-mini">Imprimer</a>
        @endif
        <a href="{{ route('schedules.index') }}" class="bouton-mini">Liste des emplois du temps</a>
    </div>
@endsection

@section('contenu')

@php
    $jours = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'];
    $libellesCycle = ['preprimaire' => 'Préprimaire', 'primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    // Le selecteur ne propose que les classes restant a planifier. La classe
    // en cours de modification y reste, sinon le champ paraitrait vide quand
    // on arrive depuis le bouton « Modifier » de la liste.
    $aProposer = $classes->filter(fn ($c) => $c->creneaux_count === 0 || ($classe && $classe->id === $c->id));
    $classesParCycle = $aProposer->groupBy(fn ($c) => $c->level->cycle ?? 'primaire');
    $dejaPlanifiees = $classes->count() - $aProposer->count();

    // Une couleur par matiere, la meme dans la palette et dans la grille :
    // c'est ce qui rend une semaine lisible d'un coup d'oeil.
    $nuancier = [
        ['fond' => '#e0f2fe', 'bord' => '#7dd3fc', 'encre' => '#075985', 'vif' => '#0ea5e9'],
        ['fond' => '#dcfce7', 'bord' => '#86efac', 'encre' => '#166534', 'vif' => '#22c55e'],
        ['fond' => '#fef3c7', 'bord' => '#fcd34d', 'encre' => '#92400e', 'vif' => '#f59e0b'],
        ['fond' => '#ede9fe', 'bord' => '#c4b5fd', 'encre' => '#5b21b6', 'vif' => '#8b5cf6'],
        ['fond' => '#ffe4e6', 'bord' => '#fda4af', 'encre' => '#9f1239', 'vif' => '#f43f5e'],
        ['fond' => '#cffafe', 'bord' => '#67e8f9', 'encre' => '#155e75', 'vif' => '#06b6d4'],
        ['fond' => '#fae8ff', 'bord' => '#f0abfc', 'encre' => '#86198f', 'vif' => '#d946ef'],
        ['fond' => '#ffedd5', 'bord' => '#fdba74', 'encre' => '#9a3412', 'vif' => '#f97316'],
        ['fond' => '#e0e7ff', 'bord' => '#a5b4fc', 'encre' => '#3730a3', 'vif' => '#6366f1'],
        ['fond' => '#d1fae5', 'bord' => '#6ee7b7', 'encre' => '#065f46', 'vif' => '#10b981'],
    ];

    $couleurs = $matieres->values()
        ->mapWithKeys(fn ($m, $i) => [$m->id => $nuancier[$i % count($nuancier)]])
        ->all();
@endphp

    {{-- ----------------------------------------------------------------
         Choix de la classe : une grille se construit classe par classe
         ---------------------------------------------------------------- --}}
    <div class="carte p-4">
        <form method="GET" action="{{ route('schedules.create') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Classe</label>
                <select name="class_id" onchange="this.form.submit()" class="champ w-64 text-sm">
                    <option value="">— Choisir une classe —</option>
                    @foreach ($classesParCycle as $cycle => $duCycle)
                        <optgroup label="{{ $libellesCycle[$cycle] ?? ucfirst($cycle) }}">
                            @foreach ($duCycle as $c)
                                <option value="{{ $c->id }}" @selected($classe && $classe->id === $c->id)>
                                    {{ $c->name }}{{ $c->creneaux_count > 0 ? ' — déjà planifiée' : '' }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @if ($dejaPlanifiees > 0)
                    <p class="mt-1 text-[11px] text-gris-400">
                        {{ $dejaPlanifiees }} classe(s) déjà planifiée(s), à modifier depuis
                        <a href="{{ route('schedules.index') }}" class="underline hover:text-ogar-600">la liste</a>.
                    </p>
                @endif
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Année scolaire</label>
                <select name="academic_year_id" onchange="this.form.submit()" class="champ w-48 text-sm">
                    @foreach ($academicYears as $a)
                        <option value="{{ $a->id }}" @selected($annee && $annee->id === $a->id)>
                            {{ $a->name }}{{ $a->is_current ? ' (courante)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($classe)
                <p class="pb-2 text-xs text-gris-400">
                    {{ $grille->isEmpty() ? 'Aucun créneau enregistré pour cette classe.' : $grille->count().' créneau(x) enregistré(s).' }}
                </p>
            @endif
        </form>
    </div>

    @if (! $classe)
        <div class="carte mt-6 p-8">
            <x-vide message="Choisissez une classe pour construire son emploi du temps."/>
        </div>
    @else

    {{-- ----------------------------------------------------------------
         Constructeur : on choisit une matière, puis on peint les cases
         ---------------------------------------------------------------- --}}
    <div class="mt-4"
         x-data="constructeurEmploiDuTemps({
            creneaux: {{ Js::from($creneaux) }},
            grille: {{ Js::from($grille) }},
            matieres: {{ Js::from($matieres) }},
            enseignants: {{ Js::from($enseignants) }},
            occupations: {{ Js::from($occupations) }},
            jours: {{ Js::from(array_keys($jours)) }},
            couleurs: {{ Js::from($couleurs) }}
         })">

        <div class="grid gap-4 lg:grid-cols-4">

            {{-- Palette --}}
            <div class="lg:col-span-1">
                <div class="carte sticky top-4 p-4">
                    <h3 class="mb-1 text-sm font-bold uppercase tracking-wide text-ogar-700">Que poser ?</h3>
                    <p class="mb-3 text-[11px] text-gris-400">
                        Choisissez une matière puis cliquez sur les cases de la grille.
                    </p>

                    @if ($matieres->isEmpty())
                        <p class="rounded bg-amber-50 p-3 text-xs text-amber-700">
                            Aucune matière n’est enregistrée pour ce cycle. Créez-les d’abord dans
                            <a href="{{ route('subjects.index') }}" class="underline">Matières</a>.
                        </p>
                    @endif

                    <div class="relative max-h-80 space-y-1 overflow-y-auto pr-1">
                        @foreach ($matieres as $m)
                            {{-- Couleurs posees en style : une classe Tailwind construite
                                 a la volee n'est pas generee a la compilation. --}}
                            <button type="button" @click="choisirMatiere({{ $m->id }})"
                                    class="flex w-full items-center gap-2 rounded-lg border px-2 py-1.5 text-left text-sm transition"
                                    :style="outil.type === 'course' && outil.matiere_id === {{ $m->id }}
                                            ? 'background:{{ $couleurs[$m->id]['fond'] }};border-color:{{ $couleurs[$m->id]['bord'] }};color:{{ $couleurs[$m->id]['encre'] }}'
                                            : 'border-color:transparent'"
                                    :class="outil.type === 'course' && outil.matiere_id === {{ $m->id }}
                                            ? 'font-semibold' : 'text-gris-700 hover:bg-gris-100'">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full"
                                      style="background:{{ $couleurs[$m->id]['vif'] }}"></span>
                                <span class="min-w-0 flex-1 truncate">{{ $m->name }}</span>
                                <span class="shrink-0 text-[10px] tabular-nums opacity-60"
                                      x-text="heuresDeLaMatiere({{ $m->id }}) || ''"></span>
                            </button>
                        @endforeach
                    </div>

                    {{-- Enseignant de la matière choisie --}}
                    <div x-show="outil.type === 'course' && outil.matiere_id" x-cloak class="mt-3 border-t border-gris-200 pt-3">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Enseignant</label>
                        <select x-model="outil.enseignant_id" class="champ w-full text-sm">
                            <option value="">— Aucun —</option>
                            <template x-for="e in enseignantsDeLaMatiere()" :key="e.id">
                                <option :value="e.id" x-text="e.nom"></option>
                            </template>
                        </select>
                        <p x-show="enseignantsDeLaMatiere().length === 0" x-cloak class="mt-1 text-[11px] text-amber-600">
                            Aucun enseignant n’est rattaché à cette matière.
                        </p>

                        <label class="mb-1 mt-3 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Salle</label>
                        <input x-model="outil.salle" type="text" placeholder="Facultatif" class="champ w-full text-sm">
                    </div>

                    <div class="mt-3 space-y-1 border-t border-gris-200 pt-3">
                        <button type="button" @click="choisirPause()"
                                class="w-full rounded px-2 py-1.5 text-left text-sm transition"
                                :class="outil.type === 'break' ? 'bg-gris-700 text-white' : 'hover:bg-gris-100 text-gris-700'">
                            Récréation / pause
                        </button>
                        <button type="button" @click="choisirGomme()"
                                class="w-full rounded px-2 py-1.5 text-left text-sm transition"
                                :class="outil.type === 'gomme' ? 'bg-corail-600 text-white' : 'hover:bg-gris-100 text-gris-700'">
                            Effacer une case
                        </button>
                    </div>
                </div>
            </div>

            {{-- Grille hebdomadaire --}}
            <div class="lg:col-span-3">
                <div class="carte overflow-hidden">
                    <div class="carte-entete">
                        <div class="flex min-w-0 items-center gap-3">
                            <h2 class="shrink-0 text-sm font-semibold text-gris-900">Semaine type</h2>

                            {{-- Ce que le prochain clic va poser : sans ce rappel,
                                 on cherche l'outil actif dans la colonne de gauche. --}}
                            <span class="flex min-w-0 items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px]"
                                  :style="styleDeLOutil()">
                                <span x-text="libelleDeLOutil()" class="truncate font-semibold"></span>
                                <span x-show="outil.type === 'course' && outil.enseignant_id" x-cloak
                                      class="truncate opacity-70" x-text="'· ' + nomEnseignant(outil.enseignant_id)"></span>
                            </span>
                        </div>

                        <div class="flex shrink-0 items-center gap-3 text-xs text-gris-500">
                            <span><span class="font-semibold text-gris-800" x-text="nombreDeCours()"></span> h placées</span>
                            <span x-show="nombreDeConflits() > 0" x-cloak class="font-semibold text-corail-600">
                                <span x-text="nombreDeConflits()"></span> conflit(s)
                            </span>
                            <button type="button" @click="ajouterCreneau()" class="bouton-mini">+ Créneau</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-xs">
                            <thead>
                                <tr class="bg-gris-100">
                                    <th class="w-44 border border-gris-200 px-2 py-2 text-left font-semibold text-gris-600">Horaire</th>
                                    @foreach ($jours as $numero => $nom)
                                        <th class="border border-gris-200 px-2 py-2 font-semibold text-gris-600">{{ $nom }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(creneau, i) in creneaux" :key="i">
                                    <tr>
                                        {{-- La plage se lit comme une étiquette ; les champs
                                             n'apparaissent qu'au clic sur le crayon. Deux
                                             sélecteurs d'heure natifs par ligne saturaient
                                             la colonne d'icônes d'horloge. --}}
                                        <td class="group/heure border border-gris-200 bg-gris-50/70 px-2 py-1.5 align-middle"
                                            x-data="{ edition: false }">
                                            <div x-show="! edition" class="flex items-center gap-2">
                                                <span class="flex items-baseline gap-1 tabular-nums">
                                                    <span class="text-[13px] font-semibold text-gris-800" x-text="creneau.debut"></span>
                                                    <span class="text-[10px] text-gris-300">&ndash;</span>
                                                    <span class="text-[13px] text-gris-500" x-text="creneau.fin"></span>
                                                </span>

                                                <span class="whitespace-nowrap rounded-full bg-white px-1.5 py-0.5 text-[10px] font-medium text-gris-400 ring-1 ring-gris-200"
                                                      x-text="duree(creneau)"></span>

                                                <span class="ml-auto flex items-center gap-0.5 opacity-0 transition group-hover/heure:opacity-100">
                                                    <button type="button" @click="edition = true"
                                                            class="rounded p-1 text-gris-400 hover:bg-white hover:text-ogar-600"
                                                            title="Modifier l’horaire">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.86 4.49l2.65 2.65M3 21l.85-3.72L15.3 5.83a1.2 1.2 0 011.7 0l1.17 1.17a1.2 1.2 0 010 1.7L6.72 20.15 3 21z"/>
                                                        </svg>
                                                    </button>
                                                    <button type="button" @click="retirerCreneau(i)"
                                                            class="rounded p-1 text-gris-400 hover:bg-white hover:text-corail-600"
                                                            title="Retirer la ligne">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                                                        </svg>
                                                    </button>
                                                </span>
                                            </div>

                                            <div x-show="edition" x-cloak class="flex items-center gap-1">
                                                <input type="time" x-model="creneau.debut"
                                                       class="w-[62px] rounded border border-gris-300 bg-white px-1 py-0.5 text-[11px] font-semibold text-gris-800 focus:border-ogar-400">
                                                <span class="text-[10px] text-gris-300">&ndash;</span>
                                                <input type="time" x-model="creneau.fin"
                                                       class="w-[62px] rounded border border-gris-300 bg-white px-1 py-0.5 text-[11px] font-semibold text-gris-800 focus:border-ogar-400">
                                                <button type="button" @click="edition = false"
                                                        class="ml-auto rounded p-1 text-emerald-600 hover:bg-white"
                                                        title="Terminer">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>

                                        <template x-for="jour in jours" :key="jour">
                                            <td class="border border-gris-200 p-0.5 align-top">
                                                <button type="button" @click="peindre(jour, i)"
                                                        class="group/case h-14 w-full rounded-md border px-1.5 py-1 text-left transition hover:shadow-sm"
                                                        :style="styleDeCase(jour, i)">
                                                    <template x-if="case_(jour, i)">
                                                        <span class="block leading-tight">
                                                            <span class="block truncate text-[11px] font-semibold"
                                                                  x-text="intituleDeCase(jour, i)"></span>
                                                            <span class="block truncate text-[10px] opacity-75"
                                                                  x-text="enseignantDeCase(jour, i)"></span>
                                                            <span x-show="enConflit(jour, i)" x-cloak
                                                                  class="mt-0.5 block truncate text-[10px] font-bold text-corail-700"
                                                                  x-text="messageDeConflit(jour, i)"></span>
                                                        </span>
                                                    </template>
                                                    <template x-if="! case_(jour, i)">
                                                        <span class="flex h-full items-center justify-center text-base text-gris-200 transition group-hover/case:text-ogar-400">+</span>
                                                    </template>
                                                </button>
                                            </td>
                                        </template>
                                    </tr>
                                </template>

                                <tr x-show="creneaux.length === 0" x-cloak>
                                    <td colspan="{{ count($jours) + 1 }}" class="border border-gris-200 p-6 text-center text-gris-400">
                                        Aucun créneau horaire. Ajoutez-en un pour commencer.
                                    </td>
                                </tr>
                            </tbody>

                            {{-- Volume horaire par jour : le déséquilibre d'une
                                 semaine se voit sur cette ligne. --}}
                            <tfoot>
                                <tr class="bg-gris-50">
                                    <td class="border border-gris-200 px-2 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                                        Heures par jour
                                    </td>
                                    <template x-for="jour in jours" :key="'total-' + jour">
                                        <td class="border border-gris-200 px-2 py-1.5 text-center text-xs font-semibold text-gris-700"
                                            x-text="heuresDuJour(jour) || '—'"></td>
                                    </template>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Enregistrement --}}
                    <form method="POST" action="{{ route('schedules.store') }}"
                          class="flex flex-wrap items-center justify-between gap-3 border-t border-gris-200 p-4">
                        @csrf
                        <input type="hidden" name="class_id" value="{{ $classe->id }}">
                        <input type="hidden" name="academic_year_id" value="{{ $annee->id }}">

                        <template x-for="(c, i) in aEnregistrer()" :key="i">
                            <span>
                                <input type="hidden" :name="`creneaux[${i}][jour]`" :value="c.jour">
                                <input type="hidden" :name="`creneaux[${i}][debut]`" :value="c.debut">
                                <input type="hidden" :name="`creneaux[${i}][fin]`" :value="c.fin">
                                <input type="hidden" :name="`creneaux[${i}][type]`" :value="c.type">
                                <input type="hidden" :name="`creneaux[${i}][matiere_id]`" :value="c.matiere_id ?? ''">
                                <input type="hidden" :name="`creneaux[${i}][enseignant_id]`" :value="c.enseignant_id ?? ''">
                                <input type="hidden" :name="`creneaux[${i}][salle]`" :value="c.salle ?? ''">
                                <input type="hidden" :name="`creneaux[${i}][titre]`" :value="c.titre ?? ''">
                            </span>
                        </template>

                        <p class="text-xs text-gris-400">
                            L’enregistrement remplace l’emploi du temps actuel de {{ $classe->name }}.
                        </p>

                        <div class="flex gap-2">
                            <button type="button" @click="toutEffacer()" class="bouton-secondaire">Vider la grille</button>
                            <button type="submit" class="bouton-primaire">Enregistrer l’emploi du temps</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @endif

@endsection

@push('scripts')
<script>
    function constructeurEmploiDuTemps(donnees) {
        return {
            creneaux: donnees.creneaux.map(c => ({ debut: c.debut, fin: c.fin })),
            matieres: donnees.matieres,
            enseignants: donnees.enseignants,
            occupations: donnees.occupations,
            jours: donnees.jours,
            couleurs: donnees.couleurs,
            cases: {},
            outil: { type: 'course', matiere_id: null, enseignant_id: '', salle: '' },

            init() {
                // La grille enregistree est rangee par (jour, ligne horaire) :
                // c'est l'index de ligne qui sert de cle, pour que modifier un
                // horaire ne fasse pas perdre les cases posees.
                donnees.grille.forEach(ligne => {
                    const i = this.creneaux.findIndex(c => c.debut === ligne.debut && c.fin === ligne.fin);
                    if (i === -1) return;

                    this.cases[`${ligne.jour}-${i}`] = {
                        type: ligne.type === 'break' ? 'break' : 'course',
                        matiere_id: ligne.matiere_id,
                        enseignant_id: ligne.enseignant_id ?? '',
                        salle: ligne.salle ?? '',
                        titre: ligne.titre ?? '',
                    };
                });
            },

            /* ----- palette ----- */

            choisirMatiere(id) {
                this.outil = { type: 'course', matiere_id: id, enseignant_id: '', salle: this.outil.salle };
                const possibles = this.enseignantsDeLaMatiere();
                if (possibles.length === 1) this.outil.enseignant_id = possibles[0].id;
            },
            choisirPause() { this.outil = { type: 'break', matiere_id: null, enseignant_id: '', salle: '' }; },
            choisirGomme() { this.outil = { type: 'gomme', matiere_id: null, enseignant_id: '', salle: '' }; },

            enseignantsDeLaMatiere() {
                if (!this.outil.matiere_id) return [];
                const rattaches = this.enseignants.filter(e => e.matieres.includes(this.outil.matiere_id));
                // Sans rattachement matiere saisi, tout enseignant reste proposable.
                return rattaches.length > 0 ? rattaches : this.enseignants;
            },

            /* ----- grille ----- */

            case_(jour, i) { return this.cases[`${jour}-${i}`] ?? null; },

            peindre(jour, i) {
                const cle = `${jour}-${i}`;

                if (this.outil.type === 'gomme') { delete this.cases[cle]; return; }
                if (this.outil.type === 'break') {
                    this.cases[cle] = { type: 'break', matiere_id: null, enseignant_id: '', salle: '', titre: 'Pause' };
                    return;
                }
                if (!this.outil.matiere_id) return;

                this.cases[cle] = {
                    type: 'course',
                    matiere_id: this.outil.matiere_id,
                    enseignant_id: this.outil.enseignant_id ?? '',
                    salle: this.outil.salle ?? '',
                    titre: '',
                };
            },

            nomDeMatiere(id) { return (this.matieres.find(m => m.id === id) || {}).name ?? ''; },
            nomEnseignant(id) { return (this.enseignants.find(e => e.id === Number(id)) || {}).nom ?? ''; },

            intituleDeCase(jour, i) {
                const c = this.case_(jour, i);
                if (!c) return '';
                return c.type === 'break' ? (c.titre || 'Pause') : this.nomDeMatiere(c.matiere_id);
            },

            enseignantDeCase(jour, i) {
                const c = this.case_(jour, i);
                if (!c || c.type === 'break') return '';
                const nom = this.nomEnseignant(c.enseignant_id);
                return c.salle ? `${nom} · ${c.salle}`.trim() : nom;
            },

            /* Couleurs posees en style : une classe Tailwind construite a la
               volee n'existe pas dans la feuille compilee. */
            couleurDe(matiereId) {
                return this.couleurs[matiereId]
                    ?? { fond: '#f1f5f9', bord: '#cbd5e1', encre: '#334155', vif: '#64748b' };
            },

            styleDeCase(jour, i) {
                const c = this.case_(jour, i);

                if (!c) return 'background:#fff;border-color:#f1f5f9';
                if (c.type === 'break') return 'background:#f8fafc;border-color:#e2e8f0;color:#94a3b8';

                const teinte = this.couleurDe(c.matiere_id);

                if (this.enConflit(jour, i)) {
                    return `background:${teinte.fond};border-color:#fb7185;box-shadow:inset 0 0 0 1px #fb7185;color:${teinte.encre}`;
                }

                return `background:${teinte.fond};border-color:${teinte.bord};color:${teinte.encre}`;
            },

            /* Rappel de l'outil actif, affiche au-dessus de la grille. */
            libelleDeLOutil() {
                if (this.outil.type === 'gomme') return 'Gomme';
                if (this.outil.type === 'break') return 'Récréation / pause';
                if (!this.outil.matiere_id) return 'Choisissez une matière';

                return this.nomDeMatiere(this.outil.matiere_id);
            },

            styleDeLOutil() {
                if (this.outil.type === 'gomme') return 'background:#ffe4e6;border-color:#fda4af;color:#9f1239';
                if (this.outil.type === 'break') return 'background:#f1f5f9;border-color:#cbd5e1;color:#475569';
                if (!this.outil.matiere_id) return 'background:#fff;border-color:#e2e8f0;color:#94a3b8';

                const teinte = this.couleurDe(this.outil.matiere_id);

                return `background:${teinte.fond};border-color:${teinte.bord};color:${teinte.encre}`;
            },

            /* Heures deja posees pour une matiere : de quoi equilibrer la
               semaine sans recompter les cases a la main. */
            heuresDeLaMatiere(matiereId) {
                return Object.values(this.cases)
                    .filter((c) => c.type === 'course' && c.matiere_id === matiereId)
                    .length;
            },

            heuresDuJour(jour) {
                return this.creneaux.reduce((n, _, i) => {
                    const c = this.case_(jour, i);

                    return n + (c && c.type === 'course' ? 1 : 0);
                }, 0);
            },

            /* ----- conflits d'enseignant ----- */

            enConflit(jour, i) { return this.messageDeConflit(jour, i) !== ''; },

            messageDeConflit(jour, i) {
                const c = this.case_(jour, i);
                if (!c || c.type !== 'course' || !c.enseignant_id) return '';

                const debut = this.creneaux[i]?.debut;
                const ailleurs = this.occupations.find(o =>
                    Number(o.enseignant_id) === Number(c.enseignant_id) && o.jour === jour && o.debut === debut);

                return ailleurs ? `Déjà en ${ailleurs.classe}` : '';
            },

            nombreDeConflits() {
                return this.jours.reduce((total, jour) =>
                    total + this.creneaux.reduce((n, _, i) => n + (this.enConflit(jour, i) ? 1 : 0), 0), 0);
            },

            nombreDeCours() {
                return Object.values(this.cases).filter(c => c.type === 'course').length;
            },

            /* ----- lignes horaires ----- */

            ajouterCreneau() {
                const derniere = this.creneaux[this.creneaux.length - 1];
                const debut = derniere ? derniere.fin : '08:00';
                this.creneaux.push({ debut, fin: this.plusUneHeure(debut) });
            },

            retirerCreneau(i) {
                // Les cases sont indexees par ligne : elles remontent d'un cran.
                const restantes = {};
                Object.entries(this.cases).forEach(([cle, valeur]) => {
                    const [jour, ligne] = cle.split('-').map(Number);
                    if (ligne === i) return;
                    restantes[`${jour}-${ligne > i ? ligne - 1 : ligne}`] = valeur;
                });
                this.cases = restantes;
                this.creneaux.splice(i, 1);
            },

            /* Duree affichee a cote de la plage : 1 h, 45 min, 1 h 30. */
            duree(creneau) {
                const [hd, md] = (creneau.debut || '0:0').split(':').map(Number);
                const [hf, mf] = (creneau.fin || '0:0').split(':').map(Number);
                const minutes = (hf * 60 + mf) - (hd * 60 + md);

                if (minutes <= 0) return '—';
                if (minutes < 60) return `${minutes} min`;

                const heures = Math.floor(minutes / 60);
                const reste = minutes % 60;

                return reste === 0 ? `${heures} h` : `${heures} h ${reste}`;
            },

            plusUneHeure(heure) {
                const [h, m] = heure.split(':').map(Number);
                return `${String((h + 1) % 24).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
            },

            toutEffacer() {
                if (confirm('Vider toute la grille ? Les créneaux ne seront supprimés qu’à l’enregistrement.')) {
                    this.cases = {};
                }
            },

            /* ----- enregistrement ----- */

            aEnregistrer() {
                const lignes = [];

                this.jours.forEach(jour => {
                    this.creneaux.forEach((creneau, i) => {
                        const c = this.case_(jour, i);
                        if (!c) return;

                        lignes.push({
                            jour,
                            debut: creneau.debut,
                            fin: creneau.fin,
                            type: c.type,
                            matiere_id: c.matiere_id,
                            enseignant_id: c.enseignant_id,
                            salle: c.salle,
                            titre: c.titre,
                        });
                    });
                });

                return lignes;
            },
        };
    }
</script>
@endpush
