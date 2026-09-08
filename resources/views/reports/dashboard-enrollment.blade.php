<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Inscriptions - {{ $schoolName }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px 0;
            border-bottom: 3px solid #7c3aed;
        }
        
        .header h1 {
            color: #7c3aed;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .header h2 {
            color: #666;
            font-size: 16px;
            font-weight: normal;
        }
        
        .export-info {
            text-align: right;
            margin-bottom: 20px;
            font-size: 10px;
            color: #666;
        }
        
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: white;
            padding: 8px 15px;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 4px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
        }
        
        .stat-title {
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #7c3aed;
        }
        
        .stat-subtitle {
            font-size: 10px;
            color: #888;
            margin-top: 3px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th {
            background: #7c3aed;
            color: white;
            padding: 8px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }
        
        .table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 11px;
        }
        
        .table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-success {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #666;
            padding: 10px;
            border-top: 1px solid #e9ecef;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .cycle-comparison {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .cycle-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .cycle-item:last-child {
            border-bottom: none;
        }
        
        .cycle-label {
            font-weight: bold;
            color: #374151;
        }
        
        .cycle-value {
            color: #7c3aed;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $schoolName }}</h1>
        <h2>Rapport des Inscriptions - Année {{ $currentYear->name ?? '2024-2025' }}</h2>
    </div>
    
    <div class="export-info">
        Généré le {{ $exportDate }} | Page 1
    </div>
    
    <!-- Vue d'Ensemble des Inscriptions -->
    <div class="section">
        <div class="section-title">📋 Vue d'Ensemble des Inscriptions</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Inscriptions</div>
                <div class="stat-value">{{ number_format($enrollmentStats['total'] ?? 0) }}</div>
                <div class="stat-subtitle">Année {{ $currentYear->name ?? '2024-2025' }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Nouveaux Élèves</div>
                <div class="stat-value">{{ number_format($enrollmentStats['nouveau'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($enrollmentStats['nouveau'] ?? 0) / max($enrollmentStats['total'] ?? 1, 1)) * 100, 1) }}% du total</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Passants</div>
                <div class="stat-value">{{ number_format($enrollmentStats['passant'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($enrollmentStats['passant'] ?? 0) / max($enrollmentStats['total'] ?? 1, 1)) * 100, 1) }}% du total</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Redoublants</div>
                <div class="stat-value">{{ number_format($enrollmentStats['redoublant'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($enrollmentStats['redoublant'] ?? 0) / max($enrollmentStats['total'] ?? 1, 1)) * 100, 1) }}% du total</div>
            </div>
        </div>
    </div>
    
    <!-- Répartition par Cycle -->
    <div class="section">
        <div class="section-title">🎓 Répartition par Cycle d'Études</div>
        <div class="cycle-comparison">
            <div class="cycle-item">
                <span class="cycle-label">Collège</span>
                <span class="cycle-value">{{ number_format($enrollmentsByCycle['college'] ?? 0) }} élèves</span>
            </div>
            <div class="cycle-item">
                <span class="cycle-label">Lycée</span>
                <span class="cycle-value">{{ number_format($enrollmentsByCycle['lycee'] ?? 0) }} élèves</span>
            </div>
            <div class="cycle-item" style="border-top: 2px solid #7c3aed; margin-top: 10px; padding-top: 15px; font-weight: bold;">
                <span class="cycle-label">TOTAL</span>
                <span class="cycle-value">{{ number_format(($enrollmentsByCycle['college'] ?? 0) + ($enrollmentsByCycle['lycee'] ?? 0)) }} élèves</span>
            </div>
        </div>
    </div>
    
    <!-- Analyse des Statuts -->
    <div class="section">
        <div class="section-title">📊 Analyse des Statuts d'Inscription</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Taux de Nouveaux</div>
                <div class="stat-value">{{ round((($enrollmentStats['nouveau'] ?? 0) / max($enrollmentStats['total'] ?? 1, 1)) * 100, 1) }}%</div>
                <div class="stat-subtitle">Élèves nouvellement inscrits</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Taux de Passants</div>
                <div class="stat-value">{{ round((($enrollmentStats['passant'] ?? 0) / max($enrollmentStats['total'] ?? 1, 1)) * 100, 1) }}%</div>
                <div class="stat-subtitle">Élèves ayant réussi</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Taux de Redoublants</div>
                <div class="stat-value">{{ round((($enrollmentStats['redoublant'] ?? 0) / max($enrollmentStats['total'] ?? 1, 1)) * 100, 1) }}%</div>
                <div class="stat-subtitle">Élèves redoublant</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Taux de Réussite</div>
                <div class="stat-value">
                    @php
                        $successRate = round((($enrollmentStats['passant'] ?? 0) / max($enrollmentStats['total'] ?? 1, 1)) * 100, 1);
                    @endphp
                    {{ $successRate }}%
                </div>
                <div class="stat-subtitle">Passants + Nouveaux</div>
            </div>
        </div>
    </div>
    
    <!-- Top 5 Classes par Inscriptions -->
    @if(isset($classesByEnrollment) && count($classesByEnrollment) > 0)
    <div class="section">
        <div class="section-title">🏆 Top 5 Classes par Nombre d'Inscriptions</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Classe</th>
                    <th>Niveau</th>
                    <th>Effectif</th>
                    <th>Capacité</th>
                    <th>Taux d'Occupation</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($classesByEnrollment as $index => $class)
                <tr>
                    <td class="text-center">
                        @if($index == 0)
                            🥇
                        @elseif($index == 1)
                            🥈
                        @elseif($index == 2)
                            🥉
                        @else
                            {{ $index + 1 }}
                        @endif
                    </td>
                    <td><strong>{{ $class->name }}</strong></td>
                    <td>{{ $class->level->name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $class->enrollments_count }}</td>
                    <td class="text-center">{{ $class->capacity ?? 30 }}</td>
                    <td class="text-center">
                        @php
                            $rate = $class->capacity > 0 ? round(($class->enrollments_count / $class->capacity) * 100) : 0;
                        @endphp
                        {{ $rate }}%
                    </td>
                    <td class="text-center">
                        @if($rate >= 90)
                            <span class="badge badge-warning">SURCHARGÉE</span>
                        @elseif($rate >= 70)
                            <span class="badge badge-info">PLEINE</span>
                        @else
                            <span class="badge badge-success">DISPONIBLE</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Indicateurs de Performance -->
    <div class="section">
        <div class="section-title">📈 Indicateurs de Performance</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Moyenne par Classe</div>
                <div class="stat-value">
                    @php
                        $avgPerClass = $totalClasses > 0 ? round(($enrollmentStats['total'] ?? 0) / $totalClasses) : 0;
                    @endphp
                    {{ $avgPerClass }}
                </div>
                <div class="stat-subtitle">élèves par classe</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Taux d'Occupation Global</div>
                <div class="stat-value">
                    @php
                        $totalCapacity = $classesByEnrollment->sum('capacity') ?? ($totalClasses * 30);
                        $globalOccupancy = $totalCapacity > 0 ? round((($enrollmentStats['total'] ?? 0) / $totalCapacity) * 100) : 0;
                    @endphp
                    {{ $globalOccupancy }}%
                </div>
                <div class="stat-subtitle">de la capacité totale</div>
            </div>
        </div>
    </div>
    
    <!-- Recommandations -->
    <div class="section">
        <div class="section-title">💡 Recommandations</div>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #7c3aed;">
            @php
                $successRate = round((($enrollmentStats['passant'] ?? 0) / max($enrollmentStats['total'] ?? 1, 1)) * 100, 1);
                $newStudentRate = round((($enrollmentStats['nouveau'] ?? 0) / max($enrollmentStats['total'] ?? 1, 1)) * 100, 1);
            @endphp
            
            @if($successRate < 60)
                <p style="margin-bottom: 10px; font-size: 11px;"><strong>⚠️ Attention :</strong> Le taux de réussite est faible. Il est recommandé de :</p>
                <ul style="margin-left: 20px; font-size: 11px; color: #666;">
                    <li>Analyser les causes des redoublements</li>
                    <li>Mettre en place un système de soutien scolaire</li>
                    <li>Améliorer les méthodes pédagogiques</li>
                </ul>
            @elseif($newStudentRate > 50)
                <p style="margin-bottom: 10px; font-size: 11px;"><strong>📈 Croissance :</strong> Forte proportion de nouveaux élèves. Il est recommandé de :</p>
                <ul style="margin-left: 20px; font-size: 11px; color: #666;">
                    <li>Prévoir des cours d'intégration</li>
                    <li>Renforcer l'accompagnement des nouveaux</li>
                    <li>Adapter les capacités d'accueil</li>
                </ul>
            @else
                <p style="margin-bottom: 10px; font-size: 11px;"><strong>✅ Équilibre :</strong> Bon équilibre entre nouveaux et passants. Continuez sur cette lancée.</p>
            @endif
        </div>
    </div>
    
    <div class="footer">
        <p>Rapport des inscriptions généré automatiquement par le système de gestion scolaire Egesco | {{ $exportDate }}</p>
    </div>
</body>
</html>
