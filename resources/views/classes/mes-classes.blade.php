@extends('layouts.app')

@section('titre', 'Mes classes')
@section('sous-titre', $enseignant
    ? $fiches->count().' classe(s) · '.$eleves.' élève(s) — '.($annee->name ?? '')
    : 'Aucune fiche enseignant rattachée à ce compte')

@section('actions-entete')
    <a href="{{ route('schedules.index') }}" class="bouton-secondaire">Mon emploi du temps</a>
    <a href="{{ route('students.index') }}" class="bouton-primaire">Mes élèves</a>
@endsection

@section('contenu')

    @unless ($enseignant)
        <div class="carte p-8">
            <x-vide message="Ce compte n’est rattaché à aucune fiche enseignant."/>
            <p class="mt-3 text-center text-xs text-gris-400">
                L’administration de l’établissement peut faire le rattachement depuis la fiche de l’enseignant.
            </p>
        </div>
    @else

    <div class="grid gap-4 sm:grid-cols-3">
        <x-statistique libelle="Mes classes" :valeur="$fiches->count()"
                       detail="Où vous intervenez" couleur="ogar"/>
        <x-statistique libelle="Mes élèves" :valeur="$eleves"
                       detail="Toutes classes confondues" couleur="emerald"/>
        <x-statistique libelle="Heures par semaine" :valeur="$heures"
                       detail="Cours effectifs" couleur="violet"/>
    </div>

    @if ($fiches->isEmpty())
        <div class="carte mt-6 p-8">
            <x-vide message="Aucune classe ne vous est attribuée cette année."/>
            <p class="mt-3 text-center text-xs text-gris-400">
                Vos classes se déduisent de l’emploi du temps : elles apparaîtront dès qu’une heure vous sera affectée.
            </p>
        </div>
    @else

    {{-- ----------------------------------------------------------------
         Une carte par classe : ce que j'y enseigne, et ce que j'y fais.
         ---------------------------------------------------------------- --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
        @foreach ($fiches as $fiche)
            @php($classe = $fiche['classe'])

            <div class="carte flex flex-col overflow-hidden">
                <div class="flex items-start justify-between gap-3 border-b border-gris-100 px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-semibold text-gris-900">{{ $classe->name }}</h2>
                        <p class="mt-0.5 truncate text-xs text-gris-500">
                            {{ $classe->getSafeLevelName() ?? '—' }}
                        </p>
                    </div>

                    @if ($fiche['jeSuisPrincipal'])
                        <x-puce couleur="ogar">Professeur principal</x-puce>
                    @endif
                </div>

                <div class="grid grid-cols-3 divide-x divide-gris-100 border-b border-gris-100 text-center">
                    <div class="px-2 py-3">
                        <p class="text-lg font-bold tabular-nums text-gris-900">{{ $classe->effectif }}</p>
                        <p class="text-[11px] uppercase tracking-wide text-gris-400">Élèves</p>
                    </div>
                    <div class="px-2 py-3">
                        <p class="text-lg font-bold tabular-nums text-gris-900">{{ $fiche['heures'] }}</p>
                        <p class="text-[11px] uppercase tracking-wide text-gris-400">Heures</p>
                    </div>
                    <div class="px-2 py-3">
                        <p class="text-lg font-bold tabular-nums text-gris-900">{{ count($fiche['jours']) }}</p>
                        <p class="text-[11px] uppercase tracking-wide text-gris-400">Jours</p>
                    </div>
                </div>

                <div class="flex-1 space-y-3 px-5 py-4 text-sm">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-400">
                            Ma ou mes matières
                        </p>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            @forelse ($fiche['matieres'] as $matiere)
                                <span class="rounded-full bg-ogar-50 px-2.5 py-1 text-xs font-medium text-ogar-700">
                                    {{ $matiere }}
                                </span>
                            @empty
                                <span class="text-xs text-gris-400">Aucune matière planifiée</span>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-400">
                            Mes jours dans cette classe
                        </p>
                        <p class="mt-1 text-xs text-gris-600">
                            {{ count($fiche['jours']) ? implode(' · ', $fiche['jours']) : 'Aucun créneau' }}
                        </p>
                    </div>

                    @unless ($fiche['jeSuisPrincipal'])
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-400">
                                Professeur principal
                            </p>
                            <p class="mt-1 text-xs text-gris-600">
                                {{ $fiche['principal']
                                    ? $fiche['principal']->last_name.' '.$fiche['principal']->first_name
                                    : 'Non désigné' }}
                            </p>
                        </div>
                    @endunless
                </div>

                <div class="flex flex-wrap gap-1.5 border-t border-gris-100 bg-gris-50 px-5 py-3">
                    <a href="{{ route('students.index', ['class' => $classe->id]) }}" class="bouton-mini">Élèves</a>
                    <a href="{{ route('classes.fiche', $classe->id) }}" class="bouton-mini">Fiche de classe</a>
                    <a href="{{ route('attendances.manage', ['class' => $classe->id, 'date' => now()->toDateString()]) }}"
                       class="bouton-mini">Appel</a>
                    <a href="{{ route('attendances.reports', $classe->id) }}" class="bouton-mini">Assiduité</a>
                    <a href="{{ route('schedules.index', ['classe' => $classe->id]) }}" class="bouton-mini">Emploi du temps</a>
                </div>
            </div>
        @endforeach
    </div>

    @endif
    @endunless

@endsection
