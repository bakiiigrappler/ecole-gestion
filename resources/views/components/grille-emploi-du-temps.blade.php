@props([
    'creneaux',              // collection de Schedule, avec subject / schoolClass / teacher
    'complement' => 'classe', // ce qu'on lit sous la matière : classe | enseignant | salle
    'legende' => true,
])

@php
    /*
     * La grille colorée de la semaine.
     *
     * C'est la lecture naturelle d'un emploi du temps : les jours en colonnes,
     * les heures en lignes, une couleur par matière. Le même composant sert à
     * l'enseignant (qui lit ses classes sous la matière), à l'élève et au
     * parent (qui lisent l'enseignant) — la seule différence est le
     * complément affiché dans la case.
     *
     * La version en noir et blanc, elle, reste le document administratif :
     * c'est l'aperçu, pas la grille de travail.
     */
    $jours = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'];

    $heure = fn ($v) => substr((string) $v, 0, 5);

    // Ne pas dessiner une colonne de samedi vide.
    $jours = array_filter($jours, fn ($n, $j) => $creneaux->contains('day_of_week', $j), ARRAY_FILTER_USE_BOTH)
        ?: [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi'];

    // Les lignes : les plages horaires réellement occupées, dans l'ordre.
    $plages = $creneaux
        ->map(fn ($c) => ['debut' => $heure($c->start_time), 'fin' => $heure($c->end_time)])
        ->unique('debut')
        ->sortBy('debut')
        ->values();

    // Une case se retrouve par (jour, heure de début).
    $grille = $creneaux->keyBy(fn ($c) => $c->day_of_week.'-'.$heure($c->start_time));

    $couleurs = \App\Support\CouleursDesMatieres::table($creneaux->pluck('subject_id'));
    $neutre = \App\Support\CouleursDesMatieres::NEUTRE;

    $matieres = $creneaux->where('type', 'course')
        ->filter(fn ($c) => $c->subject)
        ->unique('subject_id')
        ->sortBy(fn ($c) => $c->subject->name)
        ->values();

    $sousLaMatiere = function ($cours) use ($complement) {
        return match ($complement) {
            'enseignant' => $cours->teacher
                ? $cours->teacher->last_name.' '.mb_substr($cours->teacher->first_name, 0, 1).'.'
                : null,
            'salle' => $cours->room ? 'Salle '.$cours->room : null,
            default => $cours->schoolClass->name ?? null,
        };
    };
@endphp

<div {{ $attributes->merge(['class' => 'overflow-x-auto']) }}>
    <table class="w-full min-w-[680px] table-fixed border-separate border-spacing-1">
        <thead>
            <tr>
                <th class="w-20 rounded-lg bg-gris-100 px-2 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Horaire
                </th>
                @foreach ($jours as $nom)
                    <th class="rounded-lg bg-gris-100 px-2 py-2 text-[11px] font-semibold uppercase tracking-wide text-gris-600">
                        {{ $nom }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($plages as $plage)
                <tr>
                    <td class="rounded-lg bg-gris-50 px-2 py-2 align-middle">
                        <div class="text-xs font-semibold tabular-nums text-gris-700">{{ $plage['debut'] }}</div>
                        <div class="text-[11px] tabular-nums text-gris-400">{{ $plage['fin'] }}</div>
                    </td>

                    @foreach ($jours as $jour => $nom)
                        @php($cours = $grille[$jour.'-'.$plage['debut']] ?? null)

                        @if (! $cours)
                            <td class="rounded-lg border border-dashed border-gris-200 px-2 py-2"></td>
                        @elseif ($cours->type === 'break')
                            <td class="rounded-lg px-2 py-2 text-center align-middle text-[11px] uppercase tracking-wide"
                                style="background:{{ $neutre['fond'] }};color:{{ $neutre['encre'] }}">
                                {{ $cours->title ?: 'Récréation' }}
                            </td>
                        @else
                            @php($teinte = $couleurs[$cours->subject_id] ?? $neutre)

                            <td class="rounded-lg border-l-4 px-2 py-2 align-top"
                                style="background:{{ $teinte['fond'] }};border-color:{{ $teinte['vif'] }};color:{{ $teinte['encre'] }}">
                                <div class="text-xs font-semibold leading-tight">
                                    {{ $cours->subject->name ?? 'Cours' }}
                                </div>

                                @if ($detail = $sousLaMatiere($cours))
                                    <div class="mt-0.5 truncate text-[11px] opacity-80">{{ $detail }}</div>
                                @endif

                                @if ($complement !== 'salle' && $cours->room)
                                    <div class="text-[11px] opacity-60">Salle {{ $cours->room }}</div>
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if ($legende && $matieres->isNotEmpty())
    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1.5 px-1">
        @foreach ($matieres as $cours)
            @php($teinte = $couleurs[$cours->subject_id] ?? $neutre)

            <span class="flex items-center gap-1.5 text-[11px] text-gris-600">
                <span class="h-2.5 w-2.5 rounded-full" style="background:{{ $teinte['vif'] }}"></span>
                {{ $cours->subject->name }}
            </span>
        @endforeach
    </div>
@endif
