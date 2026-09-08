@extends('layouts.app')

@section('titre', 'Bulletin de '.$student->full_name)
@section('sous-titre', ($class->name ?? 'classe inconnue').' — '.($academicYear->name ?? 'année en cours'))

@section('actions-entete')
    <a href="{{ route('grades.manage-student', $student->id) }}" class="bouton-secondaire">Gérer les notes</a>
@endsection

@section('contenu')

@php
    $trimestres = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
    $numerotation = ['1er trimestre' => '1ER TRIMESTRE', '2ème trimestre' => '2EME TRIMESTRE', '3ème trimestre' => '3EME TRIMESTRE'];

    $nb = fn ($m) => $m === null || $m === '' || $m === 'N/C' ? '0' : number_format((float) $m, 2, '.', '');
    $vide = fn ($m) => $m === null || $m === '' || $m === 'N/C';

    // Heures d'absence, au format du bulletin : 5h30, 0h00 quand rien n'est releve.
    $heures = fn ($minutes) => intdiv((int) $minutes, 60) . 'h' . str_pad((int) $minutes % 60, 2, '0', STR_PAD_LEFT);

    /*
     * Appreciation visuelle du bulletin : une lettre et une pastille de couleur.
     *   A / vert   — la moyenne atteint 10 et se situe au niveau de la classe
     *   B / orange — la moyenne atteint 10 mais reste sous celle de la classe
     *   C / rouge  — la moyenne est inferieure a 10
     */
    $lettre = function ($m, $classe = null) use ($vide) {
        if ($vide($m)) return null;
        if ($m < 10) return ['C', 'bg-corail-500'];
        if ($classe !== null && $m < $classe) return ['B', 'bg-soleil-500'];

        return ['A', 'bg-emerald-500'];
    };

    // La ligne des totaux et le bilan gardent la fleche d'evolution.
    $fleche = fn ($m) => $vide($m) ? '' : ($m >= 10 ? '⬆' : '⬇');
    $couleurFleche = fn ($m) => $vide($m) ? 'text-gris-400' : ($m >= 10 ? 'text-emerald-600' : 'text-corail-600');

    $appreciation = function ($m) {
        if ($m === null || $m <= 0) return '';
        if ($m >= 16) return 'Excellent';
        if ($m >= 14) return 'Très Bien';
        if ($m >= 12) return 'Bien';
        if ($m >= 10) return 'Assez Bien';
        if ($m >= 8)  return 'Passable';
        return 'Insuffisant';
    };

    // Décision du conseil, telle qu'elle figure sur le document.
    $decision = function ($m) {
        if ($m === null || $m <= 0) return 'Non évalué';
        if ($m >= 10) return 'Admis(e)';
        if ($m >= 8)  return 'Insuffisant - Avertissement';
        return 'Insuffisant - Blâme';
    };

    $moyennesClasse = $classAverages['trimestres'] ?? [];
    $disponibles = collect($trimesterData)->filter(fn ($t) => $t['is_available'] ?? false);

    $ongletInitial = in_array(request('trimestre'), $trimestres, true)
        ? request('trimestre')
        : ($disponibles->keys()->first() ?? $trimestres[0]);

    $etablissement = $schoolName ?? ($schoolSettings->school_name ?? 'Établissement Scolaire');
@endphp

