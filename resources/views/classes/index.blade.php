@extends('layouts.app')

@section('titre', 'Classes')
@section('sous-titre', $classes->total().' classe(s) correspondant aux filtres')

@section('actions-entete')
    <a href="{{ route('classes.create') }}" class="bouton-primaire">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        <span class="hidden sm:inline">Nouvelle classe</span>
    </a>
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

    $filtresActifs = collect(request()->only(['search', 'cycle', 'level_id', 'status']))
        ->filter(fn ($v) => $v !== null && $v !== '')
        ->isNotEmpty();
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Classes ouvertes"
                       :valeur="number_format($statistiques['ouvertes'], 0, ',', ' ')"
                       :detail="$statistiques['total'].' au total'"
                       couleur="ogar"/>
        <x-statistique libelle="Élèves inscrits"
                       :valeur="number_format($statistiques['effectif'], 0, ',', ' ')"
                       detail="Année en cours"
                       couleur="emerald"/>
        <x-statistique libelle="Places offertes"
                       :valeur="number_format($statistiques['places'], 0, ',', ' ')"
                       :detail="$statistiques['places'] > 0
                            ? round($statistiques['effectif'] / $statistiques['places'] * 100).'% d’occupation'
                            : 'Capacité non renseignée'"
                       couleur="violet"/>
        <x-statistique libelle="Sans enseignant"
                       :valeur="number_format($statistiques['sans_enseignant'], 0, ',', ' ')"
                       detail="Aucune affectation"
                       :couleur="$statistiques['sans_enseignant'] > 0 ? 'rose' : 'slate'"/>
    </div>

    {{-- ----------------------------------------------------------------
         Filtres : formulaire GET, filtrage côté serveur
         ---------------------------------------------------------------- --}}
    <form method="GET" action="{{ route('classes.index') }}" data-filtre-dynamique="classes"
          class="carte mt-6 p-4">

        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="search" class="etiquette">Rechercher</label>
                <input type="search" id="search" name="search" value="{{ request('search') }}"
                       class="champ" placeholder="Nom de la classe...">
            </div>

            <div>
                <label for="cycle" class="etiquette">Cycle</label>
                <select id="cycle" name="cycle" class="champ">
                    <option value="">Tous les cycles</option>
                    @foreach ($libellesCycle as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(request('cycle') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="level_id" class="etiquette">Niveau</label>
                <select id="level_id" name="level_id" class="champ">
                    <option value="">Tous les niveaux</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected(request('level_id') == $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="etiquette">Statut</label>
                <select id="status" name="status" class="champ">
                    <option value="">Tous les statuts</option>
                    <option value="active" @selected(request('status') === 'active')>Ouvertes</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Fermées</option>
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-end gap-2">
            <button type="submit" class="bouton-primaire">Filtrer</button>

            @if ($filtresActifs)
                <a href="{{ route('classes.index') }}" class="bouton-secondaire">Réinitialiser</a>
            @endif

            <div class="ml-auto flex items-end gap-2">
                <div>
                    <label for="sort" class="etiquette">Trier par</label>
                    <select id="sort" name="sort" class="champ w-auto" onchange="this.form.requestSubmit()">
                        <option value="name" @selected(request('sort', 'name') === 'name')>Nom</option>
                        <option value="effectif" @selected(request('sort') === 'effectif')>Effectif</option>
                        <option value="capacity" @selected(request('sort') === 'capacity')>Capacité</option>
                        <option value="created_at" @selected(request('sort') === 'created_at')>Création</option>
                    </select>
                </div>

                <div>
                    <label for="direction" class="etiquette">Sens</label>
                    <select id="direction" name="direction" class="champ w-auto" onchange="this.form.requestSubmit()">
                        <option value="asc" @selected(request('direction', 'asc') === 'asc')>Croissant</option>
                        <option value="desc" @selected(request('direction') === 'desc')>Décroissant</option>
                    </select>
                </div>

                <div>
                    <label for="per_page" class="etiquette">Par page</label>
                    <select id="per_page" name="per_page" class="champ w-auto" onchange="this.form.requestSubmit()">
                        @foreach (\App\Support\ParametresPlateforme::PAGINATIONS as $taille)
                            <option value="{{ $taille }}" @selected(\App\Support\ParametresPlateforme::pagination(request('per_page')) === $taille)>{{ $taille }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

    {{-- ----------------------------------------------------------------
         Grille des classes
         ---------------------------------------------------------------- --}}
    <div class="relative mt-6" data-liste-dynamique="classes">
        <x-chargement data-voile-chargement hidden message="Chargement des classes…"/>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($classes as $class)
                @php
                    $cycle = $class->getSafeCycle();
                    $capacite = (int) $class->capacity;
                    $taux = $capacite > 0 ? (int) round($class->effectif / $capacite * 100) : null;
                    $teinteTaux = $taux === null ? 'bg-gris-300'
                        : ($taux >= 100 ? 'bg-corail-600' : ($taux >= 85 ? 'bg-soleil-500' : 'bg-emerald-600'));

                    $principal = $class->allTeachers->firstWhere('pivot.role', 'principal');
                    $intervenants = $class->allTeachers->where('pivot.role', '!=', 'principal');
                @endphp

                <article class="carte flex flex-col">
                    <div class="carte-entete">
                        <div class="min-w-0">
                            <a href="{{ route('classes.show', $class->id) }}"
                               class="block truncate text-base font-semibold text-gris-900 hover:text-ogar-700">
                                {{ $class->name }}
                            </a>
                            <div class="text-xs text-gris-400">
                                {{ $class->getSafeLevelName() }}
                                @if ($class->series) &middot; série {{ $class->series }} @endif
                            </div>
                        </div>
                        <x-puce :couleur="$teintesCycle[$cycle] ?? 'slate'" class="whitespace-nowrap">
                            {{ $libellesCycle[$cycle] ?? ucfirst($cycle) }}
                        </x-puce>
                    </div>

                    <div class="flex-1 space-y-4 p-5">
                        {{-- Effectif rapporté à la capacité : c'est le chiffre utile --}}
                        <div>
                            <div class="mb-1.5 flex items-baseline justify-between gap-3">
                                <span class="text-sm text-gris-500">Effectif</span>
                                <span class="text-sm">
                                    <span class="text-lg font-bold text-gris-900">{{ $class->effectif }}</span>
                                    <span class="text-gris-400">/ {{ $capacite ?: '—' }}</span>
                                </span>
                            </div>

                            <div class="h-2 overflow-hidden rounded-full bg-gris-100"
                                 role="progressbar" aria-valuenow="{{ $taux ?? 0 }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="h-full rounded-full {{ $teinteTaux }}" style="width: {{ min($taux ?? 0, 100) }}%"></div>
                            </div>

                            <div class="mt-1 flex items-center justify-between text-xs">
                                <span class="{{ $taux !== null && $taux >= 100 ? 'font-semibold text-corail-700' : 'text-gris-400' }}">
                                    @if ($taux === null)
                                        Capacité non renseignée
                                    @elseif ($taux >= 100)
                                        Classe pleine
                                    @else
                                        {{ 100 - $taux }}% de places libres
                                    @endif
                                </span>
                                @if (! $class->is_active)
                                    <x-puce couleur="rose">Fermée</x-puce>
                                @endif
                            </div>
                        </div>

                        <div class="border-t border-gris-100 pt-3">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gris-500">
                                Équipe pédagogique
                            </div>

                            @if ($class->allTeachers->isNotEmpty())
                                <ul class="space-y-1.5 text-sm">
                                    @if ($principal)
                                        <li class="flex items-center gap-2">
                                            <a href="{{ route('teachers.show', $principal->id) }}"
                                               class="truncate text-gris-800 hover:text-ogar-700">{{ $principal->full_name }}</a>
                                            <x-puce couleur="ogar" class="whitespace-nowrap">Principal</x-puce>
                                        </li>
                                    @endif
                                    @foreach ($intervenants->take(3) as $enseignant)
                                        <li class="truncate text-gris-600">
                                            <a href="{{ route('teachers.show', $enseignant->id) }}" class="hover:text-ogar-700">
                                                {{ $enseignant->full_name }}
                                            </a>
                                        </li>
                                    @endforeach
                                    @if ($intervenants->count() > 3)
                                        <li class="text-xs text-gris-400">+{{ $intervenants->count() - 3 }} autre(s)</li>
                                    @endif
                                </ul>
                            @else
                                <p class="text-sm text-corail-700">Aucun enseignant affecté.</p>
                            @endif
                        </div>

                        @if ($class->description)
                            <p class="border-t border-gris-100 pt-3 text-sm leading-relaxed text-gris-500">
                                {{ Str::limit($class->description, 110) }}
                            </p>
                        @endif
                    </div>

                    {{-- Deux raccourcis vers les taches quotidiennes ; le reste
                         dans un menu, pour ne pas mettre la suppression au meme
                         rang qu'une consultation. --}}
                    <div class="flex items-center gap-2 border-t border-gris-200 px-5 py-3">
                        <a href="{{ route('classes.show', $class->id) }}?onglet=eleves" class="bouton-mini">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                            </svg>
                            {{ $class->effectif }} élève{{ $class->effectif > 1 ? 's' : '' }}
                        </a>

                        <a href="{{ route('classes.show', $class->id) }}?onglet=equipe"
                           class="bouton-mini {{ $class->allTeachers->isEmpty() ? 'border-corail-300 text-corail-700 hover:bg-corail-50' : '' }}">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342"/>
                            </svg>
                            @if ($class->allTeachers->isEmpty())
                                Sans enseignant
                            @else
                                {{ $class->allTeachers->count() }} enseignant{{ $class->allTeachers->count() > 1 ? 's' : '' }}
                            @endif
                        </a>

                        <div x-data="{ ouvert: false }" class="relative ml-auto">
                            <button type="button" @click="ouvert = ! ouvert" class="bouton-mini" aria-label="Autres actions">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" d="M6.75 12h.008v.008H6.75V12zm5.25 0h.008v.008H12V12zm5.25 0h.008v.008h-.008V12z"/>
                                </svg>
                            </button>

                            <div x-show="ouvert" x-cloak @click.outside="ouvert = false"
                                 class="absolute bottom-full right-0 z-30 mb-1 w-52 overflow-hidden rounded-xl border border-gris-200 bg-white py-1 shadow-lg">
                                <a href="{{ route('classes.show', $class->id) }}"
                                   class="block cursor-pointer px-4 py-2 text-sm text-gris-700 hover:bg-gris-50">Ouvrir la fiche</a>
                                <a href="{{ route('classes.edit', $class->id) }}"
                                   class="block cursor-pointer px-4 py-2 text-sm text-gris-700 hover:bg-gris-50">Modifier la classe</a>
                                <a href="{{ route('classes.show', $class->id) }}?onglet=emploi"
                                   class="block cursor-pointer px-4 py-2 text-sm text-gris-700 hover:bg-gris-50">Emploi du temps</a>
                                <a href="{{ route('classes.fiche', $class->id) }}"
                                   class="block cursor-pointer px-4 py-2 text-sm text-gris-700 hover:bg-gris-50">Fiche de classe</a>

                                <div class="my-1 border-t border-gris-100"></div>

                                <x-confirmation :action="route('classes.destroy', $class->id)" methode="DELETE"
                                                titre="Supprimer cette classe ?"
                                                :message="'La classe '.$class->name.' compte '.$class->effectif.' élève(s) inscrit(s). La suppression est définitive.'"
                                                confirmer="Supprimer"
                                                bouton="block w-full px-4 py-2 text-left text-sm text-corail-600 hover:bg-corail-50">
                                    Supprimer la classe
                                </x-confirmation>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="carte col-span-full p-10 text-center">
                    <x-mascotte pose="vide" taille="h-24" class="mb-4"/>
                    <h2 class="text-base font-semibold text-gris-700">
                        {{ $filtresActifs ? 'Aucune classe ne correspond aux filtres' : 'Aucune classe enregistrée' }}
                    </h2>
                    <p class="mt-1 text-sm text-gris-500">
                        {{ $filtresActifs
                            ? 'Élargissez la recherche ou réinitialisez les filtres.'
                            : 'Créez une première classe pour commencer à affecter des élèves.' }}
                    </p>
                    @unless ($filtresActifs)
                        <a href="{{ route('classes.create') }}" class="bouton-primaire mt-4">Créer une classe</a>
                    @endunless
                </div>
            @endforelse
        </div>

        @if ($classes->hasPages())
            <div class="carte mt-4 px-4 py-3" data-pagination>
                {{ $classes->links() }}
            </div>
        @endif
    </div>

@endsection
