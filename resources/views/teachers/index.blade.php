@extends('layouts.app')

@section('titre', 'Enseignants')
@section('sous-titre', ($totalTeachers ?? $teachers->count()).' enseignant(s) — '.($activeTeachers ?? 0).' en activité')

@section('actions-entete')
    <a href="{{ route('teachers.create') }}" class="bouton-primaire">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        <span class="hidden sm:inline">Nouvel enseignant</span>
    </a>
@endsection

@section('contenu')

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Enseignants"
                       :valeur="number_format($totalTeachers ?? 0, 0, ',', ' ')"
                       :detail="($activeTeachers ?? 0).' en activité'"
                       couleur="ogar"/>
        <x-statistique libelle="Arrivés ce mois"
                       :valeur="number_format($newThisMonth ?? 0, 0, ',', ' ')"
                       detail="Nouveaux dossiers"
                       couleur="emerald"/>
        <x-statistique libelle="Salaire moyen"
                       :valeur="number_format($averageSalary ?? 0, 0, ',', ' ').' F'"
                       detail="Sur les contrats en cours"
                       couleur="amber"/>
        <x-statistique libelle="Préprimaire / Collège"
                       :valeur="($teachersByCycle['preprimaire'] ?? 0).' / '.($teachersByCycle['college'] ?? 0)"
                       detail="Répartition par cycle"
                       couleur="violet"/>
    </div>

    {{-- ----------------------------------------------------------------
         Filtres — formulaire GET, filtrage côté serveur
         ---------------------------------------------------------------- --}}
    <form method="GET" action="{{ route('teachers.index') }}" id="filterForm" class="carte mt-6 p-4">
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label for="searchInput" class="etiquette">Rechercher</label>
                <input type="search" name="search" id="searchInput" value="{{ request('search') }}"
                       class="champ" placeholder="Nom, prénom, identifiant employé...">
            </div>

            <div>
                <label for="cycleFilter" class="etiquette">Cycle</label>
                <select name="cycle" id="cycleFilter" class="champ">
                    <option value="">Tous les cycles</option>
                    <option value="preprimaire" @selected(request('cycle') == 'preprimaire')>Préprimaire</option>
                    <option value="primaire" @selected(request('cycle') == 'primaire')>Primaire</option>
                    <option value="college" @selected(request('cycle') == 'college')>Collège</option>
                    <option value="lycee" @selected(request('cycle') == 'lycee')>Lycée</option>
                </select>
            </div>

            <div>
                <label for="typeFilter" class="etiquette">Type</label>
                <select name="teacher_type" id="typeFilter" class="champ">
                    <option value="">Tous les types</option>
                    <option value="general" @selected(request('teacher_type') == 'general')>Polyvalent</option>
                    <option value="specialized" @selected(request('teacher_type') == 'specialized')>Spécialisé</option>
                </select>
            </div>

            <div>
                <label for="statusFilter" class="etiquette">Statut</label>
                <select name="status" id="statusFilter" class="champ">
                    <option value="">Tous les statuts</option>
                    <option value="active" @selected(request('status') == 'active')>Actif</option>
                    <option value="inactive" @selected(request('status') == 'inactive')>Inactif</option>
                </select>
            </div>
        </div>

        <div class="mt-4 flex gap-2">
            <button type="submit" class="bouton-primaire">Filtrer</button>
            <a href="{{ route('teachers.index') }}" id="clearFilters" class="bouton-secondaire">Réinitialiser</a>
        </div>
    </form>

    {{-- ----------------------------------------------------------------
         Liste
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Corps enseignant</h2>
            <span class="text-xs text-gris-400">{{ $teachers->count() }} affiché(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau" id="teachersTable">
                <thead>
                    <tr>
                        <th>Enseignant</th>
                        <th>Identifiant</th>
                        <th>Cycle</th>
                        <th>Type</th>
                        <th>Affectation</th>
                        <th>Contact</th>
                        <th>Statut</th>
                        <th class="w-28 whitespace-nowrap"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($teachers as $teacher)
                        @php
                            $teintesCycle = [
                                'preprimaire' => 'amber',
                                'primaire'    => 'emerald',
                                'college'     => 'sky',
                                'lycee'       => 'violet',
                            ];
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-avatar :personne="$teacher"/>
                                    <div class="min-w-0">
                                        <a href="{{ route('teachers.show', $teacher) }}"
                                           class="block truncate font-semibold text-gris-900 hover:text-ogar-700">
                                            {{ $teacher->full_name }}
                                        </a>
                                        <div class="text-xs text-gris-400">
                                            {{ Str::limit($teacher->qualification ?? 'Enseignant', 34) }}
                                            @if ($teacher->years_of_service)
                                                &middot; {{ $teacher->years_of_service }} ans d’expérience
                                            @endif
                                        </div>
                                        @if ($teacher->diploma_file)
                                            <a href="{{ asset('storage/'.$teacher->diploma_file) }}" target="_blank"
                                               class="text-xs font-semibold text-ogar-600 hover:underline">Diplôme</a>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td><span class="font-mono text-xs font-semibold text-ogar-700">{{ $teacher->employee_id }}</span></td>

                            <td>
                                <x-puce :couleur="$teintesCycle[$teacher->cycle] ?? 'slate'" class="whitespace-nowrap">{{ $teacher->cycle_label }}</x-puce>
                            </td>

                            <td>
                                @if ($teacher->teacher_type === 'general')
                                    <x-puce couleur="sky" class="whitespace-nowrap">Polyvalent</x-puce>
                                @else
                                    <x-puce couleur="violet" class="whitespace-nowrap">Spécialisé</x-puce>
                                @endif
                            </td>

                            <td>
                                @if ($teacher->teacher_type === 'general')
                                    @if ($teacher->assignedClass)
                                        <div class="whitespace-nowrap font-medium text-gris-800">{{ $teacher->assignedClass->name }}</div>
                                        @if ($teacher->assignedClass->description)
                                            <div class="text-xs text-gris-400">{{ Str::limit($teacher->assignedClass->description, 28) }}</div>
                                        @endif
                                    @else
                                        <span class="text-gris-400">Aucune classe</span>
                                    @endif
                                @else
                                    @if ($teacher->specialization)
                                        <div class="font-medium text-gris-800">{{ $teacher->specialization }}</div>
                                    @else
                                        <span class="text-gris-400">Non précisée</span>
                                    @endif
                                @endif
                            </td>

                            <td>
                                <div class="truncate text-xs text-gris-600">{{ $teacher->email }}</div>
                                @if ($teacher->phone)
                                    <div class="whitespace-nowrap text-xs text-gris-400">{{ $teacher->phone }}</div>
                                @endif
                            </td>

                            <td>
                                @switch($teacher->status)
                                    @case('active')   <x-puce couleur="emerald">Actif</x-puce> @break
                                    @case('inactive') <x-puce>Inactif</x-puce> @break
                                    @default          <x-puce couleur="amber">{{ ucfirst($teacher->status) }}</x-puce>
                                @endswitch
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-1 whitespace-nowrap">
                                    <a href="{{ route('teachers.show', $teacher) }}" class="bouton-mini" title="Voir la fiche">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('teachers.edit', $teacher) }}" class="bouton-mini" title="Modifier">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                        </svg>
                                    </a>
                                    <x-confirmation :action="route('teachers.destroy', $teacher)" methode="DELETE"
                                                    titre="Supprimer cet enseignant ?"
                                                    :message="'Le dossier de '.$teacher->full_name.' sera définitivement supprimé.'"
                                                    confirmer="Supprimer"
                                                    bouton="bouton-mini text-corail-600 hover:bg-corail-50">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                    </x-confirmation>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="8" message="Aucun enseignant ne correspond aux filtres."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Les listes deroulantes relancent la recherche sans passer par le bouton.
    document.addEventListener('DOMContentLoaded', () => {
        const formulaire = document.getElementById('filterForm');
        ['cycleFilter', 'typeFilter', 'statusFilter'].forEach((id) => {
            document.getElementById(id)?.addEventListener('change', () => formulaire.submit());
        });
    });
</script>
@endpush
