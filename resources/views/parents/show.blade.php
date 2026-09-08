@extends('layouts.app')

@section('titre', $parent->first_name.' '.$parent->last_name)
@section('sous-titre', $parent->students->count().' enfant(s) rattaché(s)')

@section('actions-entete')
    <a href="{{ route('parents.edit', $parent->id) }}" class="bouton-secondaire">Modifier</a>
    <a href="{{ route('students.index') }}" class="bouton-primaire">Rattacher un enfant</a>
@endsection

@section('contenu')

@php
    $libellesLien = [
        'father' => 'Père',
        'mother' => 'Mère',
        'guardian' => 'Tuteur',
        'other' => 'Autre',
    ];
    $teintesLien = ['father' => 'sky', 'mother' => 'rose', 'guardian' => 'violet', 'other' => 'slate'];

    $enfants = $parent->students;

    // Comptés sur les liens : ces informations décrivent la relation à un
    // enfant donné, pas la personne.
    $contactPrincipalPour = $enfants->filter(fn ($e) => $e->pivot->is_primary_contact);
    $autorisePour = $enfants->filter(fn ($e) => $e->pivot->can_pickup);
    $vitAvec = $enfants->filter(fn ($e) => $e->pivot->lives_with_student);

    $liensDistincts = $enfants->pluck('pivot.relationship_type')->unique()->values();

    $onglets = [
        'profil' => 'Détail du profil',
        'enfants' => 'Enfants rattachés',
    ];
@endphp

