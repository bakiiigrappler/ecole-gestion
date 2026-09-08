@php
    /*
     * Formulaire partagé par la création et la modification d'un parent.
     * $parent est un modèle vide en création.
     *
     * Le lien de parenté, le rôle de contact principal et les autorisations
     * se saisissent PAR ENFANT : ils décrivent la relation, pas la personne.
     * Un même adulte peut être père de son fils et tuteur de son neveu.
     */
    $modification = $parent->exists;

    $libellesLien = [
        'father' => 'Père',
        'mother' => 'Mère',
        'guardian' => 'Tuteur ou tutrice',
        'other' => 'Autre',
    ];

    // Liens existants, ou une ligne vide pré-remplie si on arrive depuis la
    // fiche d'un élève.
    $liensExistants = old('liens', $parent->students->map(fn ($eleve) => [
        'student_id' => (string) $eleve->id,
        'relationship_type' => $eleve->pivot->relationship_type,
        'is_primary_contact' => (bool) $eleve->pivot->is_primary_contact,
        'lives_with_student' => (bool) $eleve->pivot->lives_with_student,
        'can_pickup' => (bool) $eleve->pivot->can_pickup,
    ])->values()->all());

    if (empty($liensExistants)) {
        $liensExistants = [[
            'student_id' => (string) ($preselectedStudent->id ?? ''),
            'relationship_type' => 'father',
            'is_primary_contact' => true,
            'lives_with_student' => true,
            'can_pickup' => true,
        ]];
    }

    // Etat propre au selecteur de chaque ligne : ce qui est tape, et si la
    // liste est deployee.
    $liensExistants = collect($liensExistants)
        ->map(fn (array $lien) => $lien + ['recherche' => '', 'ouvert' => false, 'surligne' => 0])
        ->all();

    $listeEleves = $students->map(function ($e) {
        $classe = $e->enrollments->first()?->schoolClass?->name;

        return [
            'id' => (string) $e->id,
            'nom' => $e->last_name.' '.$e->first_name,
            'matricule' => $e->student_id,
            'classe' => $classe,
            'initiales' => mb_strtoupper(mb_substr($e->first_name, 0, 1).mb_substr($e->last_name, 0, 1)),
        ];
    })->values();
@endphp

