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
            padding: 10px;
            background: white;
            font-size: 10px;
        }
        
        .bulletin-container {
            width: 100%;
            background: white;
            border: 1px solid #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }
        
        .header h1 {
            font-size: 14px;
            margin: 0;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .header h2 {
            font-size: 12px;
            margin: 5px 0 0 0;
            font-weight: bold;
        }
        
        .school-info {
            margin-top: 10px;
            font-size: 9px;
        }
        
        .student-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 9px;
        }
        
        .competency-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8px;
        }
        
        .competency-table th,
        .competency-table td {
            border: 1px solid #333;
            padding: 2px;
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
            font-size: 7px;
        }
        
        .palier-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        
        .mastery-maximale {
            background-color: #d4edda;
            font-weight: bold;
            color: #155724;
        }
        
        .mastery-minimale {
            background-color: #d1ecf1;
            font-weight: bold;
            color: #0c5460;
        }
        
        .mastery-partielle {
            background-color: #fff3cd;
            font-weight: bold;
            color: #856404;
        }
        
        .mastery-non_maitrise {
            background-color: #f8d7da;
            font-weight: bold;
            color: #721c24;
        }
        
        .signatures {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 8px;
        }
        
        .signature-box {
            text-align: center;
            width: 120px;
            border-top: 1px solid #333;
            padding-top: 3px;
        }
        
        .decision {
            margin-top: 15px;
            padding: 8px;
            border: 1px solid #333;
            background-color: #f9f9f9;
            font-size: 9px;
            font-weight: bold;
        }
        
        .logo {
            max-width: 60px;
            max-height: 60px;
            margin-bottom: 5px;
        }
        
        @media print {
            @page {
                size: A4 landscape;
                margin: 5mm;
            }
            
            body {
                margin: 0;
                padding: 0;
            }
            
            .bulletin-container {
                border: none;
                padding: 0;
                width: 100%;
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

        <!-- Tableau des compétences - Structure EXACTE de l'image (sans colonnes supplémentaires) -->
        <table class="competency-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 8%;">Paliers</th>
                    <th rowspan="2" style="width: 15%;">INDICATEURS DE DÉCISION</th>
                    <!-- EDM & EAS -->
                    <th colspan="3" style="background-color: #fff2cc;">EDM & EAS</th>
                    <!-- Français -->
                    <th colspan="3" style="background-color: #d5e8d4;">Français</th>
                    <!-- Mathématiques -->
                    <th colspan="3" style="background-color: #f8cecc;">Mathématiques</th>
                </tr>
                <tr>
                    <!-- EDM & EAS -->
                    <th style="background-color: #fff2cc;">Histoire, géographie, citoyenneté</th>
                    <th style="background-color: #fff2cc;">Sciences, Technologie et informatique</th>
                    <th style="background-color: #fff2cc;">EPS et art</th>
                    
                    <!-- Français -->
                    <th style="background-color: #d5e8d4;">Compréhension orale et langage</th>
                    <th style="background-color: #d5e8d4;">Lecture, écriture et production écrite</th>
                    <th style="background-color: #d5e8d4;">Grammaire et vocabulaire</th>
                    
                    <!-- Mathématiques -->
                    <th style="background-color: #f8cecc;">Nombres et opérations et Résolution des problèmes</th>
                    <th style="background-color: #f8cecc;">Géométrie et Mesure</th>
                    <th style="background-color: #f8cecc;">Résolution des problèmes</th>
                </tr>
                <tr>
                    <th></th>
                    <th></th>
                    <!-- Critères pour chaque compétence (3 critères chacune) -->
                    @for($i = 0; $i < 9; $i++)
                        <th style="font-size: 7px;">C1</th>
                        <th style="font-size: 7px;">C2</th>
                        <th style="font-size: 7px;">C3</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @if($isAnnual && isset($allPaliersData))
                    <!-- BULLETIN ANNUEL - Structure exacte de l'image -->
                    @for($p = 1; $p <= 5; $p++)
                        @php
                            $palierData = collect($allPaliersData)->where('palier', $p)->first();
                        @endphp
                        <tr>
                            <td class="palier-row">Palier {{ $p }}</td>
                            <td>Nombre de points du critère</td>
                            @for($i = 0; $i < 9; $i++)
                                <td>5</td>
                                <td>5</td>
                                <td>5</td>
                            @endfor
                        </tr>
                        
                        <tr>
                            <td></td>
                            <td>Note de la compétence</td>
                            @for($i = 0; $i < 9; $i++)
                                @php
                                    $score = rand(8, 20); // Score aléatoire pour l'exemple
                                @endphp
                                <td colspan="3">{{ $score }}</td>
                            @endfor
                        </tr>
                        
                        <tr>
                            <td></td>
                            <td>Maîtrise de la compétence</td>
                            @for($i = 0; $i < 9; $i++)
                                @php
                                    $masteryLevels = ['maximale', 'minimale', 'partielle', 'non_maitrise'];
                                    $mastery = $masteryLevels[array_rand($masteryLevels)];
                                @endphp
                                <td colspan="3" class="mastery-{{ $mastery }}">
                                    {{ strtoupper(substr($mastery, 0, 3)) }}
                                </td>
                            @endfor
                        </tr>
                        
                        <tr>
                            <td></td>
                            <td>Maîtrise de la matière</td>
                            <td colspan="3" class="mastery-partielle">PART</td>
                            <td colspan="3" class="mastery-partielle">PART</td>
                            <td colspan="3" class="mastery-partielle">PART</td>
                        </tr>
                        
                        <tr>
                            <td></td>
                            <td>Maîtrise du palier</td>
                            <td colspan="9" class="mastery-partielle">PART</td>
                        </tr>
                    @endfor
                @else
                    <!-- BULLETIN INDIVIDUEL - Structure exacte de l'image -->
                    <tr>
                        <td class="palier-row">Palier {{ $palier }}</td>
                        <td>Nombre de points du critère</td>
                        @for($i = 0; $i < 9; $i++)
                            <td>5</td>
                            <td>5</td>
                            <td>5</td>
                        @endfor
                    </tr>
                    
                    <tr>
                        <td></td>
                        <td>Note de la compétence</td>
                        @for($i = 0; $i < 9; $i++)
                            @php
                                $score = rand(8, 20);
                            @endphp
                            <td colspan="3">{{ $score }}</td>
                        @endfor
                    </tr>
                    
                    <tr>
                        <td></td>
                        <td>Maîtrise de la compétence</td>
                        @for($i = 0; $i < 9; $i++)
                            @php
                                $masteryLevels = ['maximale', 'minimale', 'partielle', 'non_maitrise'];
                                $mastery = $masteryLevels[array_rand($masteryLevels)];
                            @endphp
                            <td colspan="3" class="mastery-{{ $mastery }}">
                                {{ strtoupper(substr($mastery, 0, 3)) }}
                            </td>
                        @endfor
                    </tr>
                    
                    <tr>
                        <td></td>
                        <td>Maîtrise de la matière</td>
                        <td colspan="3" class="mastery-partielle">PART</td>
                        <td colspan="3" class="mastery-partielle">PART</td>
                        <td colspan="3" class="mastery-partielle">PART</td>
                    </tr>
                    
                    <tr>
                        <td></td>
                        <td>Maîtrise du palier</td>
                        <td colspan="9" class="mastery-partielle">PART</td>
                    </tr>
                @endif
            </tbody>
        </table>
        
        <!-- Signatures -->
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
            @if($isAnnual)
                <div style="border: 1px solid #333; padding: 10px; margin-top: 5px; text-align: center; background-color: #f0f0f0;">
                    <strong>Orienté en 3e année</strong>
                </div>
            @else
                <div style="border: 1px solid #333; padding: 10px; margin-top: 5px; text-align: center; background-color: #f0f0f0;">
                    <em>A compléter en fin d'année</em>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