<div x-data="{ onglet: 'profil' }">

    {{-- ------------------------------------------------------------------
         En-tête du dossier
         ------------------------------------------------------------------ --}}
    <div class="carte mb-4 p-5">
        <div class="flex flex-wrap items-start gap-5">
            <x-avatar :nom="$parent->first_name.' '.$parent->last_name" taille="h-20 w-20"/>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-bold text-gris-900">{{ $parent->first_name }} {{ $parent->last_name }}</h2>

                    {{-- Un adulte peut tenir plusieurs rôles selon l'enfant. --}}
                    @foreach ($liensDistincts as $lien)
                        <x-puce :couleur="$teintesLien[$lien] ?? 'slate'">
                            {{ $libellesLien[$lien] ?? ucfirst($lien) }}
                        </x-puce>
                    @endforeach

                    @if ($parent->user_id)
                        <x-puce couleur="emerald">Accès au portail</x-puce>
                    @endif
                </div>

                <p class="mt-1 text-sm text-gris-500">
                    {{ $parent->profession ?: 'Profession non renseignée' }}
                    @if ($parent->workplace) &middot; {{ $parent->workplace }} @endif
                </p>

                <div class="mt-3 flex flex-wrap gap-x-8 gap-y-1.5 text-sm [&>div]:whitespace-nowrap">
                    <div>
                        <span class="text-gris-400">Téléphone</span>
                        <a href="tel:{{ $parent->phone }}" class="font-medium text-ogar-700 hover:underline">{{ $parent->phone }}</a>
                    </div>
                    @if ($parent->email)
                        <div>
                            <span class="text-gris-400">E-mail</span>
                            <a href="mailto:{{ $parent->email }}" class="font-medium text-ogar-700 hover:underline">{{ $parent->email }}</a>
                        </div>
                    @endif
                    <div>
                        <span class="text-gris-400">Sexe</span>
                        <span class="font-medium">{{ $parent->gender === 'female' ? 'Féminin' : 'Masculin' }}</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Fiche créée le</span>
                        <span class="font-medium">{{ $parent->created_at?->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-lg bg-ogar-50 px-4 py-3">
                    <div class="text-2xl font-bold text-ogar-700">{{ $enfants->count() }}</div>
                    <div class="text-[10px] font-semibold uppercase text-ogar-600">enfants</div>
                </div>
                <div class="rounded-lg bg-gris-50 px-4 py-3">
                    <div class="text-2xl font-bold text-gris-700">{{ $contactPrincipalPour->count() }}</div>
                    <div class="text-[10px] font-semibold uppercase text-gris-500">contact princ.</div>
                </div>
                <div class="rounded-lg bg-soleil-50 px-4 py-3">
                    <div class="text-2xl font-bold text-soleil-700">{{ $autorisePour->count() }}</div>
                    <div class="text-[10px] font-semibold uppercase text-soleil-600">récupération</div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 border-t border-gris-100 pt-4">
            <a href="{{ route('parents.edit', $parent->id) }}" class="bouton-secondaire text-xs">Modifier la fiche</a>
            @if ($parent->phone)
                <a href="tel:{{ $parent->phone }}" class="bouton-secondaire text-xs">Appeler</a>
            @endif
            @if ($parent->email)
                <a href="mailto:{{ $parent->email }}" class="bouton-secondaire text-xs">Écrire</a>
            @endif

            <x-confirmation :action="route('parents.destroy', $parent->id)" methode="DELETE"
                            titre="Supprimer ce parent ?"
                            :message="'La fiche de '.$parent->first_name.' '.$parent->last_name.' et ses '.$enfants->count().' lien(s) avec les élèves seront définitivement supprimés.'"
                            confirmer="Supprimer"
                            bouton="bouton-secondaire ml-auto text-xs text-corail-600 hover:bg-corail-50">
                Supprimer la fiche
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
                <h3 class="text-sm font-semibold text-gris-900">Coordonnées</h3>
                <a href="{{ route('parents.edit', $parent->id) }}" class="text-xs font-semibold text-ogar-600 hover:underline">Modifier</a>
            </div>
            <dl class="grid grid-cols-2 gap-y-2.5 p-5 text-sm">
                @foreach ([
                    'Prénom' => $parent->first_name,
                    'Nom' => $parent->last_name,
                    'Sexe' => $parent->gender === 'female' ? 'Féminin' : 'Masculin',
                    'Téléphone' => $parent->phone,
                    'Second téléphone' => $parent->phone_2,
                    'Adresse e-mail' => $parent->email,
                    'Adresse' => $parent->address,
                ] as $libelle => $valeur)
                    <dt class="text-gris-400">{{ $libelle }}</dt>
                    <dd class="font-medium text-gris-800">{{ $valeur ?: '—' }}</dd>
                @endforeach
            </dl>
        </div>

        <div class="carte">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Situation et accès</h3>
            </div>
            <dl class="grid grid-cols-2 gap-y-2.5 px-5 pt-5 text-sm">
                @foreach ([
                    'Profession' => $parent->profession,
                    'Lieu de travail' => $parent->workplace,
                    'Enfants rattachés' => $enfants->count(),
                    'Contact principal pour' => $contactPrincipalPour->count().' enfant(s)',
                    'Vit avec' => $vitAvec->count().' enfant(s)',
                    'Autorisé à récupérer' => $autorisePour->count().' enfant(s)',
                ] as $libelle => $valeur)
                    <dt class="text-gris-400">{{ $libelle }}</dt>
                    <dd class="font-medium text-gris-800">{{ ($valeur === null || $valeur === '') ? '—' : $valeur }}</dd>
                @endforeach
            </dl>

            <div class="mt-4 border-t border-gris-100 px-5 pb-5 pt-3">
                <h4 class="etiquette">Portail des parents</h4>
                @if ($parent->user_id)
                    <p class="text-sm text-gris-600">
                        Un compte est ouvert sur <span class="font-medium">{{ $parent->email }}</span>.
                    </p>
                @else
                    <p class="text-sm text-gris-400">
                        Aucun accès. Renseignez une adresse e-mail dans la fiche pour en ouvrir un.
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Enfants ----------------------------------------------------------- --}}
    <div x-show="onglet === 'enfants'" x-cloak>
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Enfants rattachés</h3>
                <a href="{{ route('parents.edit', $parent->id) }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                    Gérer les liens
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Classe</th>
                            <th>Lien de parenté</th>
                            <th>Rôle auprès de l’enfant</th>
                            <th class="w-24"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enfants as $enfant)
                            @php($inscription = $enfant->enrollments->first())
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-avatar :personne="$enfant"/>
                                        <div class="min-w-0">
                                            <a href="{{ route('students.show', $enfant->id) }}"
                                               class="block truncate font-semibold text-gris-900 hover:text-ogar-700">
                                                {{ $enfant->first_name }} {{ $enfant->last_name }}
                                            </a>
                                            <div class="font-mono text-xs text-gris-400">{{ $enfant->student_id }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    @if ($inscription?->schoolClass)
                                        <div class="whitespace-nowrap font-medium text-gris-800">{{ $inscription->schoolClass->name }}</div>
                                        <div class="whitespace-nowrap text-xs text-gris-400">{{ $inscription->academicYear->name ?? '' }}</div>
                                    @else
                                        <span class="text-gris-400">Non inscrit</span>
                                    @endif
                                </td>

                                <td>
                                    <x-puce :couleur="$teintesLien[$enfant->pivot->relationship_type] ?? 'slate'" class="whitespace-nowrap">
                                        {{ $libellesLien[$enfant->pivot->relationship_type] ?? ucfirst($enfant->pivot->relationship_type) }}
                                    </x-puce>
                                </td>

                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @if ($enfant->pivot->is_primary_contact)
                                            <x-puce couleur="ogar" class="whitespace-nowrap">Contact principal</x-puce>
                                        @endif
                                        @if ($enfant->pivot->lives_with_student)
                                            <x-puce couleur="sky" class="whitespace-nowrap">Vit avec</x-puce>
                                        @endif
                                        @if ($enfant->pivot->can_pickup)
                                            <x-puce couleur="emerald" class="whitespace-nowrap">Récupération</x-puce>
                                        @endif
                                        @if (! $enfant->pivot->is_primary_contact && ! $enfant->pivot->lives_with_student && ! $enfant->pivot->can_pickup)
                                            <span class="text-xs text-gris-400">Aucun</span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="flex justify-end">
                                        <a href="{{ route('students.show', $enfant->id) }}" class="bouton-mini">Fiche</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="5" message="Ce parent n’est rattaché à aucun élève."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
