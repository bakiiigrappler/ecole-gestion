@extends('layouts.app')

@section('titre', 'Nouvel élève')
@section('sous-titre', 'Le matricule est attribué automatiquement à l’enregistrement')

@section('actions-entete')
    <a href="{{ route('students.index') }}" class="bouton-secondaire">Retour à la liste</a>
@endsection

@section('contenu')

@php
    // Une seule liste de classes, groupée par cycle : plus lisible qu'un
    // enchaînement cycle → niveau → classe, et sans champ désactivé.
    $libellesCycle = [
        'preprimaire' => 'Préprimaire',
        'primaire' => 'Primaire',
        'college' => 'Collège',
        'lycee' => 'Lycée',
    ];

    $classesParCycle = $classes
        ->sortBy(fn ($c) => [$c->level?->order ?? 99, $c->name])
        ->groupBy(fn ($c) => $c->level?->cycle ?? 'autre');

    $anneeCourante = $academicYears->firstWhere('is_current', true);

    // Responsables deja enregistres, pour le selecteur avec recherche.
    $listeParents = $parents->map(fn ($p) => [
        'id' => (string) $p->id,
        'nom' => $p->first_name.' '.$p->last_name,
        'telephone' => $p->phone,
        'email' => $p->email,
        'sexe' => $p->gender,
        'enfants' => $p->students_count,
        'initiales' => mb_strtoupper(mb_substr($p->first_name, 0, 1).mb_substr($p->last_name, 0, 1)),
    ])->values();
@endphp

