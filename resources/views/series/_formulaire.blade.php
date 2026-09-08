@php
    $modification = $series->exists;
    $niveauActuel = (string) old('level_id', $series->level_id);
    $lettreActuelle = old('lettre', \App\Models\Series::lettreDuCode($series->code));
@endphp

<form method="POST" action="{{ $action }}"
      x-data="{
          niveau: '{{ $niveauActuel }}',
          rang: {{ (int) old('order', $series->order ?? 0) }},
          rangs: {{ Js::from($rangsUtilises) }},
          code: @js(old('code', $series->code)),
          lettre: @js($lettreActuelle),

          {{-- Sur une série existante le code est conservé : il sert de clé
               d'affichage un peu partout. Un bouton permet de le régénérer. --}}
          codeManuel: {{ $modification ? 'true' : 'false' }},
          codeEnCours: false,

          {{-- La règle de composition vit dans le modèle : on l'interroge au
               lieu de la réécrire ici. --}}
          async proposerCode(reprendreLaLettre = false) {
              if (this.codeManuel || ! this.niveau) return;

              this.codeEnCours = true;
              try {
                  const params = new URLSearchParams({
                      niveau: this.niveau,
                      lettre: reprendreLaLettre ? '' : (this.lettre || ''),
                      id: '{{ $modification ? $series->id : '' }}',
                  });
                  const reponse = await fetch('{{ route('series.proposerCode') }}?' + params,
                                              { headers: { Accept: 'application/json' } });
                  if (reponse.ok) {
                      const data = await reponse.json();
                      this.code = data.code;
                      if (! this.lettre || reprendreLaLettre) this.lettre = data.lettre || '';
                  }
              } catch (e) {
                  {{-- Laissé vide, le serveur le reconstruira à l'enregistrement. --}}
              }
              this.codeEnCours = false;
          },

          reprendreLaMain() {
              this.codeManuel = true;
              this.$nextTick(() => this.$refs.champCode.focus());
          },
          regenerer() {
              this.codeManuel = false;
              this.proposerCode();
          },

          {{-- Deux séries d'un même niveau au même rang s'afficheraient dans un
               ordre arbitraire : on le signale avant l'enregistrement. --}}
          get rangOccupePar() {
              const pris = this.rangs[this.niveau] || {};
              const occupant = pris[this.rang];
              return (occupant && occupant !== this.code) ? occupant : null;
          },
      }"
      class="space-y-6">
    @csrf
    @if ($modification) @method('PUT') @endif

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- ------------------------------------------------------------------
             Identification
             ------------------------------------------------------------------ --}}
        <div class="carte lg:col-span-2">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Identification de la série</h2>
                <span class="text-xs text-gris-400"><span class="text-corail-600">*</span> champs obligatoires</span>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div>
                    <label class="etiquette" for="lettre">Lettre de série <span class="text-corail-600">*</span></label>
                    <input id="lettre" name="lettre" type="text" maxlength="3" required
                           value="{{ $lettreActuelle }}"
                           x-model="lettre" @input.debounce.400ms="proposerCode()"
                           placeholder="Ex. C"
                           class="champ font-mono uppercase">
                    <p class="mt-1 text-xs text-gris-400">
                        C’est elle qui relie la série aux matières du programme.
                    </p>
                </div>

                <div>
                    <div class="flex items-baseline justify-between gap-2">
                        <label class="etiquette" for="code">Code</label>
                        <button type="button" class="cursor-pointer text-[11px] font-semibold text-ogar-600 hover:underline"
                                x-text="codeManuel ? 'Composer automatiquement' : 'Saisir manuellement'"
                                @click="codeManuel ? regenerer() : reprendreLaMain()"></button>
                    </div>

                    <div class="relative">
                        <input id="code" name="code" type="text" maxlength="10"
                               x-ref="champCode" x-model="code"
                               :readonly="! codeManuel"
                               placeholder="Composé du niveau et de la lettre"
                               class="champ font-mono uppercase @error('code') border-corail-500 @enderror"
                               :class="codeManuel ? '' : 'bg-gris-50 text-gris-600'">

                        <span x-show="codeEnCours" x-cloak
                              class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] text-gris-400">…</span>
                    </div>

                    <p class="mt-1 text-xs text-gris-400" x-show="! codeManuel">
                        Préfixe du niveau puis lettre de série. Un numéro est ajouté si le code est déjà pris.
                    </p>
                    <p class="mt-1 text-xs text-gris-400" x-show="codeManuel" x-cloak>
                        Identifiant court et unique de la série.
                    </p>
                    @error('code') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="level">Niveau <span class="text-corail-600">*</span></label>
                    <select id="level" name="level_id" required x-model="niveau"
                            @change="proposerCode({{ $modification ? 'false' : 'true' }})"
                            class="champ @error('level_id') border-corail-500 @enderror">
                        <option value="">Sélectionner un niveau</option>
                        @foreach ($niveaux as $niveau)
                            <option value="{{ $niveau->id }}" @selected($niveauActuel === (string) $niveau->id)>{{ $niveau->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gris-400">Les séries n’existent qu’au lycée.</p>
                    @error('level_id') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="etiquette" for="name">Intitulé <span class="text-corail-600">*</span></label>
                    <input id="name" name="name" type="text" required maxlength="255"
                           value="{{ old('name', $series->name) }}"
                           placeholder="Ex. Mathématiques-Sciences physiques"
                           class="champ @error('name') border-corail-500 @enderror">
                    @error('name') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="etiquette" for="description">Description</label>
                    <textarea id="description" name="description" rows="3"
                              placeholder="Orientation de la série, débouchés…"
                              class="champ @error('description') border-corail-500 @enderror">{{ old('description', $series->description) }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------------
             Affichage et statut (colonne latérale)
             ------------------------------------------------------------------ --}}
        <div class="space-y-6">
            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Ordre d’affichage</h2>
                </div>
                <div class="space-y-3 p-5">
                    <div>
                        <label class="etiquette" for="order">Rang <span class="text-corail-600">*</span></label>
                        <input id="order" name="order" type="number" required min="0" max="99"
                               value="{{ old('order', $series->order ?? 0) }}"
                               x-model.number="rang"
                               class="champ @error('order') border-corail-500 @enderror">
                        <p class="mt-1 text-xs text-gris-400">
                            Position dans les listes de son niveau, du plus petit au plus grand.
                        </p>
                        @error('order') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <p x-show="rangOccupePar" x-cloak class="rounded-lg bg-soleil-50 px-3 py-2 text-xs text-soleil-800">
                        Le rang <span x-text="rang"></span> est déjà pris par
                        <span class="font-mono font-semibold" x-text="rangOccupePar"></span>
                        à ce niveau. L’ordre entre les deux sera arbitraire.
                    </p>
                </div>
            </div>

            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Disponibilité</h2>
                </div>
                <div class="p-5">
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gris-200 p-3 transition-colors hover:bg-gris-50">
                        {{-- Champ caché : sans lui, décocher n'envoie rien et la série ne peut jamais être désactivée. --}}
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $series->is_active ?? true) ? 'checked' : '' }}
                               class="mt-0.5 h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                        <span>
                            <span class="block text-sm font-medium text-gris-900">Série active</span>
                            <span class="block text-xs text-gris-400">
                                Une série inactive n’est plus proposée à la création d’une classe,
                                sans détacher les classes existantes.
                            </span>
                        </span>
                    </label>
                </div>
            </div>

            @if ($modification)
                <div class="carte">
                    <div class="carte-entete">
                        <h2 class="text-sm font-semibold text-gris-900">Usage actuel</h2>
                    </div>
                    <dl class="grid grid-cols-2 gap-y-2.5 p-5 text-sm">
                        <dt class="text-gris-400">Classes rattachées</dt>
                        <dd class="text-right font-medium text-gris-800">{{ $series->classes()->count() }}</dd>

                        <dt class="text-gris-400">Créée le</dt>
                        <dd class="text-right font-medium text-gris-800">
                            {{ $series->created_at?->translatedFormat('j M Y') ?? '—' }}
                        </dd>
                    </dl>
                </div>
            @endif
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         Barre d'actions
         ------------------------------------------------------------------ --}}
    <div class="carte flex flex-wrap items-center gap-3 p-4">
        <button type="submit" class="bouton-primaire">
            {{ $modification ? 'Enregistrer les modifications' : 'Créer la série' }}
        </button>
        <a href="{{ $modification ? route('series.show', $series->id) : route('series.index') }}"
           class="bouton-secondaire">Annuler</a>

        @if ($modification)
            <x-confirmation :action="route('series.destroy', $series->id)" methode="DELETE"
                            titre="Supprimer cette série ?"
                            :message="'La série '.$series->name.' sera définitivement retirée du référentiel.'"
                            confirmer="Supprimer"
                            bouton="bouton-secondaire ml-auto text-xs text-corail-600 hover:bg-corail-50">
                Supprimer
            </x-confirmation>
        @endif
    </div>
</form>
