@extends('layouts.app')

@section('titre', 'Élèves')
@section('sous-titre', $students->total().' élève(s) correspondant aux filtres')

@section('actions-entete')
    {{-- Une classe est filtree : sa fiche nominative est a un clic. --}}
    @if (request('class'))
        <a href="{{ route('classes.fiche', request('class')) }}" class="bouton-secondaire">
            <span class="hidden sm:inline">Fiche de classe</span>
            <span class="sm:hidden">Fiche</span>
        </a>
    @endif

    {{-- Inscrire un eleve releve de l'administration, pas de l'enseignant. --}}
    @unless (\App\Support\PerimetreEnseignant::estEnseignant())
        <a href="{{ route('students.create') }}" class="bouton-primaire">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span class="hidden sm:inline">Nouvel élève</span>
        </a>
    @endunless
@endsection

@section('contenu')

    <div class="grid gap-4 sm:grid-cols-3">
        <x-statistique libelle="Élèves actifs"
                       :valeur="number_format($studentsByCurrentStatus['actifs'] ?? 0, 0, ',', ' ')"
                       detail="Inscrits et en cours de scolarité"
                       couleur="ogar"/>
        <x-statistique libelle="Anciens élèves"
                       :valeur="number_format($studentsByCurrentStatus['anciens'] ?? 0, 0, ',', ' ')"
                       detail="Sortis de l’établissement"
                       couleur="slate"/>
        <x-statistique libelle="Avec redoublement"
                       :valeur="number_format($studentsByCurrentStatus['avec_redoublement'] ?? 0, 0, ',', ' ')"
                       detail="Au moins une année redoublée"
                       couleur="amber"/>
    </div>

    {{-- ----------------------------------------------------------------
         Filtres : formulaire GET, filtrage côté serveur
         ---------------------------------------------------------------- --}}
    @php
        // Le filtre Classe ne propose que les classes du cycle et du niveau
        // choisis ; la cascade est calculée côté navigateur.
        $niveauxPourFiltre = $levels->map(fn ($n) => ['id' => $n->id, 'nom' => $n->name, 'cycle' => $n->cycle])->values();
        $classesPourFiltre = $classes->map(fn ($c) => ['id' => $c->id, 'nom' => $c->name, 'niveau' => $c->level_id])->values();

        $filtresActifs = collect(request()->only([
            'search', 'cycle', 'level', 'class', 'status',
            'enrollment_status', 'current_status', 'student_status',
        ]))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    @endphp

    <form method="GET" action="{{ route('students.index') }}" data-filtre-dynamique="eleves"
          x-data="{
              cycle: @js(request('cycle')),
              niveau: @js(request('level')),
              niveaux: {{ Js::from($niveauxPourFiltre) }},
              classes: {{ Js::from($classesPourFiltre) }},
              get niveauxDuCycle() {
                  return this.cycle ? this.niveaux.filter(n => n.cycle === this.cycle) : this.niveaux;
              },
              get classesDuNiveau() {
                  return this.niveau
                      ? this.classes.filter(c => String(c.niveau) === String(this.niveau))
                      : this.classes;
              },
          }"
          class="carte mt-6 p-4">

        <div class="grid gap-3 md:grid-cols-3 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label for="search" class="etiquette">Rechercher</label>
                <input type="search" id="search" name="search" value="{{ request('search') }}" class="champ"
                       placeholder="Nom, matricule, téléphone ou courriel…">
            </div>

            <div>
                <label for="cycle" class="etiquette">Cycle</label>
                <select id="cycle" name="cycle" class="champ" x-model="cycle" @change="niveau = ''">
                    <option value="">Tous les cycles</option>
                    <option value="preprimaire" @selected(request('cycle') === 'preprimaire')>Préprimaire</option>
                    <option value="primaire" @selected(request('cycle') === 'primaire')>Primaire</option>
                    <option value="college" @selected(request('cycle') === 'college')>Collège</option>
                    <option value="lycee" @selected(request('cycle') === 'lycee')>Lycée</option>
                </select>
            </div>

            <div>
                <label for="level" class="etiquette">Niveau</label>
                <select id="level" name="level" class="champ" x-model="niveau">
                    <option value="">Tous les niveaux</option>
                    <template x-for="n in niveauxDuCycle" :key="n.id">
                        <option :value="n.id" x-text="n.nom" :selected="String(n.id) === @js(request('level'))"></option>
                    </template>
                </select>
            </div>

            <div>
                <label for="class" class="etiquette">Classe</label>
                <select id="class" name="class" class="champ">
                    <option value="">Toutes les classes</option>
                    <template x-for="c in classesDuNiveau" :key="c.id">
                        <option :value="c.id" x-text="c.nom" :selected="String(c.id) === @js(request('class'))"></option>
                    </template>
                </select>
            </div>

            <div>
                <label for="status" class="etiquette">Statut administratif</label>
                <select id="status" name="status" class="champ">
                    <option value="">Tous les statuts</option>
                    <option value="active" @selected(request('status') === 'active')>Actif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactif</option>
                    <option value="graduated" @selected(request('status') === 'graduated')>Diplômé</option>
                    <option value="transferred" @selected(request('status') === 'transferred')>Transféré</option>
                </select>
            </div>

            <div>
                <label for="enrollment_status" class="etiquette">Inscription en cours</label>
                <select id="enrollment_status" name="enrollment_status" class="champ">
                    <option value="">Tous</option>
                    <option value="enrolled" @selected(request('enrollment_status') === 'enrolled')>Inscrits</option>
                    <option value="not_enrolled" @selected(request('enrollment_status') === 'not_enrolled')>Non inscrits</option>
                </select>
            </div>

            <div>
                <label for="current_status" class="etiquette">Statut élève</label>
                <select id="current_status" name="current_status" class="champ">
                    <option value="">Tous</option>
                    <option value="actif" @selected(request('current_status') === 'actif')>Actif</option>
                    <option value="ancien" @selected(request('current_status') === 'ancien')>Ancien élève</option>
                    <option value="transfere" @selected(request('current_status') === 'transfere')>Transféré</option>
                    <option value="diplome" @selected(request('current_status') === 'diplome')>Diplômé</option>
                </select>
            </div>

            <div>
                <label for="student_status" class="etiquette">Type d’inscription</label>
                <select id="student_status" name="student_status" class="champ">
                    <option value="">Tous</option>
                    <option value="nouveau" @selected(request('student_status') === 'nouveau')>Nouveau</option>
                    <option value="redoublant" @selected(request('student_status') === 'redoublant')>Redoublant</option>
                    <option value="passant" @selected(request('student_status') === 'passant')>Passant</option>
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-end gap-2">
            <button type="submit" class="bouton-primaire">Filtrer</button>

            @if ($filtresActifs)
                <a href="{{ route('students.index') }}" class="bouton-secondaire">Réinitialiser</a>
            @endif

            <div class="ml-auto">
                <label for="per_page" class="etiquette">Par page</label>
                <select id="per_page" name="per_page" class="champ w-auto" onchange="this.form.submit()">
                    @foreach (\App\Support\ParametresPlateforme::PAGINATIONS as $taille)
                        <option value="{{ $taille }}" @selected(\App\Support\ParametresPlateforme::pagination(request('per_page')) === $taille)>{{ $taille }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    {{-- ----------------------------------------------------------------
         Liste
         ---------------------------------------------------------------- --}}
    <div class="carte relative mt-6 overflow-hidden" data-liste-dynamique="eleves">
        <x-chargement data-voile-chargement hidden message="Chargement de la liste…"/>

        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Liste des élèves</h2>
            <span class="text-xs text-gris-400">
                {{ $students->firstItem() ?? 0 }}&ndash;{{ $students->lastItem() ?? 0 }} sur {{ $students->total() }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Matricule</th>
                        <th>Classe actuelle</th>
                        <th>Âge</th>
                        <th>Inscription</th>
                        <th>Type</th>
                        <th>Parents</th>
                        <th>Statut</th>
                        <th class="w-28"></th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $teintesType = ['nouveau' => 'sky', 'redoublant' => 'amber', 'passant' => 'emerald'];
                        $libellesType = ['nouveau' => 'Nouveau', 'redoublant' => 'Redoublant', 'passant' => 'Passant'];
                    @endphp

                    @forelse ($students as $student)
                        @php
                            $inscription = $student->enrollments->first();
                            $typeInscription = $inscription?->student_status ?? 'nouveau';
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-avatar :personne="$student"/>
                                    <div class="min-w-0">
                                        <a href="{{ route('students.show', $student->id) }}"
                                           class="block truncate font-semibold text-gris-900 hover:text-ogar-700">
                                            {{ $student->first_name }} {{ $student->last_name }}
                                        </a>
                                        <div class="whitespace-nowrap text-xs text-gris-400">
                                            @if ($student->date_of_birth)
                                                Né(e) le {{ \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') }}
                                            @else
                                                Date de naissance inconnue
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td><span class="font-mono text-xs font-semibold text-ogar-700">{{ $student->student_id }}</span></td>

                            <td>
                                @if ($inscription?->schoolClass)
                                    <div class="whitespace-nowrap font-medium text-gris-800">{{ $inscription->schoolClass->name }}</div>
                                    @if ($inscription->schoolClass->level)
                                        <div class="text-xs text-gris-400">{{ $inscription->schoolClass->level->name }}</div>
                                    @endif
                                @else
                                    <span class="text-gris-400">Non affecté</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap text-gris-500">
                                {{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->age.' ans' : '—' }}
                            </td>

                            <td>
                                @if ($inscription)
                                    <x-puce couleur="emerald" class="whitespace-nowrap">Inscrit</x-puce>
                                    @if ($inscription->academicYear)
                                        <div class="mt-1 whitespace-nowrap text-xs text-gris-400">{{ $inscription->academicYear->name }}</div>
                                    @endif
                                @else
                                    <x-puce couleur="amber" class="whitespace-nowrap">Non inscrit</x-puce>
                                @endif
                            </td>

                            <td>
                                @if ($inscription)
                                    <x-puce :couleur="$teintesType[$typeInscription] ?? 'slate'" class="whitespace-nowrap">
                                        {{ $libellesType[$typeInscription] ?? '—' }}
                                    </x-puce>
                                @else
                                    <span class="text-gris-400">—</span>
                                @endif
                            </td>

                            <td>
                                @if ($student->parents->isNotEmpty())
                                    <div class="text-xs text-gris-600">
                                        @foreach ($student->parents->take(2) as $parent)
                                            <div class="truncate">{{ $parent->first_name }} {{ $parent->last_name }}</div>
                                        @endforeach
                                        @if ($student->parents->count() > 2)
                                            <div class="text-gris-400">+{{ $student->parents->count() - 2 }}</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gris-400">Aucun parent lié</span>
                                @endif
                            </td>

                            <td>
                                @switch($student->status)
                                    @case('active')      <x-puce couleur="emerald">Actif</x-puce> @break
                                    @case('inactive')    <x-puce>Inactif</x-puce> @break
                                    @case('graduated')   <x-puce couleur="sky">Diplômé</x-puce> @break
                                    @default             <x-puce couleur="amber">Transféré</x-puce>
                                @endswitch
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-1 whitespace-nowrap">
                                    <a href="{{ route('students.show', $student->id) }}" class="bouton-mini" title="Voir la fiche">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('students.edit', $student->id) }}" class="bouton-mini" title="Modifier">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                        </svg>
                                    </a>
                                    <x-confirmation :action="route('students.destroy', $student->id)" methode="DELETE"
                                                    titre="Supprimer cet élève ?"
                                                    :message="'Le dossier de '.$student->first_name.' '.$student->last_name.' et ses inscriptions seront définitivement supprimés.'"
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
                        <x-vide :colonnes="9"
                                :message="$filtresActifs
                                    ? 'Aucun élève ne correspond aux filtres.'
                                    : 'Aucun élève enregistré pour le moment.'"/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($students->hasPages())
            <div class="border-t border-gris-200 px-4 py-3" data-pagination>
                {{ $students->links() }}
            </div>
        @endif
    </div>

@endsection
