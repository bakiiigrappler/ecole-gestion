@extends('layouts.app')

@section('titre', 'Séries')
@section('sous-titre', $statistiques['total'].' série(s) — '.$statistiques['actives'].' active(s)')

@section('actions-entete')
    <a href="{{ route('series.create') }}" class="bouton-primaire">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        <span class="hidden sm:inline">Nouvelle série</span>
    </a>
@endsection

@section('contenu')

@php
    $teintesNiveau = ['2nde' => 'sky', '1ère' => 'amber', 'Terminale' => 'violet'];

    // Lien de tri : il conserve les filtres et bascule le sens sur la colonne courante.
    $lienTri = fn ($colonne) => request()->fullUrlWithQuery([
        'tri' => $colonne,
        'sens' => ($tri === $colonne && $sens === 'asc') ? 'desc' : 'asc',
    ]);
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Séries"
                       :valeur="$statistiques['total']"
                       :detail="$statistiques['actives'].' active(s)'"
                       couleur="ogar"/>
        <x-statistique libelle="Seconde"
                       :valeur="$statistiques['par_niveau']['2nde'] ?? 0"
                       detail="Tronc commun et lettres"
                       couleur="emerald"/>
        <x-statistique libelle="Première"
                       :valeur="$statistiques['par_niveau']['1ère'] ?? 0"
                       detail="Avant spécialisation"
                       couleur="amber"/>
        <x-statistique libelle="Terminale"
                       :valeur="$statistiques['par_niveau']['Terminale'] ?? 0"
                       detail="Générales et techniques"
                       couleur="violet"/>
    </div>

    @if ($statistiques['sans_classe'] > 0)
        <div class="mt-6 flex items-start gap-3 rounded-xl border border-soleil-200 bg-soleil-50 px-4 py-3 text-sm text-soleil-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
            </svg>
            <span>
                <strong>{{ $statistiques['sans_classe'] }} série(s) ne sont rattachées à aucune classe.</strong>
                Elles restent proposées à la création d’une classe de lycée, mais ne servent nulle part pour l’instant.
            </span>
        </div>
    @endif

    {{-- ----------------------------------------------------------------
         Filtres — formulaire GET, filtrage côté serveur
         ---------------------------------------------------------------- --}}
    <form method="GET" action="{{ route('series.index') }}" class="carte mt-6 p-4">
        <input type="hidden" name="tri" value="{{ $tri }}">
        <input type="hidden" name="sens" value="{{ $sens }}">

        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label for="recherche" class="etiquette">Rechercher</label>
                <input type="search" name="recherche" id="recherche" value="{{ request('recherche') }}"
                       class="champ" placeholder="Code, nom, description…">
            </div>

            <div>
                <label for="niveau" class="etiquette">Niveau</label>
                <select name="niveau" id="niveau" class="champ">
                    <option value="">Tous les niveaux</option>
                    @foreach ($niveaux as $n)
                        <option value="{{ $n->id }}" @selected((string) request('niveau') === (string) $n->id)>{{ $n->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="statut" class="etiquette">Statut</label>
                <select name="statut" id="statut" class="champ">
                    <option value="">Tous les statuts</option>
                    <option value="actif" @selected(request('statut') === 'actif')>Active</option>
                    <option value="inactif" @selected(request('statut') === 'inactif')>Inactive</option>
                </select>
            </div>
        </div>

        <div class="mt-4 flex gap-2">
            <button type="submit" class="bouton-primaire">Filtrer</button>
            <a href="{{ route('series.index') }}" class="bouton-secondaire">Réinitialiser</a>
        </div>
    </form>

    {{-- ----------------------------------------------------------------
         Liste
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Référentiel des séries</h2>
            <span class="text-xs text-gris-400">{{ $series->total() }} résultat(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>
                            <a href="{{ $lienTri('code') }}" class="inline-flex items-center gap-1 hover:text-ogar-700">
                                Code
                                @if ($tri === 'code')<span class="text-ogar-600">{{ $sens === 'asc' ? '↑' : '↓' }}</span>@endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ $lienTri('name') }}" class="inline-flex items-center gap-1 hover:text-ogar-700">
                                Intitulé
                                @if ($tri === 'name')<span class="text-ogar-600">{{ $sens === 'asc' ? '↑' : '↓' }}</span>@endif
                            </a>
                        </th>
                        <th>Niveau</th>
                        <th class="text-center">
                            <a href="{{ $lienTri('order') }}" class="inline-flex items-center gap-1 hover:text-ogar-700">
                                Rang
                                @if ($tri === 'order')<span class="text-ogar-600">{{ $sens === 'asc' ? '↑' : '↓' }}</span>@endif
                            </a>
                        </th>
                        <th class="text-center">Classes</th>
                        <th class="text-center">Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($series as $serie)
                        <tr>
                            <td>
                                <a href="{{ route('series.show', $serie->id) }}"
                                   class="font-mono font-semibold text-gris-800 hover:text-ogar-700 hover:underline">
                                    {{ $serie->code }}
                                </a>
                            </td>
                            <td>
                                <div class="font-medium text-gris-800">{{ $serie->name }}</div>
                                @if ($serie->description)
                                    <div class="truncate text-[11px] text-gris-400">{{ $serie->description }}</div>
                                @endif
                            </td>
                            <td>
                                <x-puce :couleur="$teintesNiveau[$serie->level] ?? 'slate'">{{ $serie->level }}</x-puce>
                            </td>
                            <td class="text-center text-gris-600">{{ $serie->order }}</td>
                            <td class="text-center {{ $serie->classes_count > 0 ? 'font-medium text-gris-700' : 'text-gris-300' }}">
                                {{ $serie->classes_count }}
                            </td>
                            <td class="text-center">
                                <x-puce :couleur="$serie->is_active ? 'emerald' : 'slate'">
                                    {{ $serie->is_active ? 'Active' : 'Inactive' }}
                                </x-puce>
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('series.show', $serie->id) }}" class="bouton-mini">Consulter</a>
                                    <a href="{{ route('series.edit', $serie->id) }}" class="bouton-mini">Modifier</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="7" message="Aucune série ne correspond à ces critères."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($series->hasPages())
            <div class="border-t border-gris-100 px-5 py-4">
                {{ $series->links() }}
            </div>
        @endif
    </div>

@endsection
