@extends('layouts.app')

@section('titre', 'Matières')
@section('sous-titre', $statistiques['total'].' matière(s) — '.$statistiques['actives'].' active(s)')

@section('actions-entete')
    <a href="{{ route('subjects.create') }}" class="bouton-primaire">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        <span class="hidden sm:inline">Nouvelle matière</span>
    </a>
@endsection

@section('contenu')

@php
    $libellesCycle = ['primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    $teintesCycle = ['primaire' => 'emerald', 'college' => 'sky', 'lycee' => 'violet'];
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Matières"
                       :valeur="number_format($statistiques['total'], 0, ',', ' ')"
                       :detail="$statistiques['actives'].' active(s)'"
                       couleur="ogar"/>
        <x-statistique libelle="Primaire"
                       :valeur="$statistiques['par_cycle']['primaire'] ?? 0"
                       detail="Enseignement polyvalent"
                       couleur="emerald"/>
        <x-statistique libelle="Collège"
                       :valeur="$statistiques['par_cycle']['college'] ?? 0"
                       detail="Tronc commun"
                       couleur="amber"/>
        <x-statistique libelle="Sans enseignant"
                       :valeur="$statistiques['sans_enseignant']"
                       detail="Personne ne peut les assurer"
                       :couleur="$statistiques['sans_enseignant'] > 0 ? 'rose' : 'violet'"/>
    </div>

    {{-- ----------------------------------------------------------------
         Filtres — formulaire GET, filtrage côté serveur
         ---------------------------------------------------------------- --}}
    <form method="GET" action="{{ route('subjects.index') }}" class="carte mt-6 p-4">
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label for="recherche" class="etiquette">Rechercher</label>
                <input type="search" name="recherche" id="recherche" value="{{ request('recherche') }}"
                       class="champ" placeholder="Nom, code, description…">
            </div>

            <div>
                <label for="cycle" class="etiquette">Cycle</label>
                <select name="cycle" id="cycle" class="champ">
                    <option value="">Tous les cycles</option>
                    @foreach ($libellesCycle as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(request('cycle') === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="serie" class="etiquette">Série</label>
                <select name="serie" id="serie" class="champ">
                    <option value="">Toutes les séries</option>
                    @foreach ($series as $lettre)
                        <option value="{{ $lettre }}" @selected(request('serie') === $lettre)>Série {{ $lettre }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-gris-400">Ne concerne que le lycée.</p>
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

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button type="submit" class="bouton-primaire">Filtrer</button>
            <a href="{{ route('subjects.index') }}" class="bouton-secondaire">Réinitialiser</a>

            <label class="ml-auto flex cursor-pointer items-center gap-2 text-sm text-gris-600">
                <input type="checkbox" name="sans_enseignant" value="1" @checked(request('sans_enseignant') === '1')
                       onchange="this.form.submit()"
                       class="h-4 w-4 cursor-pointer rounded border-gris-300 text-ogar-600 focus:ring-ogar-600">
                Seulement celles sans enseignant
            </label>
        </div>
    </form>

    {{-- ----------------------------------------------------------------
         Liste
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Programme d’enseignement</h2>
            <span class="text-xs text-gris-400">{{ $subjects->total() }} résultat(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Matière</th>
                        <th>Cycle</th>
                        <th>Séries</th>
                        <th class="text-center">Coef.</th>
                        <th class="text-center">Enseignants</th>
                        <th class="text-center">Notes</th>
                        <th class="text-center">Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subjects as $subject)
                        <tr>
                            <td>
                                <a href="{{ route('subjects.show', $subject->id) }}"
                                   class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                    {{ $subject->name }}
                                </a>
                                <div class="font-mono text-[11px] text-gris-400">{{ $subject->code }}</div>
                            </td>
                            <td>
                                <x-puce :couleur="$teintesCycle[$subject->cycle] ?? 'slate'">
                                    {{ $libellesCycle[$subject->cycle] ?? ucfirst($subject->cycle) }}
                                </x-puce>
                            </td>
                            <td>
                                @if ($subject->cycle !== 'lycee')
                                    <span class="text-xs text-gris-400">toutes</span>
                                @elseif (empty($subject->series))
                                    <span class="text-xs italic text-corail-600">aucune série</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($subject->series as $lettre)
                                            <span class="rounded bg-gris-100 px-1.5 py-0.5 text-[10px] font-semibold text-gris-600">{{ $lettre }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-center font-semibold text-gris-700">
                                {{ (int) $subject->coefficient }}
                            </td>
                            <td class="text-center">
                                @if ($subject->teachers_count > 0)
                                    <span class="font-medium text-gris-700">{{ $subject->teachers_count }}</span>
                                @else
                                    <span class="text-xs font-medium text-corail-600">aucun</span>
                                @endif
                            </td>
                            <td class="text-center {{ $subject->grades_count > 0 ? 'text-gris-700' : 'text-gris-300' }}">
                                {{ $subject->grades_count }}
                            </td>
                            <td class="text-center">
                                <x-puce :couleur="$subject->is_active ? 'emerald' : 'slate'">
                                    {{ $subject->is_active ? 'Active' : 'Inactive' }}
                                </x-puce>
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('subjects.show', $subject->id) }}" class="bouton-mini">Consulter</a>
                                    <a href="{{ route('subjects.edit', $subject->id) }}" class="bouton-mini">Modifier</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="8" message="Aucune matière ne correspond à ces critères."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subjects->hasPages())
            <div class="border-t border-gris-100 px-5 py-4">
                {{ $subjects->links() }}
            </div>
        @endif
    </div>

@endsection
