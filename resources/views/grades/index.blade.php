@extends('layouts.app')

@section('titre', 'Notes du secondaire')
@section('sous-titre', $statistiques['notes'].' note(s) — '.$statistiques['eleves'].' élève(s) sur '.$statistiques['matieres'].' matière(s)')

@section('actions-entete')
    <a href="{{ route('bulletins.index') }}" class="bouton-secondaire">Bulletins</a>
    <a href="{{ route('grades.create', array_filter(['class_id' => request('class_id')])) }}" class="bouton-primaire">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        <span class="hidden sm:inline">Saisir des notes</span>
    </a>
@endsection

@section('contenu')

@php
    $libellesCycle = ['college' => 'Collège', 'lycee' => 'Lycée'];
    $teintesCycle = ['college' => 'sky', 'lycee' => 'violet'];

    // Une moyenne se lit d'un coup d'œil : vert au-dessus de 12, ambre au-dessus
    // de 10, corail en dessous.
    $teinteMoyenne = fn ($m) => $m === null ? 'slate' : ($m >= 12 ? 'emerald' : ($m >= 10 ? 'amber' : 'rose'));
    $classeMoyenne = fn ($m) => $m === null ? 'text-gris-300'
        : ($m >= 12 ? 'text-emerald-700' : ($m >= 10 ? 'text-soleil-700' : 'text-corail-700'));

    $note = fn ($m) => $m === null ? '—' : number_format((float) $m, 2, ',', ' ');

    $trimestres = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Notes saisies"
                       :valeur="number_format($statistiques['notes'], 0, ',', ' ')"
                       :detail="$statistiques['eleves'].' élève(s) évalué(s)'"
                       couleur="ogar"/>
        <x-statistique libelle="Moyenne générale"
                       :valeur="$statistiques['moyenne'] !== null ? $note($statistiques['moyenne']).'/20' : '—'"
                       detail="Toutes matières, ramenées sur 20"
                       :couleur="$teinteMoyenne($statistiques['moyenne'])"/>
        <x-statistique libelle="Notes suffisantes"
                       :valeur="$statistiques['taux_reussite'] !== null ? $statistiques['taux_reussite'].'%' : '—'"
                       detail="Au moins 10 sur 20"
                       couleur="emerald"/>
        <x-statistique libelle="Matières évaluées"
                       :valeur="$statistiques['matieres']"
                       detail="Collège et lycée"
                       couleur="violet"/>
    </div>

    {{-- Moyenne par trimestre : trois raccourcis de filtre --}}
    @if ($statistiques['par_trimestre']->isNotEmpty())
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            @foreach ($statistiques['par_trimestre'] as $ligne)
                <a href="{{ route('grades.index', ['term' => $ligne->term]) }}"
                   class="carte flex items-center justify-between px-4 py-3 transition-colors hover:bg-gris-50 {{ request('term') === $ligne->term ? 'ring-2 ring-ogar-300' : '' }}">
                    <span>
                        <span class="block text-sm font-medium text-gris-700">{{ $ligne->term }}</span>
                        <span class="block text-[11px] text-gris-400">{{ $ligne->n }} note(s)</span>
                    </span>
                    <span class="text-lg font-bold {{ $classeMoyenne($ligne->moyenne) }}">
                        {{ $note($ligne->moyenne) }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif


    {{-- ----------------------------------------------------------------
         Un onglet par classe : c'est ainsi qu'un enseignant lit ses notes.
         ---------------------------------------------------------------- --}}
    @if ($estEnseignant && $mesClasses->isNotEmpty())
        <div class="carte mt-6 overflow-hidden">
            <div class="flex flex-wrap gap-1 border-b border-gris-100 bg-gris-50 px-3 pt-3">
                @foreach ($mesClasses as $classe)
                    <a href="{{ route('grades.index', array_filter([
                            'class_id' => $classe->id,
                            'term' => request('term'),
                            'subject_id' => request('subject_id'),
                       ])) }}"
                       class="rounded-t-lg border border-b-0 px-4 py-2 text-sm font-medium transition
                              {{ (int) request('class_id') === (int) $classe->id
                                 ? 'border-gris-200 bg-white text-ogar-700'
                                 : 'border-transparent text-gris-500 hover:text-gris-800' }}">
                        {{ $classe->name }}
                    </a>
                @endforeach
            </div>
            <p class="px-5 py-3 text-xs text-gris-400">
                Vous ne voyez que les élèves de vos classes, sur les matières que vous y enseignez.
            </p>
        </div>
    @endif

    {{-- ----------------------------------------------------------------
         Filtres — formulaire GET, filtrage côté serveur
         ---------------------------------------------------------------- --}}
    <form method="GET" action="{{ route('grades.index') }}" class="carte mt-6 p-4">
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label for="recherche" class="etiquette">Rechercher</label>
                <input type="search" name="recherche" id="recherche" value="{{ request('recherche') }}"
                       class="champ" placeholder="Nom ou matricule de l’élève…">
            </div>

            <div>
                <label for="class_id" class="etiquette">Classe</label>
                <select name="class_id" id="class_id" class="champ">
                    <option value="">Toutes les classes</option>
                    @foreach ($classes as $classe)
                        <option value="{{ $classe->id }}" @selected((string) request('class_id') === (string) $classe->id)>
                            {{ $classe->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="subject_id" class="etiquette">Matière</label>
                <select name="subject_id" id="subject_id" class="champ">
                    <option value="">Toutes les matières</option>
                    @foreach ($subjects as $matiere)
                        <option value="{{ $matiere->id }}" @selected((string) request('subject_id') === (string) $matiere->id)>
                            {{ $matiere->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="term" class="etiquette">Trimestre</label>
                <select name="term" id="term" class="champ">
                    <option value="">Tous les trimestres</option>
                    @foreach ($trimestres as $t)
                        <option value="{{ $t }}" @selected(request('term') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="per_page" class="etiquette">Par page</label>
                <select name="per_page" id="per_page" class="champ">
                    @foreach (\App\Support\ParametresPlateforme::PAGINATIONS as $n)
                        <option value="{{ $n }}" @selected(\App\Support\ParametresPlateforme::pagination(request('per_page')) === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="submit" class="bouton-primaire">Filtrer</button>
            <a href="{{ route('grades.index') }}" class="bouton-secondaire">Réinitialiser</a>

            <span class="ml-auto text-xs text-gris-400">
                Les moyennes sont ramenées sur 20, quelle que soit la note maximale de l’épreuve.
            </span>
        </div>
    </form>

    {{-- ----------------------------------------------------------------
         Relevé par élève
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Relevé par élève</h2>
            <span class="text-xs text-gris-400">{{ $releves->total() }} relevé(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Classe</th>
                        <th class="text-center">Notes</th>
                        <th class="text-center">Matières</th>
                        <th class="text-center">1<sup>er</sup> trim.</th>
                        <th class="text-center">2<sup>e</sup> trim.</th>
                        <th class="text-center">3<sup>e</sup> trim.</th>
                        <th class="text-center">Moyenne</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($releves as $releve)
                        @php($eleve = $eleves[$releve->student_id] ?? null)
                        @php($classe = $classesDesReleves[$releve->class_id] ?? null)
                        <tr>
                            <td>
                                @if ($eleve)
                                    <a href="{{ route('students.show', $eleve->id) }}"
                                       class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                        {{ $eleve->full_name }}
                                    </a>
                                    <div class="font-mono text-[11px] text-gris-400">{{ $eleve->student_id }}</div>
                                @else
                                    <span class="text-xs italic text-gris-400">élève supprimé</span>
                                @endif
                            </td>
                            <td>
                                @if ($classe)
                                    <a href="{{ route('classes.show', $classe->id) }}"
                                       class="text-gris-700 hover:text-ogar-700 hover:underline">{{ $classe->name }}</a>
                                    <div class="text-[11px]">
                                        <x-puce :couleur="$teintesCycle[$classe->getSafeCycle()] ?? 'slate'">
                                            {{ $libellesCycle[$classe->getSafeCycle()] ?? '—' }}
                                        </x-puce>
                                    </div>
                                @else
                                    <span class="text-xs text-gris-400">—</span>
                                @endif
                            </td>
                            <td class="text-center text-gris-600">{{ $releve->total_notes }}</td>
                            <td class="text-center text-gris-600">{{ $releve->matieres }}</td>

                            @foreach (['t1', 't2', 't3'] as $trimestre)
                                <td class="text-center font-medium {{ $classeMoyenne($releve->$trimestre) }}">
                                    {{ $note($releve->$trimestre) }}
                                </td>
                            @endforeach

                            <td class="text-center">
                                <x-puce :couleur="$teinteMoyenne($releve->moyenne)">
                                    {{ $note($releve->moyenne) }}/20
                                </x-puce>
                            </td>
                            <td class="text-right">
                                @if ($eleve)
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('grades.manage-student', $eleve->id) }}" class="bouton-mini">Notes</a>
                                        <a href="{{ route('grades.bulletin', $eleve->id) }}" class="bouton-mini">Bulletin</a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="9" message="Aucune note ne correspond à ces critères."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($releves->hasPages())
            <div class="border-t border-gris-100 px-5 py-4">
                {{ $releves->links() }}
            </div>
        @endif
    </div>

@endsection
