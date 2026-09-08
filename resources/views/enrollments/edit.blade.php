@extends('layouts.app')

@section('titre', 'Modifier l’inscription')
@section('sous-titre', ($enrollment->student->full_name ?? $enrollment->applicant_full_name ?: '#'.$enrollment->id).' — '.($enrollment->academicYear->name ?? 'année inconnue'))

@section('actions-entete')
    <a href="{{ route('enrollments.show', $enrollment->id) }}" class="bouton-secondaire">Voir le dossier</a>
@endsection

@section('contenu')

@php
    $libellesCycle = ['preprimaire' => 'Préprimaire', 'primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    $libellesPaiement = ['completed' => 'Soldé', 'partial' => 'Partiel', 'pending' => 'Impayé', 'overdue' => 'En retard'];
    $teintesPaiement = ['completed' => 'emerald', 'partial' => 'amber', 'pending' => 'rose', 'overdue' => 'rose'];

    $franc = fn ($m) => number_format((float) $m, 0, ',', ' ').' F';

    $eleve = $enrollment->student;

    // Chaque classe transporte son cycle, sa capacité et son effectif : le choix
    // se fait en voyant la place restante, pas en découvrant la surcapacité après.
    $classesJs = $classes->map(fn ($c) => [
        'id' => (string) $c->id,
        'nom' => $c->name,
        'cycle' => $c->getSafeCycle(),
        'niveau' => $c->getSafeLevelName(),
        'capacite' => (int) $c->capacity,
        'effectif' => (int) ($effectifs[$c->id] ?? 0),
    ])->values();
@endphp

<form method="POST" action="{{ route('enrollments.update', $enrollment->id) }}"
      x-data="{
          classes: {{ Js::from($classesJs) }},
          classeId: '{{ old('class_id', $enrollment->class_id) }}',
          cycleFiltre: '',
          classeOrigine: '{{ $enrollment->class_id }}',

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
              {{-- L'élève occupe déjà une place dans sa classe actuelle. --}}
              const dejaCompte = c.id === this.classeOrigine ? 1 : 0;
              return c.capacite - c.effectif + dejaCompte;
          },
          get changementDeClasse() {
              return this.classeId !== this.classeOrigine;
          },
      }"
      class="space-y-6">
    @csrf
    @method('PUT')

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- ------------------------------------------------------------------
             Affectation
             ------------------------------------------------------------------ --}}
        <div class="carte lg:col-span-2">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Affectation</h2>
                <span class="text-xs text-gris-400"><span class="text-corail-600">*</span> champs obligatoires</span>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div>
                    <label class="etiquette" for="academic_year_id">Année scolaire <span class="text-corail-600">*</span></label>
                    <select id="academic_year_id" name="academic_year_id" required
                            class="champ @error('academic_year_id') border-corail-500 @enderror">
                        @foreach ($academicYears as $annee)
                            <option value="{{ $annee->id }}"
                                @selected((string) old('academic_year_id', $enrollment->academic_year_id) === (string) $annee->id)>
                                {{ $annee->name }}{{ $annee->is_current ? ' (courante)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('academic_year_id') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="enrollment_date">Date d’inscription <span class="text-corail-600">*</span></label>
                    <input id="enrollment_date" name="enrollment_date" type="date" required
                           value="{{ old('enrollment_date', $enrollment->enrollment_date ? \Carbon\Carbon::parse($enrollment->enrollment_date)->format('Y-m-d') : '') }}"
                           class="champ @error('enrollment_date') border-corail-500 @enderror">
                    @error('enrollment_date') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="cycle-filtre">Filtrer les classes par cycle</label>
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
                    <select id="class_id" name="class_id" required x-model="classeId"
                            class="champ @error('class_id') border-corail-500 @enderror">
                        <option value="">Sélectionner une classe</option>
                        <template x-for="classe in classesProposees" :key="classe.id">
                            <option :value="classe.id"
                                    x-text="classe.nom + ' — ' + classe.niveau + ' (' + classe.effectif + (classe.capacite ? '/' + classe.capacite : '') + ')'">
                            </option>
                        </template>
                    </select>

                    <template x-if="placesRestantes !== null">
                        <p class="mt-1 text-xs" :class="placesRestantes <= 0 ? 'text-corail-700' : 'text-gris-400'">
                            <span x-show="placesRestantes > 0">
                                <span x-text="placesRestantes"></span> place(s) disponible(s) dans cette classe.
                            </span>
                            <span x-show="placesRestantes <= 0" x-cloak>
                                Cette classe est déjà à pleine capacité.
                            </span>
                        </p>
                    </template>

                    @error('class_id') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div x-show="changementDeClasse" x-cloak class="sm:col-span-2">
                    <div class="flex items-start gap-3 rounded-xl border border-soleil-200 bg-soleil-50 px-4 py-3 text-sm text-soleil-800">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                        </svg>
                        <span>
                            Changement de classe : les présences et les notes déjà saisies restent
                            rattachées à <strong>{{ $enrollment->schoolClass->name ?? 'l’ancienne classe' }}</strong>.
                        </span>
                    </div>
                </div>

                <div>
                    <label class="etiquette" for="status">Statut du dossier <span class="text-corail-600">*</span></label>
                    <select id="status" name="status" required class="champ @error('status') border-corail-500 @enderror">
                        @foreach (['active' => 'Active', 'completed' => 'Terminée', 'transferred' => 'Transférée', 'dropped' => 'Abandonnée'] as $cle => $libelle)
                            <option value="{{ $cle }}" @selected(old('status', $enrollment->status) === $cle)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gris-400">
                        Seuls les dossiers actifs comptent dans l’effectif d’une classe.
                    </p>
                    @error('status') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiquette" for="student_status">Situation de l’élève</label>
                    <select id="student_status" name="student_status" class="champ @error('student_status') border-corail-500 @enderror">
                        @foreach (['nouveau' => 'Nouveau', 'redoublant' => 'Redoublant', 'passant' => 'Passant'] as $cle => $libelle)
                            <option value="{{ $cle }}" @selected(old('student_status', $enrollment->student_status) === $cle)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                    @error('student_status') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="etiquette" for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3"
                              placeholder="Remarques sur le dossier…"
                              class="champ @error('notes') border-corail-500 @enderror">{{ old('notes', $enrollment->notes) }}</textarea>
                    @error('notes') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------------
             Élève et règlement (colonne latérale)
             ------------------------------------------------------------------ --}}
        <div class="space-y-6">
            <div class="carte">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Élève</h2>
                </div>
                <div class="p-5">
                    @if ($eleve)
                        <div class="flex items-center gap-3">
                            <x-avatar :nom="$eleve->full_name" :photo="$eleve->photo ?? null" class="h-12 w-12 shrink-0"/>
                            <div class="min-w-0">
                                <a href="{{ route('students.show', $eleve->id) }}"
                                   class="block truncate font-semibold text-gris-900 hover:text-ogar-700 hover:underline">
                                    {{ $eleve->full_name }}
                                </a>
                                <div class="font-mono text-xs text-gris-400">{{ $eleve->student_id }}</div>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-gris-400">
                            L’élève d’une inscription ne se change pas ici : créez plutôt une
                            inscription au bon dossier.
                        </p>
                    @else
                        <p class="text-sm font-medium text-gris-900">
                            {{ $enrollment->applicant_full_name ?: 'Candidat sans nom' }}
                        </p>
                        <p class="mt-1 text-xs text-soleil-700">
                            Aucun élève n’a encore été créé à partir de ce dossier.
                        </p>
                        <a href="{{ route('enrollments.create-student', $enrollment->id) }}"
                           class="bouton-primaire mt-3 w-full justify-center text-xs">Créer l’élève</a>
                    @endif
                </div>
            </div>

            <div class="carte">
                <div class="carte-entete">
                    <div>
                        <h2 class="text-sm font-semibold text-gris-900">Règlement</h2>
                        <p class="mt-0.5 text-xs text-gris-400">Calculé à partir des paiements</p>
                    </div>
                    <x-puce :couleur="$teintesPaiement[$enrollment->payment_status] ?? 'slate'">
                        {{ $libellesPaiement[$enrollment->payment_status] ?? $enrollment->payment_status }}
                    </x-puce>
                </div>
                <div class="space-y-4 p-5">
                    <dl class="grid grid-cols-2 gap-y-2.5 text-sm">
                        <dt class="text-gris-400">Facturé</dt>
                        <dd class="text-right font-medium text-gris-900">{{ $franc($enrollment->total_fees) }}</dd>

                        <dt class="text-gris-400">Encaissé</dt>
                        <dd class="text-right font-medium text-emerald-700">{{ $franc($enrollment->amount_paid) }}</dd>

                        <dt class="text-gris-400">Reste dû</dt>
                        <dd class="text-right font-semibold {{ $enrollment->balance_due > 0 ? 'text-corail-700' : 'text-gris-400' }}">
                            {{ $franc($enrollment->balance_due) }}
                        </dd>

                        <dt class="text-gris-400">Paiements</dt>
                        <dd class="text-right font-medium text-gris-800">{{ $enrollment->payments->count() }}</dd>
                    </dl>

                    <div>
                        <label class="etiquette" for="payment_due_date">Échéance de paiement</label>
                        <input id="payment_due_date" name="payment_due_date" type="date"
                               value="{{ old('payment_due_date', $enrollment->payment_due_date ? \Carbon\Carbon::parse($enrollment->payment_due_date)->format('Y-m-d') : '') }}"
                               class="champ @error('payment_due_date') border-corail-500 @enderror">
                        @error('payment_due_date') <p class="mt-1 text-xs text-corail-700">{{ $message }}</p> @enderror
                    </div>

                    <p class="rounded-lg bg-gris-50 px-3 py-2 text-xs text-gris-500">
                        Les montants ne se saisissent pas ici : ils découlent des paiements
                        enregistrés. Passez par le module Paiements pour les corriger.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         Barre d'actions
         ------------------------------------------------------------------ --}}
    <div class="carte flex flex-wrap items-center gap-3 p-4">
        <button type="submit" class="bouton-primaire">Enregistrer les modifications</button>
        <a href="{{ route('enrollments.show', $enrollment->id) }}" class="bouton-secondaire">Annuler</a>

        <a href="{{ route('enrollments.receipt', $enrollment->id) }}" class="bouton-secondaire ml-auto text-xs">Reçu</a>

        <x-confirmation :action="route('enrollments.destroy', $enrollment->id)" methode="DELETE"
                        titre="Supprimer cette inscription ?"
                        :message="$enrollment->payments->isNotEmpty()
                            ? $enrollment->payments->count().' paiement(s) sont rattachés à ce dossier : la suppression sera refusée.'
                            : 'Cette inscription sera définitivement supprimée.'"
                        confirmer="Supprimer"
                        bouton="bouton-secondaire text-xs text-corail-600 hover:bg-corail-50">
            Supprimer
        </x-confirmation>
    </div>
</form>

@endsection
