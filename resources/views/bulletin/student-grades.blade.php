@extends('layouts.app')

@section('titre', 'Relevé de notes — '.$student->full_name)
@section('sous-titre', ($inscription->schoolClass->name ?? 'Classe non renseignée')
    .($annee ? ' · '.$annee->name : '')
    .' · '.$profil['notes'].' note(s)')

@section('actions-entete')
    @if ($dossier || in_array(auth()->user()?->role, ['student', 'parent'], true))
        <a href="{{ route('orientation.mon-dossier', ['eleve' => $student->id]) }}" class="bouton-secondaire">
            Orientation
        </a>
    @endif
    <a href="{{ route('grades.bulletin', $student->id) }}" class="bouton-primaire">Le bulletin</a>
@endsection

@section('contenu')

@php
    $note = fn ($v) => \App\Support\Orientation\ProfilEleve::nombre($v);
    $encre = fn ($v) => $v === null ? 'text-gris-400' : ($v >= 10 ? 'text-gris-900' : 'text-corail-600');

    $meilleure = $parMatiere->whereNotNull('moyenne')->sortByDesc('moyenne')->first();
    $plusFaible = $parMatiere->whereNotNull('moyenne')->sortBy('moyenne')->first();
@endphp

    {{-- ----------------------------------------------------------------
         Ce que le relevé dit en un coup d'œil
         ---------------------------------------------------------------- --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Moyenne générale" :valeur="$note($profil['generale']).'/20'"
                       :detail="$profil['libelle']" couleur="ogar"/>
        <x-statistique libelle="Disciplines scientifiques" :valeur="$note($profil['sciences']).'/20'"
                       detail="Maths, sciences, technologie" couleur="sky"/>
        <x-statistique libelle="Disciplines littéraires" :valeur="$note($profil['lettres']).'/20'"
                       detail="Français, langues, histoire" couleur="violet"/>
        <x-statistique libelle="Matières suivies" :valeur="$parMatiere->count()"
                       :detail="$profil['notes'].' note(s) saisie(s)'" couleur="slate"/>
    </div>

    @if ($parMatiere->isEmpty())

        <div class="carte mt-6 p-8 text-center">
            <h2 class="text-base font-semibold text-gris-900">Aucune note pour cette année</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed text-gris-600">
                Rien n’a encore été saisi pour {{ $student->first_name }} en
                {{ $annee->name ?? 'l’année en cours' }}. Le relevé se remplira au fil des évaluations.
            </p>
        </div>

    @else

        {{-- Ce que les moyennes racontent --}}
        <div class="carte mt-6 p-5">
            <p class="text-sm leading-relaxed text-gris-700">{{ $profil['lecture'] }}</p>

            @if ($meilleure && $plusFaible && $meilleure['matiere'] !== $plusFaible['matiere'])
                <p class="mt-2 text-[11px] text-gris-500">
                    Point fort : <span class="font-medium text-gris-700">{{ $meilleure['matiere'] }}</span>
                    ({{ $note($meilleure['moyenne']) }}/20) ·
                    À travailler : <span class="font-medium text-gris-700">{{ $plusFaible['matiere'] }}</span>
                    ({{ $note($plusFaible['moyenne']) }}/20)
                </p>
            @endif
        </div>

        {{-- ------------------------------------------------------------
             Une ligne par matière, une colonne par trimestre
             ------------------------------------------------------------ --}}
        <div class="carte mt-4 overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Moyennes par matière</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        Chaque note est ramenée sur 20 : les devoirs n’ont pas tous le même barème.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Matière</th>
                            <th class="text-center">Coef.</th>
                            @foreach ($trimestres as $trimestre)
                                <th class="text-center">{{ $trimestre }}</th>
                            @endforeach
                            <th class="text-center">Moyenne</th>
                            <th>Enseignant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($parMatiere as $ligne)
                            <tr>
                                <td class="font-medium text-gris-800">{{ $ligne['matiere'] }}</td>
                                <td class="text-center tabular-nums text-gris-500">{{ $ligne['coefficient'] }}</td>

                                @foreach ($trimestres as $trimestre)
                                    <td class="text-center tabular-nums {{ $encre($ligne['trimestres'][$trimestre] ?? null) }}">
                                        {{ $note($ligne['trimestres'][$trimestre] ?? null) }}
                                    </td>
                                @endforeach

                                <td class="text-center">
                                    <span class="font-bold tabular-nums {{ $encre($ligne['moyenne']) }}">
                                        {{ $note($ligne['moyenne']) }}
                                    </span>
                                </td>

                                <td class="text-[11px] text-gris-500">{{ $ligne['enseignant'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Le détail, note par note
             ------------------------------------------------------------ --}}
        <div class="carte mt-4 overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Le détail des évaluations</h2>
                <span class="text-xs text-gris-400">{{ $profil['notes'] }} note(s)</span>
            </div>

            <div class="divide-y divide-gris-100">
                @foreach ($parMatiere as $ligne)
                    <details>
                        <summary class="flex cursor-pointer items-center justify-between gap-3 px-5 py-3 hover:bg-gris-50">
                            <span class="text-sm font-medium text-gris-800">{{ $ligne['matiere'] }}</span>
                            <span class="text-sm tabular-nums {{ $encre($ligne['moyenne']) }}">
                                {{ $note($ligne['moyenne']) }}/20
                                <span class="text-[11px] font-normal text-gris-400">
                                    · {{ $ligne['notes']->count() }} note(s)
                                </span>
                            </span>
                        </summary>

                        <ul class="divide-y divide-gris-100 bg-gris-50">
                            @foreach ($ligne['notes'] as $evaluation)
                                <li class="flex flex-wrap items-center justify-between gap-3 px-8 py-2 text-sm">
                                    <span class="text-gris-600">{{ $evaluation->term ?: 'Trimestre non précisé' }}</span>

                                    <span class="flex items-center gap-3">
                                        @if ($evaluation->comments)
                                            <span class="max-w-md truncate text-[11px] italic text-gris-500">
                                                {{ $evaluation->comments }}
                                            </span>
                                        @endif

                                        <span class="tabular-nums {{ $evaluation->score / $evaluation->max_score >= 0.5 ? 'text-gris-800' : 'text-corail-600' }}">
                                            {{ rtrim(rtrim(number_format($evaluation->score, 2, ',', ' '), '0'), ',') }}
                                            <span class="text-[11px] text-gris-400">
                                                / {{ rtrim(rtrim(number_format($evaluation->max_score, 2, ',', ' '), '0'), ',') }}
                                            </span>
                                        </span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endforeach
            </div>
        </div>

    @endif

@endsection