<form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data"
      x-data="{
          inscrire: {{ old('create_enrollment') ? 'true' : 'false' }},
          apercuPhoto: null,
          previsualiser(evenement) {
              const fichier = evenement.target.files[0];
              if (! fichier) { this.apercuPhoto = null; return; }
              const lecteur = new FileReader();
              lecteur.onload = e => this.apercuPhoto = e.target.result;
              lecteur.readAsDataURL(fichier);
          },
          retirerPhoto() {
              this.apercuPhoto = null;
              this.$refs.photo.value = '';
          },
      }"
      class="space-y-6">
    @csrf

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- ------------------------------------------------------------------
             Identité
             ------------------------------------------------------------------ --}}
        <div class="carte lg:col-span-2">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Identité de l’élève</h2>
                <span class="text-xs text-gris-400"><span class="text-corail-600">*</span> champs obligatoires</span>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="etiquette" for="matricule_apercu">Matricule</label>
                    <input id="matricule_apercu" class="champ cursor-not-allowed bg-gris-100 font-mono text-gris-500"
                           value="" placeholder="{{ $prochainMatricule }}"
                           readonly aria-readonly="true" tabindex="-1">
                    <p class="mt-1 text-xs text-gris-400">
                        Clé du dossier : elle relie l’élève à ses inscriptions, notes et paiements.
                        Attribuée à l’enregistrement, elle ne se saisit pas à la main.
                    </p>
                </div>

                <div>
                    <label class="etiquette" for="first_name">Prénom <span class="text-corail-600">*</span></label>
                    <input id="first_name" name="first_name" type="text" required autofocus
                           value="{{ old('first_name') }}" autocomplete="given-name"
                           class="champ @error('first_name') border-corail-500 @enderror">
                    @error('first_name') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="last_name">Nom <span class="text-corail-600">*</span></label>
                    <input id="last_name" name="last_name" type="text" required
                           value="{{ old('last_name') }}" autocomplete="family-name"
                           class="champ @error('last_name') border-corail-500 @enderror">
                    @error('last_name') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="date_of_birth">Date de naissance <span class="text-corail-600">*</span></label>
                    <input id="date_of_birth" name="date_of_birth" type="date" required
                           value="{{ old('date_of_birth') }}" max="{{ now()->toDateString() }}"
                           class="champ @error('date_of_birth') border-corail-500 @enderror">
                    @error('date_of_birth') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="gender">Sexe <span class="text-corail-600">*</span></label>
                    <select id="gender" name="gender" required
                            class="champ @error('gender') border-corail-500 @enderror">
                        <option value="">Sélectionner</option>
                        <option value="male" @selected(old('gender') === 'male')>Masculin</option>
                        <option value="female" @selected(old('gender') === 'female')>Féminin</option>
                    </select>
                    @error('gender') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="place_of_birth">Lieu de naissance</label>
                    <input id="place_of_birth" name="place_of_birth" type="text"
                           value="{{ old('place_of_birth') }}" class="champ"
                           placeholder="{{ $schoolSettings->city ?? 'Libreville' }}">
                    @error('place_of_birth') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="phone">Téléphone</label>
                    <input id="phone" name="phone" type="tel" maxlength="30"
                           value="{{ old('phone') }}" class="champ @error('phone') border-corail-500 @enderror"
                           placeholder="Ex. 066112233">
                    <p class="mt-1 text-xs text-gris-400">
                        Numéro de l’élève lui-même ; celui du responsable se saisit sur sa fiche.
                    </p>
                    @error('phone') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="email">Courriel</label>
                    <input id="email" name="email" type="email" maxlength="255"
                           value="{{ old('email') }}" class="champ @error('email') border-corail-500 @enderror">
                    @error('email') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="emergency_contact">Contact d’urgence</label>
                    <input id="emergency_contact" name="emergency_contact" type="text"
                           value="{{ old('emergency_contact') }}" class="champ"
                           placeholder="Nom et numéro à joindre">
                    @error('emergency_contact') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="etiquette" for="address">Adresse <span class="text-corail-600">*</span></label>
                    <textarea id="address" name="address" rows="2" required
                              class="champ @error('address') border-corail-500 @enderror"
                              placeholder="Quartier, ville">{{ old('address') }}</textarea>
                    @error('address') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="etiquette" for="medical_conditions">Conditions médicales particulières</label>
                    <textarea id="medical_conditions" name="medical_conditions" rows="2" class="champ"
                              placeholder="Allergies, traitement en cours, précautions…">{{ old('medical_conditions') }}</textarea>
                    <p class="mt-1 text-xs text-gris-400">
                        Information transmise à l’infirmerie et aux enseignants concernés.
                    </p>
                    @error('medical_conditions') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2"
                     x-data="{ aptitude: '{{ old('fitness_status', 'apte') }}' }">
                    <label class="etiquette">Aptitude</label>

                    <div class="grid gap-2 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition-colors"
                               :class="aptitude === 'apte' ? 'border-emerald-300 bg-emerald-50/50' : 'border-gris-200 hover:bg-gris-50'">
                            <input type="radio" name="fitness_status" value="apte" x-model="aptitude"
                                   class="h-4 w-4 cursor-pointer border-gris-300 text-emerald-600 focus:ring-emerald-600">
                            <span>
                                <span class="block text-sm font-medium text-gris-900">Apte</span>
                                <span class="block text-xs text-gris-400">Aucune restriction</span>
                            </span>
                        </label>

                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition-colors"
                               :class="aptitude === 'inapte' ? 'border-corail-300 bg-corail-50/50' : 'border-gris-200 hover:bg-gris-50'">
                            <input type="radio" name="fitness_status" value="inapte" x-model="aptitude"
                                   class="h-4 w-4 cursor-pointer border-gris-300 text-corail-600 focus:ring-corail-600">
                            <span>
                                <span class="block text-sm font-medium text-gris-900">Inapte</span>
                                <span class="block text-xs text-gris-400">Restriction à préciser</span>
                            </span>
                        </label>
                    </div>

                    <div x-show="aptitude === 'inapte'" x-cloak class="mt-3">
                        <label class="etiquette" for="unfitness_reason">
                            Motif de l’inaptitude <span class="text-corail-600">*</span>
                        </label>
                        <textarea id="unfitness_reason" name="unfitness_reason" rows="2"
                                  :required="aptitude === 'inapte'"
                                  placeholder="Ex. dispense d’EPS sur certificat médical jusqu’en juin"
                                  class="champ @error('unfitness_reason') border-corail-500 @enderror">{{ old('unfitness_reason') }}</textarea>
                        <p class="mt-1 text-xs text-gris-400">
                            Visible par l’équipe pédagogique et l’infirmerie.
                        </p>
                        @error('unfitness_reason') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>
                </div>

            </div>
        </div>

        {{-- ------------------------------------------------------------------
             Photo et dossier
             ------------------------------------------------------------------ --}}
        <div class="space-y-6">
            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Photo</h2>
                    <button type="button" x-show="apercuPhoto" x-cloak @click="retirerPhoto()"
                            class="cursor-pointer text-xs font-semibold text-corail-600 hover:underline">Retirer</button>
                </div>

                <div class="space-y-3 p-5">
                    <div class="flex justify-center">
                        <template x-if="apercuPhoto">
                            <img :src="apercuPhoto" alt="Aperçu de la photo"
                                 class="h-28 w-28 rounded-full object-cover ring-1 ring-gris-200">
                        </template>
                        <template x-if="! apercuPhoto">
                            <span class="flex h-28 w-28 items-center justify-center rounded-full bg-ogar-50 ring-1 ring-ogar-100">
                                <svg class="h-10 w-10 text-ogar-300" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                </svg>
                            </span>
                        </template>
                    </div>

                    <input id="photo" name="photo" type="file" accept="image/*" x-ref="photo"
                           @change="previsualiser($event)"
                           class="champ file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-gris-100 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-gris-700">
                    <p class="text-xs text-gris-400">Facultative. JPEG, PNG ou GIF, 2 Mo maximum.</p>
                    @error('photo') <p class="text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Dossier</h2>
                </div>

                <div class="space-y-4 p-5">
                    <div>
                        <label class="etiquette" for="enrollment_date">Date d’entrée <span class="text-corail-600">*</span></label>
                        <input id="enrollment_date" name="enrollment_date" type="date" required
                               value="{{ old('enrollment_date', now()->toDateString()) }}"
                               class="champ @error('enrollment_date') border-corail-500 @enderror">
                        <p class="mt-1 text-xs text-gris-400">Première entrée dans l’établissement.</p>
                        @error('enrollment_date') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiquette" for="status">Statut administratif</label>
                        <select id="status" name="status" class="champ">
                            <option value="active" @selected(old('status', 'active') === 'active')>Actif</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactif</option>
                            <option value="graduated" @selected(old('status') === 'graduated')>Diplômé</option>
                            <option value="transferred" @selected(old('status') === 'transferred')>Transféré</option>
                        </select>
                        @error('status') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <p class="rounded-lg bg-gris-50 px-3 py-2.5 text-xs leading-relaxed text-gris-500">
                        Les responsables légaux se rattachent plus bas dans ce formulaire,
                        ou plus tard depuis
                        <a href="{{ route('parents.index') }}" class="font-semibold text-ogar-600 hover:underline">le module Parents</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------------
         Inscription facultative
         ---------------------------------------------------------------------- --}}
    <div class="carte" :class="inscrire && 'ring-1 ring-ogar-200'">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Inscription dans une classe</h2>
            <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-gris-700">
                <input type="checkbox" name="create_enrollment" value="on" x-model="inscrire"
                       class="h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                Inscrire dès maintenant
            </label>
        </div>

        <div class="p-5">
            <p x-show="! inscrire" class="text-sm text-gris-500">
                L’élève sera enregistré sans affectation. Vous pourrez l’inscrire plus tard depuis
                le module Inscriptions ou depuis sa fiche.
            </p>

            <div x-show="inscrire" x-cloak class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="etiquette" for="academic_year_id">Année scolaire <span class="text-corail-600">*</span></label>
                    <select id="academic_year_id" name="academic_year_id"
                            class="champ @error('academic_year_id') border-corail-500 @enderror">
                        @foreach ($academicYears as $annee)
                            <option value="{{ $annee->id }}"
                                    @selected(old('academic_year_id', $anneeCourante?->id) == $annee->id)>
                                {{ $annee->name }}@if ($annee->is_current) — en cours @endif
                            </option>
                        @endforeach
                    </select>
                    @error('academic_year_id') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="etiquette" for="class_id">Classe <span class="text-corail-600">*</span></label>
                    <select id="class_id" name="class_id"
                            class="champ @error('class_id') border-corail-500 @enderror">
                        <option value="">Choisir une classe…</option>
                        @foreach ($classesParCycle as $cycle => $classesDuCycle)
                            <optgroup label="{{ $libellesCycle[$cycle] ?? ucfirst($cycle) }}">
                                @foreach ($classesDuCycle as $classe)
                                    <option value="{{ $classe->id }}" @selected(old('class_id') == $classe->id)>
                                        {{ $classe->name }}@if ($classe->level) &nbsp;·&nbsp; {{ $classe->level->name }} @endif
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('class_id')
                        <p class="mt-1 text-xs text-corail-700">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-xs text-gris-400">
                            {{ $classes->count() }} classes ouvertes, regroupées par cycle.
                        </p>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------------
         Responsables légaux — rattachement direct depuis la fiche de l'élève
         ---------------------------------------------------------------------- --}}
    <div class="carte" x-data="responsablesDeLEleve({{ Js::from($listeParents) }})">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Responsables légaux</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    <span x-text="lignes.length"></span> responsable(s) &middot;
                    désignez celui que l’établissement joindra en premier
                </p>
            </div>
            <button type="button" @click="ajouter()" class="bouton-mini">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Ajouter un responsable
            </button>
        </div>

        @foreach ($errors->get('responsables.*') as $messages)
            <p class="px-5 pt-4 text-xs text-corail-700">{{ $messages[0] }}</p>
        @endforeach

        <div class="p-5">
            <p x-show="! lignes.length" class="text-sm text-gris-500">
                Aucun responsable pour le moment. L’élève peut être créé sans, et rattaché plus tard
                depuis <a href="{{ route('parents.index') }}" class="font-semibold text-ogar-600 hover:underline">le module Parents</a>.
            </p>

            <div class="space-y-3">
                <template x-for="(ligne, rang) in lignes" :key="rang">
                    <div class="rounded-xl border border-gris-200"
                         :class="ligne.is_primary_contact && 'border-ogar-300 ring-1 ring-ogar-100'">

                        {{-- Choix : responsable connu ou nouvelle fiche --}}
                        <div class="flex flex-wrap items-center gap-3 border-b border-gris-100 p-4">
                            <div class="flex rounded-lg border border-gris-200 p-0.5">
                                <button type="button" @click="ligne.mode = 'existant'"
                                        class="cursor-pointer rounded px-3 py-1 text-xs font-semibold transition-colors"
                                        :class="ligne.mode === 'existant' ? 'bg-ogar-600 text-white' : 'text-gris-600 hover:bg-gris-50'">
                                    Déjà enregistré
                                </button>
                                <button type="button" @click="ligne.mode = 'nouveau'"
                                        class="cursor-pointer rounded px-3 py-1 text-xs font-semibold transition-colors"
                                        :class="ligne.mode === 'nouveau' ? 'bg-ogar-600 text-white' : 'text-gris-600 hover:bg-gris-50'">
                                    Nouvelle fiche
                                </button>
                            </div>

                            <input type="hidden" :name="'responsables[' + rang + '][mode]'" :value="ligne.mode">

                            <button type="button" @click="retirer(rang)"
                                    class="ml-auto cursor-pointer rounded-lg p-2 text-gris-400 transition-colors hover:bg-corail-50 hover:text-corail-600"
                                    title="Retirer ce responsable">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Responsable déjà enregistré : sélecteur avec recherche --}}
                        <div x-show="ligne.mode === 'existant'" class="p-4">
                            <input type="hidden" :name="'responsables[' + rang + '][parent_id]'" :value="ligne.parent_id">

                            <template x-if="parentDe(rang)">
                                <div class="flex items-center gap-3 rounded-lg bg-gris-50 p-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-ogar-100 text-xs font-bold text-ogar-800 ring-1 ring-ogar-200"
                                          x-text="parentDe(rang).initiales"></span>
                                    <div class="min-w-0">
                                        <div class="truncate font-semibold text-gris-900" x-text="parentDe(rang).nom"></div>
                                        <div class="truncate text-xs text-gris-400">
                                            <span x-text="parentDe(rang).telephone"></span>
                                            <template x-if="parentDe(rang).enfants">
                                                <span> &middot; <span x-text="parentDe(rang).enfants"></span> enfant(s) déjà rattaché(s)</span>
                                            </template>
                                        </div>
                                    </div>
                                    <button type="button" @click="effacer(rang)"
                                            class="ml-auto cursor-pointer text-xs font-semibold text-ogar-600 hover:underline">
                                        Changer
                                    </button>
                                </div>
                            </template>

                            <div x-show="! parentDe(rang)" class="relative" @click.outside="ligne.ouvert = false">
                                <label class="etiquette" :for="'parent-' + rang">
                                    Responsable <span class="text-corail-600">*</span>
                                </label>

                                <input :id="'parent-' + rang" type="text" class="champ" autocomplete="off"
                                       placeholder="Nom, prénom ou téléphone…"
                                       x-model="ligne.recherche"
                                       @focus="ligne.ouvert = true; ligne.surligne = 0"
                                       @input="ligne.ouvert = true; ligne.surligne = 0"
                                       @keydown.arrow-down.prevent="naviguer(rang, 1)"
                                       @keydown.arrow-up.prevent="naviguer(rang, -1)"
                                       @keydown.enter.prevent="valider(rang)"
                                       @keydown.escape="ligne.ouvert = false"
                                       role="combobox" aria-autocomplete="list" :aria-expanded="ligne.ouvert">

                                <ul x-show="ligne.ouvert" x-cloak role="listbox"
                                    class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-gris-200 bg-white shadow-lg">

                                    <template x-for="(parent, index) in resultats(rang)" :key="parent.id">
                                        <li role="option" @click="choisir(rang, parent)" @mouseenter="ligne.surligne = index"
                                            class="flex cursor-pointer items-center gap-3 px-3 py-2 transition-colors"
                                            :class="index === ligne.surligne ? 'bg-ogar-50' : 'hover:bg-gris-50'">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gris-100 text-[10px] font-bold text-gris-600"
                                                  x-text="parent.initiales"></span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-medium text-gris-800" x-text="parent.nom"></span>
                                                <span class="block truncate text-[11px] text-gris-400" x-text="parent.telephone"></span>
                                            </span>
                                        </li>
                                    </template>

                                    <li x-show="! resultats(rang).length" class="px-3 py-6 text-center text-sm text-gris-400">
                                        Aucun responsable ne correspond. Passez à « Nouvelle fiche ».
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {{-- Nouvelle fiche : le strict nécessaire, à compléter plus tard --}}
                        <div x-show="ligne.mode === 'nouveau'" x-cloak class="grid gap-4 p-4 sm:grid-cols-4">
                            <div>
                                <label class="etiquette" :for="'prenom-' + rang">Prénom <span class="text-corail-600">*</span></label>
                                <input :id="'prenom-' + rang" type="text" class="champ"
                                       :name="'responsables[' + rang + '][first_name]'" x-model="ligne.first_name">
                            </div>
                            <div>
                                <label class="etiquette" :for="'nom-' + rang">Nom <span class="text-corail-600">*</span></label>
                                <input :id="'nom-' + rang" type="text" class="champ"
                                       :name="'responsables[' + rang + '][last_name]'" x-model="ligne.last_name">
                            </div>
                            <div>
                                <label class="etiquette" :for="'sexe-' + rang">Sexe <span class="text-corail-600">*</span></label>
                                <select :id="'sexe-' + rang" class="champ"
                                        :name="'responsables[' + rang + '][gender]'"
                                        x-model="ligne.gender" @change="revaliderLien(rang)">
                                    <option value="">Sélectionner</option>
                                    <option value="male">Masculin</option>
                                    <option value="female">Féminin</option>
                                </select>
                            </div>
                            <div>
                                <label class="etiquette" :for="'tel-' + rang">Téléphone <span class="text-corail-600">*</span></label>
                                <input :id="'tel-' + rang" type="tel" class="champ" placeholder="077 12 34 56"
                                       :name="'responsables[' + rang + '][phone]'" x-model="ligne.phone">
                            </div>
                            <p class="text-[11px] text-gris-400 sm:col-span-4">
                                La fiche sera créée avec l’adresse de l’élève ; le reste se complète depuis le module Parents.
                            </p>
                        </div>

                        {{-- Lien et rôle auprès de cet élève --}}
                        <div class="grid gap-4 border-t border-gris-100 p-4 sm:grid-cols-3">
                            <div>
                                <label class="etiquette" :for="'lienr-' + rang">
                                    Lien de parenté <span class="text-corail-600">*</span>
                                </label>
                                <select :id="'lienr-' + rang" class="champ"
                                        :name="'responsables[' + rang + '][relationship_type]'"
                                        x-model="ligne.relationship_type">
                                    <template x-for="choix in liensPossibles(rang)" :key="choix.valeur">
                                        <option :value="choix.valeur" x-text="choix.libelle"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <span class="etiquette">Rôle auprès de l’élève</span>

                                <div class="flex flex-wrap gap-x-6 gap-y-1.5 pt-1">
                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gris-700">
                                        <input type="hidden" :name="'responsables[' + rang + '][is_primary_contact]'" value="0">
                                        <input type="checkbox" value="1"
                                               :name="'responsables[' + rang + '][is_primary_contact]'"
                                               x-model="ligne.is_primary_contact" @change="basculerPrincipal(rang)"
                                               class="h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                                        Contact principal
                                    </label>

                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gris-700">
                                        <input type="hidden" :name="'responsables[' + rang + '][lives_with_student]'" value="0">
                                        <input type="checkbox" value="1"
                                               :name="'responsables[' + rang + '][lives_with_student]'"
                                               x-model="ligne.lives_with_student"
                                               class="h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                                        Vit avec l’élève
                                    </label>

                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gris-700">
                                        <input type="hidden" :name="'responsables[' + rang + '][can_pickup]'" value="0">
                                        <input type="checkbox" value="1"
                                               :name="'responsables[' + rang + '][can_pickup]'"
                                               x-model="ligne.can_pickup"
                                               class="h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                                        Autorisé à venir le chercher
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3">
        <p class="mr-auto text-xs text-gris-400"
           x-text="inscrire
               ? 'L’élève sera créé puis inscrit dans la classe choisie.'
               : 'L’élève sera créé sans inscription.'"></p>
        <a href="{{ route('students.index') }}" class="bouton-secondaire">Annuler</a>
        <button type="submit" class="bouton-primaire">Créer le dossier</button>
    </div>
</form>

@endsection
