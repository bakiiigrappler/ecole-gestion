<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin d'Évaluation des Acquis Scolaires - {{ $student->first_name }} {{ $student->last_name }}</title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
        }
        
        .bulletin-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border: 2px solid #333;
            padding: 15px;
            transform: rotate(0deg);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .header h1 {
            font-size: 18px;
            margin: 0;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .header h2 {
            font-size: 16px;
            margin: 10px 0 0 0;
            font-weight: bold;
        }
        
        .school-info {
            margin-top: 15px;
            font-size: 12px;
        }
        
        .student-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 12px;
        }
        
        .competency-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        .competency-table th,
        .competency-table td {
            border: 1px solid #333;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }
        
        .competency-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .competency-header {
            background-color: #e0e0e0;
            font-weight: bold;
            text-align: center;
        }
        
        .criteria-header {
            background-color: #f5f5f5;
            font-size: 10px;
        }
        
        .palier-row {
            font-weight: bold;
        }
        
        .mastery-maximale {
            background-color: #d4edda;
            font-weight: bold;
        }
        
        .mastery-minimale {
            background-color: #d1ecf1;
            font-weight: bold;
        }
        
        .mastery-partielle {
            background-color: #fff3cd;
            font-weight: bold;
        }
        
        .mastery-non_maitrise {
            background-color: #f8d7da;
            font-weight: bold;
        }
        
        .signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
        }
        
        .signature-box {
            text-align: center;
            width: 150px;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        
        .decision {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #333;
            background-color: #f9f9f9;
            font-size: 12px;
            font-weight: bold;
        }
        
        .logo {
            max-width: 80px;
            max-height: 80px;
            margin-bottom: 10px;
        }
        
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }
            
            body {
                margin: 0;
                padding: 0;
                transform: rotate(0deg);
            }
            
            .bulletin-container {
                border: none;
                padding: 0;
                max-width: none;
                width: 100%;
            }
        }
        
        /* Format paysage pour l'écran */
        @media screen {
            body {
                transform: rotate(0deg);
            }
            
            .bulletin-container {
                max-width: 1200px;
            }
        }
    </style>
</head>
<body>
    <div class="bulletin-container">
        <!-- En-tête -->
        <div class="header">
            @if(isset($schoolSettings) && $schoolSettings->school_logo)
                <img src="{{ $schoolSettings->logo_url }}" alt="Logo {{ $schoolName }}" class="logo">
            @endif
            
            <h1>BULLETIN D'ÉVALUATION DES ACQUIS SCOLAIRES</h1>
            <h2>ANNÉE PRIMAIRE</h2>
            
            @if(isset($schoolSettings))
                <div class="school-info">
                    <strong>{{ $schoolName ?? 'Établissement Scolaire' }}</strong><br>
                    {{ $schoolSettings->school_address }}<br>
                    Tél: {{ $schoolSettings->school_phone }} | Email: {{ $schoolSettings->school_email }}
                </div>
            @endif
        </div>
        
        <!-- Informations de l'élève -->
        <div class="student-info">
            <div>
                <strong>Nom:</strong> {{ $student->first_name }} {{ $student->last_name }}<br>
                <strong>Matricule:</strong> {{ $student->student_id }}<br>
                <strong>Classe:</strong> {{ $currentEnrollment->schoolClass->name }}
            </div>
            <div>
                <strong>Date de naissance:</strong> {{ $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : 'N/A' }}<br>
                <strong>Année scolaire:</strong> {{ $currentEnrollment->academicYear->name ?? date('Y') }}<br>
                @if($isAnnual)
                    <strong>Période:</strong> <span style="color: red; font-weight: bold;">BILAN ANNUEL (Paliers 1-5)</span>
                @else
                    <strong>Période:</strong> Palier {{ $palier }}
                @endif
            </div>
        </div>

        
        <!-- Tableau des compétences -->
        @if($isAnnual && isset($allPaliersData))
            <!-- TABLEAU ANNUEL - Format exact de l'image -->
            <table class="competency-table" style="width: 100%; border-collapse: collapse; font-size: 10px;">
                <thead>
                    <tr>
                        <th rowspan="2" style="border: 1px solid #333; padding: 4px; background: #f0f0f0; width: 8%;">Paliers</th>
                        <th rowspan="2" style="border: 1px solid #333; padding: 4px; background: #f0f0f0; width: 20%;">INDICATEURS DE DÉCISION</th>
                        @for($p = 1; $p <= 5; $p++)
                            <th style="border: 1px solid #333; padding: 4px; background: #f0f0f0; width: 12%;">Palier {{ $p }}</th>
                        @endfor
                        <th style="border: 1px solid #333; padding: 4px; background: #d4edda; width: 12%;">Profil de sortie</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($competencies as $subjectArea => $subjectCompetencies)
                        <!-- En-tête du domaine -->
                        <tr>
                            <td rowspan="{{ $subjectCompetencies->count() * 5 + 1 }}" style="border: 1px solid #333; padding: 4px; background: #f8f9fa; font-weight: bold; text-align: center; vertical-align: middle;">
                                {{ $subjectArea }}
                            </td>
                        </tr>
                        @foreach($subjectCompetencies as $competency)
                            <!-- Nom de la compétence -->
                            <tr>
                                <td style="border: 1px solid #333; padding: 4px; font-weight: bold; background: #f8f9fa;">
                                    {{ $competency->name }}
                                </td>
                                <td style="border: 1px solid #333; padding: 4px; font-size: 9px;">
                                    Nombre de points du critère
                                </td>
                                @for($p = 1; $p <= 5; $p++)
                                    @php
                                        $palierData = collect($allPaliersData)->where('palier', $p)->first();
                                        $evaluation = null;
                                        if ($palierData && isset($palierData['evaluations'])) {
                                            $evaluation = collect($palierData['evaluations'])->where('competency_id', $competency->id)->first();
                                        }
                                    @endphp
                                    <td style="border: 1px solid #333; padding: 4px; text-align: center;">
                                        @if($evaluation)
                                            {{ $evaluation->c1_max_points ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endfor
                                <td style="border: 1px solid #333; padding: 4px; text-align: center; background: #d4edda;">
                                    @if(isset($allPaliersData[0]['evaluations'][0]))
                                        {{ $allPaliersData[0]['evaluations'][0]->c1_max_points ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            
                            <!-- Note de la compétence -->
                            <tr>
                                <td style="border: 1px solid #333; padding: 4px; font-size: 9px;">
                                    Note de la compétence
                                </td>
                                @for($p = 1; $p <= 5; $p++)
                                    @php
                                        $palierData = collect($allPaliersData)->where('palier', $p)->first();
                                        $evaluation = null;
                                        if ($palierData && isset($palierData['evaluations'])) {
                                            $evaluation = collect($palierData['evaluations'])->where('competency_id', $competency->id)->first();
                                        }
                                    @endphp
                                    <td style="border: 1px solid #333; padding: 4px; text-align: center;">
                                        @if($evaluation)
                                            {{ $evaluation->total_points_obtained ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endfor
                                <td style="border: 1px solid #333; padding: 4px; text-align: center; background: #d4edda;">
                                    @php
                                        $avgPoints = 0;
                                        $count = 0;
                                        for ($p = 1; $p <= 5; $p++) {
                                            $palierData = collect($allPaliersData)->where('palier', $p)->first();
                                            if ($palierData && isset($palierData['evaluations'])) {
                                                $evaluation = collect($palierData['evaluations'])->where('competency_id', $competency->id)->first();
                                                if ($evaluation && $evaluation->total_points_obtained) {
                                                    $avgPoints += $evaluation->total_points_obtained;
                                                    $count++;
                                                }
                                            }
                                        }
                                        $finalAvg = $count > 0 ? round($avgPoints / $count) : '-';
                                    @endphp
                                    {{ $finalAvg }}
                                </td>
                            </tr>
                            
                            <!-- Maîtrise de la compétence -->
                            <tr>
                                <td style="border: 1px solid #333; padding: 4px; font-size: 9px;">
                                    Maîtrise de la compétence
                                </td>
                                @for($p = 1; $p <= 5; $p++)
                                    @php
                                        $palierData = collect($allPaliersData)->where('palier', $p)->first();
                                        $evaluation = null;
                                        if ($palierData && isset($palierData['evaluations'])) {
                                            $evaluation = collect($palierData['evaluations'])->where('competency_id', $competency->id)->first();
                                        }
                                    @endphp
                                    <td style="border: 1px solid #333; padding: 4px; text-align: center;">
                                        @if($evaluation)
                                            <span class="mastery-{{ $evaluation->competency_mastery }}">
                                                {{ strtoupper(substr($evaluation->competency_mastery, 0, 3)) }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endfor
                                <td style="border: 1px solid #333; padding: 4px; text-align: center; background: #d4edda; font-weight: bold;">
                                    @php
                                        $masteryLevels = [];
                                        for ($p = 1; $p <= 5; $p++) {
                                            $palierData = collect($allPaliersData)->where('palier', $p)->first();
                                            if ($palierData && isset($palierData['evaluations'])) {
                                                $evaluation = collect($palierData['evaluations'])->where('competency_id', $competency->id)->first();
                                                if ($evaluation) {
                                                    $masteryLevels[] = $evaluation->competency_mastery;
                                                }
                                            }
                                        }
                                        
                                        $exitProfile = 'non_maitrise';
                                        if (count($masteryLevels) > 0) {
                                            $maximaleCount = count(array_filter($masteryLevels, function($m) { return $m === 'maximale'; }));
                                            $minimaleCount = count(array_filter($masteryLevels, function($m) { return $m === 'minimale'; }));
                                            $partielleCount = count(array_filter($masteryLevels, function($m) { return $m === 'partielle'; }));
                                            
                                            if ($maximaleCount >= count($masteryLevels) * 0.6) {
                                                $exitProfile = 'maximale';
                                            } elseif ($minimaleCount >= count($masteryLevels) * 0.6) {
                                                $exitProfile = 'minimale';
                                            } elseif ($partielleCount >= count($masteryLevels) * 0.6) {
                                                $exitProfile = 'partielle';
                                            }
                                        }
                                    @endphp
                                    <span class="mastery-{{ $exitProfile }}">
                                        {{ strtoupper(substr($exitProfile, 0, 3)) }}
                                    </span>
                                </td>
                            </tr>
                            
                            <!-- Maîtrise de la matière -->
                            <tr>
                                <td style="border: 1px solid #333; padding: 4px; font-size: 9px;">
                                    Maîtrise de la matière
                                </td>
                                @for($p = 1; $p <= 5; $p++)
                                    @php
                                        $palierData = collect($allPaliersData)->where('palier', $p)->first();
                                        $evaluation = null;
                                        if ($palierData && isset($palierData['evaluations'])) {
                                            $evaluation = collect($palierData['evaluations'])->where('competency_id', $competency->id)->first();
                                        }
                                    @endphp
                                    <td style="border: 1px solid #333; padding: 4px; text-align: center;">
                                        @if($evaluation)
                                            <span class="mastery-{{ $evaluation->subject_mastery ?? $evaluation->competency_mastery }}">
                                                {{ strtoupper(substr($evaluation->subject_mastery ?? $evaluation->competency_mastery, 0, 3)) }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endfor
                                <td style="border: 1px solid #333; padding: 4px; text-align: center; background: #d4edda; font-weight: bold;">
                                    <span class="mastery-{{ $exitProfile }}">
                                        {{ strtoupper(substr($exitProfile, 0, 3)) }}
                                    </span>
                                </td>
                            </tr>
                            
                            <!-- Maîtrise du palier -->
                            <tr>
                                <td style="border: 1px solid #333; padding: 4px; font-size: 9px;">
                                    Maîtrise du palier
                                </td>
                                @for($p = 1; $p <= 5; $p++)
                                    @php
                                        $palierData = collect($allPaliersData)->where('palier', $p)->first();
                                        $evaluation = null;
                                        if ($palierData && isset($palierData['evaluations'])) {
                                            $evaluation = collect($palierData['evaluations'])->where('competency_id', $competency->id)->first();
                                        }
                                    @endphp
                                    <td style="border: 1px solid #333; padding: 4px; text-align: center;">
                                        @if($evaluation)
                                            <span class="mastery-{{ $evaluation->palier_mastery ?? $evaluation->competency_mastery }}">
                                                {{ strtoupper(substr($evaluation->palier_mastery ?? $evaluation->competency_mastery, 0, 3)) }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endfor
                                <td style="border: 1px solid #333; padding: 4px; text-align: center; background: #d4edda; font-weight: bold;">
                                    <span class="mastery-{{ $exitProfile }}">
                                        {{ strtoupper(substr($exitProfile, 0, 3)) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        @else
            <!-- TABLEAU INDIVIDUEL -->
            <table class="competency-table">
                <thead>
                    <tr>
                        <th rowspan="3" class="competency-header">Paliers</th>
                        <th rowspan="3" class="competency-header">INDICATEURS DE DÉCISION</th>
                        @foreach($competencies as $subjectArea => $subjectCompetencies)
                            @foreach($subjectCompetencies as $competency)
                                <th colspan="4" class="competency-header">{{ $competency->name }}</th>
                            @endforeach
                        @endforeach
                    </tr>
                <tr>
                    @foreach($competencies as $subjectArea => $subjectCompetencies)
                        @foreach($subjectCompetencies as $competency)
                            <th colspan="4" class="competency-header">{{ $subjectArea }}</th>
                        @endforeach
                    @endforeach
                </tr>
                <tr>
                    @foreach($competencies as $subjectArea => $subjectCompetencies)
                        @foreach($subjectCompetencies as $competency)
                            <th class="criteria-header">C1</th>
                            <th class="criteria-header">C2</th>
                            <th class="criteria-header">C3</th>
                            <th class="criteria-header">C4</th>
                        @endforeach
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <!-- Ligne des points maximum -->
                <tr>
                    <td class="palier-row">Palier {{ $palier }}</td>
                    <td>Nombre de points du critère</td>
                    @foreach($competencies as $subjectArea => $subjectCompetencies)
                        @foreach($subjectCompetencies as $competency)
                            @php
                                $evaluation = $evaluations->get($subjectArea, collect())->where('competency_id', $competency->id)->first();
                            @endphp
                            @if($evaluation)
                                <td>{{ $evaluation->c1_max_points }}</td>
                                <td>{{ $evaluation->c2_max_points }}</td>
                                <td>{{ $evaluation->c3_max_points }}</td>
                                <td>{{ $evaluation->c4_max_points }}</td>
                            @else
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                            @endif
                        @endforeach
                    @endforeach
                </tr>
                
                <!-- Ligne des points obtenus -->
                <tr>
                    <td></td>
                    <td>Note de la compétence</td>
                    @foreach($competencies as $subjectArea => $subjectCompetencies)
                        @foreach($subjectCompetencies as $competency)
                            @php
                                $evaluation = $evaluations->get($subjectArea, collect())->where('competency_id', $competency->id)->first();
                            @endphp
                            @if($evaluation)
                                <td colspan="4">{{ $evaluation->total_points_obtained }}</td>
                            @else
                                <td colspan="4">-</td>
                            @endif
                        @endforeach
                    @endforeach
                </tr>
                
                <!-- Ligne de maîtrise de la compétence -->
                <tr>
                    <td></td>
                    <td>Maîtrise de la compétence</td>
                    @foreach($competencies as $subjectArea => $subjectCompetencies)
                        @foreach($subjectCompetencies as $competency)
                            @php
                                $evaluation = $evaluations->get($subjectArea, collect())->where('competency_id', $competency->id)->first();
                            @endphp
                            @if($evaluation)
                                <td colspan="4" class="mastery-{{ $evaluation->competency_mastery }}">
                                    {{ ucfirst(str_replace('_', ' ', $evaluation->competency_mastery)) }}
                                </td>
                            @else
                                <td colspan="4">-</td>
                            @endif
                        @endforeach
                    @endforeach
                </tr>
                
                <!-- Ligne de maîtrise de la matière -->
                <tr>
                    <td></td>
                    <td>Maîtrise de la matière</td>
                    @foreach($competencies as $subjectArea => $subjectCompetencies)
                        @php
                            $subjectEvaluations = $evaluations->get($subjectArea, collect());
                            $avgMastery = $subjectEvaluations->avg(function($eval) {
                                $masteryValues = ['non_maitrise' => 0, 'partielle' => 1, 'minimale' => 2, 'maximale' => 3];
                                return $masteryValues[$eval->competency_mastery] ?? 0;
                            });
                            $overallMastery = $avgMastery >= 2.5 ? 'maximale' : ($avgMastery >= 1.5 ? 'minimale' : ($avgMastery >= 0.5 ? 'partielle' : 'non_maitrise'));
                        @endphp
                        <td colspan="{{ $subjectCompetencies->count() * 4 }}" class="mastery-{{ $overallMastery }}">
                            {{ ucfirst(str_replace('_', ' ', $overallMastery)) }}
                        </td>
                    @endforeach
                </tr>
                
                <!-- Ligne de maîtrise du palier -->
                <tr>
                    <td></td>
                    <td>Maîtrise du palier</td>
                    @php
                        $allEvaluations = $evaluations->flatten();
                        $palierAvgMastery = $allEvaluations->avg(function($eval) {
                            $masteryValues = ['non_maitrise' => 0, 'partielle' => 1, 'minimale' => 2, 'maximale' => 3];
                            return $masteryValues[$eval->competency_mastery] ?? 0;
                        });
                        $palierMastery = $palierAvgMastery >= 2.5 ? 'maximale' : ($palierAvgMastery >= 1.5 ? 'minimale' : ($palierAvgMastery >= 0.5 ? 'partielle' : 'non_maitrise'));
                        $totalColumns = $competencies->sum(function($subjectCompetencies) {
                            return $subjectCompetencies->count() * 4;
                        });
                    @endphp
                    <td colspan="{{ $totalColumns }}" class="mastery-{{ $palierMastery }}">
                        {{ ucfirst(str_replace('_', ' ', $palierMastery)) }}
                    </td>
                </tr>
            </tbody>
        </table>
        @endif
        
        <!-- Profil de sortie -->
        @if($palier == 5)
            <div class="decision">
                <strong>PROFIL DE SORTIE:</strong>
                @php
                    $exitEvaluations = $evaluations->flatten()->where('is_exit_profile', true);
                    $exitMastery = $exitEvaluations->avg(function($eval) {
                        $masteryValues = ['non_maitrise' => 0, 'partielle' => 1, 'minimale' => 2, 'maximale' => 3];
                        return $masteryValues[$eval->exit_profile] ?? 0;
                    });
                    $finalMastery = $exitMastery >= 2.5 ? 'maximale' : ($exitMastery >= 1.5 ? 'minimale' : ($exitMastery >= 0.5 ? 'partielle' : 'non_maitrise'));
                @endphp
                <span class="mastery-{{ $finalMastery }}">
                    {{ ucfirst(str_replace('_', ' ', $finalMastery)) }}
                </span>
            </div>
        @endif
        
        @if($isAnnual && isset($allPaliersData))
            <!-- SECTION VISAS ET DÉCISION POUR BULLETIN ANNUEL -->
            <div style="margin-top: 30px;">
                <!-- Tableau des visas par palier -->
                <table style="width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid #333; padding: 8px; background: #f0f0f0; width: 20%;">Visas</th>
                            @for($p = 1; $p <= 5; $p++)
                                <th style="border: 1px solid #333; padding: 8px; background: #f0f0f0; width: 12%;">Période {{ $p }}</th>
                            @endfor
                            <th style="border: 1px solid #333; padding: 8px; background: #d4edda; width: 20%;">Décision du conseil de classe en fin d'année</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Visa du Directeur -->
                        <tr>
                            <td style="border: 1px solid #333; padding: 8px; font-weight: bold;">
                                Visa du (de la) Directeur (trice)
                            </td>
                            @for($p = 1; $p <= 5; $p++)
                                <td style="border: 1px solid #333; padding: 8px; text-align: center; height: 40px;">
                                    <div style="border-bottom: 1px solid #333; margin-bottom: 5px;"></div>
                                    <small>Signature</small>
                                </td>
                            @endfor
                            <td rowspan="3" style="border: 1px solid #333; padding: 8px; text-align: center; vertical-align: middle; background: #d4edda;">
                                @php
                                    // Calculer la décision finale basée sur toutes les évaluations
                                    $allEvaluations = collect($allPaliersData)->pluck('evaluations')->flatten();
                                    $masteryCounts = $allEvaluations->countBy('competency_mastery');
                                    $totalEvaluations = $allEvaluations->count();
                                    
                                    $decision = 'Passage en classe supérieure';
                                    if ($masteryCounts->get('non_maitrise', 0) > $totalEvaluations * 0.3) {
                                        $decision = 'Redoublement';
                                    } elseif ($masteryCounts->get('partielle', 0) > $totalEvaluations * 0.4) {
                                        $decision = 'Passage avec accompagnement renforcé';
                                    }
                                @endphp
                                <strong>{{ $decision }}</strong>
                            </td>
                        </tr>
                        
                        <!-- Visa de l'enseignant -->
                        <tr>
                            <td style="border: 1px solid #333; padding: 8px; font-weight: bold;">
                                Visa de l'enseignant (e)
                            </td>
                            @for($p = 1; $p <= 5; $p++)
                                <td style="border: 1px solid #333; padding: 8px; text-align: center; height: 40px;">
                                    <div style="border-bottom: 1px solid #333; margin-bottom: 5px;"></div>
                                    <small>Signature</small>
                                </td>
                            @endfor
                        </tr>
                        
                        <!-- Visa du parent -->
                        <tr>
                            <td style="border: 1px solid #333; padding: 8px; font-weight: bold;">
                                Visa du (de la) parent (e)
                            </td>
                            @for($p = 1; $p <= 5; $p++)
                                <td style="border: 1px solid #333; padding: 8px; text-align: center; height: 40px;">
                                    <div style="border-bottom: 1px solid #333; margin-bottom: 5px;"></div>
                                    <small>Signature</small>
                                </td>
                            @endfor
                        </tr>
                    </tbody>
                </table>
            </div>
        @else
            <!-- Signatures pour bulletin individuel -->
            <div class="signatures">
                <div class="signature-box">
                    <strong>Visa du (de la) Directeur (trice)</strong><br><br><br>
                    _________________
                </div>
                <div class="signature-box">
                    <strong>Visa de l'enseignant (e)</strong><br><br><br>
                    _________________
                </div>
                <div class="signature-box">
                    <strong>Visa du (de la) parent (e)</strong><br><br><br>
                    _________________
                </div>
            </div>
            
            <!-- Décision du conseil de classe -->
            <div class="decision">
                <strong>DÉCISION DU CONSEIL DE CLASSE EN FIN D'ANNÉE:</strong><br>
                @if($palier == 5)
                    @php
                        $decision = $finalMastery == 'non_maitrise' ? 'Redoublement' : 'Passage en classe supérieure';
                    @endphp
                    {{ $decision }}
                @else
                    <em>À compléter en fin d'année</em>
                @endif
            </div>
        @endif
    </div>
</body>
</html>
