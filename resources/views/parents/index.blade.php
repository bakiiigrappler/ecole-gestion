@extends('layouts.app')

@section('titre', 'Parents')
@section('sous-titre', ($totalParents ?? $parents->total()).' contact(s) enregistré(s)')

@section('actions-entete')
    <a href="{{ route('parents.create') }}" class="bouton-primaire">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        <span class="hidden sm:inline">Nouveau parent</span>
    </a>
@endsection

@section('contenu')

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Parents"
                       :valeur="number_format($totalParents ?? 0, 0, ',', ' ')"
                       detail="Fiches enregistrées"
                       couleur="ogar"/>
        <x-statistique libelle="Contacts joignables"
                       :valeur="number_format($activeContacts ?? 0, 0, ',', ' ')"
                       detail="Téléphone ou e-mail renseigné"
                       couleur="emerald"/>
        <x-statistique libelle="Contacts principaux"
                       :valeur="number_format($primaryContacts ?? 0, 0, ',', ' ')"
                       detail="Pour au moins un enfant"
                       couleur="violet"/>
        <x-statistique libelle="Autorisés à récupérer"
                       :valeur="number_format($canPickup ?? 0, 0, ',', ' ')"
                       detail="Pour au moins un enfant"
                       couleur="amber"/>
    </div>

    <form method="GET" action="{{ route('parents.index') }}" id="filterForm"
          data-filtre-dynamique="parents" class="carte mt-6 p-4">
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label for="searchInput" class="etiquette">Rechercher</label>
                <input type="search" name="search" id="searchInput" value="{{ request('search') }}"
                       class="champ" placeholder="Nom, prénom, téléphone, e-mail...">
            </div>

            <div>
                <label for="relationFilter" class="etiquette">Lien de parenté</label>
                <select name="relationship" id="relationFilter" class="champ">
                    <option value="">Tous</option>
                    <option value="father" @selected(request('relationship') == 'father')>Père</option>
                    <option value="mother" @selected(request('relationship') == 'mother')>Mère</option>
                    <option value="guardian" @selected(request('relationship') == 'guardian')>Tuteur</option>
                    <option value="other" @selected(request('relationship') == 'other')>Autre</option>
                </select>
            </div>

            <div>
                <label for="primaryFilter" class="etiquette">Contact principal</label>
                <select name="is_primary_contact" id="primaryFilter" class="champ">
                    <option value="">Tous</option>
                    <option value="1" @selected(request('is_primary_contact') === '1')>Oui</option>
                    <option value="0" @selected(request('is_primary_contact') === '0')>Non</option>
                </select>
            </div>

            <div>
                <label for="pickupFilter" class="etiquette">Autorisé à récupérer</label>
                <select name="can_pickup" id="pickupFilter" class="champ">
                    <option value="">Tous</option>
                    <option value="1" @selected(request('can_pickup') === '1')>Oui</option>
                    <option value="0" @selected(request('can_pickup') === '0')>Non</option>
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-end gap-2">
            <button type="submit" class="bouton-primaire">Filtrer</button>
            <a href="{{ route('parents.index') }}" id="clearFilters" class="bouton-secondaire">Réinitialiser</a>

            <div class="ml-auto">
                <label for="perPageFilter" class="etiquette">Par page</label>
                <select name="per_page" id="perPageFilter" class="champ w-auto">
                    @foreach (\App\Support\ParametresPlateforme::PAGINATIONS as $taille)
                        <option value="{{ $taille }}" @selected(\App\Support\ParametresPlateforme::pagination(request('per_page')) === $taille)>{{ $taille }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <div class="carte relative mt-6 overflow-hidden" data-liste-dynamique="parents">
        <x-chargement data-voile-chargement hidden message="Chargement de la liste…"/>

        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Liste des parents</h2>
            <span class="text-xs text-gris-400">{{ $parents->total() }} au total</span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau" id="parentsTable">
                <thead>
                    <tr>
                        <th>Parent</th>
                        <th>Lien</th>
                        <th>Enfants</th>
                        <th>Contact</th>
                        <th>Profession</th>
                        <th>Autorisations</th>
                        <th class="w-28"></th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $libellesLien = ['father' => 'Père', 'mother' => 'Mère', 'guardian' => 'Tuteur', 'other' => 'Autre'];
                        $teintesLien = ['father' => 'sky', 'mother' => 'rose', 'guardian' => 'violet', 'other' => 'slate'];
                    @endphp

                    @forelse ($parents as $parent)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-avatar :nom="$parent->first_name.' '.$parent->last_name"/>
                                    <div class="min-w-0">
                                        <a href="{{ route('parents.show', $parent->id) }}"
                                           class="block truncate font-semibold text-gris-900 hover:text-ogar-700">
                                            {{ $parent->first_name }} {{ $parent->last_name }}
                                        </a>
                                        <div class="whitespace-nowrap text-xs text-gris-400">
                                            Inscrit le {{ $parent->created_at->format('d/m/Y') }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                {{-- Un adulte peut tenir plusieurs roles selon l'enfant :
                                     on affiche les liens distincts de ses rattachements. --}}
                                @php($liens = $parent->students->pluck('pivot.relationship_type')->unique())
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($liens as $lien)
                                        <x-puce :couleur="$teintesLien[$lien] ?? 'slate'" class="whitespace-nowrap">
                                            {{ $libellesLien[$lien] ?? ucfirst($lien) }}
                                        </x-puce>
                                    @empty
                                        <span class="text-xs text-gris-400">—</span>
                                    @endforelse
                                </div>
                            </td>

                            <td>
                                @if ($parent->students->count() > 0)
                                    <div class="text-xs text-gris-600">
                                        @foreach ($parent->students->take(2) as $student)
                                            <div class="truncate">{{ $student->first_name }} {{ $student->last_name }}</div>
                                        @endforeach
                                        @if ($parent->students->count() > 2)
                                            <div class="text-gris-400">+{{ $parent->students->count() - 2 }} autre(s)</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gris-400">Aucun enfant lié</span>
                                @endif
                            </td>

                            <td>
                                @if ($parent->phone)
                                    <a href="tel:{{ $parent->phone }}" class="block whitespace-nowrap text-xs text-gris-600 hover:text-ogar-700">
                                        {{ $parent->phone }}
                                    </a>
                                @endif
                                @if ($parent->email)
                                    <a href="mailto:{{ $parent->email }}" class="block truncate text-xs text-gris-400 hover:text-ogar-700">
                                        {{ $parent->email }}
                                    </a>
                                @endif
                                @if (! $parent->phone && ! $parent->email)
                                    <span class="text-xs text-gris-400">—</span>
                                @endif
                            </td>

                            <td>
                                @if ($parent->profession)
                                    <div class="text-xs text-gris-600">{{ Str::limit($parent->profession, 24) }}</div>
                                @endif
                                @if ($parent->workplace)
                                    <div class="text-xs text-gris-400">{{ Str::limit($parent->workplace, 24) }}</div>
                                @endif
                                @if (! $parent->profession && ! $parent->workplace)
                                    <span class="text-xs text-gris-400">—</span>
                                @endif
                            </td>

                            <td>
                                @php($principalPour = $parent->students->filter(fn ($e) => $e->pivot->is_primary_contact)->count())
                                @php($recuperationPour = $parent->students->filter(fn ($e) => $e->pivot->can_pickup)->count())
                                <div class="flex flex-wrap gap-1">
                                    @if ($principalPour)
                                        <x-puce couleur="ogar" class="whitespace-nowrap">Principal ({{ $principalPour }})</x-puce>
                                    @endif
                                    @if ($recuperationPour)
                                        <x-puce couleur="emerald" class="whitespace-nowrap">Récupération ({{ $recuperationPour }})</x-puce>
                                    @endif
                                    @if (! $principalPour && ! $recuperationPour)
                                        <span class="text-xs text-gris-400">Aucune</span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-1 whitespace-nowrap">
                                    <a href="{{ route('parents.show', $parent->id) }}" class="bouton-mini" title="Voir la fiche">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('parents.edit', $parent->id) }}" class="bouton-mini" title="Modifier">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                        </svg>
                                    </a>
                                    <x-confirmation :action="route('parents.destroy', $parent->id)" methode="DELETE"
                                                    titre="Supprimer ce parent ?"
                                                    :message="'La fiche de '.$parent->first_name.' '.$parent->last_name.' et ses liens avec les élèves seront supprimés.'"
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
                        <x-vide :colonnes="7" message="Aucun parent ne correspond aux filtres."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($parents->hasPages())
            <div class="border-t border-gris-200 px-4 py-3" data-pagination>
                {{ $parents->withQueryString()->links() }}
            </div>
        @endif
    </div>

@endsection

@push('scripts')
<script>
    // Les listes deroulantes relancent la recherche sans passer par le bouton.
    document.addEventListener('DOMContentLoaded', () => {
        const formulaire = document.getElementById('filterForm');
        ['relationFilter', 'primaryFilter', 'pickupFilter', 'perPageFilter'].forEach((id) => {
            document.getElementById(id)?.addEventListener('change', () => formulaire.submit());
        });
    });
</script>
@endpush