<div x-data="{ trimestre: '{{ $ongletInitial }}' }">

    {{-- Choix du trimestre et export --}}
    <div class="mb-4 flex flex-wrap items-center gap-1 border-b border-gris-200">
        @foreach ($trimestres as $t)
            @php($dispo = $trimesterData[$t]['is_available'] ?? false)
            <button type="button" @click="trimestre = '{{ $t }}'"
                    :class="trimestre === '{{ $t }}' ? 'border-ogar-700 text-ogar-700' : 'border-transparent text-gris-500 hover:text-gris-700'"
                    class="cursor-pointer border-b-2 px-4 py-2 text-sm font-semibold transition">
                {{ $t }}
                @unless ($dispo)<span class="text-[10px] font-normal text-gris-400">(vide)</span>@endunless
            </button>
        @endforeach

        <div class="ml-auto pb-1">
            @foreach ($trimestres as $t)
                @if ($trimesterData[$t]['is_available'] ?? false)
                    <button type="button" x-show="trimestre === '{{ $t }}'" x-cloak
                            class="bouton-primaire text-xs"
                            data-export-pdf="bulletin-{{ $loop->index }}"
                            {{-- Le bulletin est plus large que haut : en portrait il
                                 n'occupait que le tiers superieur de la feuille. --}}
                            data-orientation="paysage"
                            data-page-unique
                            data-nom-fichier="Bulletin_{{ \Illuminate\Support\Str::slug($student->full_name) }}_T{{ $loop->index + 1 }}.pdf">
                        Télécharger en PDF
                    </button>
                @endif
            @endforeach
        </div>
    </div>

    @foreach ($trimestres as $indexTrimestre => $t)
        @php($donnees = $trimesterData[$t] ?? [])
        @php($matieres = collect($donnees['subjects'] ?? []))
        @php($moyenneEleve = ($donnees['is_available'] ?? false) ? $donnees['cumulative_score'] : null)
        @php($profil = $donnees['class_profile'] ?? [])
        @php($totalCoefficients = $matieres->sum(fn ($m) => (float) ($m['coefficient'] ?? 1)))
        @php($totalPoints = $matieres->sum(fn ($m) => (float) ($m['average'] ?? 0) * (float) ($m['coefficient'] ?? 1)))

        <div x-show="trimestre === '{{ $t }}'" x-cloak>
            @if ($matieres->isEmpty())
                <div class="carte">
                    <div class="carte-entete">
                        <h3 class="text-sm font-semibold text-gris-900">{{ $t }}</h3>
                        <a href="{{ route('grades.create', ['student_id' => $student->id]) }}" class="bouton-primaire text-xs">
                            Saisir des notes
                        </a>
                    </div>
                    <div class="p-6">
                        <x-vide :message="'Trimestre non disponible : aucune note pour le '.$t.'.'"/>
                    </div>
                </div>
            @else

            {{-- ------------------------------------------------------------------
                 Le bulletin. Ce bloc est celui que html2canvas photographie :
                 le PDF est exactement ce qui s'affiche ici.
                 ------------------------------------------------------------------ --}}
            <div id="bulletin-{{ $indexTrimestre }}" class="mx-auto max-w-4xl bg-white p-4 text-[11px] text-gris-900 ring-1 ring-gris-200">

                {{-- En-tête : logo de l'établissement à gauche, sceau de la
                     République à droite. Les emplacements restent visibles
                     quand l'image manque, sinon on croit à un bug d'affichage
                     alors qu'il suffit de la téléverser dans les paramètres. --}}
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-2">
                        @if ($schoolSettings->logo_url ?? null)
                            <img src="{{ $schoolSettings->logo_url }}" alt="Logo de l’établissement"
                                 class="h-12 w-12 shrink-0 object-contain">
                        @else
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded border border-dashed border-gris-300 text-center text-[7px] leading-tight text-gris-400">
                                Logo<br>établissement
                            </span>
                        @endif
                        <div class="leading-tight">
                            <p class="text-[10px] text-gris-700">Ministère de l’Éducation Nationale</p>
                            <p class="text-[11px] font-bold uppercase text-gris-900">{{ $etablissement }}</p>
                            <p class="text-[9px] text-gris-600">
                                {{-- Les colonnes s'appellent school_bp / school_phone :
                                     po_box et phone n'existent pas, cette ligne
                                     restait donc vide. --}}
                                @if ($schoolSettings->school_bp ?? null) {{ $schoolSettings->school_bp }} @endif
                                @if ($schoolSettings->school_phone ?? null) Tél/fax : {{ $schoolSettings->school_phone }} @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-2">
                        @if ($schoolSettings->seal_url ?? null)
                            <img src="{{ $schoolSettings->seal_url }}" alt="Sceau de la République"
                                 class="h-12 w-16 shrink-0 object-contain">
                        @else
                            <span class="flex h-12 w-16 shrink-0 items-center justify-center rounded border border-dashed border-gris-300 text-center text-[7px] leading-tight text-gris-400">
                                Sceau de la<br>République
                            </span>
                        @endif
                        <p class="whitespace-nowrap text-[10px] leading-tight text-gris-700">
                            Année Scolaire : {{ $academicYear->name ?? '—' }}
                        </p>
                    </div>
                </div>

                <p class="mt-2 text-center text-[13px] font-bold uppercase tracking-wide text-gris-900">
                    Bulletin – {{ $numerotation[$t] }}
                    {{-- Le 3e trimestre est celui qui porte la decision de passage. --}}
                    @if ($indexTrimestre === 2) &gt;&gt; @endif
                </p>

                {{-- Encadré de l'élève --}}
                <div class="mt-2 flex border border-gris-800">
                    <div class="flex w-24 shrink-0 items-center justify-center border-r border-gris-800 p-1">
                        @if ($student->photo)
                            <img src="{{ asset('storage/'.$student->photo) }}" alt="" class="h-16 w-16 object-cover">
                        @else
                            <svg class="h-14 w-14 text-ogar-700" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.42 0-8 2.24-8 5v3h16v-3c0-2.76-3.58-5-8-5z"/>
                            </svg>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="border-b border-gris-800 px-2 py-1 text-center text-[12px] font-bold">
                            {{ mb_strtoupper($student->last_name) }} {{ $student->first_name }}
                            [ID-UA : {{ $studentInfo['matricule'] ?? $student->student_id }}]
                        </p>

                        <div class="grid grid-cols-4 border-b border-gris-800 text-[10px] [&>div]:border-r [&>div]:border-gris-400 [&>div]:px-2 [&>div]:py-1 [&>div:last-child]:border-r-0">
                            <div><span class="font-semibold">Né(e) le :</span> {{ $student->date_of_birth?->format('d-m-Y') ?? 'N/C' }}</div>
                            <div><span class="font-semibold">Lieu de naissance:</span> {{ $student->place_of_birth ?: 'N/C' }}</div>
                            <div><span class="font-semibold">Sexe :</span> {{ $student->gender === 'male' ? 'Masculin' : ($student->gender === 'female' ? 'Féminin' : 'N/C') }}</div>
                            <div><span class="font-semibold">Statut:</span> [{{ mb_substr($student->current_status ?? 'N', 0, 1) }}]</div>
                        </div>

                        <div class="grid grid-cols-4 text-[10px] [&>div]:border-r [&>div]:border-gris-400 [&>div]:px-2 [&>div]:py-1 [&>div:last-child]:border-r-0">
                            <div><span class="font-semibold">Classe :</span> {{ $class->name ?? 'N/C' }}</div>
                            <div><span class="font-semibold">Effectif :</span> {{ $totalStudents ?: 'N/C' }}</div>
                            <div><span class="font-semibold">Masculin :</span> {{ $maleStudents }} <span class="font-semibold">Féminin :</span> {{ $femaleStudents }}</div>
                            <div><span class="font-semibold">Nationalité :</span> Gabonaise</div>
                        </div>
                    </div>
                </div>

                {{-- Tableau des disciplines --}}
                <table class="mt-2 w-full border-collapse border border-gris-800 text-[10px]">
                    <thead>
                        <tr class="bg-gris-100 text-center font-bold">
                            <th rowspan="2" class="border border-gris-800 px-1 py-1 text-left">DISCIPLINES</th>
                            <th colspan="2" class="border border-gris-800 px-1 py-1">MOYENNE</th>
                            <th rowspan="2" class="border border-gris-800 px-1 py-1">COEF</th>
                            <th rowspan="2" class="border border-gris-800 px-1 py-1">NOTE X<br>COEF</th>
                            <th rowspan="2" class="border border-gris-800 px-1 py-1">RANG</th>
                            <th rowspan="2" class="border border-gris-800 px-1 py-1">ABSENCES</th>
                            <th rowspan="2" class="border border-gris-800 px-1 py-1">Appréciation</th>
                            <th rowspan="2" class="border border-gris-800 px-1 py-1">Professeur</th>
                        </tr>
                        <tr class="bg-gris-100 text-center font-bold">
                            <th class="border border-gris-800 px-1 py-0.5">Apprenant</th>
                            <th class="border border-gris-800 px-1 py-0.5">Classe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($matieres as $matiere)
                            @php($m = $matiere['average'] ?? null)
                            @php($coefficient = (float) ($matiere['coefficient'] ?? 1))
                            <tr>
                                <td class="border border-gris-800 px-1 py-0.5">{{ $matiere['name'] ?? '' }}</td>
                                @php($marque = $lettre($m, $matiere['class_average'] ?? null))
                                <td class="whitespace-nowrap border border-gris-800 px-1 py-0.5 text-center font-semibold">
                                    @if ($marque)
                                        <span class="mr-0.5 text-[9px] font-bold text-gris-700">{{ $marque[0] }}</span>
                                        <span class="mr-1 inline-block h-2.5 w-2.5 rounded-full align-middle {{ $marque[1] }}"></span>
                                    @endif
                                    {{ $nb($m) }}
                                </td>
                                <td class="border border-gris-800 px-1 py-0.5 text-center">{{ $nb($matiere['class_average'] ?? null) }}</td>
                                <td class="border border-gris-800 px-1 py-0.5 text-center">{{ rtrim(rtrim(number_format($coefficient, 1, '.', ''), '0'), '.') }}</td>
                                <td class="border border-gris-800 px-1 py-0.5 text-center">{{ $nb(($m ?? 0) * $coefficient) }}</td>
                                <td class="border border-gris-800 px-1 py-0.5 text-center">{{ $matiere['rank'] ?? 'N/C' }}</td>
                                <td class="border border-gris-800 px-1 py-0.5 text-center">{{ $heures($matiere['absences'] ?? 0) }}</td>
                                <td class="border border-gris-800 px-1 py-0.5">{{ $matiere['appreciation'] ?? $appreciation($m) }}</td>
                                <td class="border border-gris-800 px-1 py-0.5">
                                    {{ ($matiere['teacher_name'] ?? 'N/A') !== 'N/A' ? $matiere['teacher_name'] : '' }}
                                </td>
                            </tr>
                        @endforeach

                        <tr class="bg-gris-100 font-bold">
                            <td class="border border-gris-800 px-1 py-1">TOTAUX</td>
                            <td colspan="2" class="border border-gris-800"></td>
                            <td class="border border-gris-800 px-1 py-1 text-center">{{ rtrim(rtrim(number_format($totalCoefficients, 1, '.', ''), '0'), '.') }}</td>
                            <td class="border border-gris-800 px-1 py-1 text-center">{{ number_format($totalPoints, 2, '.', '') }}</td>
                            <td class="border border-gris-800 px-1 py-1 text-center">{{ $donnees['rank'] ?? 'N/C' }}</td>
                            <td class="border border-gris-800 px-1 py-1 text-center">{{ $heures($donnees['absences'] ?? 0) }}</td>
                            <td colspan="2" class="border border-gris-800 px-1 py-1 text-right">
                                Moyenne: <span class="text-[12px]">{{ $nb($moyenneEleve) }}</span>
                                <span class="{{ $couleurFleche($moyenneEleve) }}">{{ $fleche($moyenneEleve) }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                {{-- Profil de la classe et bilan --}}
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <div class="border border-gris-800">
                        <p class="border-b border-gris-800 bg-gris-100 px-2 py-1 text-center text-[10px] font-bold">
                            PROFIL DE LA CLASSE
                        </p>
                        <table class="w-full text-[10px]">
                            <tbody>
                                @foreach ([
                                    'Forte moyenne trim' => $profil['forte'] ?? null,
                                    'Faible moyenne trim' => $profil['faible'] ?? null,
                                    'Moyenne de la classe' => $profil['moyenne'] ?? ($moyennesClasse[$t] ?? null),
                                ] as $libelle => $valeur)
                                    <tr>
                                        <td class="px-2 py-0.5">{{ $libelle }}</td>
                                        <td class="px-2 py-0.5 text-right font-semibold">{{ $nb($valeur) }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td class="px-2 py-0.5 font-semibold">PROFESSEUR PRINCIPAL</td>
                                    <td class="px-2 py-0.5 text-right">
                                        {{ $principalTeacherName !== 'N/C' ? $principalTeacherName : '' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="border border-gris-800">
                        <p class="border-b border-gris-800 bg-gris-100 px-2 py-1 text-center text-[10px] font-bold">BILAN</p>
                        <table class="w-full text-[10px]">
                            <thead>
                                <tr class="border-b border-gris-400 font-semibold">
                                    <th class="px-2 py-0.5 text-left">Moyenne</th>
                                    <th class="px-2 py-0.5 text-center">Apprenant</th>
                                    <th class="px-2 py-0.5 text-center">Classe</th>
                                    <th class="px-2 py-0.5 text-center">Rang</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($trimestres as $rang => $autre)
                                    @php($lot = $trimesterData[$autre] ?? [])
                                    {{-- Un bulletin ne montre que les trimestres déjà écoulés :
                                         ceux à venir restent à zéro, comme sur le document. --}}
                                    @php($dispoAutre = ($lot['is_available'] ?? false) && $rang <= $indexTrimestre)
                                    <tr>
                                        <td class="px-2 py-0.5">{{ $rang + 1 }}{{ $rang === 0 ? 'er' : 'e' }} TRIMESTRE</td>
                                        <td class="whitespace-nowrap px-2 py-0.5 text-center font-semibold">
                                            {{ $dispoAutre ? $nb($lot['cumulative_score']) : '0' }}
                                            @if ($dispoAutre && $rang > 0)
                                                @php($avant = $trimesterData[$trimestres[$rang - 1]] ?? [])
                                                @if ($avant['is_available'] ?? false)
                                                    @php($ecart = $lot['cumulative_score'] - $avant['cumulative_score'])
                                                    <span class="text-[11px] {{ $ecart > 0 ? 'text-emerald-600' : ($ecart < 0 ? 'text-corail-600' : 'text-gris-400') }}">
                                                        {{ $ecart > 0 ? '⬆' : ($ecart < 0 ? '⬇' : '→') }}
                                                    </span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-2 py-0.5 text-center">
                                            {{ $dispoAutre ? $nb($lot['class_average'] ?? ($moyennesClasse[$autre] ?? null)) : '0' }}
                                        </td>
                                        <td class="px-2 py-0.5 text-center">{{ $dispoAutre ? ($lot['rank'] ?? '0') : '0' }}</td>
                                    </tr>
                                @endforeach

                                {{-- La moyenne annuelle ne se calcule qu'une fois l'année
                                     terminée : elle reste vide sur les deux premiers bulletins. --}}
                                @php($ecoules = $disponibles->filter(fn ($x, $cle) => array_search($cle, $trimestres, true) <= $indexTrimestre))
                                @php($annuelle = $indexTrimestre === 2 && $ecoules->isNotEmpty())
                                @php($rangs = $ecoules->pluck('rank')->filter(fn ($r) => is_numeric($r)))
                                <tr class="border-t border-gris-400 font-semibold">
                                    <td class="px-2 py-0.5">MOYENNE ANNUELLE</td>
                                    <td class="px-2 py-0.5 text-center">
                                        {{ $annuelle ? $nb($ecoules->avg(fn ($x) => $x['cumulative_score'])) : '' }}
                                    </td>
                                    <td class="px-2 py-0.5 text-center">
                                        {{ $annuelle ? $nb($ecoules->avg(fn ($x) => $x['class_average'] ?? null)) : '' }}
                                    </td>
                                    <td class="px-2 py-0.5 text-center">
                                        {{ $annuelle && $rangs->isNotEmpty() ? (int) round($rangs->avg()) : '' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Décision du conseil de classe --}}
                <div class="mt-2 border border-gris-800">
                    <p class="border-b border-gris-800 bg-gris-100 px-2 py-1 text-center text-[10px] font-bold">
                        APPRÉCIATION OU DÉCISION DU CONSEIL DE CLASSE
                    </p>

                    <div class="grid grid-cols-3 border-b border-gris-400 text-[10px] [&>div]:border-r [&>div]:border-gris-400 [&>div]:px-2 [&>div]:py-1 [&>div:last-child]:border-r-0">
                        <div>Conduite : <span class="font-semibold">NC</span></div>
                        <div>Travail : <span class="font-semibold">{{ $appreciation($moyenneEleve) }}</span></div>
                        <div>Fréquentation : <span class="font-semibold">À suivre</span></div>
                    </div>

                    <div class="relative px-2 py-6 text-center">
                        <p class="text-[20px] font-bold {{ ($moyenneEleve ?? 0) >= 10 ? 'text-emerald-700' : 'text-corail-700' }}">
                            {{ $decision($moyenneEleve) }}
                        </p>

                        {{-- Au 3e trimestre, la decision indique la classe d'accueil. --}}
                        @if ($indexTrimestre === 2 && ($moyenneEleve ?? 0) >= 10 && $classeDePassage)
                            <p class="text-[13px] font-bold uppercase text-gris-900">{{ $classeDePassage }}</p>
                        @endif

                        <p class="mt-1 text-[10px] text-gris-600">{{ now()->format('d-m-Y') }}</p>

                        <div class="absolute right-4 top-2 text-center text-[10px]">
                            <p class="italic text-gris-700">Le Proviseur,</p>
                            <div class="mt-8 w-32 border-t border-gris-400"></div>
                        </div>
                    </div>
                </div>

                {{-- Pied : code du bulletin et mention du conseil --}}
                @php($codeBulletin = date('Y').str_pad($student->id, 6, '0', STR_PAD_LEFT).str_pad($indexTrimestre + 1, 4, '0', STR_PAD_LEFT))
                <div class="mt-2 flex items-end justify-between gap-4">
                    <div>
                        <div class="flex h-10 items-end gap-px" aria-hidden="true">
                            @foreach (str_split($codeBulletin) as $chiffre)
                                <span class="w-1 bg-gris-900" style="height: {{ 45 + ((int) $chiffre * 5) }}%"></span>
                                <span class="w-px" style="height: 100%"></span>
                            @endforeach
                        </div>
                        <p class="mt-0.5 font-mono text-[9px] text-gris-700">*{{ $codeBulletin }}*</p>
                    </div>

                    <p class="pb-1 text-[9px] text-gris-600">* Conseil de Classe</p>

                    <p class="pb-1 text-[9px] text-gris-500">{{ $etablissement }}</p>
                </div>
            </div>
            @endif
        </div>
    @endforeach
</div>

@endsection
