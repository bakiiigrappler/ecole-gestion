@extends('layouts.app')

@section('titre', 'Nouvelle classe')
@section('sous-titre', 'Créer une classe et composer son équipe pédagogique')

@section('actions-entete')
    <a href="{{ route('classes.index') }}" class="bouton-secondaire">Retour à la liste</a>
@endsection

@section('contenu')

@php
    $libellesCycle = [
        'preprimaire' => 'Préprimaire',
        'primaire' => 'Primaire',
        'college' => 'Collège',
        'lycee' => 'Lycée',
    ];

    $cyclesParNiveau = $levels->mapWithKeys(fn ($n) => [(string) $n->id => $n->cycle])->all();

    $candidatsJs = $enseignants->map(fn ($e) => [
        'id' => (string) $e->id,
        'nom' => $e->full_name,
        'matricule' => $e->employee_id,
        'cycle' => $e->cycle,
        'specialite' => $e->teacher_type === 'general' ? 'Polyvalent' : ($e->specialization ?: 'Spécialisé'),
        'initiales' => mb_strtoupper(mb_substr($e->first_name, 0, 1).mb_substr($e->last_name, 0, 1)),
        'charge' => (int) ($chargeParEnseignant[$e->id] ?? 0),
    ])->values();
@endphp

<form method="POST" action="{{ route('classes.store') }}"
      x-data="{
          niveau: '{{ old('level_id') }}',
          serieId: '{{ old('series_id') }}',
          nom: @js(old('name')),
          nomManuel: {{ old('name') ? 'true' : 'false' }},
          nomEnCours: false,

          {{-- Chiffres (« 6ème 1 ») ou lettres (« 6ème A ») : le serveur propose
               d'abord la numérotation déjà pratiquée au niveau choisi. --}}
          numerotation: '{{ old('numerotation', 'chiffre') }}',

          cycles: {{ Js::from($cyclesParNiveau) }},
          libelles: {{ Js::from($libellesCycle) }},
          series: [],

          equipe: {{ Js::from(collect(old('enseignants', []))->map(fn ($i) => (string) $i)->values()) }},
          principal: '{{ old('principal') }}',
          recherche: '',
          ouvert: false,
          surligne: 0,
          candidats: {{ Js::from($candidatsJs) }},

          init() {
              if (this.niveau) this.chargerSeries();
          },

          get cycle() { return this.cycles[this.niveau] || ''; },
          get cycleLisible() { return this.libelles[this.cycle] || 'Choisissez un niveau'; },

          {{-- Les séries dépendent du niveau : la liste vient du référentiel,
               au lieu d'un tableau écrit en dur dans un fichier JavaScript. --}}
          async chargerSeries() {
              this.series = [];
              if (! this.niveau) { this.serieId = ''; this.proposerNom(true); return; }

              try {
                  const r = await fetch('{{ route('classes.seriesDuNiveau') }}?level_id=' + this.niveau,
                                        { headers: { Accept: 'application/json' } });
                  if (r.ok) this.series = (await r.json()).series || [];
              } catch (e) { /* le champ reste simplement vide */ }

              if (! this.series.some((s) => String(s.id) === this.serieId)) this.serieId = '';
              this.proposerNom(true);
          },

          {{-- Nom proposé par le serveur : « <Niveau> <numéro> », le premier libre. --}}
          async proposerNom(suivreLeNiveau = false) {
              if (this.nomManuel || ! this.niveau) return;

              this.nomEnCours = true;
              try {
                  const params = new URLSearchParams({
                      level_id: this.niveau,
                      series_id: this.serieId || '',
                      numerotation: suivreLeNiveau ? '' : this.numerotation,
                  });
                  const r = await fetch('{{ route('classes.proposerNom') }}?' + params,
                                        { headers: { Accept: 'application/json' } });
                  if (r.ok) {
                      const data = await r.json();
                      this.nom = data.nom;
                      if (suivreLeNiveau) this.numerotation = data.numerotation;
                  }
              } catch (e) { /* l'utilisateur peut toujours saisir le nom */ }
              this.nomEnCours = false;
          },

          reprendreLeNom() {
              this.nomManuel = true;
              this.$nextTick(() => this.$refs.champNom.focus());
          },
          regenererLeNom() {
              this.nomManuel = false;
              this.proposerNom();
          },

          {{-- Équipe : mêmes règles que sur la fiche de classe. --}}
          get membres() {
              return this.equipe.map((id) => this.candidats.find((c) => c.id === id)).filter(Boolean);
          },
          get resultats() {
              const motif = this.recherche.trim().toLowerCase();
              let libres = this.candidats.filter((c) => ! this.equipe.includes(c.id));

              {{-- Un enseignant de collège n'a rien à faire devant une classe de primaire. --}}
              if (this.cycle) libres = libres.filter((c) => c.cycle === this.cycle);

              if (motif) {
                  libres = libres.filter((c) =>
                      [c.nom, c.matricule, c.specialite].some((v) => (v || '').toLowerCase().includes(motif))
                  );
              }
              return libres.slice(0, 30);
          },
          ajouter(candidat) {
              if (! candidat || this.equipe.includes(candidat.id)) return;
              this.equipe.push(candidat.id);
              if (! this.principal) this.principal = candidat.id;
              this.recherche = '';
              this.surligne = 0;
              this.ouvert = false;
          },
          retirer(id) {
              this.equipe = this.equipe.filter((x) => x !== id);
              if (this.principal === id) this.principal = this.equipe[0] || '';
          },
          deplacer(pas) {
              this.ouvert = true;
              const n = this.resultats.length;
              if (! n) return;
              this.surligne = (this.surligne + pas + n) % n;
          },
          valider() {
              if (this.ouvert) this.ajouter(this.resultats[this.surligne]);
          },
      }"
      class="space-y-6">
    @csrf

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- ------------------------------------------------------------------
             Identification
             ------------------------------------------------------------------ --}}
        <div class="carte lg:col-span-2">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Identification de la classe</h2>
                <span class="text-xs text-gris-400"><span class="text-corail-600">*</span> champs obligatoires</span>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div>
                    <label class="etiquette" for="level_id">Niveau <span class="text-corail-600">*</span></label>
                    <select id="level_id" name="level_id" required x-model="niveau" @change="chargerSeries()"
                            class="champ @error('level_id') border-corail-500 @enderror">
                        <option value="">Sélectionner un niveau</option>
                        @foreach ($levels->groupBy('cycle') as $cycleNiveau => $niveaux)
                            <optgroup label="{{ $libellesCycle[$cycleNiveau] ?? ucfirst($cycleNiveau) }}">
                                @foreach ($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}" @selected((string) old('level_id') === (string) $niveau->id)>
                                        {{ $niveau->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('level_id') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette">Cycle</label>
                    <div class="flex h-[42px] items-center rounded-lg border border-gris-200 bg-gris-50 px-3 text-sm text-gris-600">
                        <span x-text="cycleLisible"></span>
                    </div>
                    <p class="mt-1 text-xs text-gris-400">Déduit du niveau, il ne se saisit pas.</p>
                </div>

                <div x-show="series.length" x-cloak class="sm:col-span-2">
                    <label class="etiquette" for="series_id">Série</label>
                    <select id="series_id" name="series_id" x-model="serieId" @change="proposerNom()"
                            class="champ @error('series_id') border-corail-500 @enderror">
                        <option value="">Aucune série</option>
                        <template x-for="serie in series" :key="serie.id">
                            <option :value="serie.id" x-text="serie.code + ' — ' + serie.name"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-gris-400">
                        Séries ouvertes à ce niveau. Elle entre dans le nom proposé.
                    </p>
                    @error('series_id') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="numerotation">Numérotation</label>
                    <select id="numerotation" name="numerotation" x-model="numerotation" @change="proposerNom()"
                            :disabled="nomManuel"
                            class="champ" :class="nomManuel ? 'bg-gris-50 text-gris-400' : ''">
                        <option value="chiffre">Chiffres — 1, 2, 3…</option>
                        <option value="lettre">Lettres — A, B, C…</option>
                    </select>
                    <p class="mt-1 text-xs text-gris-400">
                        La numérotation déjà employée au niveau est reprise par défaut.
                    </p>
                </div>

                <div>
                    <div class="flex items-baseline justify-between gap-2">
                        <label class="etiquette" for="name">Nom de la classe <span class="text-corail-600">*</span></label>
                        <button type="button" class="cursor-pointer text-[11px] font-semibold text-ogar-600 hover:underline"
                                x-text="nomManuel ? 'Proposer automatiquement' : 'Saisir manuellement'"
                                @click="nomManuel ? regenererLeNom() : reprendreLeNom()"></button>
                    </div>

                    <div class="relative">
                        <input id="name" name="name" type="text" required maxlength="255"
                               x-ref="champNom" x-model="nom"
                               :readonly="! nomManuel"
                               placeholder="Choisissez d’abord un niveau"
                               class="champ @error('name') border-corail-500 @enderror"
                               :class="nomManuel ? '' : 'bg-gris-50 text-gris-600'">

                        <span x-show="nomEnCours" x-cloak
                              class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] text-gris-400">…</span>
                    </div>

                    <p class="mt-1 text-xs text-gris-400" x-show="! nomManuel">
                        Niveau, série éventuelle, puis
                        <span x-text="numerotation === 'lettre' ? 'la première lettre libre' : 'le premier numéro libre'"></span>.
                    </p>
                    <p class="mt-1 text-xs text-gris-400" x-show="nomManuel" x-cloak>
                        Ce nom apparaît sur les listes d’appel, les bulletins et les reçus.
                    </p>
                    @error('name') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="etiquette" for="description">Description</label>
                    <textarea id="description" name="description" rows="3"
                              placeholder="Spécificité de la classe, options, remarques…"
                              class="champ @error('description') border-corail-500 @enderror">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------------
             Capacité et ouverture (colonne latérale)
             ------------------------------------------------------------------ --}}
        <div class="space-y-6">
            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Capacité et ouverture</h2>
                </div>
                <div class="space-y-4 p-5">
                    <div>
                        <label class="etiquette" for="capacity">Capacité d’accueil</label>
                        <input id="capacity" name="capacity" type="number" min="1" max="200"
                               value="{{ old('capacity', 30) }}" placeholder="30"
                               class="champ @error('capacity') border-corail-500 @enderror">
                        <p class="mt-1 text-xs text-gris-400">
                            Nombre maximal d’élèves. Modifiable à tout moment.
                        </p>
                        @error('capacity') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gris-200 p-3 transition-colors hover:bg-gris-50">
                        {{-- Champ caché : sans lui, décocher n'envoie rien et la classe naîtrait fermée. --}}
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="mt-0.5 h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                        <span>
                            <span class="block text-sm font-medium text-gris-900">Ouvrir la classe</span>
                            <span class="block text-xs text-gris-400">
                                Une classe fermée n’accepte aucune inscription et disparaît des listes de saisie.
                            </span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Prochaines étapes</h2>
                </div>
                <div class="p-5 text-xs text-gris-500">
                    <p>Une fois la classe créée, vous pourrez :</p>
                    <ul class="mt-2 space-y-1 pl-4 [&>li]:list-disc">
                        <li>y inscrire des élèves depuis le module Inscriptions ;</li>
                        <li>compléter l’équipe et couvrir les matières du cycle ;</li>
                        <li>planifier son emploi du temps.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         Équipe pédagogique
         ------------------------------------------------------------------ --}}
    <div class="carte">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Équipe pédagogique</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    <span x-text="equipe.length"></span> enseignant(s) &middot; facultatif, complétable plus tard
                </p>
            </div>
        </div>

        @error('principal') <p class="px-5 pt-4 text-xs text-corail-700">{{ $message }}</p> @enderror

        <div class="space-y-4 p-5">
            <div class="relative" @click.outside="ouvert = false">
                <label class="etiquette" for="ajout-enseignant">Ajouter un enseignant</label>
                <input id="ajout-enseignant" type="text" class="champ" autocomplete="off"
                       placeholder="Nom, matricule ou spécialité…"
                       x-model="recherche"
                       :disabled="! niveau"
                       @focus="ouvert = true" @input="ouvert = true; surligne = 0"
                       @keydown.escape.prevent="ouvert = false"
                       @keydown.arrow-down.prevent="deplacer(1)"
                       @keydown.arrow-up.prevent="deplacer(-1)"
                       @keydown.enter.prevent="valider()"
                       role="combobox" aria-autocomplete="list" :aria-expanded="ouvert">

                <p class="mt-1 text-[11px] text-gris-400">
                    <span x-show="! niveau">Choisissez d’abord un niveau.</span>
                    <span x-show="niveau" x-cloak>
                        Seuls les enseignants du cycle <span x-text="cycleLisible.toLowerCase()"></span> sont proposés.
                    </span>
                </p>

                <ul x-show="ouvert && niveau" x-cloak role="listbox"
                    class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-gris-200 bg-white shadow-lg">
                    <template x-for="(candidat, i) in resultats" :key="candidat.id">
                        <li role="option" :aria-selected="i === surligne"
                            @click="ajouter(candidat)" @mouseenter="surligne = i"
                            class="flex cursor-pointer items-center gap-3 px-3 py-2 transition-colors"
                            :class="i === surligne ? 'bg-ogar-50' : ''">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gris-100 text-[10px] font-bold text-gris-600"
                                  x-text="candidat.initiales"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-gris-800" x-text="candidat.nom"></span>
                                <span class="block truncate text-[11px] text-gris-400">
                                    <span class="font-mono" x-text="candidat.matricule"></span>
                                    &middot; <span x-text="candidat.specialite"></span>
                                    &middot; <span x-text="candidat.charge"></span> classe(s)
                                </span>
                            </span>
                        </li>
                    </template>
                    <li x-show="! resultats.length" class="px-3 py-6 text-center text-sm text-gris-400">
                        Aucun enseignant disponible ne correspond.
                    </li>
                </ul>
            </div>

            <div class="space-y-2 border-t border-gris-100 pt-4">
                <p x-show="! membres.length" class="py-4 text-center text-sm text-gris-400">
                    Aucun enseignant pour l’instant. L’équipe se compose aussi après la création.
                </p>

                <template x-for="membre in membres" :key="membre.id">
                    <div class="flex flex-wrap items-center gap-3 rounded-xl border p-3"
                         :class="principal === membre.id ? 'border-ogar-300 bg-ogar-50/40' : 'border-gris-200'">
                        <input type="hidden" name="enseignants[]" :value="membre.id">

                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-ogar-100 text-xs font-bold text-ogar-800"
                              x-text="membre.initiales"></span>

                        <div class="min-w-0 flex-1">
                            <div class="truncate font-semibold text-gris-900" x-text="membre.nom"></div>
                            <div class="truncate text-xs text-gris-400">
                                <span class="font-mono" x-text="membre.matricule"></span>
                                &middot; <span x-text="membre.specialite"></span>
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2 whitespace-nowrap text-sm text-gris-700">
                            <input type="radio" name="principal" :value="membre.id" x-model="principal"
                                   class="h-4 w-4 cursor-pointer border-gris-300 text-ogar-600 focus:ring-ogar-600">
                            Professeur principal
                        </label>

                        <button type="button" @click="retirer(membre.id)"
                                class="cursor-pointer rounded-lg p-2 text-gris-400 transition-colors hover:bg-corail-50 hover:text-corail-600"
                                title="Retirer de l’équipe">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         Barre d'actions
         ------------------------------------------------------------------ --}}
    <div class="carte flex flex-wrap items-center gap-3 p-4">
        <button type="submit" class="bouton-primaire">Créer la classe</button>
        <a href="{{ route('classes.index') }}" class="bouton-secondaire">Annuler</a>
    </div>
</form>

@endsection
