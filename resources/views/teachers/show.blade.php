@extends('layouts.app')

@section('titre', $teacher->full_name)
@section('sous-titre', 'Matricule '.$teacher->employee_id)

@section('actions-entete')
    <a href="{{ route('teachers.edit', $teacher) }}" class="bouton-secondaire">Modifier</a>
    <a href="{{ route('schedules.index') }}" class="bouton-primaire">Emploi du temps</a>
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

    $libellesStatut = ['active' => 'Actif', 'inactive' => 'Inactif', 'suspended' => 'Suspendu'];
    $teintesStatut = ['active' => 'emerald', 'inactive' => 'slate', 'suspended' => 'rose'];

    $polyvalent = $teacher->teacher_type === 'general';

    // Classes tenues : la classe attribuée pour un polyvalent, plus toutes
    // celles où il intervient via la table de liaison.
    $classesTenues = $teacher->classes;
    $creneaux = $teacher->schedules->sortBy(['day_of_week', 'start_time']);

    $jours = [
        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
        5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
        'monday' => 'Lundi', 'tuesday' => 'Mardi', 'wednesday' => 'Mercredi',
        'thursday' => 'Jeudi', 'friday' => 'Vendredi', 'saturday' => 'Samedi',
    ];

    $onglets = [
        'profil' => 'Détail du profil',
        'enseignement' => 'Enseignement',
        'emploi' => 'Emploi du temps',
    ];
@endphp

