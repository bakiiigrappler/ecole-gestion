@extends('layouts.app')

@section('titre', 'Bulletins — '.$classe->name)
@section('sous-titre', ($classe->getSafeLevelName() ?? '—').' · '.$eleves->count().' élève(s) — '.($annee->name ?? 'année en cours'))

@section('actions-entete')
    @if ($classe->getSafeLevel())
        <a href="{{ route('bulletins.byLevel', $classe->getSafeLevel()->id) }}" class="bouton-secondaire">
            Classes du niveau
        </a>
    @endif
    <a href="{{ route('bulletins.index') }}" class="bouton-primaire">Tous les bulletins</a>
@endsection

@section('contenu')

@php
    $libellesCycle = ['preprimaire' => 'Préprimaire', 'primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    $teintesCycle = ['preprimaire' => 'amber', 'primaire' => 'emerald', 'college' => 'sky', 'lycee' => 'violet'];
    $cycle = $classe->getSafeCycle();

    $notes = fn ($eleve) => (int) ($chiffres[$eleve->id]->notes ?? 0);
    $moyenne = fn ($eleve) => isset($chiffres[$eleve->id]) ? (float) $chiffres[$eleve->id]->moyenne : null;

    $notesEleves = $eleves->filter(fn ($e) => $notes($e) > 0);
    $moyennes = $notesEleves->map(fn ($e) => $moyenne($e))->filter();
    $moyenneClasse = $moyennes->isNotEmpty() ? $moyennes->avg() : null;

    // Classes écrites en entier : Tailwind ne compile pas une teinte interpolée.
    $encre = fn ($m) => $m === null ? 'text-gris-400' : ($m >= 12 ? 'text-emerald-600' : ($m >= 10 ? 'text-soleil-600' : 'text-corail-600'));
    $puce = fn ($m) => $m === null ? 'slate' : ($m >= 12 ? 'emerald' : ($m >= 10 ? 'amber' : 'rose'));

    // Le préprimaire et le primaire s'évaluent par compétences, pas par notes.
    $parCompetences = in_array($cycle, ['preprimaire', 'primaire'], true);
@endphp

    {{-- ----------------------------------------------------------------
         Un onglet par classe : un enseignant passe de l'une a l'autre sans
         repasser par une liste de l'etablissement.
         ---------------------------------------------------------------- --}}
    @if (($estEnseignant ?? false) && ($mesClasses ?? collect())->count() > 1)
        <div class="mb-6 flex flex-wrap gap-1 border-b border-gris-200">
            @foreach ($mesClasses as $uneClasse)
                <a href="{{ route('bulletins.class', $uneClasse->id) }}"
                   class="rounded-t-lg border border-b-0 px-4 py-2 text-sm font-medium transition
                          {{ (int) $classe->id === (int) $uneClasse->id
                             ? 'border-gris-200 bg-white text-ogar-700'
                             : 'border-transparent text-gris-500 hover:text-gris-800' }}">
                    {{ $uneClasse->name }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Élèves inscrits" :valeur="$eleves->count()"
                       :detail="($annee->name ?? '—')" couleur="ogar"/>
        <x-statistique libelle="Élèves notés" :valeur="$notesEleves->count()"
                       :detail="$eleves->isNotEmpty() ? round($notesEleves->count() / $eleves->count() * 100).'% de la classe' : '—'"
                       :couleur="$notesEleves->isNotEmpty() ? 'emerald' : 'rose'"/>
        <x-statistique libelle="Moyenne de la classe"
                       :valeur="$moyenneClasse !== null ? number_format($moyenneClasse, 2, ',', ' ') : '—'"
                       detail="Sur 20"
                       couleur="violet"/>
        <x-statistique libelle="Niveau" :valeur="$classe->getSafeLevelName() ?? '—'"
                       :detail="$libellesCycle[$cycle] ?? $cycle" couleur="amber"/>
    </div>

    @if ($parCompetences)
        <div class="mt-4 flex items-start gap-3 rounded-xl border border-ogar-200 bg-ogar-50 px-4 py-3 text-sm text-ogar-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
            </svg>
            <span>
                Ce cycle s’évalue <strong>par compétences</strong> et non par notes chiffrées.
                Les bulletins correspondants se préparent depuis
                <a href="{{ $cycle === 'preprimaire' ? route('pre-primary-evaluations.index') : route('competency-evaluations.index') }}"
                   class="font-semibold underline">
                    {{ $cycle === 'preprimaire' ? 'Notes préprimaire' : 'Compétences (primaire)' }}</a>.
            </span>
        </div>
    @endif

    {{-- ----------------------------------------------------------------
         Les élèves de la classe et leur bulletin
         ---------------------------------------------------------------- --}}
    <div class="carte mt-4 overflow-hidden" x-data="{ recherche: '' }">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Élèves de la classe</h2>
                <p class="mt-0.5 text-xs text-gris-400">Ouvrez un bulletin pour le consulter ou l’exporter.</p>
            </div>
            <input x-model="recherche" type="search" placeholder="Rechercher un élève…"
                   class="champ w-56 text-xs">
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Matricule</th>
                        <th class="text-center">Notes saisies</th>
                        <th class="text-center">Moyenne</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($eleves as $eleve)
                        @php($m = $moyenne($eleve))
                        <tr x-show="recherche === '' || '{{ Str::lower($eleve->full_name.' '.$eleve->student_id) }}'.includes(recherche.toLowerCase())">
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-avatar :nom="$eleve->full_name" :photo="$eleve->photo ?? null" class="h-9 w-9 shrink-0 text-[11px]"/>
                                    <a href="{{ route('students.show', $eleve->id) }}"
                                       class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                        {{ $eleve->full_name }}
                                    </a>
                                </div>
                            </td>
                            <td class="font-mono text-[11px] text-gris-500">{{ $eleve->student_id }}</td>
                            <td class="text-center {{ $notes($eleve) > 0 ? 'text-gris-700' : 'text-gris-400' }}">
                                {{ $notes($eleve) }}
                            </td>
                            <td class="text-center">
                                @if ($m === null)
                                    <span class="text-xs text-gris-400">—</span>
                                @else
                                    <x-puce :couleur="$puce($m)">{{ number_format($m, 2, ',', ' ') }}</x-puce>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('grades.bulletin', $eleve->id) }}"
                                       class="bouton-mini {{ $notes($eleve) === 0 ? 'cursor-not-allowed opacity-50' : 'border-ogar-600 bg-ogar-600 text-white' }}">
                                        Bulletin
                                    </a>
                                    <a href="{{ route('bulletins.student', $eleve->id) }}" class="bouton-mini">Notes</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="5" message="Aucun élève inscrit dans cette classe sur l’année en cours."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
