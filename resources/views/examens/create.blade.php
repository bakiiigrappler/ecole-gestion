@extends('layouts.app')

@section('titre', 'Ouvrir une session d’examen')
@section('sous-titre', 'Ce qui ne changera plus une fois les inscriptions ouvertes')

@section('actions-entete')
    <a href="{{ route('examens.index') }}" class="bouton-secondaire">Toutes les sessions</a>
@endsection

@section('contenu')

<form method="POST" action="{{ route('examens.store') }}" class="mx-auto max-w-2xl space-y-4">
    @csrf

    <div class="carte overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">La session</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    Un examen par série : les coefficients ne sont pas les mêmes d’une série à l’autre.
                </p>
            </div>
        </div>

        <div class="grid gap-4 p-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="nom" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Intitulé <span class="text-corail-600">*</span>
                </label>
                <input type="text" name="nom" id="nom" required value="{{ old('nom') }}"
                       class="champ w-full text-sm" placeholder="Baccalauréat général, session 2027">
                @error('nom')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="type" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Examen <span class="text-corail-600">*</span>
                </label>
                <select name="type" id="type" required class="champ w-full text-sm">
                    @foreach (\App\Models\Examen::TYPES as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(old('type') === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="annee" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Année <span class="text-corail-600">*</span>
                </label>
                <input type="number" name="annee" id="annee" required min="2000" max="2100"
                       value="{{ old('annee', $examen->annee) }}" class="champ w-full text-sm tabular-nums">
            </div>

            <div>
                <label for="serie" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Série
                </label>
                <input type="text" name="serie" id="serie" value="{{ old('serie') }}"
                       class="champ w-full text-sm" placeholder="C, D, A2, G2…">
                <p class="mt-1 text-[11px] text-gris-400">Laissez vide pour le CEP et le BEPC.</p>
            </div>

            <div>
                <label for="debut_epreuves" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Début des épreuves
                </label>
                <input type="date" name="debut_epreuves" id="debut_epreuves" value="{{ old('debut_epreuves') }}"
                       class="champ w-full text-sm">
            </div>

            <div>
                <label for="fin_epreuves" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Fin des épreuves
                </label>
                <input type="date" name="fin_epreuves" id="fin_epreuves" value="{{ old('fin_epreuves') }}"
                       class="champ w-full text-sm">
                @error('fin_epreuves')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="carte overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Les seuils</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    Ce qui sépare l’admission de l’ajournement, et ce qui ouvre le second tour.
                </p>
            </div>
        </div>

        <div class="grid gap-4 p-5 md:grid-cols-2">
            <div>
                <label for="moyenne_admission" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Moyenne d’admission <span class="text-corail-600">*</span>
                </label>
                <input type="number" step="0.25" min="0" max="20" name="moyenne_admission" id="moyenne_admission"
                       required value="{{ old('moyenne_admission', 10) }}" class="champ w-full text-sm tabular-nums">
            </div>

            <div>
                <label for="moyenne_second_tour" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Seuil du second tour
                </label>
                <input type="number" step="0.25" min="0" max="20" name="moyenne_second_tour" id="moyenne_second_tour"
                       value="{{ old('moyenne_second_tour') }}" class="champ w-full text-sm tabular-nums">
                <p class="mt-1 text-[11px] text-gris-400">Laissez vide s’il n’y a pas de second tour.</p>
                @error('moyenne_second_tour')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label for="notes" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Notes internes
                </label>
                <textarea name="notes" id="notes" rows="2" class="champ w-full text-sm">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <button type="submit" class="bouton-primaire">Ouvrir la session</button>
        <a href="{{ route('examens.index') }}" class="bouton-secondaire">Annuler</a>
    </div>
</form>

@endsection