<div x-data="{ onglet: 'profil' }">

    {{-- ------------------------------------------------------------------
         En-tête du dossier
         ------------------------------------------------------------------ --}}
    <div class="carte mb-4 p-5">
        <div class="flex flex-wrap items-start gap-5">
            <x-avatar :personne="$teacher" taille="h-20 w-20"/>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-bold text-gris-900">{{ $teacher->full_name }}</h2>
                    <x-puce :couleur="$teintesStatut[$teacher->status] ?? 'slate'">
                        {{ $libellesStatut[$teacher->status] ?? ucfirst($teacher->status) }}
                    </x-puce>
                    <x-puce :couleur="$teintesCycle[$teacher->cycle] ?? 'slate'">
                        {{ $teacher->cycle_label ?? ($libellesCycle[$teacher->cycle] ?? $teacher->cycle) }}
                    </x-puce>
                    <x-puce :couleur="$polyvalent ? 'sky' : 'violet'">
                        {{ $polyvalent ? 'Polyvalent' : 'Spécialisé' }}
                    </x-puce>
                </div>

                <p class="mt-1 text-sm text-gris-500">
                    {{ $teacher->qualification ?: 'Qualification non renseignée' }}
                    @if ($polyvalent && $teacher->assignedClass)
                        &middot; {{ $teacher->assignedClass->name }}
                    @elseif (! $polyvalent && $teacher->specialization)
                        &middot; {{ $teacher->specialization }}
                    @endif
                </p>

                <div class="mt-3 flex flex-wrap gap-x-8 gap-y-1.5 text-sm [&>div]:whitespace-nowrap">
                    <div>
                        <span class="text-gris-400">Matricule</span>
                        <span class="font-mono font-medium text-ogar-700">{{ $teacher->employee_id }}</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Ancienneté</span>
                        <span class="font-medium">{{ $teacher->years_of_service }} an(s)</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Embauché le</span>
                        <span class="font-medium">{{ $teacher->hire_date?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Téléphone</span>
                        <span class="font-medium">{{ $teacher->phone ?: '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-lg bg-ogar-50 px-4 py-3">
                    <div class="text-2xl font-bold text-ogar-700">{{ $classesTenues->count() }}</div>
                    <div class="text-[10px] font-semibold uppercase text-ogar-600">classes</div>
                </div>
                <div class="rounded-lg bg-gris-50 px-4 py-3">
                    <div class="text-2xl font-bold text-gris-700">{{ $teacher->subjects->count() }}</div>
                    <div class="text-[10px] font-semibold uppercase text-gris-500">matières</div>
                </div>
                <div class="rounded-lg bg-soleil-50 px-4 py-3">
                    <div class="text-2xl font-bold text-soleil-700">{{ $creneaux->count() }}</div>
                    <div class="text-[10px] font-semibold uppercase text-soleil-600">créneaux</div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 border-t border-gris-100 pt-4">
            <a href="{{ route('teachers.edit', $teacher) }}" class="bouton-secondaire text-xs">Modifier le dossier</a>
            @if ($teacher->diploma_file)
                <a href="{{ asset('storage/'.$teacher->diploma_file) }}" target="_blank" class="bouton-secondaire text-xs">
                    Consulter le diplôme
                </a>
            @endif
            <a href="{{ route('grades.index') }}" class="bouton-secondaire text-xs">Notes saisies</a>

            <x-confirmation :action="route('teachers.destroy', $teacher)" methode="DELETE"
                            titre="Supprimer cet enseignant ?"
                            :message="'Le dossier de '.$teacher->full_name.' sera définitivement supprimé.'"
                            confirmer="Supprimer"
                            bouton="bouton-secondaire ml-auto text-xs text-corail-600 hover:bg-corail-50">
                Supprimer le dossier
            </x-confirmation>
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         Onglets
         ------------------------------------------------------------------ --}}
    <div class="mb-4 flex flex-wrap gap-1 border-b border-gris-200">
        @foreach ($onglets as $cle => $libelle)
            <button type="button" @click="onglet = '{{ $cle }}'"
                    :class="onglet === '{{ $cle }}' ? 'border-ogar-700 text-ogar-700' : 'border-transparent text-gris-500 hover:text-gris-700'"
                    class="cursor-pointer border-b-2 px-4 py-2 text-sm font-semibold transition">{{ $libelle }}</button>
        @endforeach
    </div>

    {{-- Profil ------------------------------------------------------------ --}}
    <div x-show="onglet === 'profil'" class="grid gap-4 lg:grid-cols-2">
        <div class="carte">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">État civil et contact</h3>
                <a href="{{ route('teachers.edit', $teacher) }}" class="text-xs font-semibold text-ogar-600 hover:underline">Modifier</a>
            </div>
            <dl class="grid grid-cols-2 gap-y-2.5 p-5 text-sm">
                @foreach ([
                    'Prénom' => $teacher->first_name,
                    'Nom' => $teacher->last_name,
                    'Date de naissance' => $teacher->date_of_birth?->format('d/m/Y'),
                    'Sexe' => $teacher->gender ? ($teacher->gender === 'female' ? 'Féminin' : 'Masculin') : null,
                    'Adresse e-mail' => $teacher->email,
                    'Téléphone' => $teacher->phone,
                    'Adresse' => $teacher->address,
                ] as $libelle => $valeur)
                    <dt class="text-gris-400">{{ $libelle }}</dt>
                    <dd class="font-medium text-gris-800">{{ $valeur ?: '—' }}</dd>
                @endforeach
            </dl>
        </div>

        <div class="carte">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Contrat et qualification</h3>
            </div>
            <dl class="grid grid-cols-2 gap-y-2.5 px-5 pt-5 text-sm">
                @foreach ([
                    'Matricule' => $teacher->employee_id,
                    'Statut' => $libellesStatut[$teacher->status] ?? $teacher->status,
                    'Cycle' => $libellesCycle[$teacher->cycle] ?? $teacher->cycle,
                    'Type' => $polyvalent ? 'Polyvalent' : 'Spécialisé',
                    'Qualification' => $teacher->qualification,
                    'Spécialité' => $polyvalent ? null : $teacher->specialization,
                    'Date d’embauche' => $teacher->hire_date?->format('d/m/Y'),
                    'Ancienneté' => $teacher->years_of_service.' an(s)',
                    'Salaire mensuel' => $teacher->salary ? number_format($teacher->salary, 0, ',', ' ').' FCFA' : null,
                ] as $libelle => $valeur)
                    <dt class="text-gris-400">{{ $libelle }}</dt>
                    <dd class="font-medium text-gris-800">{{ $valeur ?: '—' }}</dd>
                @endforeach
            </dl>

            <div class="mt-4 border-t border-gris-100 px-5 pb-5 pt-3">
                <h4 class="etiquette">Diplôme</h4>
                @if ($teacher->diploma_file)
                    <a href="{{ asset('storage/'.$teacher->diploma_file) }}" target="_blank"
                       class="text-sm font-semibold text-ogar-600 hover:underline">Consulter la pièce jointe</a>
                @else
                    <p class="text-sm text-gris-400">Aucune pièce déposée</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Enseignement ------------------------------------------------------ --}}
    <div x-show="onglet === 'enseignement'" x-cloak class="grid gap-4 lg:grid-cols-2">
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Classes tenues</h3>
                <a href="{{ route('classes.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                    Module Classes
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Classe</th>
                            <th>Niveau</th>
                            <th>Rôle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classesTenues as $classe)
                            <tr>
                                <td>
                                    <a href="{{ route('classes.show', $classe->id) }}"
                                       class="font-semibold text-gris-900 hover:text-ogar-700">{{ $classe->name }}</a>
                                </td>
                                <td class="text-gris-500">{{ $classe->level->name ?? '—' }}</td>
                                <td>
                                    @if ($classe->pivot?->role === 'principal')
                                        <x-puce couleur="ogar" class="whitespace-nowrap">Professeur principal</x-puce>
                                    @else
                                        <x-puce class="whitespace-nowrap">Intervenant</x-puce>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="3" message="Aucune classe attribuée."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Matières enseignées</h3>
                <a href="{{ route('subjects.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                    Module Matières
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Matière</th>
                            <th>Code</th>
                            <th>Cycle</th>
                            <th>Coefficient</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($teacher->subjects as $matiere)
                            <tr>
                                <td class="font-semibold text-gris-900">{{ $matiere->name }}</td>
                                <td><span class="font-mono text-xs text-gris-500">{{ $matiere->code }}</span></td>
                                <td>
                                    <x-puce :couleur="$teintesCycle[$matiere->cycle] ?? 'slate'" class="whitespace-nowrap">
                                        {{ $libellesCycle[$matiere->cycle] ?? ucfirst($matiere->cycle ?? '—') }}
                                    </x-puce>
                                </td>
                                <td class="text-gris-600">{{ $matiere->coefficient }}</td>
                            </tr>
                        @empty
                            <x-vide :colonnes="4" message="Aucune matière rattachée."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Emploi du temps --------------------------------------------------- --}}
    <div x-show="onglet === 'emploi'" x-cloak>
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Créneaux de la semaine</h3>
                <a href="{{ route('schedules.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                    Module Emplois du temps
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Jour</th>
                            <th>Horaire</th>
                            <th>Classe</th>
                            <th>Matière</th>
                            <th>Salle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($creneaux as $creneau)
                            <tr>
                                <td class="whitespace-nowrap font-medium text-gris-800">
                                    {{ $jours[$creneau->day_of_week] ?? $creneau->day_of_week }}
                                </td>
                                <td class="whitespace-nowrap text-gris-600">
                                    {{ \Illuminate\Support\Str::substr($creneau->start_time, 0, 5) }}
                                    &ndash;
                                    {{ \Illuminate\Support\Str::substr($creneau->end_time, 0, 5) }}
                                </td>
                                <td>{{ $creneau->schoolClass->name ?? '—' }}</td>
                                <td>{{ $creneau->subject->name ?? $creneau->title ?? '—' }}</td>
                                <td class="text-gris-500">{{ $creneau->room ?: '—' }}</td>
                            </tr>
                        @empty
                            <x-vide :colonnes="5" message="Aucun créneau planifié pour cet enseignant."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
