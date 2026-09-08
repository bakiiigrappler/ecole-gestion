@extends('layouts.app')

@section('titre', 'Mes notes')
@section('sous-titre', $notes->count().' note(s) — '.(optional($inscription?->schoolClass)->name ?? 'sans classe').' · '.($annee->name ?? ''))

@section('actions-entete')
    <a href="{{ route('mon-espace') }}" class="bouton-secondaire">Mon tableau de bord</a>
@endsection

@section('contenu')

@php
    $encre = fn ($m) => $m === null
        ? 'text-gris-400'
        : ($m >= 12 ? 'text-emerald-600' : ($m >= 10 ? 'text-soleil-600' : 'text-corail-600'));

    $nombre = fn ($m) => $m === null ? '—' : number_format($m, 2, ',', ' ');
@endphp

    @if ($notes->isEmpty())
        <div class="carte p-8">
            <x-vide message="Aucune note n’a encore été saisie pour vous."/>
            <p class="mt-3 text-center text-xs text-gris-400">
                Vos notes et votre bulletin apparaîtront ici dès que vos enseignants les auront saisies.
            </p>
        </div>
    @else

    {{-- ----------------------------------------------------------------
         Mes moyennes de trimestre, et le bulletin qui va avec
         ---------------------------------------------------------------- --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Moyenne générale" :valeur="$nombre($moyenneGenerale)"
                       :detail="$notes->count().' note(s) sur l’année'" couleur="ogar"/>

        @foreach ($parTrimestre as $trimestre)
            <x-statistique :libelle="$trimestre['trimestre']"
                           :valeur="$nombre($trimestre['moyenne'])"
                           :detail="$trimestre['notes'].' note(s) · '.$trimestre['matieres'].' matière(s)'"
                           :couleur="$trimestre['moyenne'] >= 10 ? 'emerald' : 'corail'"/>
        @endforeach
    </div>

    {{-- ----------------------------------------------------------------
         Mes bulletins
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Mes bulletins</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    Un bulletin par trimestre noté, à consulter ou à télécharger.
                </p>
            </div>
        </div>

        <div class="grid gap-px bg-gris-100 sm:grid-cols-3">
            @foreach ($parTrimestre as $trimestre)
                <div class="flex items-center justify-between gap-3 bg-white px-5 py-4">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gris-900">{{ $trimestre['trimestre'] }}</p>
                        <p class="text-xs {{ $encre($trimestre['moyenne']) }}">
                            {{ $nombre($trimestre['moyenne']) }} / 20
                        </p>
                    </div>

                    <div class="flex shrink-0 gap-1.5">
                        <a href="{{ route('grades.bulletin', ['studentId' => $eleve->id, 'trimester' => $trimestre['trimestre']]) }}"
                           class="bouton-mini">Voir</a>
                        <a href="{{ route('grades.bulletin.pdf', ['studentId' => $eleve->id, 'trimester' => $trimestre['trimestre']]) }}"
                           class="bouton-mini">PDF</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Matière par matière, trimestre par trimestre
         ---------------------------------------------------------------- --}}
    <div class="carte mt-4 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Matière par matière</h2>
            <span class="text-xs text-gris-400">{{ $matieres->count() }} matière(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Matière</th>
                        <th>Enseignant</th>
                        <th class="text-center">Notes</th>
                        @foreach ($termes as $terme)
                            <th class="text-center">{{ $terme }}</th>
                        @endforeach
                        <th class="text-center">Moyenne</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($matieres as $ligne)
                        <tr>
                            <td class="font-medium text-gris-800">{{ $ligne['matiere'] }}</td>
                            <td class="text-gris-600">{{ $ligne['enseignant'] ?? '—' }}</td>
                            <td class="text-center text-gris-600">{{ $ligne['notes'] }}</td>
                            @foreach ($termes as $terme)
                                @php($valeur = $ligne['trimestres'][$terme] ?? null)
                                <td class="text-center tabular-nums {{ $encre($valeur) }}">
                                    {{ $nombre($valeur) }}
                                </td>
                            @endforeach
                            <td class="text-center font-semibold tabular-nums {{ $encre($ligne['moyenne']) }}">
                                {{ $nombre($ligne['moyenne']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Le détail : chaque note, une par une
         ---------------------------------------------------------------- --}}
    <div class="carte mt-4 overflow-hidden" x-data="{ ouvert: false }">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Le détail de mes notes</h2>
                <p class="mt-0.5 text-xs text-gris-400">Chaque évaluation, avec sa note brute.</p>
            </div>
            <button type="button" @click="ouvert = ! ouvert"
                    class="text-xs font-semibold text-ogar-600 hover:underline"
                    x-text="ouvert ? 'Masquer' : 'Afficher'"></button>
        </div>

        <div x-show="ouvert" x-cloak class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Matière</th>
                        <th class="text-center">Trimestre</th>
                        <th class="text-center">Note</th>
                        <th class="text-center">Sur 20</th>
                        <th>Enseignant</th>
                        <th class="text-center">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($notes as $note)
                        @php($sur20 = $note->score / $note->max_score * 20)
                        <tr>
                            <td class="font-medium text-gris-800">{{ $note->subject->name ?? '—' }}</td>
                            <td class="text-center text-gris-600">{{ $note->term ?: '—' }}</td>
                            <td class="text-center tabular-nums text-gris-700">
                                {{ rtrim(rtrim(number_format((float) $note->score, 2, ',', ' '), '0'), ',') }}
                                <span class="text-gris-400">/ {{ (int) $note->max_score }}</span>
                            </td>
                            <td class="text-center font-semibold tabular-nums {{ $encre($sur20) }}">
                                {{ number_format($sur20, 2, ',', ' ') }}
                            </td>
                            <td class="text-gris-600">
                                {{ $note->teacher ? $note->teacher->last_name.' '.$note->teacher->first_name : '—' }}
                            </td>
                            <td class="text-center tabular-nums text-gris-500">
                                {{ $note->created_at?->format('d/m/Y') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @endif

@endsection
