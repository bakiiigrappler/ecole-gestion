@extends('layouts.app')

@section('titre', 'Créer le profil de l’élève')
@section('sous-titre', 'Dossier '.($enrollment->enrollment_code ?: '#'.$enrollment->id).' — '.$enrollment->applicant_full_name)

@section('actions-entete')
    <a href="{{ route('enrollments.show', $enrollment->id) }}" class="bouton-secondaire">Voir le dossier</a>
@endsection

@section('contenu')

@php
    $libellesCycle = ['preprimaire' => 'Préprimaire', 'primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    $teintesCycle = ['preprimaire' => 'amber', 'primaire' => 'emerald', 'college' => 'sky', 'lycee' => 'violet'];

    $libellesLien = ['father' => 'Père', 'mother' => 'Mère', 'guardian' => 'Tuteur', 'other' => 'Autre'];
    $libellesPaiement = ['completed' => 'Soldé', 'partial' => 'Partiel', 'pending' => 'Impayé', 'overdue' => 'En retard'];
    $teintesPaiement = ['completed' => 'emerald', 'partial' => 'amber', 'pending' => 'rose', 'overdue' => 'rose'];

    $franc = fn ($m) => number_format((float) $m, 0, ',', ' ').' F';

    $cycle = $enrollment->schoolClass?->getSafeCycle();
    $naissance = $enrollment->identite_naissance;

    // Un responsable n'est proposé que si le dossier en porte un.
    $aUnResponsable = $enrollment->parent_first_name || $enrollment->parent_last_name || $enrollment->parent_phone;
    $nomResponsable = trim($enrollment->parent_first_name.' '.$enrollment->parent_last_name);

    $choixDefaut = old('responsable', $aUnResponsable ? ($responsableExistant ? 'existant' : 'nouveau') : 'aucun');
@endphp

<form method="POST" action="{{ route('enrollments.store-student', $enrollment->id) }}" enctype="multipart/form-data"
      x-data="{
          matricule: @js(old('student_id', $matriculePropose)),
          matriculeManuel: {{ old('student_id') ? 'true' : 'false' }},
          responsable: '{{ $choixDefaut }}',
          apercuPhoto: null,

          reprendreLeMatricule() {
              this.matriculeManuel = true;
              this.$nextTick(() => this.$refs.champMatricule.focus());
          },
          regenererLeMatricule() {
              this.matriculeManuel = false;
              this.matricule = @js($matriculePropose);
          },

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

    {{-- ------------------------------------------------------------------
         Ce qui vient du dossier : rappelé, non modifiable ici
         ------------------------------------------------------------------ --}}
    <div class="carte">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Repris du dossier d’inscription</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    Ces informations deviennent celles de l’élève. Modifiez-les depuis le dossier si besoin.
                </p>
            </div>
            <a href="{{ route('enrollments.edit', $enrollment->id) }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                Corriger le dossier
            </a>
        </div>

        <div class="grid gap-x-8 gap-y-3 p-5 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-[11px] font-semibold uppercase text-gris-400">Nom complet</p>
                <p class="text-sm font-medium text-gris-900">{{ $enrollment->applicant_full_name ?: '—' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase text-gris-400">Naissance</p>
                <p class="text-sm font-medium text-gris-900">
                    {{ $naissance?->format('d/m/Y') ?? '—' }}
                    @if ($enrollment->applicant_age !== null)
                        <span class="text-gris-400">({{ $enrollment->applicant_age }} ans)</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase text-gris-400">Sexe</p>
                <p class="text-sm font-medium text-gris-900">
                    {{ $enrollment->identite_sexe === 'male' ? 'Garçon' : ($enrollment->identite_sexe ? 'Fille' : '—') }}
                </p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase text-gris-400">Classe</p>
                <p class="text-sm font-medium text-gris-900">
                    {{ $enrollment->schoolClass->name ?? '—' }}
                    @if ($cycle)
                        <x-puce :couleur="$teintesCycle[$cycle] ?? 'slate'">{{ $libellesCycle[$cycle] ?? $cycle }}</x-puce>
                    @endif
                </p>
            </div>

            <div class="sm:col-span-2">
                <p class="text-[11px] font-semibold uppercase text-gris-400">Adresse</p>
                <p class="text-sm text-gris-700">{{ $enrollment->identite_adresse ?: '—' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase text-gris-400">Contact de l’élève</p>
                <p class="text-sm text-gris-700">
                    {{ $enrollment->identite_telephone ?: '—' }}
                    @if ($enrollment->identite_courriel)
                        <span class="block truncate text-xs text-gris-500">{{ $enrollment->identite_courriel }}</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase text-gris-400">Règlement</p>
                <p class="text-sm font-medium text-gris-900">
                    {{ $franc($enrollment->amount_paid) }}
                    <span class="text-gris-400">/ {{ $franc($enrollment->total_fees) }}</span>
                    <x-puce :couleur="$teintesPaiement[$enrollment->payment_status] ?? 'slate'">
                        {{ $libellesPaiement[$enrollment->payment_status] ?? $enrollment->payment_status }}
                    </x-puce>
                </p>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">

        {{-- ------------------------------------------------------------------
             Ce qui reste à compléter
             ------------------------------------------------------------------ --}}
        <div class="space-y-6 lg:col-span-2">

            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Dossier scolaire</h2>
                    <span class="text-xs text-gris-400">Tout est facultatif, sauf le matricule</span>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <div class="flex items-baseline justify-between gap-2">
                            <label class="etiquette" for="student_id">Matricule</label>
                            <button type="button" class="cursor-pointer text-[11px] font-semibold text-ogar-600 hover:underline"
                                    x-text="matriculeManuel ? 'Reprendre le numéro proposé' : 'Saisir manuellement'"
                                    @click="matriculeManuel ? regenererLeMatricule() : reprendreLeMatricule()"></button>
                        </div>
                        <input id="student_id" name="student_id" type="text" maxlength="50"
                               x-ref="champMatricule" x-model="matricule" :readonly="! matriculeManuel"
                               class="champ font-mono @error('student_id') border-corail-500 @enderror"
                               :class="matriculeManuel ? '' : 'bg-gris-50 text-gris-600'">
                        <p class="mt-1 text-xs text-gris-400" x-show="! matriculeManuel">
                            Prochain numéro libre de l’année. Il identifie l’élève partout dans l’application.
                        </p>
                        @error('student_id') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiquette" for="place_of_birth">Lieu de naissance</label>
                        <input id="place_of_birth" name="place_of_birth" type="text" maxlength="255"
                               value="{{ old('place_of_birth') }}" placeholder="Ex. Libreville"
                               class="champ @error('place_of_birth') border-corail-500 @enderror">
                        @error('place_of_birth') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="etiquette" for="emergency_contact">Contact d’urgence</label>
                        <input id="emergency_contact" name="emergency_contact" type="text" maxlength="255"
                               value="{{ old('emergency_contact') }}"
                               placeholder="Nom et téléphone de la personne à joindre"
                               class="champ @error('emergency_contact') border-corail-500 @enderror">
                        @error('emergency_contact') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="etiquette" for="medical_conditions">Informations médicales</label>
                        <textarea id="medical_conditions" name="medical_conditions" rows="3"
                                  placeholder="Allergies, traitement en cours, précautions particulières…"
                                  class="champ @error('medical_conditions') border-corail-500 @enderror">{{ old('medical_conditions') }}</textarea>
                        <p class="mt-1 text-xs text-gris-400">
                            Consultable par l’infirmerie et l’équipe pédagogique.
                        </p>
                        @error('medical_conditions') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2" x-data="{ aptitude: '{{ old('fitness_status', 'apte') }}' }">
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
                            <p class="mt-1 text-xs text-gris-400">Visible par l’équipe pédagogique et l’infirmerie.</p>
                            @error('unfitness_reason') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------------------------------
                 Responsable : le dossier en porte un, on ne le perd pas
                 ------------------------------------------------------------------ --}}
            <div class="carte">
                <div class="carte-entete">
                    <div>
                        <h2 class="text-sm font-semibold text-gris-900">Responsable de l’élève</h2>
                        <p class="mt-0.5 text-xs text-gris-400">
                            @if ($aUnResponsable)
                                Déclaré au dépôt du dossier. Il devient le contact principal.
                            @else
                                Aucun responsable n’a été déclaré au dépôt du dossier.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="space-y-3 p-5">
                    @if ($aUnResponsable)
                        @if ($responsableExistant)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors"
                                   :class="responsable === 'existant' ? 'border-ogar-300 bg-ogar-50/50' : 'border-gris-200 hover:bg-gris-50'">
                                <input type="radio" name="responsable" value="existant" x-model="responsable"
                                       class="mt-0.5 h-4 w-4 cursor-pointer border-gris-300 text-ogar-600 focus:ring-ogar-600">
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-gris-900">
                                        Rattacher {{ $responsableExistant->full_name }}
                                    </span>
                                    <span class="block text-xs text-gris-500">
                                        Ce parent existe déjà dans l’application
                                        @if ($responsableExistant->phone) &middot; {{ $responsableExistant->phone }} @endif
                                        &middot; {{ $responsableExistant->students()->count() }} enfant(s) rattaché(s)
                                    </span>
                                </span>
                            </label>
                            <input type="hidden" name="parent_id" value="{{ $responsableExistant->id }}">
                        @endif

                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors"
                               :class="responsable === 'nouveau' ? 'border-ogar-300 bg-ogar-50/50' : 'border-gris-200 hover:bg-gris-50'">
                            <input type="radio" name="responsable" value="nouveau" x-model="responsable"
                                   class="mt-0.5 h-4 w-4 cursor-pointer border-gris-300 text-ogar-600 focus:ring-ogar-600">
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-gris-900">
                                    Créer {{ $nomResponsable ?: 'le responsable' }}
                                </span>
                                <span class="block text-xs text-gris-500">
                                    {{ $enrollment->parent_phone ?: 'sans téléphone' }}
                                    @if ($enrollment->parent_email) &middot; {{ $enrollment->parent_email }} @endif
                                </span>
                            </span>
                        </label>
                    @endif

                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors"
                           :class="responsable === 'aucun' ? 'border-gris-300 bg-gris-50' : 'border-gris-200 hover:bg-gris-50'">
                        <input type="radio" name="responsable" value="aucun" x-model="responsable"
                               class="mt-0.5 h-4 w-4 cursor-pointer border-gris-300 text-ogar-600 focus:ring-ogar-600">
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-gris-900">Plus tard</span>
                            <span class="block text-xs text-gris-500">
                                Le rattachement se fait aussi depuis la fiche de l’élève ou le module Parents.
                            </span>
                        </span>
                    </label>

                    <div x-show="responsable !== 'aucun'" x-cloak class="sm:max-w-xs">
                        <label class="etiquette" for="lien">Lien avec l’élève</label>
                        <select id="lien" name="lien" class="champ">
                            @foreach ($libellesLien as $cle => $libelle)
                                <option value="{{ $cle }}"
                                    @selected(old('lien', $enrollment->parent_relationship ?? 'guardian') === $cle)>
                                    {{ $libelle }}
                                </option>
                            @endforeach
                        </select>
                        @error('lien') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    @error('parent_id') <p class="text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------------
             Photo et enregistrement
             ------------------------------------------------------------------ --}}
        <div class="lg:sticky lg:top-20 space-y-6">
            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Photo</h2>
                </div>
                <div class="p-5">
                    <div class="flex items-center gap-4">
                        <template x-if="apercuPhoto">
                            <img :src="apercuPhoto" alt="Aperçu"
                                 class="h-20 w-20 shrink-0 rounded-2xl object-cover ring-1 ring-gris-200">
                        </template>
                        <template x-if="! apercuPhoto">
                            <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-gris-100 text-gris-400">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                </svg>
                            </span>
                        </template>

                        <div class="min-w-0 flex-1">
                            <input id="photo" name="photo" type="file" accept="image/*"
                                   x-ref="photo" @change="previsualiser($event)"
                                   class="block w-full cursor-pointer text-xs text-gris-500
                                          file:mr-3 file:cursor-pointer file:rounded-lg file:border-0
                                          file:bg-gris-100 file:px-3 file:py-2 file:text-xs file:font-semibold
                                          file:text-gris-700 hover:file:bg-gris-200">
                            <button type="button" x-show="apercuPhoto" x-cloak @click="annulerPhoto()"
                                    class="mt-2 cursor-pointer text-[11px] font-semibold text-corail-600 hover:underline">
                                Retirer la photo
                            </button>
                            <p class="mt-1 text-[11px] text-gris-400">JPEG ou PNG, 2 Mo maximum.</p>
                        </div>
                    </div>
                    @error('photo') <p class="mt-2 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Création</h2>
                </div>
                <div class="space-y-3 p-5">
                    <p class="text-xs leading-relaxed text-gris-500">
                        L’élève sera créé avec l’identité du dossier, rattaché à
                        <span class="font-medium text-gris-800">{{ $enrollment->schoolClass->name ?? 'sa classe' }}</span>
                        pour {{ $enrollment->academicYear->name ?? 'l’année en cours' }}, et l’inscription
                        passera de « en attente » à « élève créé ».
                    </p>

                    <button type="submit" class="bouton-primaire w-full justify-center">Créer l’élève</button>

                    <a href="{{ route('enrollments.pending-students') }}"
                       class="block text-center text-xs text-gris-500 hover:text-gris-700 hover:underline">
                        Traiter ce dossier plus tard
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection
