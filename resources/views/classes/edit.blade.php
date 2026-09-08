@extends('layouts.app')

@section('titre', 'Modifier '.$class->name)
@section('sous-titre', $class->getSafeLevelName().' — '.($class->series ? 'série '.$class->series : 'toutes séries'))

@section('actions-entete')
    <a href="{{ route('classes.show', $class->id) }}" class="bouton-secondaire">Voir la fiche</a>
@endsection

@section('contenu')

@php
    $libellesCycle = [
        'preprimaire' => 'Préprimaire',
        'primaire' => 'Primaire',
        'college' => 'Collège',
        'lycee' => 'Lycée',
    ];
    $teintesCycle = ['preprimaire' => 'amber', 'primaire' => 'emerald', 'college' => 'sky', 'lycee' => 'violet'];

    $cycle = $class->getSafeCycle();
    $equipe = $class->allTeachers;
    $principal = $equipe->firstWhere('pivot.role', 'principal');

    // Le niveau porte le cycle : la vue le montre, l'utilisateur ne le saisit pas.
    $cyclesParNiveau = $levels->mapWithKeys(fn ($n) => [$n->id => $n->cycle])->all();

    $serieActuelle = old('series_id', $class->series_id);
@endphp

<form method="POST" action="{{ route('classes.update', $class->id) }}"
      x-data="{
          niveau: '{{ old('level_id', $class->level_id) }}',
          cycles: {{ Js::from($cyclesParNiveau) }},
          libelles: {{ Js::from($libellesCycle) }},
          capacite: {{ (int) old('capacity', $class->capacity) ?: 'null' }},
          effectif: {{ $effectif }},

          get cycle() {
              return this.cycles[this.niveau] || '';
          },
          get cycleLisible() {
              return this.libelles[this.cycle] || 'Choisissez un niveau';
          },
          get placesRestantes() {
              if (! this.capacite) return null;
              return this.capacite - this.effectif;
          },
      }"
      class="space-y-6">
    @csrf
    @method('PUT')

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
                <div class="sm:col-span-2">
                    <label class="etiquette" for="name">Nom de la classe <span class="text-corail-600">*</span></label>
                    <input id="name" name="name" type="text" required maxlength="255"
                           value="{{ old('name', $class->name) }}"
                           placeholder="Ex. 6ème A"
                           class="champ @error('name') border-corail-500 @enderror">
                    <p class="mt-1 text-xs text-gris-400">
                        Ce nom apparaît sur les listes d’appel, les bulletins et les reçus.
                    </p>
                    @error('name') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="level_id">Niveau <span class="text-corail-600">*</span></label>
                    <select id="level_id" name="level_id" required x-model="niveau"
                            class="champ @error('level_id') border-corail-500 @enderror">
                        <option value="">Sélectionner un niveau</option>
                        @foreach ($levels->groupBy('cycle') as $cycleNiveau => $niveaux)
                            <optgroup label="{{ $libellesCycle[$cycleNiveau] ?? ucfirst($cycleNiveau) }}">
                                @foreach ($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}"
                                        {{ (string) old('level_id', $class->level_id) === (string) $niveau->id ? 'selected' : '' }}>
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

                @if ($series->isNotEmpty())
                    <div class="sm:col-span-2">
                        <label class="etiquette" for="series_id">Série</label>
                        <select id="series_id" name="series_id" class="champ @error('series_id') border-corail-500 @enderror">
                            <option value="">Aucune série</option>
                            @foreach ($series as $serie)
                                <option value="{{ $serie->id }}" {{ (string) $serieActuelle === (string) $serie->id ? 'selected' : '' }}>
                                    {{ $serie->code }} — {{ $serie->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gris-400">
                            Séries ouvertes en {{ $class->getSafeLevelName() }}. Laissez vide pour une classe sans série.
                        </p>
                        @error('series_id') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="sm:col-span-2">
                    <label class="etiquette" for="description">Description</label>
                    <textarea id="description" name="description" rows="3"
                              placeholder="Spécificité de la classe, options, remarques…"
                              class="champ @error('description') border-corail-500 @enderror">{{ old('description', $class->description) }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------------
             Situation de la classe (colonne latérale)
             ------------------------------------------------------------------ --}}
        <div class="space-y-6">
            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Situation actuelle</h2>
                </div>
                <div class="space-y-3 p-5">
                    <div class="flex flex-wrap gap-2">
                        <x-puce :couleur="$teintesCycle[$cycle] ?? 'slate'">{{ $libellesCycle[$cycle] ?? ucfirst($cycle) }}</x-puce>
                        @if (! $class->is_active) <x-puce couleur="rose">Fermée</x-puce> @endif
                        @if ($equipe->isEmpty()) <x-puce couleur="rose">Sans enseignant</x-puce> @endif
                    </div>

                    <dl class="grid grid-cols-2 gap-y-2.5 text-sm">
                        <dt class="text-gris-400">Élèves inscrits</dt>
                        <dd class="text-right font-semibold text-gris-900">{{ $effectif }}</dd>

                        <dt class="text-gris-400">Équipe</dt>
                        <dd class="text-right font-medium text-gris-800">{{ $equipe->count() }} enseignant(s)</dd>

                        <dt class="text-gris-400">Professeur principal</dt>
                        <dd class="text-right font-medium text-gris-800">{{ $principal?->full_name ?? '—' }}</dd>
                    </dl>

                    @if ($effectif > 0)
                        <p class="rounded-lg bg-gris-50 px-3 py-2 text-xs text-gris-500">
                            {{ $effectif }} élève(s) sont inscrits : la capacité ne peut plus descendre en dessous.
                        </p>
                    @endif
                </div>
            </div>

            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Capacité et ouverture</h2>
                </div>
                <div class="space-y-4 p-5">
                    <div>
                        <label class="etiquette" for="capacity">Capacité d’accueil</label>
                        <input id="capacity" name="capacity" type="number" x-model.number="capacite"
                               min="{{ max($effectif, 1) }}" max="200"
                               value="{{ old('capacity', $class->capacity) }}"
                               placeholder="30"
                               class="champ @error('capacity') border-corail-500 @enderror">

                        <template x-if="placesRestantes !== null">
                            <p class="mt-1 text-xs"
                               :class="placesRestantes < 0 ? 'text-corail-700' : 'text-gris-400'">
                                <span x-show="placesRestantes >= 0">
                                    <span x-text="placesRestantes"></span> place(s) encore disponible(s).
                                </span>
                                <span x-show="placesRestantes < 0" x-cloak>
                                    Capacité inférieure à l’effectif inscrit.
                                </span>
                            </p>
                        </template>

                        @error('capacity') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gris-200 p-3 transition-colors hover:bg-gris-50">
                        {{-- Champ caché : sans lui, décocher n'envoie rien et la classe ne peut jamais être fermée. --}}
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $class->is_active) ? 'checked' : '' }}
                               class="mt-0.5 h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                        <span>
                            <span class="block text-sm font-medium text-gris-900">Classe ouverte</span>
                            <span class="block text-xs text-gris-400">
                                Une classe fermée n’accepte plus d’inscription et disparaît des listes de saisie.
                            </span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         Barre d'actions
         ------------------------------------------------------------------ --}}
    <div class="carte flex flex-wrap items-center gap-3 p-4">
        <button type="submit" class="bouton-primaire">Enregistrer les modifications</button>
        <a href="{{ route('classes.show', $class->id) }}" class="bouton-secondaire">Annuler</a>

        <a href="{{ route('classes.show', ['class' => $class->id, 'onglet' => 'equipe']) }}"
           class="bouton-secondaire ml-auto text-xs">
            Gérer l’équipe pédagogique
        </a>

        <x-confirmation :action="route('classes.destroy', $class->id)" methode="DELETE"
                        titre="Supprimer cette classe ?"
                        :message="'La classe '.$class->name.' compte '.$effectif.' élève(s) inscrit(s). La suppression est définitive.'"
                        confirmer="Supprimer"
                        bouton="bouton-secondaire text-xs text-corail-600 hover:bg-corail-50">
            Supprimer
        </x-confirmation>
    </div>
</form>

@endsection
