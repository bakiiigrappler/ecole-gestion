@php
    $libellesCycle = ['primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    $seriesChoisies = collect(old('series', $subject->series ?? []))->map(fn ($s) => (string) $s)->all();
    $modification = $subject->exists;
@endphp

<form method="POST" action="{{ $action }}"
      x-data="{
          cycle: '{{ old('cycle', $subject->cycle) }}',
          series: {{ Js::from($seriesChoisies) }},
          nom: @js(old('name', $subject->name)),
          code: @js(old('code', $subject->code)),

          {{-- Sur une matière existante le code est conservé tel quel : il figure
               sur les bulletins déjà édités. Un bouton permet de le régénérer. --}}
          codeManuel: {{ $modification ? 'true' : 'false' }},
          codeEnCours: false,

          {{-- Hors lycée la notion de série n'a pas cours : le bloc disparaît. --}}
          get auLycee() { return this.cycle === 'lycee'; },

          {{-- La règle de nommage vit dans le modèle : on l'interroge au lieu de
               la réécrire ici, sinon les deux versions finiraient par diverger. --}}
          async proposerCode() {
              if (this.codeManuel || ! this.nom.trim()) return;

              this.codeEnCours = true;
              try {
                  const params = new URLSearchParams({
                      nom: this.nom,
                      cycle: this.cycle || '',
                      id: '{{ $modification ? $subject->id : '' }}',
                  });
                  const reponse = await fetch('{{ route('subjects.proposerCode') }}?' + params,
                                              { headers: { Accept: 'application/json' } });
                  if (reponse.ok) this.code = (await reponse.json()).code;
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
                <h2 class="text-sm font-semibold text-gris-900">Identification de la matière</h2>
                <span class="text-xs text-gris-400"><span class="text-corail-600">*</span> champs obligatoires</span>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div>
                    <label class="etiquette" for="name">Nom <span class="text-corail-600">*</span></label>
                    <input id="name" name="name" type="text" required maxlength="255"
                           value="{{ old('name', $subject->name) }}"
                           placeholder="Ex. Mathématiques"
                           x-model="nom" @input.debounce.400ms="proposerCode()"
                           class="champ @error('name') border-corail-500 @enderror">
                    @error('name') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="flex items-baseline justify-between gap-2">
                        <label class="etiquette" for="code">Code</label>
                        <button type="button" class="cursor-pointer text-[11px] font-semibold text-ogar-600 hover:underline"
                                x-text="codeManuel ? 'Générer automatiquement' : 'Saisir manuellement'"
                                @click="codeManuel ? regenerer() : reprendreLaMain()"></button>
                    </div>

                    <div class="relative">
                        <input id="code" name="code" type="text" maxlength="50"
                               x-ref="champCode" x-model="code"
                               :readonly="! codeManuel"
                               placeholder="Généré depuis l’intitulé"
                               class="champ font-mono uppercase @error('code') border-corail-500 @enderror"
                               :class="codeManuel ? '' : 'bg-gris-50 text-gris-600'">

                        <span x-show="codeEnCours" x-cloak
                              class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] text-gris-400">…</span>
                    </div>

                    <p class="mt-1 text-xs text-gris-400" x-show="! codeManuel">
                        Déduit de l’intitulé et du cycle. Un numéro est ajouté si le code est déjà pris.
                    </p>
                    <p class="mt-1 text-xs text-gris-400" x-show="codeManuel" x-cloak>
                        Identifiant court et unique, repris sur les bulletins.
                    </p>
                    @error('code') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="cycle">Cycle <span class="text-corail-600">*</span></label>
                    <select id="cycle" name="cycle" required x-model="cycle" @change="proposerCode()"
                            class="champ @error('cycle') border-corail-500 @enderror">
                        <option value="">Sélectionner un cycle</option>
                        @foreach ($libellesCycle as $cle => $libelle)
                            <option value="{{ $cle }}" @selected(old('cycle', $subject->cycle) === $cle)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gris-400">
                        Le préprimaire n’a pas de matières : l’enseignement y est global.
                    </p>
                    @error('cycle') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="coefficient">Coefficient <span class="text-corail-600">*</span></label>
                    <input id="coefficient" name="coefficient" type="number" required
                           step="1" min="1" max="10"
                           value="{{ (int) old('coefficient', $subject->coefficient ?: 1) }}"
                           class="champ @error('coefficient') border-corail-500 @enderror">
                    <p class="mt-1 text-xs text-gris-400">
                        Poids de la matière dans la moyenne générale.
                    </p>
                    @error('coefficient') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="etiquette" for="description">Description</label>
                    <textarea id="description" name="description" rows="3"
                              placeholder="Contenu du programme, remarques…"
                              class="champ @error('description') border-corail-500 @enderror">{{ old('description', $subject->description) }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------------
             Séries et statut (colonne latérale)
             ------------------------------------------------------------------ --}}
        <div class="space-y-6">
            <div class="carte" x-show="auLycee" x-cloak>
                <div class="carte-entete">
                    <div>
                        <h2 class="text-sm font-semibold text-gris-900">Séries visées</h2>
                        <p class="mt-0.5 text-xs text-gris-400">
                            <span x-text="series.length"></span> série(s) sélectionnée(s)
                        </p>
                    </div>
                    <button type="button" class="bouton-mini"
                            @click="series = series.length === {{ $series->count() }} ? [] : {{ Js::from($series->map(fn ($s) => (string) $s)->values()) }}">
                        <span x-text="series.length === {{ $series->count() }} ? 'Tout retirer' : 'Tout cocher'"></span>
                    </button>
                </div>

                <div class="p-5">
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($series as $lettre)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors"
                                   :class="series.includes('{{ $lettre }}') ? 'border-ogar-300 bg-ogar-50/50 text-gris-900' : 'border-gris-200 text-gris-600 hover:bg-gris-50'">
                                <input type="checkbox" name="series[]" value="{{ $lettre }}" x-model="series"
                                       class="h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                                Série {{ $lettre }}
                            </label>
                        @endforeach
                    </div>

                    <p x-show="! series.length" x-cloak class="mt-3 text-xs text-corail-700">
                        Une matière de lycée doit viser au moins une série.
                    </p>
                    @error('series') <p class="mt-3 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Disponibilité</h2>
                </div>
                <div class="p-5">
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gris-200 p-3 transition-colors hover:bg-gris-50">
                        {{-- Champ caché : sans lui, décocher n'envoie rien et la matière ne peut jamais être désactivée. --}}
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $subject->is_active ?? true) ? 'checked' : '' }}
                               class="mt-0.5 h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                        <span>
                            <span class="block text-sm font-medium text-gris-900">Matière active</span>
                            <span class="block text-xs text-gris-400">
                                Une matière inactive disparaît des saisies de notes et des emplois du temps,
                                sans effacer l’historique.
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
                        <dt class="text-gris-400">Enseignants</dt>
                        <dd class="text-right font-medium text-gris-800">{{ $subject->teachers()->count() }}</dd>

                        <dt class="text-gris-400">Notes saisies</dt>
                        <dd class="text-right font-medium text-gris-800">{{ $subject->grades()->count() }}</dd>

                        <dt class="text-gris-400">Créneaux</dt>
                        <dd class="text-right font-medium text-gris-800">{{ $subject->schedules()->count() }}</dd>
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
            {{ $modification ? 'Enregistrer les modifications' : 'Créer la matière' }}
        </button>
        <a href="{{ $modification ? route('subjects.show', $subject->id) : route('subjects.index') }}"
           class="bouton-secondaire">Annuler</a>

        @if ($modification)
            <x-confirmation :action="route('subjects.destroy', $subject->id)" methode="DELETE"
                            titre="Supprimer cette matière ?"
                            :message="'La matière '.$subject->name.' sera définitivement retirée du programme.'"
                            confirmer="Supprimer"
                            bouton="bouton-secondaire ml-auto text-xs text-corail-600 hover:bg-corail-50">
                Supprimer
            </x-confirmation>
        @endif
    </div>
</form>
