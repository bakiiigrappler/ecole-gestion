@extends('layouts.app')

@section('titre', 'Bulletin — '.$student->first_name.' '.$student->last_name)
@section('sous-titre', ($currentEnrollment->schoolClass->name ?? '—').' · '.($isAnnual ? 'Bilan annuel' : 'Palier '.$palier).' · '.($academicYear->name ?? ''))

@section('actions-entete')
    <button type="button"
            class="bouton-secondaire"
            data-export-pdf="bulletin-competences"
            data-orientation="paysage"
            data-page-unique
            data-nom-fichier="Bulletin_{{ \Illuminate\Support\Str::slug($student->first_name.' '.$student->last_name) }}{{ $isAnnual ? '_annuel' : '_palier'.$palier }}.pdf">
        Télécharger le bulletin
    </button>
    <a href="{{ route('competency-evaluations.bulletins', $currentEnrollment->schoolClass->id) }}" class="bouton-primaire">
        Retour à la classe
    </a>
@endsection

@section('contenu')

@php
    /*
     * Le document precedent posait chaque critere dans sa propre colonne, ce
     * qui produisait une bande de « C1 C2 C3 » illisible a droite de la page.
     * Ici une ligne par competence : ses criteres tiennent dans une cellule,
     * et la page reste lisible en paysage.
     */
    $libellesMaitrise = [
        'maximale' => 'Maximale',
        'minimale' => 'Minimale',
        'partielle' => 'Partielle',
        'non_maitrise' => 'Non maîtrisé',
    ];

    // Document destine a l'impression : gris, noir et blanc.
    $fondMaitrise = [
        'maximale' => '#ffffff',
        'minimale' => '#f1f5f9',
        'partielle' => '#e2e8f0',
        'non_maitrise' => '#cbd5e1',
    ];

    // Toutes les evaluations, quel que soit le mode d'affichage.
    $lots = $isAnnual && isset($allPaliersData)
        ? collect($allPaliersData)->pluck('evaluations')->flatten(1)
        : collect($evaluations)->flatten(1);

    $lots = collect($lots)->filter();

    // Maitrise d'ensemble : celle que le palier porte, sinon la plus frequente.
    $palierMastery = $lots->pluck('palier_mastery')->filter()->countBy()->sortDesc()->keys()->first();
    $profil = $lots->firstWhere('is_exit_profile', true)?->exit_profile ?? $palierMastery;

    $totalObtenu = $lots->sum('total_points_obtained');
    $totalMax = $lots->sum('total_points_max');

    $etablissement = $schoolSettings ?? \App\Models\SchoolSettings::first();

    // Nomenclature de la page de garde : les compétences du référentiel,
    // groupées par matière, dans l'ordre du document officiel.
    $ordreDesMatieres = ['EDM & EAS', 'Mathématiques', 'Français'];
    $lettres = ['A', 'B', 'C', 'D', 'E'];

    $nomenclature = collect($competencies)
        ->sortBy(fn ($liste, $matiere) => array_search($matiere, $ordreDesMatieres) === false
            ? 99
            : array_search($matiere, $ordreDesMatieres))
        ->values()
        ->mapWithKeys(fn ($liste, $rang) => [
            $lettres[$rang] ?? ($rang + 1) => [
                'matiere' => $liste->first()->subject_area ?? '—',
                'competences' => $liste->pluck('name')->values()->all(),
            ],
        ]);

    // Redoublant : l'élève a déjà été inscrit dans cette classe une année avant.
    $redoublant = $student->enrollments
        ->where('class_id', $currentEnrollment->class_id)
        ->where('id', '!=', $currentEnrollment->id)
        ->isNotEmpty();

    $enseignant = optional($lots->pluck('teacher_id')->filter()->first()
        ? \App\Models\Teacher::find($lots->pluck('teacher_id')->filter()->first())
        : null);
    $enseignant = $enseignant->first_name ? $enseignant->first_name.' '.$enseignant->last_name : null;
@endphp

    @if ($lots->isEmpty())
        <div class="carte p-8">
            <x-vide message="Aucune évaluation n’a encore été saisie pour cet élève."/>
            <div class="mt-4 text-center">
                <a href="{{ route('competency-evaluations.create', ['class_id' => $currentEnrollment->schoolClass->id]) }}"
                   class="bouton-primaire">Évaluer cet élève</a>
            </div>
        </div>
    @else

    {{-- ------------------------------------------------------------------
         Le document. Ce bloc est celui que html2canvas photographie :
         le PDF est exactement ce qui s'affiche ici.
         ------------------------------------------------------------------ --}}
    <div id="bulletin-competences" class="mx-auto max-w-6xl space-y-4">

    {{-- ==================================================================
         Feuillet 1 : la page de garde, telle qu'elle figure au document
         officiel — nomenclature des compétences à gauche, identification
         de l'apprenant à droite.
         ================================================================== --}}
    {{-- Le feuillet a le grain de la feuille : A4 paysage, 1123 x 794 px a
         96 ppp. Sans cela la photographie etait plus large que haute et se
         posait en haut du PDF, laissant le tiers inferieur vide. --}}
    <div data-page-pdf class="mx-auto flex flex-col bg-white p-6 text-gris-900 ring-1 ring-gris-200"
         style="width: 1123px; min-height: 794px;">

        <div class="grid flex-1 grid-cols-2 gap-5">

            {{-- Panneau de gauche : la nomenclature --}}
            <div class="border border-gris-400 p-4 text-[9px] leading-relaxed">
                <p class="mb-2 text-[10px] font-bold uppercase">
                    I — Identification des disciplines à l’intérieur de chaque compétence
                </p>

                @foreach ($nomenclature as $lettre => $bloc)
                    <p class="mt-2 font-bold uppercase">{{ $lettre }} — {{ $bloc['matiere'] }} :</p>
                    <ul class="ml-3 list-disc">
                        @foreach ($bloc['competences'] as $rang => $intitule)
                            <li>Compétence {{ $rang + 1 }} : {{ $intitule }}</li>
                        @endforeach
                    </ul>
                @endforeach

                <p class="mb-1 mt-4 text-[10px] font-bold uppercase">
                    II — Critères d’évaluation et situations de réussite d’une compétence
                </p>
                <ul class="ml-3 list-disc">
                    <li>C1 : critère 1 (interprétation correcte de la situation)</li>
                    <li>C2 : critère 2 (utilisation correcte des outils de la matière)</li>
                    <li>C3 : critère 3 (cohérence de la production)</li>
                    <li>Max : maîtrise maximale de la compétence (8 à 9 points en critères minimaux)</li>
                    <li>Min : maîtrise minimale de la compétence (5 à 7 points en critères minimaux)</li>
                    <li>Part : maîtrise partielle de la compétence (3 à 4 points en critères minimaux)</li>
                    <li>NM : non-maîtrise de la compétence (0 à 2 points en critères minimaux)</li>
                    <li>NB : 1 point de perfectionnement est attribué à l’élève qui atteint 5/9 points dans les critères minimaux.</li>
                </ul>

                <p class="mb-1 mt-4 text-[10px] font-bold uppercase">
                    III — Conditions de passage en classe supérieure
                </p>
                <ul class="ml-3 list-disc">
                    <li>Avoir au moins un niveau de maîtrise minimale au profil de sortie.</li>
                    <li>Avoir au moins un niveau de maîtrise minimale dans 3 paliers sur 5.</li>
                </ul>

                <p class="mb-1 mt-4 text-[10px] font-bold uppercase">
                    IV — Remplissage du bulletin d’évaluation
                </p>
                <ul class="ml-3 list-disc">
                    <li>Le nombre de points de chaque critère d’une compétence est prélevé sur la grille de correction de la situation.</li>
                    <li>La note de la compétence est la somme des points des trois critères, plus le point de perfectionnement si l’apprenant obtient 5/9 dans les critères minimaux.</li>
                    <li>Lorsque l’apprenant obtient une note supérieure ou égale à 5/10 mais avec un critère nul, il obtient automatiquement une maîtrise partielle.</li>
                </ul>
            </div>

            {{-- Panneau de droite : l'identification --}}
            <div class="border border-gris-400 p-4">
                <div class="text-right leading-tight">
                    <p class="text-[11px] font-bold uppercase">République Gabonaise</p>
                    <p class="text-[9px] italic text-gris-600">Union — Travail — Justice</p>
                    <p class="mt-1 text-[8px] uppercase leading-snug text-gris-600">
                        Ministère de l’Éducation Nationale<br>
                        Direction générale de l’enseignement scolaire et normale<br>
                        {{ $etablissement->school_address ?? 'Direction de l’académie' }}
                    </p>
                </div>

                <p class="mt-4 text-center text-base font-bold uppercase tracking-wide">Bulletin d’évaluation</p>

                <div class="mt-4 flex items-start justify-between gap-3">
                    <p class="flex-1 text-center text-sm font-bold uppercase">
                        {{ $schoolName ?? ($etablissement->school_name ?? 'Établissement scolaire') }}
                    </p>

                    @if ($etablissement->seal_url ?? null)
                        <img src="{{ $etablissement->seal_url }}" alt="Sceau de la République"
                             class="h-14 w-16 shrink-0 object-contain">
                    @else
                        <span class="flex h-14 w-16 shrink-0 items-center justify-center rounded-full border border-gris-400 text-center text-[7px] leading-tight text-gris-400">
                            Sceau
                        </span>
                    @endif
                </div>

                <dl class="mt-8 space-y-3 text-[11px]">
                    <div class="flex gap-2">
                        <dt class="font-bold">Année scolaire :</dt>
                        <dd>{{ $academicYear->name ?? '—' }}</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="font-bold">Classe :</dt>
                        <dd>{{ $currentEnrollment->schoolClass->name ?? '—' }}</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="font-bold">Noms et prénoms de l’apprenant :</dt>
                        <dd>{{ $student->last_name }} {{ $student->first_name }}</dd>
                    </div>

                    <div>
                        <dt class="font-bold">Statut de l’apprenant :</dt>
                        <dd class="ml-3 mt-0.5 space-y-0.5">
                            <span class="block">Nouveau [{{ $redoublant ? ' ' : 'X' }}]</span>
                            <span class="block">Redoublant [{{ $redoublant ? 'X' : ' ' }}]</span>
                        </dd>
                    </div>

                    <div class="flex items-end gap-2 pt-2">
                        <dt class="shrink-0 font-bold">Noms et prénoms de l’enseignant :</dt>
                        <dd class="flex-1 border-b border-gris-400 pb-0.5">{{ $enseignant ?? '' }}</dd>
                    </div>
                    <div class="flex items-end gap-2 pt-2">
                        <dt class="shrink-0 font-bold">Noms et prénoms du Directeur :</dt>
                        <dd class="flex-1 border-b border-gris-400 pb-0.5">{{ $etablissement->principal_name ?? '' }}</dd>
                    </div>
                </dl>

                <div class="mt-10 flex justify-end">
                    <span class="flex h-20 w-16 items-center justify-center border border-gris-400 text-center text-[8px] uppercase leading-tight text-gris-400">
                        Photo<br>élève
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================================================================
         Feuillet 2 : la grille d'évaluation
         ================================================================== --}}
    <div data-page-pdf class="mx-auto flex flex-col bg-white p-6 text-gris-900 ring-1 ring-gris-200"
         style="width: 1123px; min-height: 794px;">

        {{-- En-tête : logo à gauche, sceau de la République à droite --}}
        <div class="flex items-start justify-between gap-4 border-b-2 border-gris-800 pb-3">
            <div class="flex items-start gap-3">
                @if ($schoolSettings->logo_url ?? null)
                    <img src="{{ $schoolSettings->logo_url }}" alt="Logo de l’établissement"
                         class="h-12 w-12 shrink-0 object-contain">
                @else
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                        Logo<br>établissement
                    </span>
                @endif
                <div class="leading-tight">
                    <p class="text-[10px] text-gris-600">Ministère de l’Éducation Nationale</p>
                    <p class="text-sm font-bold uppercase">{{ $schoolName ?? ($schoolSettings->school_name ?? 'Établissement scolaire') }}</p>
                    <p class="text-[9px] text-gris-500">
                        @if ($schoolSettings->school_bp ?? null) {{ $schoolSettings->school_bp }} @endif
                        @if ($schoolSettings->school_phone ?? null) Tél/fax : {{ $schoolSettings->school_phone }} @endif
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="text-right leading-tight">
                    <p class="text-[10px] uppercase tracking-wide text-gris-500">Année scolaire</p>
                    <p class="text-sm font-semibold">{{ $academicYear->name ?? '—' }}</p>
                </div>

                @if ($schoolSettings->seal_url ?? null)
                    <img src="{{ $schoolSettings->seal_url }}" alt="Sceau de la République"
                         class="h-12 w-16 shrink-0 object-contain">
                @else
                    <span class="flex h-12 w-16 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                        Sceau de la<br>République
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-4 text-center">
            <h1 class="text-lg font-bold uppercase tracking-wide">Bulletin d’évaluation par compétences</h1>
            <p class="mt-0.5 text-sm text-gris-600">
                {{ $isAnnual ? 'Bilan annuel — tous les paliers' : 'Palier '.$palier }}
            </p>
        </div>

        {{-- Identité de l'élève --}}
        <table class="mt-3 w-full border-collapse border border-gris-400 text-[11px]">
            <tr>
                <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-semibold uppercase tracking-wide text-gris-600">Élève</td>
                <td class="border border-gris-400 px-2 py-1 font-semibold">{{ $student->last_name }} {{ $student->first_name }}</td>
                <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-semibold uppercase tracking-wide text-gris-600">Matricule</td>
                <td class="border border-gris-400 px-2 py-1 font-mono">{{ $student->student_id }}</td>
                <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-semibold uppercase tracking-wide text-gris-600">Classe</td>
                <td class="border border-gris-400 px-2 py-1">{{ $currentEnrollment->schoolClass->name ?? '—' }}</td>
                <td class="border border-gris-400 bg-gris-100 px-2 py-1 font-semibold uppercase tracking-wide text-gris-600">Né(e) le</td>
                <td class="border border-gris-400 px-2 py-1">
                    {{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : '—' }}
                </td>
            </tr>
        </table>

        {{-- Les compétences, domaine par domaine --}}
        <table class="mt-3 w-full border-collapse border border-gris-400 text-[10px]">
            <thead>
                <tr class="bg-gris-200">
                    <th class="border border-gris-400 px-2 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-700">
                        Compétence
                    </th>
                    @if ($isAnnual)
                        <th class="w-16 border border-gris-400 px-1 py-1.5 font-semibold uppercase tracking-wide text-gris-700">Palier</th>
                    @endif
                    <th class="w-44 border border-gris-400 px-2 py-1.5 font-semibold uppercase tracking-wide text-gris-700">
                        Points par critère
                    </th>
                    <th class="w-20 border border-gris-400 px-1 py-1.5 font-semibold uppercase tracking-wide text-gris-700">Total</th>
                    <th class="w-28 border border-gris-400 px-1 py-1.5 font-semibold uppercase tracking-wide text-gris-700">Maîtrise</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lots->groupBy(fn ($e) => $e->competency->subject_area ?? 'Autre') as $matiere => $evaluationsMatiere)
                    <tr>
                        <td colspan="{{ $isAnnual ? 5 : 4 }}"
                            class="border border-gris-400 bg-gris-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-gris-700">
                            {{ $matiere }}
                        </td>
                    </tr>

                    @foreach ($evaluationsMatiere->sortBy('palier') as $evaluation)
                        @php($criteres = collect([1, 2, 3, 4])
                            ->map(fn ($i) => [
                                'obtenu' => (int) $evaluation->{"c{$i}_points"},
                                'max' => (int) $evaluation->{"c{$i}_max_points"},
                            ])
                            ->filter(fn ($c) => $c['max'] > 0))
                        <tr>
                            <td class="border border-gris-400 px-2 py-1">
                                {{ $evaluation->competency->name ?? 'Compétence supprimée' }}
                            </td>

                            @if ($isAnnual)
                                <td class="border border-gris-400 px-1 py-1 text-center text-gris-600">{{ $evaluation->palier }}</td>
                            @endif

                            <td class="border border-gris-400 px-2 py-1 text-center">
                                @foreach ($criteres as $rang => $critere)
                                    <span class="mr-2 inline-block whitespace-nowrap">
                                        <span class="text-gris-500">C{{ $rang + 1 }}</span>
                                        <span class="font-semibold">{{ $critere['obtenu'] }}</span><span class="text-gris-400">/{{ $critere['max'] }}</span>
                                    </span>
                                @endforeach
                            </td>

                            <td class="border border-gris-400 px-1 py-1 text-center font-semibold">
                                {{ $evaluation->total_points_obtained }}<span class="font-normal text-gris-400">/{{ $evaluation->total_points_max }}</span>
                            </td>

                            <td class="border border-gris-400 px-1 py-1 text-center font-semibold"
                                style="background:{{ $fondMaitrise[$evaluation->competency_mastery] ?? '#ffffff' }}">
                                {{ $libellesMaitrise[$evaluation->competency_mastery] ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gris-200">
                    <td class="border border-gris-400 px-2 py-1.5 font-bold uppercase tracking-wide text-gris-700"
                        colspan="{{ $isAnnual ? 3 : 2 }}">
                        Total
                    </td>
                    <td class="border border-gris-400 px-1 py-1.5 text-center font-bold">
                        {{ $totalObtenu }}<span class="font-normal text-gris-500">/{{ $totalMax }}</span>
                    </td>
                    <td class="border border-gris-400 px-1 py-1.5 text-center font-bold">
                        {{ $libellesMaitrise[$palierMastery] ?? '—' }}
                    </td>
                </tr>
            </tfoot>
        </table>

        {{-- Synthèse --}}
        <div class="mt-3 border border-gris-400">
            <div class="flex items-center justify-between gap-4 bg-gris-100 px-3 py-1.5">
                <span class="text-[10px] font-bold uppercase tracking-wide text-gris-700">
                    {{ $isAnnual ? 'Profil de sortie' : 'Maîtrise du palier' }}
                </span>
                <span class="text-sm font-bold">{{ $libellesMaitrise[$profil] ?? 'Non renseigné' }}</span>
            </div>

            @if ($isAnnual)
                <div class="px-3 py-2 text-[10px] text-gris-600">
                    <span class="font-semibold uppercase tracking-wide">Décision du conseil de classe :</span>
                    <span class="ml-1 italic text-gris-400">à compléter en fin d’année</span>
                </div>
            @endif
        </div>

        {{-- Légende et visas : au bas de la feuille, comme sur le document
             officiel — `mt-auto` pousse le bloc sous le tableau quel que soit
             le nombre de compétences. --}}
        <div class="mt-auto flex items-start justify-between gap-6 pt-6">
            <div>
                <p class="mb-1 text-[9px] font-bold uppercase tracking-wide text-gris-600">Légende</p>
                <div class="flex flex-wrap gap-3 text-[9px] text-gris-600">
                    @foreach ($libellesMaitrise as $cle => $libelle)
                        <span class="flex items-center gap-1">
                            <span class="inline-block h-3.5 w-6 shrink-0 border border-gris-400" style="background:{{ $fondMaitrise[$cle] }}"></span>
                            {{ $libelle }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-10 text-[9px] text-gris-500">
                @foreach (['Le Directeur', 'L’enseignant(e)', 'Le parent'] as $visa)
                    <span class="inline-block w-28 text-center">
                        <span class="block">{{ $visa }}</span>
                        <span class="mt-8 block border-t border-gris-400"></span>
                    </span>
                @endforeach
            </div>
        </div>

        <p class="mt-4 text-[9px] text-gris-500">Édité le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
    </div>

    @endif

@endsection
