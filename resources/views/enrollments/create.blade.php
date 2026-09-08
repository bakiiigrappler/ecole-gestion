@extends('layouts.app')

@section('titre', 'Nouvelle inscription')
@section('sous-titre', 'Déposer un dossier d’inscription ou réinscrire un élève')

@section('actions-entete')
    <a href="{{ route('enrollments.index') }}" class="bouton-secondaire">Toutes les inscriptions</a>
@endsection

@section('contenu')

@php
    $libellesCycle = [
        'preprimaire' => 'Préprimaire',
        'primaire' => 'Primaire',
        'college' => 'Collège',
        'lycee' => 'Lycée',
    ];

    $classesJs = $classes->map(fn ($c) => [
        'id' => (string) $c->id,
        'nom' => $c->name,
        'cycle' => $c->getSafeCycle(),
        'niveau' => $c->getSafeLevelName(),
        'levelId' => (string) $c->level_id,
        'capacite' => (int) $c->capacity,
        'effectif' => (int) ($effectifs[$c->id] ?? 0),
    ])->values();

    $anneesJs = $academicYears->map(fn ($a) => ['id' => (string) $a->id, 'nom' => $a->name])->values();
@endphp

<form method="POST" action="{{ route('enrollments.store') }}"
      x-data="{
          type: '{{ old('enrollment_type', 'nouvelle') }}',
          classes: {{ Js::from($classesJs) }},
          annees: {{ Js::from($anneesJs) }},

          anneeId: '{{ old('academic_year_id', $anneeCourante?->id) }}',
          classeId: '{{ old('class_id') }}',
          cycleFiltre: '',

          matricule: @js(old('reinscription_student_id') ?? ''),
          saisie: @js(old('reinscription_student_id') ?? ''),
          recherche: false,
          eleve: null,
          erreurRecherche: '',

          {{-- Suggestions pendant la frappe : plus besoin de connaître le
               matricule par cœur ni d'appuyer sur un bouton. --}}
          suggestions: [],
          suggestionEnCours: false,
          listeOuverte: false,
          surligneSuggestion: 0,

          total: {{ (float) old('total_fees', 0) }},
          verse: {{ (float) old('amount_paid', 0) }},
          totalManuel: {{ old('total_fees') ? 'true' : 'false' }},
          fraisEnCours: false,
          fraisObligatoires: [],
          fraisOptionnels: [],
          optionsCochees: [],

          moyen: '{{ old('payment_method') }}',
          operateur: '{{ old('mobile_money_provider') }}',

          {{-- Champs renseignés, suivis en direct : le récapitulatif indique ce
               qui manque avant d'envoyer, au lieu de le découvrir au retour. --}}
          identite: { prenom: '', nom: '', naissance: '', sexe: '', adresse: '' },

          init() {
              if (this.classeId) this.chargerFrais();
              this.$nextTick(() => this.relireIdentite());
          },

          relireIdentite() {
              this.identite = {
                  prenom: this.$refs.prenom?.value || '',
                  nom: this.$refs.nom?.value || '',
                  naissance: this.$refs.naissance?.value || '',
                  sexe: this.$refs.sexe?.value || '',
                  adresse: this.$refs.adresse?.value || '',
              };
          },

          get nomComplet() {
              const n = (this.identite.prenom + ' ' + this.identite.nom).trim();
              return n || null;
          },
          get classeLisible() {
              const c = this.classeChoisie;
              if (! c) return null;
              return c.nom + ' — ' + c.niveau;
          },
          get anneeLisible() {
              const a = this.annees.find((x) => x.id === String(this.anneeId));
              return a ? a.nom : null;
          },

          get etapes() {
              return [
                  {
                      cle: 'identite',
                      libelle: 'Identité de l’inscrit',
                      fait: !! (this.identite.prenom && this.identite.nom
                                && this.identite.naissance && this.identite.sexe && this.identite.adresse),
                  },
                  {
                      cle: 'affectation',
                      libelle: 'Classe et année',
                      fait: !! (this.classeId && this.anneeId),
                  },
                  {
                      cle: 'reglement',
                      libelle: 'Montant à payer',
                      fait: this.total > 0,
                  },
              ].concat(this.reinscription ? [{
                  cle: 'eleve',
                  libelle: 'Élève retrouvé',
                  fait: !! this.eleve,
              }] : []);
          },
          get etapesFaites() { return this.etapes.filter((e) => e.fait).length; },
          get complet() { return this.etapesFaites === this.etapes.length; },

          get reinscription() { return this.type === 'reinscription'; },

          {{-- Classes proposées : filtrées par cycle pour s'y retrouver parmi 37. --}}
          get classesProposees() {
              return this.cycleFiltre
                  ? this.classes.filter((c) => c.cycle === this.cycleFiltre)
                  : this.classes;
          },
          get classeChoisie() {
              return this.classes.find((c) => c.id === this.classeId) || null;
          },
          get placesRestantes() {
              const c = this.classeChoisie;
              if (! c || ! c.capacite) return null;
              return c.capacite - c.effectif;
          },

          {{-- Le montant vient du référentiel des frais, il n'est plus tapé de
               mémoire. Le détail reste visible pour qu'on sache ce qu'on facture. --}}
          async chargerFrais() {
              const classe = this.classeChoisie;
              this.fraisObligatoires = [];
              this.fraisOptionnels = [];
              this.optionsCochees = [];

              if (! classe) { this.recalculerTotal(); return; }

              this.fraisEnCours = true;
              try {
                  const r = await fetch('{{ route('enrollments.fraisDuNiveau') }}?level_id=' + classe.levelId,
                                        { headers: { Accept: 'application/json' } });
                  if (r.ok) {
                      const data = await r.json();
                      this.fraisObligatoires = data.obligatoires || [];
                      this.fraisOptionnels = data.optionnels || [];
                  }
              } catch (e) { /* le montant reste saisissable à la main */ }
              this.fraisEnCours = false;
              this.recalculerTotal();
          },

          get totalOptions() {
              return this.fraisOptionnels
                  .filter((f) => this.optionsCochees.includes(String(f.id)))
                  .reduce((s, f) => s + Number(f.amount), 0);
          },
          get totalReferentiel() {
              return this.fraisObligatoires.reduce((s, f) => s + Number(f.amount), 0) + this.totalOptions;
          },
          recalculerTotal() {
              if (this.totalManuel) return;
              this.total = this.totalReferentiel;
          },

          reprendreLeTotal() {
              this.totalManuel = true;
              this.$nextTick(() => this.$refs.champTotal.focus());
          },
          recomposerLeTotal() {
              this.totalManuel = false;
              this.recalculerTotal();
          },

          get reste() { return Math.max(this.total - this.verse, 0); },
          get statutReglement() {
              if (this.verse <= 0) return { libelle: 'Impayé', classe: 'text-corail-700' };
              if (this.verse >= this.total) return { libelle: 'Soldé', classe: 'text-emerald-700' };
              return { libelle: 'Partiel', classe: 'text-soleil-700' };
          },
          get versementExcessif() { return this.verse > this.total; },

          franc(n) {
              return new Intl.NumberFormat('fr-FR').format(Math.round(n || 0)) + ' F';
          },

          async suggerer() {
              const motif = (this.saisie || '').trim();
              this.eleve = null;
              this.matricule = '';
              this.erreurRecherche = '';
              this.surligneSuggestion = 0;
              this.listeOuverte = true;

              if (motif.length < 2) { this.suggestions = []; return; }

              this.suggestionEnCours = true;
              try {
                  const params = new URLSearchParams({ q: motif, academic_year_id: this.anneeId || '' });
                  const r = await fetch('{{ route('enrollments.suggererEleves') }}?' + params,
                                        { headers: { Accept: 'application/json' } });
                  if (r.ok) this.suggestions = (await r.json()).eleves || [];
              } catch (e) {
                  this.suggestions = [];
              }
              this.suggestionEnCours = false;
          },

          deplacerSuggestion(pas) {
              this.listeOuverte = true;
              const n = this.suggestions.length;
              if (! n) return;
              this.surligneSuggestion = (this.surligneSuggestion + pas + n) % n;
          },
          validerSuggestion() {
              if (this.listeOuverte && this.suggestions.length) {
                  this.choisirEleve(this.suggestions[this.surligneSuggestion]);
              }
          },

          {{-- Un clic sur une proposition vaut validation : on va chercher la
               fiche complète et on remplit l'identité. --}}
          choisirEleve(item) {
              if (! item) return;
              this.saisie = item.nom + ' — ' + item.matricule;
              this.matricule = item.matricule;
              this.listeOuverte = false;
              this.suggestions = [];
              this.chercherEleve();
          },

          oublierEleve() {
              this.eleve = null;
              this.matricule = '';
              this.saisie = '';
              this.suggestions = [];
              this.erreurRecherche = '';
              this.$nextTick(() => this.$refs.champRecherche.focus());
          },

          {{-- Réinscription : on reprend l'identité de l'élève retenu au lieu
               de la ressaisir. --}}
          async chercherEleve() {
              const m = (this.matricule || '').trim();
              this.eleve = null;
              this.erreurRecherche = '';
              if (! m) return;

              this.recherche = true;
              try {
                  const params = new URLSearchParams({ matricule: m, academic_year_id: this.anneeId || '' });
                  const r = await fetch('{{ route('enrollments.rechercherEleve') }}?' + params,
                                        { headers: { Accept: 'application/json' } });
                  const data = await r.json();

                  if (! data.trouve) {
                      this.erreurRecherche = data.message || 'Élève introuvable.';
                  } else if (data.deja_inscrit) {
                      this.erreurRecherche = 'Cet élève est déjà inscrit en ' + data.deja_inscrit.classe
                                           + ' pour ' + data.deja_inscrit.annee + '.';
                  } else {
                      this.eleve = data;
                      this.$refs.prenom.value = data.eleve.prenom || '';
                      this.$refs.nom.value = data.eleve.nom || '';
                      this.$refs.naissance.value = data.eleve.naissance || '';
                      this.$refs.telephone.value = data.eleve.telephone || '';
                      this.$refs.courriel.value = data.eleve.email || '';
                      this.$refs.adresse.value = data.eleve.adresse || '';
                      if (data.eleve.sexe) this.$refs.sexe.value = data.eleve.sexe;
                      if (data.precedente) {
                          this.$refs.classePrecedente.value = data.precedente.class_id || '';
                          this.$refs.anneePrecedente.value = data.precedente.academic_year_id || '';
                      }
                  }
              } catch (e) {
                  this.erreurRecherche = 'La recherche n’a pas abouti.';
              }
              this.recherche = false;
          },
      }"
      class="space-y-6">
    @csrf

    {{-- ------------------------------------------------------------------
         Type de dossier
         ------------------------------------------------------------------ --}}
    <div class="carte">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Type de dossier</h2>
        </div>
        <div class="grid gap-3 p-5 sm:grid-cols-2">
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-colors"
                   :class="type === 'nouvelle' ? 'border-ogar-300 bg-ogar-50/50' : 'border-gris-200 hover:bg-gris-50'">
                <input type="radio" name="enrollment_type" value="nouvelle" x-model="type"
                       class="mt-0.5 h-4 w-4 cursor-pointer border-gris-300 text-ogar-600 focus:ring-ogar-600">
                <span>
                    <span class="block text-sm font-semibold text-gris-900">Nouvelle inscription</span>
                    <span class="block text-xs text-gris-500">
                        L’élève n’existe pas encore. Le dossier est déposé, puis le profil élève
                        est créé dans la foulée.
                    </span>
                </span>
            </label>

            <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-colors"
                   :class="type === 'reinscription' ? 'border-ogar-300 bg-ogar-50/50' : 'border-gris-200 hover:bg-gris-50'">
                <input type="radio" name="enrollment_type" value="reinscription" x-model="type"
                       class="mt-0.5 h-4 w-4 cursor-pointer border-gris-300 text-ogar-600 focus:ring-ogar-600">
                <span>
                    <span class="block text-sm font-semibold text-gris-900">Réinscription</span>
                    <span class="block text-xs text-gris-500">
                        L’élève est déjà dans l’établissement. Son identité est reprise depuis
                        son matricule.
                    </span>
                </span>
            </label>
        </div>

        {{-- Recherche de l'élève : les propositions arrivent pendant la frappe,
             on valide en cliquant l'une d'elles ou au clavier. --}}
        <div x-show="reinscription" x-cloak class="grid gap-5 border-t border-gris-100 p-5 md:grid-cols-2">

            {{-- Colonne de gauche : la recherche --}}
            <div>
            <label class="etiquette" for="recherche-eleve">
                Élève à réinscrire <span class="text-corail-600">*</span>
            </label>

            {{-- Le matricule retenu part avec le formulaire ; le champ visible
                 sert à chercher. --}}
            <input type="hidden" name="reinscription_student_id" :value="matricule">

            <div class="relative" @click.outside="listeOuverte = false">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gris-400"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                    </svg>

                    <input id="recherche-eleve" type="text" autocomplete="off"
                           x-ref="champRecherche"
                           x-model="saisie"
                           @input.debounce.250ms="suggerer()"
                           @focus="listeOuverte = true"
                           @keydown.escape.prevent="listeOuverte = false"
                           @keydown.arrow-down.prevent="deplacerSuggestion(1)"
                           @keydown.arrow-up.prevent="deplacerSuggestion(-1)"
                           @keydown.enter.prevent="validerSuggestion()"
                           placeholder="Matricule ou nom de l’élève…"
                           role="combobox" aria-autocomplete="list" :aria-expanded="listeOuverte"
                           class="champ pl-9 pr-9 @error('reinscription_student_id') border-corail-500 @enderror"
                           :class="eleve ? 'border-emerald-300 bg-emerald-50/40' : ''">

                    {{-- Indicateur d'attente, ou bouton d'effacement une fois choisi --}}
                    <span x-show="suggestionEnCours" x-cloak
                          class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gris-400">…</span>

                    {{-- Visible des qu'un eleve est retenu, y compris quand il s'avere deja
                         inscrit : sinon on ne pouvait plus changer de choix. --}}
                    <button type="button" x-show="(eleve || matricule) && ! suggestionEnCours" x-cloak
                            @click="oublierEleve()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 cursor-pointer rounded-lg p-1.5 text-gris-400 transition-colors hover:bg-gris-100 hover:text-gris-600"
                            title="Changer d’élève">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <ul x-show="listeOuverte && (suggestions.length || (saisie || '').trim().length >= 2)" x-cloak role="listbox"
                    class="absolute z-30 mt-1 max-h-80 w-full overflow-y-auto rounded-lg border border-gris-200 bg-white shadow-lg">
                    <template x-for="(item, i) in suggestions" :key="item.matricule">
                        <li role="option" :aria-selected="i === surligneSuggestion"
                            @click="choisirEleve(item)" @mouseenter="surligneSuggestion = i"
                            class="flex cursor-pointer items-center gap-3 px-3 py-2 transition-colors"
                            :class="[i === surligneSuggestion ? 'bg-ogar-50' : '', item.deja_inscrit ? 'opacity-60' : '']">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                                  :class="item.deja_inscrit ? 'bg-gris-100 text-gris-500' : 'bg-ogar-100 text-ogar-800'"
                                  x-text="item.initiales"></span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-gris-800" x-text="item.nom"></span>
                                <span class="block truncate text-[11px] text-gris-400">
                                    <span class="font-mono" x-text="item.matricule"></span>
                                    <template x-if="item.derniere_classe">
                                        <span> &middot; <span x-text="item.derniere_classe"></span>
                                            en <span x-text="item.derniere_annee"></span></span>
                                    </template>
                                </span>
                            </span>

                            <template x-if="item.deja_inscrit">
                                <span class="shrink-0 rounded-full bg-soleil-50 px-2 py-0.5 text-[10px] font-semibold text-soleil-700"
                                      x-text="'déjà en ' + item.deja_inscrit"></span>
                            </template>
                        </li>
                    </template>

                    <li x-show="! suggestions.length && ! suggestionEnCours"
                        class="px-3 py-6 text-center text-sm text-gris-400">
                        Aucun élève ne correspond à cette recherche.
                    </li>
                </ul>
            </div>

            <p class="mt-1 text-[11px] text-gris-400">
                Deux caractères suffisent. Les élèves déjà inscrits sur l’année choisie sont signalés.
            </p>

            @error('reinscription_student_id') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
            </div>

            {{-- Colonne de droite : l'issue de la recherche. Elle occupe la place
                 laissée vide par le champ et évite d'empiler les messages. --}}
            <div class="flex">
                {{-- Élève retenu --}}
                <div x-show="eleve" x-cloak
                     class="flex w-full items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800"
                          x-text="eleve
                              ? (eleve.eleve.prenom.charAt(0) + eleve.eleve.nom.charAt(0)).toUpperCase()
                              : ''"></span>
                    <div class="min-w-0 text-sm text-emerald-900">
                        <p class="font-semibold" x-text="eleve ? eleve.eleve.prenom + ' ' + eleve.eleve.nom : ''"></p>
                        <p class="font-mono text-[11px] text-emerald-700" x-text="eleve ? eleve.eleve.matricule : ''"></p>
                        <template x-if="eleve && eleve.precedente">
                            <p class="mt-1 text-xs text-emerald-700">
                                Dernière inscription : <span x-text="eleve.precedente.classe"></span>
                                en <span x-text="eleve.precedente.annee"></span>.
                            </p>
                        </template>
                        <p class="mt-1 text-xs text-emerald-700">Son identité est reprise ci-dessous.</p>
                    </div>
                </div>

                {{-- Recherche infructueuse --}}
                <div x-show="erreurRecherche && ! eleve" x-cloak
                     class="flex w-full items-start gap-3 rounded-xl border border-corail-200 bg-corail-50 px-4 py-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-corail-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                    <p class="text-sm text-corail-800" x-text="erreurRecherche"></p>
                </div>

                {{-- Au repos : ce que fera la recherche --}}
                <div x-show="! eleve && ! erreurRecherche" x-cloak
                     class="flex w-full items-start gap-3 rounded-xl border border-dashed border-gris-200 px-4 py-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-gris-300" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                    <p class="text-xs leading-relaxed text-gris-400">
                        Choisissez un élève dans la liste pour reprendre son identité, sa dernière
                        classe et son année. Rien n’est ressaisi à la main.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">

        {{-- ------------------------------------------------------------------
             Le dossier : identité, affectation, règlement
             ------------------------------------------------------------------ --}}
        <div class="space-y-6 lg:col-span-2">

            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Identité de l’inscrit</h2>
                    <span class="text-xs text-gris-400"><span class="text-corail-600">*</span> champs obligatoires</span>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <label class="etiquette" for="applicant_first_name">Prénom <span class="text-corail-600">*</span></label>
                        <input id="applicant_first_name" name="applicant_first_name" type="text" required maxlength="255"
                               x-ref="prenom" @input="relireIdentite()" value="{{ old('applicant_first_name') }}"
                               class="champ @error('applicant_first_name') border-corail-500 @enderror">
                        @error('applicant_first_name') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiquette" for="applicant_last_name">Nom <span class="text-corail-600">*</span></label>
                        <input id="applicant_last_name" name="applicant_last_name" type="text" required maxlength="255"
                               x-ref="nom" @input="relireIdentite()" value="{{ old('applicant_last_name') }}"
                               class="champ @error('applicant_last_name') border-corail-500 @enderror">
                        @error('applicant_last_name') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiquette" for="applicant_date_of_birth">Date de naissance <span class="text-corail-600">*</span></label>
                        <input id="applicant_date_of_birth" name="applicant_date_of_birth" type="date" required
                               x-ref="naissance" @change="relireIdentite()" max="{{ now()->subYear()->format('Y-m-d') }}"
                               value="{{ old('applicant_date_of_birth') }}"
                               class="champ @error('applicant_date_of_birth') border-corail-500 @enderror">
                        @error('applicant_date_of_birth') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiquette" for="applicant_gender">Sexe <span class="text-corail-600">*</span></label>
                        <select id="applicant_gender" name="applicant_gender" required x-ref="sexe" @change="relireIdentite()"
                                class="champ @error('applicant_gender') border-corail-500 @enderror">
                            <option value="">Sélectionner</option>
                            <option value="male" @selected(old('applicant_gender') === 'male')>Garçon</option>
                            <option value="female" @selected(old('applicant_gender') === 'female')>Fille</option>
                        </select>
                        @error('applicant_gender') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiquette" for="applicant_phone">Téléphone</label>
                        <input id="applicant_phone" name="applicant_phone" type="tel" maxlength="255"
                               x-ref="telephone" value="{{ old('applicant_phone') }}"
                               class="champ @error('applicant_phone') border-corail-500 @enderror">
                        @error('applicant_phone') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiquette" for="applicant_email">Courriel</label>
                        <input id="applicant_email" name="applicant_email" type="email" maxlength="255"
                               x-ref="courriel" value="{{ old('applicant_email') }}"
                               class="champ @error('applicant_email') border-corail-500 @enderror">
                        @error('applicant_email') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="etiquette" for="applicant_address">Adresse <span class="text-corail-600">*</span></label>
                        <textarea id="applicant_address" name="applicant_address" rows="2" required
                                  x-ref="adresse" @input="relireIdentite()"
                                  class="champ @error('applicant_address') border-corail-500 @enderror">{{ old('applicant_address') }}</textarea>
                        @error('applicant_address') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="carte">
                <div class="carte-entete">
                    <div>
                        <h2 class="text-sm font-semibold text-gris-900">Responsable déclaré</h2>
                        <p class="mt-0.5 text-xs text-gris-400">
                            Coordonnées du dépôt du dossier ; le rattachement au parent se fait
                            à la création de l’élève.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <label class="etiquette" for="parent_first_name">Prénom</label>
                        <input id="parent_first_name" name="parent_first_name" type="text" maxlength="255"
                               value="{{ old('parent_first_name') }}" class="champ">
                    </div>

                    <div>
                        <label class="etiquette" for="parent_last_name">Nom</label>
                        <input id="parent_last_name" name="parent_last_name" type="text" maxlength="255"
                               value="{{ old('parent_last_name') }}" class="champ">
                    </div>

                    <div>
                        <label class="etiquette" for="parent_relationship">Lien avec l’élève</label>
                        <select id="parent_relationship" name="parent_relationship" class="champ">
                            <option value="">Non précisé</option>
                            <option value="father" @selected(old('parent_relationship') === 'father')>Père</option>
                            <option value="mother" @selected(old('parent_relationship') === 'mother')>Mère</option>
                            <option value="guardian" @selected(old('parent_relationship') === 'guardian')>Tuteur</option>
                            <option value="other" @selected(old('parent_relationship') === 'other')>Autre</option>
                        </select>
                    </div>

                    <div>
                        <label class="etiquette" for="parent_phone">Téléphone</label>
                        <input id="parent_phone" name="parent_phone" type="tel" maxlength="255"
                               value="{{ old('parent_phone') }}" class="champ">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="etiquette" for="parent_email">Courriel</label>
                        <input id="parent_email" name="parent_email" type="email" maxlength="255"
                               value="{{ old('parent_email') }}"
                               class="champ @error('parent_email') border-corail-500 @enderror">
                        @error('parent_email') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Affectation</h2>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <label class="etiquette" for="academic_year_id">Année scolaire <span class="text-corail-600">*</span></label>
                        <select id="academic_year_id" name="academic_year_id" required x-model="anneeId"
                                class="champ @error('academic_year_id') border-corail-500 @enderror">
                            @foreach ($academicYears as $annee)
                                <option value="{{ $annee->id }}"
                                    @selected((string) old('academic_year_id', $anneeCourante?->id) === (string) $annee->id)>
                                    {{ $annee->name }}{{ $annee->is_current ? ' (courante)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('academic_year_id') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiquette" for="cycle-filtre">Filtrer par cycle</label>
                        <select id="cycle-filtre" x-model="cycleFiltre" class="champ">
                            <option value="">Tous les cycles</option>
                            @foreach ($libellesCycle as $cle => $libelle)
                                <option value="{{ $cle }}">{{ $libelle }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gris-400">Aide au choix, ce champ n’est pas enregistré.</p>
                    </div>

                    <div>
                        <label class="etiquette" for="class_id">Classe <span class="text-corail-600">*</span></label>
                        <select id="class_id" name="class_id" required x-model="classeId" @change="chargerFrais()"
                                class="champ @error('class_id') border-corail-500 @enderror">
                            <option value="">Sélectionner une classe</option>
                            <template x-for="classe in classesProposees" :key="classe.id">
                                <option :value="classe.id"
                                        x-text="classe.nom + ' (' + classe.effectif + (classe.capacite ? '/' + classe.capacite : '') + ')'">
                                </option>
                            </template>
                        </select>

                        <template x-if="placesRestantes !== null">
                            <p class="mt-1 text-xs" :class="placesRestantes <= 0 ? 'text-corail-700' : 'text-gris-400'">
                                <span x-show="placesRestantes > 0">
                                    <span x-text="placesRestantes"></span> place(s) disponible(s).
                                </span>
                                <span x-show="placesRestantes <= 0" x-cloak>
                                    Classe complète : l’inscription sera refusée.
                                </span>
                            </p>
                        </template>

                        @error('class_id') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiquette" for="enrollment_date">Date d’inscription <span class="text-corail-600">*</span></label>
                        <input id="enrollment_date" name="enrollment_date" type="date" required
                               value="{{ old('enrollment_date', now()->format('Y-m-d')) }}"
                               class="champ @error('enrollment_date') border-corail-500 @enderror">
                        @error('enrollment_date') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiquette" for="student_status">Situation de l’élève <span class="text-corail-600">*</span></label>
                        <select id="student_status" name="student_status" required
                                class="champ @error('student_status') border-corail-500 @enderror">
                            @foreach (['nouveau' => 'Nouveau', 'redoublant' => 'Redoublant', 'passant' => 'Passant'] as $cle => $libelle)
                                <option value="{{ $cle }}" @selected(old('student_status', 'nouveau') === $cle)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                        @error('student_status') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="etiquette" for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="2"
                                  placeholder="Remarques sur le dossier…"
                                  class="champ">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="carte" x-show="reinscription" x-cloak>
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Année précédente</h2>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <label class="etiquette" for="previous_class_id">Classe précédente</label>
                        <select id="previous_class_id" name="previous_class_id" x-ref="classePrecedente" class="champ">
                            <option value="">Non précisée</option>
                            @foreach ($classes as $c)
                                <option value="{{ $c->id }}" @selected((string) old('previous_class_id') === (string) $c->id)>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="etiquette" for="previous_academic_year_id">Année précédente</label>
                        <select id="previous_academic_year_id" name="previous_academic_year_id" x-ref="anneePrecedente" class="champ">
                            <option value="">Non précisée</option>
                            @foreach ($academicYears as $a)
                                <option value="{{ $a->id }}" @selected((string) old('previous_academic_year_id') === (string) $a->id)>
                                    {{ $a->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="etiquette" for="previous_year_result">Résultat</label>
                        <select id="previous_year_result" name="previous_year_result" class="champ">
                            @foreach ([
                                'non_applicable' => 'Non applicable',
                                'admis' => 'Admis',
                                'redouble' => 'Redouble',
                            ] as $cle => $libelle)
                                <option value="{{ $cle }}" @selected(old('previous_year_result', 'non_applicable') === $cle)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="etiquette" for="previous_year_average">Moyenne obtenue</label>
                        <input id="previous_year_average" name="previous_year_average" type="number"
                               step="0.01" min="0" max="20" value="{{ old('previous_year_average') }}"
                               placeholder="sur 20"
                               class="champ @error('previous_year_average') border-corail-500 @enderror">
                        @error('previous_year_average') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="etiquette" for="status_comments">Commentaire</label>
                        <textarea id="status_comments" name="status_comments" rows="2"
                                  class="champ">{{ old('status_comments') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Règlement : le détail des frais à gauche, la saisie à droite --}}
            <div class="grid gap-6 xl:grid-cols-2">

                <div class="carte">
                    <div class="carte-entete">
                        <div>
                            <h2 class="text-sm font-semibold text-gris-900">Frais de scolarité</h2>
                            <p class="mt-0.5 text-xs text-gris-400">Tarifs du niveau, pris dans le référentiel</p>
                        </div>
                        <span x-show="fraisEnCours" x-cloak class="text-xs text-gris-400">Chargement…</span>
                    </div>

                    <div class="p-5">
                        <p x-show="! classeId" class="text-sm text-gris-400">
                            Choisissez une classe pour voir les frais applicables.
                        </p>

                        <div x-show="classeId && ! fraisObligatoires.length && ! fraisEnCours" x-cloak
                             class="rounded-lg bg-soleil-50 px-3 py-2 text-xs text-soleil-800">
                            Aucun tarif n’est défini pour ce niveau dans le référentiel. Saisissez le montant à la main.
                        </div>

                        <div x-show="fraisObligatoires.length" x-cloak class="space-y-2">
                            <p class="text-xs font-semibold uppercase text-gris-400">Frais obligatoires</p>
                            <template x-for="frais in fraisObligatoires" :key="frais.id">
                                <div class="flex items-center justify-between rounded-lg bg-gris-50 px-3 py-2 text-sm">
                                    <span class="text-gris-700" x-text="frais.name"></span>
                                    <span class="font-semibold text-gris-900" x-text="franc(frais.amount)"></span>
                                </div>
                            </template>
                        </div>

                        <div x-show="fraisOptionnels.length" x-cloak class="mt-4 space-y-2">
                            <p class="text-xs font-semibold uppercase text-gris-400">Options</p>
                            <template x-for="frais in fraisOptionnels" :key="frais.id">
                                <label class="flex cursor-pointer items-center justify-between rounded-lg border px-3 py-2 text-sm transition-colors"
                                       :class="optionsCochees.includes(String(frais.id)) ? 'border-ogar-300 bg-ogar-50/40' : 'border-gris-200 hover:bg-gris-50'">
                                    <span class="flex items-center gap-2">
                                        <input type="checkbox" :value="String(frais.id)" x-model="optionsCochees"
                                               @change="recalculerTotal()"
                                               class="h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                                        <span class="text-gris-700" x-text="frais.name"></span>
                                    </span>
                                    <span class="font-semibold text-gris-900" x-text="franc(frais.amount)"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="carte">
                    <div class="carte-entete">
                        <h2 class="text-sm font-semibold text-gris-900">Règlement</h2>
                        <span class="text-xs font-semibold" :class="statutReglement.classe" x-text="statutReglement.libelle"></span>
                    </div>

                    <div class="space-y-4 p-5">
                        <div>
                            <div class="flex items-baseline justify-between gap-2">
                                <label class="etiquette" for="total_fees">Total à payer <span class="text-corail-600">*</span></label>
                                <button type="button" class="cursor-pointer text-[11px] font-semibold text-ogar-600 hover:underline"
                                        x-text="totalManuel ? 'Reprendre le tarif' : 'Saisir un autre montant'"
                                        @click="totalManuel ? recomposerLeTotal() : reprendreLeTotal()"></button>
                            </div>
                            <input id="total_fees" name="total_fees" type="number" required min="0" step="1"
                                   x-ref="champTotal" x-model.number="total" :readonly="! totalManuel"
                                   class="champ @error('total_fees') border-corail-500 @enderror"
                                   :class="totalManuel ? '' : 'bg-gris-50 text-gris-600'">
                            <p class="mt-1 text-xs text-gris-400" x-show="! totalManuel">
                                Somme des frais obligatoires du niveau et des options cochées.
                            </p>
                            @error('total_fees') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="etiquette" for="amount_paid">Montant versé <span class="text-corail-600">*</span></label>
                            <input id="amount_paid" name="amount_paid" type="number" required min="0" step="1"
                                   x-model.number="verse"
                                   class="champ @error('amount_paid') border-corail-500 @enderror">
                            <p x-show="versementExcessif" x-cloak class="mt-1 text-xs text-corail-700">
                                Le versement dépasse le total à payer.
                            </p>
                            @error('amount_paid') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                        </div>

                        <dl class="grid grid-cols-2 gap-y-2 border-t border-gris-100 pt-3 text-sm">
                            <dt class="text-gris-400">Reste à percevoir</dt>
                            <dd class="text-right font-bold" :class="reste > 0 ? 'text-corail-700' : 'text-emerald-700'"
                                x-text="franc(reste)"></dd>
                        </dl>

                        <div>
                            <label class="etiquette" for="payment_method">Moyen de paiement</label>
                            <select id="payment_method" name="payment_method" x-model="moyen"
                                    class="champ @error('payment_method') border-corail-500 @enderror">
                                <option value="">Non précisé</option>
                                @foreach ([
                                    'cash' => 'Espèces',
                                    'bank_transfer' => 'Virement bancaire',
                                    'check' => 'Chèque',
                                    'mobile_money' => 'Mobile Money',
                                    'other' => 'Autre',
                                ] as $cle => $libelle)
                                    <option value="{{ $cle }}" @selected(old('payment_method') === $cle)>{{ $libelle }}</option>
                                @endforeach
                            </select>
                            @error('payment_method') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="moyen === 'mobile_money'" x-cloak class="space-y-3 rounded-xl bg-gris-50 p-3">
                            <div>
                                <label class="etiquette" for="mobile_money_provider">Opérateur</label>
                                <select id="mobile_money_provider" name="mobile_money_provider" x-model="operateur" class="champ">
                                    <option value="">Sélectionner</option>
                                    <option value="airtel" @selected(old('mobile_money_provider') === 'airtel')>Airtel Money (07…)</option>
                                    <option value="moov" @selected(old('mobile_money_provider') === 'moov')>Moov Money (06…)</option>
                                </select>
                                @error('mobile_money_provider') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="etiquette" for="mobile_money_number">Numéro</label>
                                <input id="mobile_money_number" name="mobile_money_number" type="tel"
                                       inputmode="numeric" maxlength="9"
                                       :placeholder="operateur === 'airtel' ? '07XXXXXXX' : '06XXXXXXX'"
                                       value="{{ old('mobile_money_number') }}"
                                       class="champ font-mono @error('mobile_money_number') border-corail-500 @enderror">
                                <p class="mt-1 text-xs text-gris-400">9 chiffres, sans indicatif.</p>
                                @error('mobile_money_number') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="etiquette" for="payment_due_date">Échéance du solde</label>
                            <input id="payment_due_date" name="payment_due_date" type="date"
                                   value="{{ old('payment_due_date') }}"
                                   class="champ @error('payment_due_date') border-corail-500 @enderror">
                            @error('payment_due_date') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="etiquette" for="payment_notes">Note de paiement</label>
                            <textarea id="payment_notes" name="payment_notes" rows="2" class="champ">{{ old('payment_notes') }}</textarea>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ------------------------------------------------------------------
             Récapitulatif : il suit le défilement et porte l'enregistrement,
             pour ne pas avoir à redescendre au bas d'un formulaire long.
             ------------------------------------------------------------------ --}}
        <div class="lg:sticky lg:top-20">
            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Récapitulatif</h2>
                    <span class="text-xs font-medium"
                          :class="complet ? 'text-emerald-600' : 'text-gris-400'"
                          x-text="etapesFaites + '/' + etapes.length"></span>
                </div>

                {{-- Progression --}}
                <div class="h-1 bg-gris-100">
                    <div class="h-full transition-all duration-300"
                         :class="complet ? 'bg-emerald-500' : 'bg-ogar-500'"
                         :style="'width: ' + Math.round(etapesFaites / etapes.length * 100) + '%'"></div>
                </div>

                <div class="space-y-4 p-5">
                    {{-- Qui, où --}}
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                              :class="nomComplet ? 'bg-ogar-100 text-ogar-800' : 'bg-gris-100 text-gris-400'"
                              x-text="nomComplet
                                  ? (identite.prenom.charAt(0) + identite.nom.charAt(0)).toUpperCase()
                                  : '?'"></span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold"
                               :class="nomComplet ? 'text-gris-900' : 'text-gris-400'"
                               x-text="nomComplet || 'Candidat à renseigner'"></p>
                            <p class="truncate text-xs text-gris-500"
                               x-text="classeLisible || 'Classe non choisie'"></p>
                            <p class="truncate text-[11px] text-gris-400" x-text="anneeLisible"></p>
                        </div>
                    </div>

                    {{-- Ce qui manque --}}
                    <ul class="space-y-1.5 border-t border-gris-100 pt-3">
                        <template x-for="etape in etapes" :key="etape.cle">
                            <li class="flex items-center gap-2 text-xs">
                                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full"
                                      :class="etape.fait ? 'bg-emerald-100 text-emerald-700' : 'bg-gris-100 text-gris-400'">
                                    <svg x-show="etape.fait" class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                    </svg>
                                    <span x-show="! etape.fait" class="h-1 w-1 rounded-full bg-gris-400"></span>
                                </span>
                                <span :class="etape.fait ? 'text-gris-600' : 'text-gris-400'" x-text="etape.libelle"></span>
                            </li>
                        </template>
                    </ul>

                    {{-- Argent --}}
                    <dl class="space-y-1.5 border-t border-gris-100 pt-3 text-sm">
                        <div class="flex items-baseline justify-between">
                            <dt class="text-gris-400">Total</dt>
                            <dd class="font-semibold text-gris-900" x-text="franc(total)"></dd>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <dt class="text-gris-400">Versé</dt>
                            <dd class="font-medium text-emerald-700" x-text="franc(verse)"></dd>
                        </div>
                        <div class="flex items-baseline justify-between border-t border-gris-100 pt-1.5">
                            <dt class="font-medium text-gris-600">Reste dû</dt>
                            <dd class="text-base font-bold"
                                :class="reste > 0 ? 'text-corail-700' : 'text-emerald-700'"
                                x-text="franc(reste)"></dd>
                        </div>
                    </dl>

                    <div class="flex items-center justify-between rounded-lg bg-gris-50 px-3 py-2">
                        <span class="text-xs text-gris-500">Situation</span>
                        <span class="text-xs font-semibold" :class="statutReglement.classe"
                              x-text="statutReglement.libelle"></span>
                    </div>
                </div>

                <div class="space-y-2 border-t border-gris-100 p-5">
                    <button type="submit" class="bouton-primaire w-full justify-center">
                        <span x-show="! reinscription">Enregistrer l’inscription</span>
                        <span x-show="reinscription" x-cloak>Enregistrer la réinscription</span>
                    </button>

                    <p x-show="! complet" x-cloak class="text-center text-[11px] text-gris-400">
                        Il reste <span x-text="etapes.length - etapesFaites"></span> point(s) à renseigner.
                    </p>

                    <a href="{{ route('enrollments.index') }}"
                       class="block text-center text-xs text-gris-500 hover:text-gris-700 hover:underline">
                        Annuler
                    </a>
                </div>
            </div>

            <p class="mt-3 px-1 text-[11px] leading-relaxed text-gris-400">
                <span x-show="! reinscription">
                    Le profil de l’élève se crée juste après l’enregistrement, depuis le dossier.
                </span>
                <span x-show="reinscription" x-cloak>
                    L’élève existe déjà : le dossier lui sera rattaché directement.
                </span>
            </p>
        </div>
    </div>
</form>

@endsection
