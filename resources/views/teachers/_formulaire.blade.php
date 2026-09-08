@php
    /*
     * Formulaire partagé par la création et la modification d'un enseignant.
     * $teacher est un modèle vide en création, ce qui permet aux deux vues de
     * pointer ici sans dupliquer une trentaine de champs.
     */
    $modification = $teacher->exists;

    $libellesCycle = [
        'preprimaire' => 'Préprimaire',
        'primaire' => 'Primaire',
        'college' => 'Collège',
        'lycee' => 'Lycée',
    ];

    $classesParCycle = $classes
        ->sortBy(fn ($c) => [$c->level?->order ?? 99, $c->name])
        ->groupBy(fn ($c) => $c->level?->cycle ?? 'autre');
@endphp

<form method="POST" enctype="multipart/form-data"
      action="{{ $modification ? route('teachers.update', $teacher) : route('teachers.store') }}"
      x-data="{
          typeEnseignant: '{{ old('teacher_type', $teacher->teacher_type ?? 'general') }}',
          apercuPhoto: null,
          previsualiser(evenement) {
              const fichier = evenement.target.files[0];
              if (! fichier) { this.apercuPhoto = null; return; }
              const lecteur = new FileReader();
              lecteur.onload = e => this.apercuPhoto = e.target.result;
              lecteur.readAsDataURL(fichier);
          },
          annulerPhoto() {
              this.apercuPhoto = null;
              this.$refs.photo.value = '';
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
                <div class="sm:col-span-2">
                    <label class="etiquette" for="employee_id">Matricule @if ($modification)<span class="text-corail-600">*</span>@endif</label>
                    @if ($modification)
                        <input id="employee_id" name="employee_id" type="text"
                               value="{{ old('employee_id', $teacher->employee_id) }}"
                               class="champ font-mono @error('employee_id') border-corail-500 @enderror">
                        <p class="mt-1 text-xs text-gris-400">
                            Clé du dossier : elle relie l’enseignant à ses classes, matières et notes.
                            Modifiable, mais elle doit rester unique dans l’établissement.
                        </p>
                    @else
                        <input id="employee_id" class="champ cursor-not-allowed bg-gris-100 font-mono text-gris-500"
                               value="" placeholder="{{ $prochainMatricule ?? '' }}"
                               readonly aria-readonly="true" tabindex="-1">
                        <p class="mt-1 text-xs text-gris-400">
                            Clé du dossier, attribuée à l’enregistrement : préfixe ENS, année, puis numéro d’ordre.
                        </p>
                    @endif
                    @error('employee_id') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="first_name">Prénom <span class="text-corail-600">*</span></label>
                    <input id="first_name" name="first_name" type="text" required autocomplete="given-name"
                           value="{{ old('first_name', $teacher->first_name) }}"
                           class="champ @error('first_name') border-corail-500 @enderror">
                    @error('first_name') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="last_name">Nom <span class="text-corail-600">*</span></label>
                    <input id="last_name" name="last_name" type="text" required autocomplete="family-name"
                           value="{{ old('last_name', $teacher->last_name) }}"
                           class="champ @error('last_name') border-corail-500 @enderror">
                    @error('last_name') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="date_of_birth">Date de naissance</label>
                    <input id="date_of_birth" name="date_of_birth" type="date" max="{{ now()->toDateString() }}"
                           value="{{ old('date_of_birth', $teacher->date_of_birth?->format('Y-m-d')) }}"
                           class="champ @error('date_of_birth') border-corail-500 @enderror">
                    @error('date_of_birth') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="gender">Sexe</label>
                    <select id="gender" name="gender" class="champ">
                        <option value="">Sélectionner</option>
                        <option value="male" @selected(old('gender', $teacher->gender) === 'male')>Masculin</option>
                        <option value="female" @selected(old('gender', $teacher->gender) === 'female')>Féminin</option>
                    </select>
                    @error('gender') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="email">Adresse e-mail <span class="text-corail-600">*</span></label>
                    <input id="email" name="email" type="email" required autocomplete="email"
                           value="{{ old('email', $teacher->email) }}"
                           class="champ @error('email') border-corail-500 @enderror">
                    @error('email') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="phone">Téléphone</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone', $teacher->phone) }}"
                           class="champ" placeholder="077 12 34 56">
                    @error('phone') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="etiquette" for="address">Adresse</label>
                    <textarea id="address" name="address" rows="2" class="champ"
                              placeholder="Quartier, ville">{{ old('address', $teacher->address) }}</textarea>
                    @error('address') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------------
             Photo et contrat
             ------------------------------------------------------------------ --}}
        <div class="space-y-6">
            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Photo</h2>
                    <button type="button" x-show="apercuPhoto" x-cloak @click="annulerPhoto()"
                            class="cursor-pointer text-xs font-semibold text-corail-600 hover:underline">Annuler</button>
                </div>

                <div class="space-y-3 p-5">
                    <div class="flex justify-center">
                        <template x-if="apercuPhoto">
                            <img :src="apercuPhoto" alt="Aperçu de la photo"
                                 class="h-28 w-28 rounded-full object-cover ring-1 ring-ogar-300">
                        </template>
                        <template x-if="! apercuPhoto">
                            <span>
                                @if ($modification)
                                    <x-avatar :personne="$teacher" taille="h-28 w-28"/>
                                @else
                                    <span class="flex h-28 w-28 items-center justify-center rounded-full bg-ogar-50 ring-1 ring-ogar-100">
                                        <svg class="h-10 w-10 text-ogar-300" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                        </svg>
                                    </span>
                                @endif
                            </span>
                        </template>
                    </div>

                    <input id="photo" name="photo" type="file" accept="image/*" x-ref="photo"
                           @change="previsualiser($event)"
                           class="champ file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-gris-100 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-gris-700">
                    <p class="text-xs text-gris-400">
                        Facultative. JPEG, PNG ou GIF, 2 Mo maximum.
                        @if ($modification) Laisser vide pour conserver la photo actuelle. @endif
                    </p>
                    @error('photo') <p class="text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Contrat</h2>
                </div>

                <div class="space-y-4 p-5">
                    <div>
                        <label class="etiquette" for="hire_date">Date d’embauche <span class="text-corail-600">*</span></label>
                        <input id="hire_date" name="hire_date" type="date" required max="{{ now()->toDateString() }}"
                               value="{{ old('hire_date', $teacher->hire_date?->format('Y-m-d') ?? now()->toDateString()) }}"
                               class="champ @error('hire_date') border-corail-500 @enderror">
                        @if ($modification && $teacher->hire_date)
                            <p class="mt-1 text-xs text-gris-400">{{ $teacher->years_of_service }} an(s) d’ancienneté.</p>
                        @endif
                        @error('hire_date') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiquette" for="salary">Salaire mensuel</label>
                        <div class="relative">
                            <input id="salary" name="salary" type="number" min="0" step="1000"
                                   value="{{ old('salary', $teacher->salary ? (int) $teacher->salary : '') }}"
                                   class="champ pr-14">
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-gris-400">FCFA</span>
                        </div>
                        @error('salary') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiquette" for="status">Statut <span class="text-corail-600">*</span></label>
                        <select id="status" name="status" required class="champ">
                            <option value="active" @selected(old('status', $teacher->status ?? 'active') === 'active')>Actif</option>
                            <option value="inactive" @selected(old('status', $teacher->status) === 'inactive')>Inactif</option>
                            <option value="suspended" @selected(old('status', $teacher->status) === 'suspended')>Suspendu</option>
                        </select>
                        @error('status') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiquette" for="diploma_file">Diplôme</label>
                        <input id="diploma_file" name="diploma_file" type="file" accept=".pdf,.jpg,.jpeg,.png"
                               class="champ file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-gris-100 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-gris-700">
                        @if ($modification && $teacher->diploma_file)
                            <p class="mt-1 text-xs text-gris-400">
                                <a href="{{ asset('storage/'.$teacher->diploma_file) }}" target="_blank"
                                   class="font-semibold text-ogar-600 hover:underline">Pièce actuelle</a>
                                — laisser vide pour la conserver.
                            </p>
                        @else
                            <p class="mt-1 text-xs text-gris-400">PDF ou image, 15 Mo maximum.</p>
                        @endif
                        @error('diploma_file') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------------
         Affectation pédagogique
         ---------------------------------------------------------------------- --}}
    <div class="carte">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Affectation pédagogique</h2>
            <span class="text-xs text-gris-400" x-text="typeEnseignant === 'general'
                ? 'Un polyvalent tient une classe entière'
                : 'Un spécialisé enseigne une matière dans plusieurs classes'"></span>
        </div>

        <div class="grid gap-4 p-5 sm:grid-cols-3">
            <div>
                <label class="etiquette" for="cycle">Cycle <span class="text-corail-600">*</span></label>
                <select id="cycle" name="cycle" required class="champ @error('cycle') border-corail-500 @enderror">
                    <option value="">Sélectionner</option>
                    @foreach ($libellesCycle as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(old('cycle', $teacher->cycle) === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
                @error('cycle') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="etiquette" for="teacher_type">Type d’enseignant <span class="text-corail-600">*</span></label>
                <select id="teacher_type" name="teacher_type" required x-model="typeEnseignant"
                        class="champ @error('teacher_type') border-corail-500 @enderror">
                    <option value="general">Polyvalent</option>
                    <option value="specialized">Spécialisé</option>
                </select>
                @error('teacher_type') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="etiquette" for="qualification">Qualification</label>
                <input id="qualification" name="qualification" type="text"
                       value="{{ old('qualification', $teacher->qualification) }}"
                       class="champ" placeholder="Licence, Master, CAPES…">
                @error('qualification') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
            </div>

            {{-- Un polyvalent tient une classe ; un spécialisé enseigne une matière. --}}
            <div x-show="typeEnseignant === 'general'" x-cloak class="sm:col-span-3">
                <label class="etiquette" for="assigned_class_id">Classe attribuée</label>
                <select id="assigned_class_id" name="assigned_class_id"
                        class="champ @error('assigned_class_id') border-corail-500 @enderror">
                    <option value="">Aucune pour le moment</option>
                    @foreach ($classesParCycle as $cycle => $classesDuCycle)
                        <optgroup label="{{ $libellesCycle[$cycle] ?? ucfirst($cycle) }}">
                            @foreach ($classesDuCycle as $classe)
                                <option value="{{ $classe->id }}"
                                        @selected(old('assigned_class_id', $teacher->assignedClass?->id) == $classe->id)>
                                    {{ $classe->name }}@if ($classe->level) &nbsp;·&nbsp; {{ $classe->level->name }} @endif
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('assigned_class_id')
                    <p class="mt-1 text-xs text-corail-700">{{ $message }}</p>
                @else
                    <p class="mt-1 text-xs text-gris-400">
                        Obligatoire pour un polyvalent de collège ou de lycée.
                        {{ $classes->count() }} classes ouvertes, regroupées par cycle.
                    </p>
                @enderror
            </div>

            <div x-show="typeEnseignant === 'specialized'" x-cloak class="sm:col-span-3">
                <label class="etiquette" for="specialization">Matière enseignée <span class="text-corail-600">*</span></label>
                <input id="specialization" name="specialization" type="text" list="liste-matieres"
                       value="{{ old('specialization', $teacher->specialization) }}"
                       class="champ @error('specialization') border-corail-500 @enderror"
                       placeholder="Mathématiques, Français…">
                <datalist id="liste-matieres">
                    @foreach ($subjects->pluck('name')->unique() as $matiere)
                        <option value="{{ $matiere }}"></option>
                    @endforeach
                </datalist>
                @error('specialization')
                    <p class="mt-1 text-xs text-corail-700">{{ $message }}</p>
                @else
                    <p class="mt-1 text-xs text-gris-400">
                        Saisie libre, avec les matières déjà enregistrées en suggestion.
                    </p>
                @enderror
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3">
        @if ($modification)
            <x-confirmation :action="route('teachers.destroy', $teacher)" methode="DELETE"
                            titre="Supprimer cet enseignant ?"
                            :message="'Le dossier de '.$teacher->full_name.' sera définitivement supprimé.'"
                            confirmer="Supprimer"
                            bouton="mr-auto text-sm font-semibold text-corail-600 hover:underline">
                Supprimer cet enseignant
            </x-confirmation>
        @endif

        <a href="{{ $modification ? route('teachers.show', $teacher) : route('teachers.index') }}"
           class="bouton-secondaire">Annuler</a>
        <button class="bouton-primaire">{{ $modification ? 'Enregistrer les modifications' : 'Créer le dossier' }}</button>
    </div>
</form>
