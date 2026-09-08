{{--
    Formulaire d'un frais, partagé entre création et modification.

    `level_id` figurait dans le modèle mais dans aucun des deux formulaires :
    un frais ne pouvait être rattaché qu'à une classe, jamais à un niveau.
--}}

@php
    $frais = $fee ?? new \App\Models\Fee();
    $anneeCourante = $academicYears->firstWhere('is_current', true);
@endphp

<div class="space-y-4 lg:col-span-2">

    <div class="carte overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Identité du frais</h2>
        </div>

        <div class="grid gap-4 p-5 md:grid-cols-3">
            <div class="md:col-span-2">
                <label for="name" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Libellé <span class="text-corail-600">*</span>
                </label>
                <input type="text" name="name" id="name" required
                       value="{{ old('name', $frais->name) }}"
                       placeholder="Frais de scolarité 6ème" class="champ w-full text-sm">
                @error('name')
                    <p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="fee_type" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Type <span class="text-corail-600">*</span>
                </label>
                <select name="fee_type" id="fee_type" required class="champ w-full text-sm">
                    @foreach ($feeTypes as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(old('fee_type', $frais->fee_type) === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
                @error('fee_type')
                    <p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-3">
                <label for="description" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Description
                </label>
                <textarea name="description" id="description" rows="2"
                          class="champ w-full text-sm">{{ old('description', $frais->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="carte overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Montant et échéance</h2>
        </div>

        <div class="grid gap-4 p-5 md:grid-cols-3">
            <div>
                <label for="amount" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Montant (FCFA) <span class="text-corail-600">*</span>
                </label>
                <input type="number" name="amount" id="amount" min="0" step="1" required
                       value="{{ old('amount', $frais->amount ? (int) $frais->amount : '') }}" class="champ w-full text-sm">
                @error('amount')
                    <p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="frequency" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Périodicité <span class="text-corail-600">*</span>
                </label>
                <select name="frequency" id="frequency" required class="champ w-full text-sm">
                    @foreach ($frequencies as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(old('frequency', $frais->frequency) === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-gris-400">Un frais mensuel est compté sur 10 mois de classe.</p>
            </div>

            <div>
                <label for="due_date" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Date d’échéance
                </label>
                <input type="date" name="due_date" id="due_date"
                       value="{{ old('due_date', optional($frais->due_date)->format('Y-m-d')) }}" class="champ w-full text-sm">
            </div>
        </div>
    </div>

    <div class="carte overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Portée</h2>
        </div>

        <div class="grid gap-4 p-5 md:grid-cols-3">
            <div>
                <label for="academic_year_id" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Année scolaire <span class="text-corail-600">*</span>
                </label>
                <select name="academic_year_id" id="academic_year_id" required class="champ w-full text-sm">
                    @foreach ($academicYears as $annee)
                        <option value="{{ $annee->id }}"
                                @selected(old('academic_year_id', $frais->academic_year_id ?? optional($anneeCourante)->id) == $annee->id)>
                            {{ $annee->name }}{{ $annee->is_current ? ' (courante)' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('academic_year_id')
                    <p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="level_id" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Niveau
                </label>
                <select name="level_id" id="level_id" class="champ w-full text-sm">
                    <option value="">Tout l’établissement</option>
                    @foreach ($levels as $niveau)
                        <option value="{{ $niveau->id }}" @selected(old('level_id', $frais->level_id) == $niveau->id)>
                            {{ $niveau->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="class_id" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Classe
                </label>
                <select name="class_id" id="class_id" class="champ w-full text-sm">
                    <option value="">Aucune en particulier</option>
                    @foreach ($classes as $classe)
                        <option value="{{ $classe->id }}" @selected(old('class_id', $frais->class_id) == $classe->id)>
                            {{ $classe->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-gris-400">Une classe précise l’emporte sur le niveau.</p>
            </div>
        </div>
    </div>
</div>

{{-- ----------------------------------------------------------------
     Options et enregistrement
     ---------------------------------------------------------------- --}}
<div class="space-y-4">
    <div class="carte p-5">
        <h2 class="mb-3 text-sm font-semibold text-gris-900">Options</h2>

        <label class="flex cursor-pointer items-start gap-2">
            <input type="checkbox" name="is_mandatory" value="1" class="mt-0.5"
                   @checked(old('is_mandatory', $frais->exists ? $frais->is_mandatory : true))>
            <span class="text-sm">
                <span class="block font-medium text-gris-800">Obligatoire</span>
                <span class="block text-[11px] text-gris-400">Exigé de tous les élèves concernés.</span>
            </span>
        </label>

        <label class="mt-3 flex cursor-pointer items-start gap-2">
            <input type="checkbox" name="is_active" value="1" class="mt-0.5"
                   @checked(old('is_active', $frais->exists ? $frais->is_active : true))>
            <span class="text-sm">
                <span class="block font-medium text-gris-800">Actif</span>
                <span class="block text-[11px] text-gris-400">Un frais désactivé n’est plus facturé.</span>
            </span>
        </label>

        <div class="mt-5 space-y-2 border-t border-gris-100 pt-4">
            <button type="submit" class="bouton-primaire w-full justify-center">
                {{ $frais->exists ? 'Enregistrer les modifications' : 'Créer le frais' }}
            </button>
            <a href="{{ route('fees.index') }}" class="bouton-secondaire w-full justify-center">Annuler</a>
        </div>
    </div>
</div>
