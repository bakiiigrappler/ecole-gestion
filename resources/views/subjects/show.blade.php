@extends('layouts.app')

@section('titre', $subject->name)
@section('sous-titre', $subject->code.' — coefficient '.(int) $subject->coefficient)

@section('actions-entete')
    <a href="{{ route('subjects.edit', $subject->id) }}" class="bouton-secondaire">Modifier</a>
    <a href="{{ route('subjects.index') }}" class="bouton-primaire">Toutes les matières</a>
@endsection

@section('contenu')

@php
    $libellesCycle = ['primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    $teintesCycle = ['primaire' => 'emerald', 'college' => 'sky', 'lycee' => 'violet'];

    $creneaux = $subject->schedules->sortBy(['day_of_week', 'start_time']);
    $jours = [
        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
        'monday' => 'Lundi', 'tuesday' => 'Mardi', 'wednesday' => 'Mercredi',
        'thursday' => 'Jeudi', 'friday' => 'Vendredi', 'saturday' => 'Samedi',
    ];

    $moyenne = $totalNotes > 0
        ? $notes->sum(fn ($n) => $n->moyenne * $n->total) / $totalNotes
        : null;

    $onglets = [
        'detail' => 'Détail',
        'enseignants' => 'Enseignants ('.$subject->teachers->count().')',
        'resultats' => 'Résultats',
        'creneaux' => 'Créneaux ('.$creneaux->count().')',
    ];

    $ongletInitial = request('onglet', 'detail');
    $ongletInitial = array_key_exists($ongletInitial, $onglets) ? $ongletInitial : 'detail';
@endphp

<div x-data="{ onglet: '{{ $ongletInitial }}' }">

    {{-- ------------------------------------------------------------------
         En-tête de la matière
         ------------------------------------------------------------------ --}}
    <div class="carte mb-4 p-5">
        <div class="flex flex-wrap items-start gap-5">
            <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-ogar-50 ring-1 ring-ogar-100">
                <svg class="h-9 w-9 text-ogar-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                </svg>
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-bold text-gris-900">{{ $subject->name }}</h2>
                    <x-puce :couleur="$teintesCycle[$subject->cycle] ?? 'slate'">
                        {{ $libellesCycle[$subject->cycle] ?? ucfirst($subject->cycle) }}
                    </x-puce>
                    @if (! $subject->is_active)
                        <x-puce couleur="rose">Inactive</x-puce>
                    @endif
                    @if ($subject->teachers->isEmpty())
                        <x-puce couleur="rose">Sans enseignant</x-puce>
                    @endif
                </div>

                <p class="mt-1 font-mono text-sm text-gris-500">{{ $subject->code }}</p>

                <div class="mt-3 flex flex-wrap gap-x-8 gap-y-1.5 text-sm [&>div]:whitespace-nowrap">
                    <div>
                        <span class="text-gris-400">Coefficient</span>
                        <span class="font-medium">{{ (int) $subject->coefficient }}</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Séries</span>
                        <span class="font-medium">
                            @if ($subject->cycle !== 'lycee')
                                toutes
                            @else
                                {{ empty($subject->series) ? 'aucune' : implode(', ', $subject->series) }}
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="text-gris-400">Créneaux</span>
                        <span class="font-medium">{{ $creneaux->count() }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-lg {{ $subject->teachers->isEmpty() ? 'bg-corail-50' : 'bg-ogar-50' }} px-4 py-3">
                    <div class="text-2xl font-bold {{ $subject->teachers->isEmpty() ? 'text-corail-700' : 'text-ogar-700' }}">
                        {{ $subject->teachers->count() }}
                    </div>
                    <div class="text-[10px] font-semibold uppercase {{ $subject->teachers->isEmpty() ? 'text-corail-600' : 'text-ogar-600' }}">enseignants</div>
                </div>
                <div class="rounded-lg bg-gris-50 px-4 py-3">
                    <div class="text-2xl font-bold text-gris-700">{{ $totalNotes }}</div>
                    <div class="text-[10px] font-semibold uppercase text-gris-500">notes</div>
                </div>
                <div class="rounded-lg bg-soleil-50 px-4 py-3">
                    <div class="text-2xl font-bold text-soleil-700">
                        {{ $moyenne !== null ? number_format($moyenne, 1, ',', ' ') : '—' }}
                    </div>
                    <div class="text-[10px] font-semibold uppercase text-soleil-600">moyenne</div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 border-t border-gris-100 pt-4">
            <a href="{{ route('subjects.edit', $subject->id) }}" class="bouton-secondaire text-xs">Modifier la matière</a>
            <a href="{{ route('schedules.index') }}" class="bouton-secondaire text-xs">Emplois du temps</a>

            <x-confirmation :action="route('subjects.destroy', $subject->id)" methode="DELETE"
                            titre="Supprimer cette matière ?"
                            :message="$totalNotes > 0
                                ? 'Cette matière porte '.$totalNotes.' note(s) : la suppression sera refusée. Désactivez-la plutôt.'
                                : 'La matière '.$subject->name.' sera définitivement retirée du programme.'"
                            confirmer="Supprimer"
                            bouton="bouton-secondaire ml-auto text-xs text-corail-600 hover:bg-corail-50">
                Supprimer la matière
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

    {{-- Détail ------------------------------------------------------------ --}}
    <div x-show="onglet === 'detail'" class="grid gap-4 lg:grid-cols-3">
        <div class="carte lg:col-span-2">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Fiche de la matière</h3>
            </div>
            <dl class="grid grid-cols-2 gap-y-2.5 p-5 text-sm">
                <dt class="text-gris-400">Nom</dt>
                <dd class="font-medium text-gris-900">{{ $subject->name }}</dd>

                <dt class="text-gris-400">Code</dt>
                <dd class="font-mono font-medium text-gris-900">{{ $subject->code }}</dd>

                <dt class="text-gris-400">Cycle</dt>
                <dd class="font-medium text-gris-900">{{ $libellesCycle[$subject->cycle] ?? ucfirst($subject->cycle) }}</dd>

                <dt class="text-gris-400">Coefficient</dt>
                <dd class="font-medium text-gris-900">{{ (int) $subject->coefficient }}</dd>

                <dt class="text-gris-400">Statut</dt>
                <dd><x-puce :couleur="$subject->is_active ? 'emerald' : 'slate'">{{ $subject->is_active ? 'Active' : 'Inactive' }}</x-puce></dd>

                <dt class="text-gris-400">Créée le</dt>
                <dd class="font-medium text-gris-900">{{ $subject->created_at?->translatedFormat('j F Y') ?? '—' }}</dd>
            </dl>

            @if ($subject->description)
                <div class="border-t border-gris-100 p-5">
                    <p class="text-xs font-semibold uppercase text-gris-400">Description</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-gris-700">{{ $subject->description }}</p>
                </div>
            @endif
        </div>

        <div class="carte">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Séries concernées</h3>
            </div>
            <div class="p-5">
                @if ($subject->cycle !== 'lycee')
                    <p class="text-sm text-gris-500">
                        Les séries n’existent qu’au lycée : cette matière s’applique à toutes les classes
                        de {{ strtolower($libellesCycle[$subject->cycle] ?? $subject->cycle) }}.
                    </p>
                @elseif (empty($subject->series))
                    <p class="text-sm text-corail-700">
                        Aucune série n’est renseignée : cette matière n’apparaîtra dans aucun programme de lycée.
                    </p>
                @else
                    <div class="flex flex-wrap gap-2">
                        @foreach ($subject->series as $lettre)
                            <span class="rounded-full bg-ogar-50 px-2.5 py-1 text-xs font-semibold text-ogar-700">Série {{ $lettre }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Enseignants -------------------------------------------------------- --}}
    <div x-show="onglet === 'enseignants'" x-cloak class="carte overflow-hidden">
        <div class="carte-entete">
            <div>
                <h3 class="text-sm font-semibold text-gris-900">Enseignants rattachés</h3>
                <p class="mt-0.5 text-xs text-gris-400">
                    Ceux qui peuvent assurer cette matière, d’après la table de liaison.
                </p>
            </div>
            <a href="{{ route('teachers.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                Module Enseignants
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Enseignant</th>
                        <th>Matricule</th>
                        <th>Cycle</th>
                        <th class="text-center">Statut</th>
                        <th class="text-right">Fiche</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subject->teachers as $enseignant)
                        <tr>
                            <td class="font-medium text-gris-800">{{ $enseignant->full_name }}</td>
                            <td class="font-mono text-xs text-gris-500">{{ $enseignant->employee_id }}</td>
                            <td class="text-gris-600">{{ ucfirst($enseignant->cycle ?? '—') }}</td>
                            <td class="text-center">
                                <x-puce :couleur="$enseignant->status === 'active' ? 'emerald' : 'slate'">
                                    {{ $enseignant->status === 'active' ? 'Actif' : 'Inactif' }}
                                </x-puce>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('teachers.show', $enseignant->id) }}" class="bouton-mini">Consulter</a>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="5" message="Aucun enseignant n’est rattaché à cette matière."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Résultats ---------------------------------------------------------- --}}
    <div x-show="onglet === 'resultats'" x-cloak class="carte overflow-hidden">
        <div class="carte-entete">
            <div>
                <h3 class="text-sm font-semibold text-gris-900">Résultats par trimestre</h3>
                <p class="mt-0.5 text-xs text-gris-400">
                    Moyennes ramenées sur 20, toutes classes confondues.
                </p>
            </div>
            <a href="{{ route('grades.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                Module Notes
            </a>
        </div>

        @if ($totalNotes === 0)
            <div class="p-6">
                <x-vide message="Aucune note n’a encore été saisie dans cette matière."/>
            </div>
        @else
            <div class="divide-y divide-gris-100">
                @foreach ($notes as $ligne)
                    <div class="px-5 py-4">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-sm font-medium text-gris-800">{{ $ligne->term }}</span>
                            <span class="text-sm font-bold {{ $ligne->moyenne >= 12 ? 'text-emerald-700' : ($ligne->moyenne >= 10 ? 'text-soleil-700' : 'text-corail-700') }}">
                                {{ number_format($ligne->moyenne, 2, ',', ' ') }}<span class="text-xs font-normal text-gris-400">/20</span>
                            </span>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gris-100">
                            <div class="h-full rounded-full {{ $ligne->moyenne >= 12 ? 'bg-emerald-500' : ($ligne->moyenne >= 10 ? 'bg-soleil-500' : 'bg-corail-500') }}"
                                 style="width: {{ min(round($ligne->moyenne / 20 * 100), 100) }}%"></div>
                        </div>
                        <div class="mt-1 text-[11px] text-gris-400">{{ $ligne->total }} note(s)</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Créneaux ----------------------------------------------------------- --}}
    <div x-show="onglet === 'creneaux'" x-cloak class="carte overflow-hidden">
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
                        <th>Enseignant</th>
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
                            <td>{{ $creneau->teacher?->full_name ?? '—' }}</td>
                            <td class="text-gris-500">{{ $creneau->room ?: '—' }}</td>
                        </tr>
                    @empty
                        <x-vide :colonnes="5" message="Aucun créneau planifié pour cette matière."/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
