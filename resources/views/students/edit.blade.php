@extends('layouts.app')

@section('titre', 'Modifier '.$student->full_name)
@section('sous-titre', 'Matricule '.$student->student_id)

@section('actions-entete')
    <a href="{{ route('students.show', $student->id) }}" class="bouton-secondaire">Voir la fiche</a>
@endsection

@section('contenu')

@php
    $inscriptions = $student->enrollments->sortByDesc('enrollment_date');
    $inscriptionActuelle = $inscriptions->firstWhere('status', 'active') ?? $inscriptions->first();

    $libellesPaiement = ['completed' => 'Soldé', 'partial' => 'Partiel', 'pending' => 'Impayé', 'overdue' => 'En retard'];
    $teintesPaiement = ['completed' => 'emerald', 'partial' => 'amber', 'pending' => 'rose', 'overdue' => 'rose'];
@endphp

<form method="POST" action="{{ route('students.update', $student->id) }}" enctype="multipart/form-data"
      x-data="{
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
    @method('PUT')

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
                    <label class="etiquette" for="student_id">Matricule <span class="text-corail-600">*</span></label>
                    <input id="student_id" name="student_id" type="text" required
                           value="{{ old('student_id', $student->student_id) }}"
                           class="champ font-mono @error('student_id') border-corail-500 @enderror">
                    <p class="mt-1 text-xs text-gris-400">
                        Clé du dossier : elle relie l’élève à ses inscriptions, notes et paiements.
                        Modifiable, mais elle doit rester unique dans l’établissement.
                    </p>
                    @error('student_id') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="first_name">Prénom <span class="text-corail-600">*</span></label>
                    <input id="first_name" name="first_name" type="text" required
                           value="{{ old('first_name', $student->first_name) }}" autocomplete="given-name"
                           class="champ @error('first_name') border-corail-500 @enderror">
                    @error('first_name') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="last_name">Nom <span class="text-corail-600">*</span></label>
                    <input id="last_name" name="last_name" type="text" required
                           value="{{ old('last_name', $student->last_name) }}" autocomplete="family-name"
                           class="champ @error('last_name') border-corail-500 @enderror">
                    @error('last_name') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="date_of_birth">Date de naissance <span class="text-corail-600">*</span></label>
                    <input id="date_of_birth" name="date_of_birth" type="date" required
                           value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}"
                           max="{{ now()->toDateString() }}"
                           class="champ @error('date_of_birth') border-corail-500 @enderror">
                    @if ($student->date_of_birth)
                        <p class="mt-1 text-xs text-gris-400">
                            {{ \Carbon\Carbon::parse($student->date_of_birth)->age }} ans aujourd’hui.
                        </p>
                    @endif
                    @error('date_of_birth') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="gender">Sexe <span class="text-corail-600">*</span></label>
                    <select id="gender" name="gender" required
                            class="champ @error('gender') border-corail-500 @enderror">
                        <option value="male" @selected(old('gender', $student->gender) === 'male')>Masculin</option>
                        <option value="female" @selected(old('gender', $student->gender) === 'female')>Féminin</option>
                    </select>
                    @error('gender') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="place_of_birth">Lieu de naissance</label>
                    <input id="place_of_birth" name="place_of_birth" type="text"
                           value="{{ old('place_of_birth', $student->place_of_birth) }}" class="champ">
                    @error('place_of_birth') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="phone">Téléphone</label>
                    <input id="phone" name="phone" type="tel" maxlength="30"
                           value="{{ old('phone', $student->phone) }}" class="champ @error('phone') border-corail-500 @enderror"
                           placeholder="Ex. 066112233">
                    <p class="mt-1 text-xs text-gris-400">
                        Numéro de l’élève lui-même ; celui du responsable se saisit sur sa fiche.
                    </p>
                    @error('phone') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="email">Courriel</label>
                    <input id="email" name="email" type="email" maxlength="255"
                           value="{{ old('email', $student->email) }}" class="champ @error('email') border-corail-500 @enderror">
                    @error('email') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="emergency_contact">Contact d’urgence</label>
                    <input id="emergency_contact" name="emergency_contact" type="text"
                           value="{{ old('emergency_contact', $student->emergency_contact) }}" class="champ"
                           placeholder="Nom et numéro à joindre">
                    @error('emergency_contact') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="etiquette" for="address">Adresse <span class="text-corail-600">*</span></label>
                    <textarea id="address" name="address" rows="2" required
                              class="champ @error('address') border-corail-500 @enderror">{{ old('address', $student->address) }}</textarea>
                    @error('address') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="etiquette" for="medical_conditions">Conditions médicales particulières</label>
                    <textarea id="medical_conditions" name="medical_conditions" rows="2"
                              class="champ"
                              placeholder="Allergies, traitement en cours, précautions…">{{ old('medical_conditions', $student->medical_conditions) }}</textarea>
                    <p class="mt-1 text-xs text-gris-400">
                        Information transmise à l’infirmerie et aux enseignants concernés.
                    </p>
                    @error('medical_conditions') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2"
                     x-data="{ aptitude: '{{ old('fitness_status', $student->fitness_status ?? 'apte') }}' }">
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
                                  class="champ @error('unfitness_reason') border-corail-500 @enderror">{{ old('unfitness_reason', $student->unfitness_reason) }}</textarea>
                        <p class="mt-1 text-xs text-gris-400">
                            Visible par l’équipe pédagogique et l’infirmerie.
                        </p>
                        @error('unfitness_reason') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>
                </div>

            </div>
        </div>

        {{-- ------------------------------------------------------------------
             Photo, dossier et situation
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
                            <img :src="apercuPhoto" alt="Nouvelle photo"
                                 class="h-28 w-28 rounded-full object-cover ring-1 ring-ogar-300">
                        </template>
                        <template x-if="! apercuPhoto">
                            <span><x-avatar :personne="$student" taille="h-28 w-28"/></span>
                        </template>
                    </div>

                    <input id="photo" name="photo" type="file" accept="image/*" x-ref="photo"
                           @change="previsualiser($event)"
                           class="champ file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-gris-100 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-gris-700">
                    <p class="text-xs text-gris-400" x-text="apercuPhoto
                        ? 'La photo sera remplacée à l’enregistrement.'
                        : 'Laisser vide pour conserver la photo actuelle.'"></p>
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
                               value="{{ old('enrollment_date', $student->enrollment_date?->format('Y-m-d')) }}"
                               class="champ @error('enrollment_date') border-corail-500 @enderror">
                        @error('enrollment_date') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiquette" for="status">Statut administratif <span class="text-corail-600">*</span></label>
                        <select id="status" name="status" required class="champ">
                            <option value="active" @selected(old('status', $student->status) === 'active')>Actif</option>
                            <option value="inactive" @selected(old('status', $student->status) === 'inactive')>Inactif</option>
                            <option value="graduated" @selected(old('status', $student->status) === 'graduated')>Diplômé</option>
                            <option value="transferred" @selected(old('status', $student->status) === 'transferred')>Transféré</option>
                        </select>
                        <p class="mt-1 text-xs text-gris-400">
                            Un élève inactif reste dans les archives mais sort des listes courantes.
                        </p>
                        @error('status') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Rappel de la situation, en lecture seule : l'inscription se gère
                 dans son propre module, pas depuis la fiche de l'élève. --}}
            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Situation actuelle</h2>
                    <a href="{{ route('enrollments.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                        Inscriptions
                    </a>
                </div>

                <dl class="space-y-2.5 p-5 text-sm">
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-gris-400">Classe</dt>
                        <dd class="text-right font-medium text-gris-800">
                            {{ $inscriptionActuelle?->schoolClass->name ?? 'Non affecté' }}
                        </dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-gris-400">Année scolaire</dt>
                        <dd class="text-right font-medium text-gris-800">
                            {{ $inscriptionActuelle?->academicYear->name ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-gris-400">Paiement</dt>
                        <dd class="text-right">
                            @if ($inscriptionActuelle)
                                <x-puce :couleur="$teintesPaiement[$inscriptionActuelle->payment_status] ?? 'slate'">
                                    {{ $libellesPaiement[$inscriptionActuelle->payment_status] ?? '—' }}
                                </x-puce>
                            @else
                                <span class="text-gris-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3 border-t border-gris-100 pt-2.5">
                        <dt class="text-gris-400">Responsables</dt>
                        <dd class="text-right font-medium text-gris-800">{{ $student->parents->count() }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3">
        <x-confirmation :action="route('students.destroy', $student->id)" methode="DELETE"
                        titre="Supprimer cet élève ?"
                        :message="'Le dossier de '.$student->full_name.' et ses '.$inscriptions->count().' inscription(s) seront définitivement supprimés.'"
                        confirmer="Supprimer"
                        bouton="mr-auto text-sm font-semibold text-corail-600 hover:underline">
            Supprimer cet élève
        </x-confirmation>

        <a href="{{ route('students.show', $student->id) }}" class="bouton-secondaire">Annuler</a>
        <button type="submit" class="bouton-primaire">Enregistrer les modifications</button>
    </div>
</form>

@endsection