<form method="POST" enctype="multipart/form-data"
      action="{{ $modification ? route('parents.update', $parent->id) : route('parents.store') }}"
      x-data="{
          liens: {{ Js::from($liensExistants) }},
          eleves: {{ Js::from($listeEleves) }},
          sexe: '{{ old('gender', $parent->gender) }}',

          /* Un homme ne peut pas etre « mere », ni une femme « pere ». Le lien
             reste libre par enfant, mais dans les limites du sexe saisi. */
          get liensPossibles() {
              const tous = {{ Js::from($libellesLien) }};
              const exclu = this.sexe === 'male' ? 'mother' : (this.sexe === 'female' ? 'father' : null);

              return Object.entries(tous)
                  .filter(([valeur]) => valeur !== exclu)
                  .map(([valeur, libelle]) => ({ valeur, libelle }));
          },

          /* Changer le sexe peut rendre des liens deja choisis impossibles. */
          revaliderLiens() {
              const permis = this.liensPossibles.map((l) => l.valeur);

              this.liens.forEach((lien) => {
                  if (! permis.includes(lien.relationship_type)) {
                      lien.relationship_type = this.sexe === 'male' ? 'father' : 'mother';
                  }
              });
          },

          ajouter() {
              this.liens.push({
                  student_id: '',
                  relationship_type: this.sexe === 'female' ? 'mother' : 'father',
                  is_primary_contact: false,
                  lives_with_student: true,
                  can_pickup: true,
                  recherche: '',
                  ouvert: false,
                  surligne: 0,
              });

              this.$nextTick(() => this.$refs['recherche' + (this.liens.length - 1)]?.focus());
          },

          /* ------------------------------------------------------------------
             Selecteur d'eleve avec recherche.

             Une liste deroulante de sept cents eleves est inutilisable : ici on
             tape un nom, un prenom, un matricule ou une classe, et la liste se
             reduit a mesure.
             ------------------------------------------------------------------ */
          eleveDe(rang) {
              return this.eleves.find((e) => String(e.id) === String(this.liens[rang].student_id)) || null;
          },

          resultats(rang) {
              const motif = (this.liens[rang].recherche || '').trim().toLowerCase();
              const pris = this.liens
                  .filter((_, i) => i !== rang)
                  .map((lien) => String(lien.student_id));

              const libres = this.eleves.filter((e) => ! pris.includes(String(e.id)));

              if (! motif) return libres.slice(0, 40);

              return libres.filter((e) =>
                  [e.nom, e.matricule, e.classe].some((v) => (v || '').toLowerCase().includes(motif))
              ).slice(0, 40);
          },

          choisir(rang, eleve) {
              this.liens[rang].student_id = String(eleve.id);
              this.liens[rang].recherche = '';
              this.liens[rang].ouvert = false;
          },

          effacer(rang) {
              this.liens[rang].student_id = '';
              this.liens[rang].recherche = '';
              this.liens[rang].ouvert = true;
              this.$nextTick(() => this.$refs['recherche' + rang]?.focus());
          },

          naviguer(rang, pas) {
              const lien = this.liens[rang];
              if (! lien.ouvert) { lien.ouvert = true; return; }

              const total = this.resultats(rang).length;
              if (! total) return;
              lien.surligne = (lien.surligne + pas + total) % total;
          },

          valider(rang) {
              const eleve = this.resultats(rang)[this.liens[rang].surligne];
              if (eleve) this.choisir(rang, eleve);
          },

          retirer(rang) {
              if (this.liens.length > 1) this.liens.splice(rang, 1);
          },

          /* Un élève n'a qu'un contact principal : cocher l'un décoche l'autre
             pour le même enfant. */
          basculerPrincipal(rang) {
              const eleve = this.liens[rang].student_id;
              if (! this.liens[rang].is_primary_contact) return;

              this.liens.forEach((lien, i) => {
                  if (i !== rang && lien.student_id === eleve) lien.is_primary_contact = false;
              });
          },

          /* Les élèves déjà choisis ne sont plus proposés dans les autres lignes. */
          disponibles(rang) {
              const pris = this.liens
                  .filter((_, i) => i !== rang)
                  .map((lien) => lien.student_id);

              return this.eleves.filter((e) => ! pris.includes(e.id));
          },
      }"
      class="space-y-6">
    @csrf
    @if ($modification) @method('PUT') @endif

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- ------------------------------------------------------------------
             Identité et coordonnées
             ------------------------------------------------------------------ --}}
        <div class="carte lg:col-span-2">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Identité et coordonnées</h2>
                <span class="text-xs text-gris-400"><span class="text-corail-600">*</span> champs obligatoires</span>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div>
                    <label class="etiquette" for="first_name">Prénom <span class="text-corail-600">*</span></label>
                    <input id="first_name" name="first_name" type="text" required autocomplete="given-name"
                           value="{{ old('first_name', $parent->first_name) }}"
                           class="champ @error('first_name') border-corail-500 @enderror">
                    @error('first_name') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="last_name">Nom <span class="text-corail-600">*</span></label>
                    <input id="last_name" name="last_name" type="text" required autocomplete="family-name"
                           value="{{ old('last_name', $parent->last_name) }}"
                           class="champ @error('last_name') border-corail-500 @enderror">
                    @error('last_name') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="gender">Sexe <span class="text-corail-600">*</span></label>
                    <select id="gender" name="gender" required x-model="sexe" @change="revaliderLiens()"
                            class="champ @error('gender') border-corail-500 @enderror">
                        <option value="">Sélectionner</option>
                        <option value="male" @selected(old('gender', $parent->gender) === 'male')>Masculin</option>
                        <option value="female" @selected(old('gender', $parent->gender) === 'female')>Féminin</option>
                    </select>
                    @error('gender') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="phone">Téléphone <span class="text-corail-600">*</span></label>
                    <input id="phone" name="phone" type="tel" required
                           value="{{ old('phone', $parent->phone) }}"
                           class="champ @error('phone') border-corail-500 @enderror"
                           placeholder="077 12 34 56">
                    @error('phone') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="phone_2">Second téléphone</label>
                    <input id="phone_2" name="phone_2" type="tel"
                           value="{{ old('phone_2', $parent->phone_2) }}" class="champ">
                    @error('phone_2') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="email">Adresse e-mail</label>
                    <input id="email" name="email" type="email" autocomplete="email"
                           value="{{ old('email', $parent->email) }}"
                           class="champ @error('email') border-corail-500 @enderror">
                    <p class="mt-1 text-xs text-gris-400">
                        @if ($modification && $parent->user_id)
                            Un accès au portail des parents est déjà ouvert.
                        @else
                            Renseignée, elle ouvre un accès au portail des parents.
                        @endif
                    </p>
                    @error('email') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="etiquette" for="address">Adresse</label>
                    <textarea id="address" name="address" rows="2" class="champ"
                              placeholder="Quartier, ville">{{ old('address', $parent->address) }}</textarea>
                    @error('address') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="profession">Profession</label>
                    <input id="profession" name="profession" type="text"
                           value="{{ old('profession', $parent->profession) }}" class="champ">
                    @error('profession') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="workplace">Lieu de travail</label>
                    <input id="workplace" name="workplace" type="text"
                           value="{{ old('workplace', $parent->workplace) }}" class="champ">
                    @error('workplace') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------------
             Repères
             ------------------------------------------------------------------ --}}
        <div class="space-y-6">
            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Ce parent</h2>
                </div>

                <div class="space-y-4 p-5">
                    <div class="flex justify-center">
                        <x-avatar :nom="trim(old('first_name', $parent->first_name).' '.old('last_name', $parent->last_name)) ?: ' '"
                                  taille="h-24 w-24"/>
                    </div>

                    <p class="rounded-lg bg-ogar-50 px-3 py-2.5 text-xs leading-relaxed text-ogar-800">
                        Le lien de parenté et les autorisations se règlent <strong>enfant par enfant</strong>,
                        dans le tableau ci-dessous : un même adulte peut être père de son fils
                        et tuteur de son neveu.
                    </p>

                    @if ($modification)
                        <dl class="space-y-2 border-t border-gris-100 pt-4 text-sm">
                            <div class="flex items-baseline justify-between gap-3">
                                <dt class="text-gris-400">Enfants rattachés</dt>
                                <dd class="font-semibold text-gris-900" x-text="liens.length"></dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3">
                                <dt class="text-gris-400">Créé le</dt>
                                <dd class="font-medium text-gris-800">{{ $parent->created_at?->format('d/m/Y') }}</dd>
                            </div>
                        </dl>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------------
         Enfants rattachés — une carte par lien
         ---------------------------------------------------------------------- --}}
    <div class="carte">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Enfants rattachés</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    <span x-text="liens.length"></span> enfant(s) &middot;
                    le lien et les autorisations se règlent enfant par enfant
                </p>
            </div>
            <button type="button" @click="ajouter()" class="bouton-mini">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Ajouter un enfant
            </button>
        </div>

        @error('liens') <p class="px-5 pt-4 text-xs text-corail-700">{{ $message }}</p> @enderror

        @foreach ($errors->get('liens.*') as $messages)
            <p class="px-5 pt-4 text-xs text-corail-700">{{ $messages[0] }}</p>
        @endforeach

        <div class="space-y-3 p-5">
            <template x-for="(lien, rang) in liens" :key="rang">
                <div class="rounded-xl border border-gris-200"
                     :class="lien.is_primary_contact && 'border-ogar-300 ring-1 ring-ogar-100'">

                    {{-- En-tête de la ligne : l'élève choisi, ou le sélecteur --}}
                    <div class="flex flex-wrap items-start gap-3 border-b border-gris-100 p-4">

                        {{-- Élève choisi : on montre qui c'est, pas un identifiant --}}
                        <template x-if="eleveDe(rang)">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-ogar-100 text-xs font-bold text-ogar-800 ring-1 ring-ogar-200"
                                      x-text="eleveDe(rang).initiales"></span>
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-gris-900" x-text="eleveDe(rang).nom"></div>
                                    <div class="truncate text-xs text-gris-400">
                                        <span class="font-mono" x-text="eleveDe(rang).matricule"></span>
                                        <template x-if="eleveDe(rang).classe">
                                            <span> &middot; <span x-text="eleveDe(rang).classe"></span></span>
                                        </template>
                                        <template x-if="! eleveDe(rang).classe">
                                            <span> &middot; non inscrit</span>
                                        </template>
                                    </div>
                                </div>
                                <button type="button" @click="effacer(rang)"
                                        class="ml-auto cursor-pointer text-xs font-semibold text-ogar-600 hover:underline">
                                    Changer
                                </button>
                            </div>
                        </template>

                        {{-- Aucun élève : sélecteur avec recherche --}}
                        <template x-if="! eleveDe(rang)">
                            <div class="relative min-w-0 flex-1" @click.outside="lien.ouvert = false">
                                <label class="etiquette" :for="'recherche-' + rang">
                                    Élève <span class="text-corail-600">*</span>
                                </label>

                                <input :id="'recherche-' + rang" type="text" class="champ" autocomplete="off"
                                       placeholder="Nom, prénom, matricule ou classe…"
                                       :x-ref="'recherche' + rang"
                                       x-model="lien.recherche"
                                       @focus="lien.ouvert = true; lien.surligne = 0"
                                       @input="lien.ouvert = true; lien.surligne = 0"
                                       @keydown.arrow-down.prevent="naviguer(rang, 1)"
                                       @keydown.arrow-up.prevent="naviguer(rang, -1)"
                                       @keydown.enter.prevent="valider(rang)"
                                       @keydown.escape="lien.ouvert = false"
                                       role="combobox" aria-autocomplete="list" :aria-expanded="lien.ouvert">

                                <ul x-show="lien.ouvert" x-cloak role="listbox"
                                    class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-gris-200 bg-white shadow-lg">

                                    <template x-for="(eleve, index) in resultats(rang)" :key="eleve.id">
                                        <li role="option" @click="choisir(rang, eleve)" @mouseenter="lien.surligne = index"
                                            class="flex cursor-pointer items-center gap-3 px-3 py-2 transition-colors"
                                            :class="index === lien.surligne ? 'bg-ogar-50' : 'hover:bg-gris-50'">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gris-100 text-[10px] font-bold text-gris-600"
                                                  x-text="eleve.initiales"></span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-medium text-gris-800" x-text="eleve.nom"></span>
                                                <span class="block truncate text-[11px] text-gris-400">
                                                    <span class="font-mono" x-text="eleve.matricule"></span>
                                                    <template x-if="eleve.classe">
                                                        <span> &middot; <span x-text="eleve.classe"></span></span>
                                                    </template>
                                                </span>
                                            </span>
                                        </li>
                                    </template>

                                    <li x-show="! resultats(rang).length" class="px-3 py-6 text-center text-sm text-gris-400">
                                        Aucun élève ne correspond.
                                    </li>
                                </ul>
                            </div>
                        </template>

                        {{-- La valeur réellement envoyée --}}
                        <input type="hidden" :name="'liens[' + rang + '][student_id]'" :value="lien.student_id">

                        <button type="button" @click="retirer(rang)" :disabled="liens.length === 1"
                                class="cursor-pointer rounded-lg p-2 text-gris-400 transition-colors hover:bg-corail-50 hover:text-corail-600 disabled:cursor-not-allowed disabled:text-gris-200 disabled:hover:bg-transparent"
                                :title="liens.length === 1 ? 'Au moins un enfant est requis' : 'Retirer cet enfant'">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Lien de parenté et rôle auprès de cet enfant --}}
                    <div class="grid gap-4 p-4 sm:grid-cols-3">
                        <div>
                            <label class="etiquette" :for="'lien-' + rang">
                                Lien de parenté <span class="text-corail-600">*</span>
                            </label>
                            <select :id="'lien-' + rang" :name="'liens[' + rang + '][relationship_type]'"
                                    x-model="lien.relationship_type" required class="champ">
                                <template x-for="choix in liensPossibles" :key="choix.valeur">
                                    <option :value="choix.valeur" x-text="choix.libelle"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-[11px] text-gris-400" x-show="! sexe">
                                Renseignez le sexe pour restreindre les liens proposés.
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <span class="etiquette">Rôle auprès de cet enfant</span>

                            <div class="flex flex-wrap gap-x-6 gap-y-1.5 pt-1">
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-gris-700">
                                    <input type="hidden" :name="'liens[' + rang + '][is_primary_contact]'" value="0">
                                    <input type="checkbox" value="1"
                                           :name="'liens[' + rang + '][is_primary_contact]'"
                                           x-model="lien.is_primary_contact" @change="basculerPrincipal(rang)"
                                           class="h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                                    Contact principal
                                </label>

                                <label class="flex cursor-pointer items-center gap-2 text-sm text-gris-700">
                                    <input type="hidden" :name="'liens[' + rang + '][lives_with_student]'" value="0">
                                    <input type="checkbox" value="1"
                                           :name="'liens[' + rang + '][lives_with_student]'"
                                           x-model="lien.lives_with_student"
                                           class="h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                                    Vit avec l’enfant
                                </label>

                                <label class="flex cursor-pointer items-center gap-2 text-sm text-gris-700">
                                    <input type="hidden" :name="'liens[' + rang + '][can_pickup]'" value="0">
                                    <input type="checkbox" value="1"
                                           :name="'liens[' + rang + '][can_pickup]'"
                                           x-model="lien.can_pickup"
                                           class="h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                                    Autorisé à venir le chercher
                                </label>
                            </div>

                            <p class="mt-2 text-[11px] text-gris-400" x-show="lien.is_primary_contact">
                                C’est ce responsable que l’établissement joindra en premier pour cet enfant.
                            </p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3">
        @if ($modification)
            <x-confirmation :action="route('parents.destroy', $parent->id)" methode="DELETE"
                            titre="Supprimer ce parent ?"
                            :message="'La fiche de '.$parent->first_name.' '.$parent->last_name.' et ses liens avec les élèves seront définitivement supprimés.'"
                            confirmer="Supprimer"
                            bouton="mr-auto text-sm font-semibold text-corail-600 hover:underline">
                Supprimer ce parent
            </x-confirmation>
        @endif

        <a href="{{ $modification ? route('parents.show', $parent->id) : route('parents.index') }}"
           class="bouton-secondaire">Annuler</a>
        <button class="bouton-primaire">{{ $modification ? 'Enregistrer les modifications' : 'Créer la fiche' }}</button>
    </div>
</form>
