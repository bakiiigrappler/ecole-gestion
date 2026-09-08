<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Général - {{ $schoolName }}</title>
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
            border-bottom: 3px solid #2563eb;
        }
        
        .header h1 {
            color: #2563eb;
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
            background: linear-gradient(135deg, #2563eb, #1e40af);
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
            color: #2563eb;
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
            background: #2563eb;
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
        
        .page-break {
            page-break-before: always;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .mb-0 { margin-bottom: 0; }
        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 10px; }
        .mb-3 { margin-bottom: 15px; }
        .mb-4 { margin-bottom: 20px; }
        .mb-5 { margin-bottom: 25px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $schoolName }}</h1>
        <h2>Rapport Général - Année {{ $currentYear->name ?? '2024-2025' }}</h2>
    </div>
    
    <div class="export-info">
        Généré le {{ $exportDate }} | Page 1
    </div>
    
    <!-- Statistiques Générales -->
    <div class="section">
        <div class="section-title">📊 Vue d'Ensemble</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Élèves</div>
                <div class="stat-value">{{ number_format($totalStudents ?? 0) }}</div>
                <div class="stat-subtitle">Inscrits dans l'établissement</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Élèves Actifs</div>
                <div class="stat-value">{{ number_format($studentStats['actifs'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($studentStats['actifs'] ?? 0) / max($totalStudents ?? 1, 1)) * 100, 1) }}% du total</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Enseignants</div>
                <div class="stat-value">{{ number_format($totalTeachers ?? 0) }}</div>
                <div class="stat-subtitle">Personnel enseignant actif</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Classes</div>
                <div class="stat-value">{{ number_format($totalClasses ?? 0) }}</div>
                <div class="stat-subtitle">Classes actives</div>
            </div>
        </div>
    </div>
    
    <!-- Répartition par Genre -->
    <div class="section">
        <div class="section-title">👥 Répartition par Genre</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Masculin</div>
                <div class="stat-value">{{ number_format($studentStats['garcons'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($studentStats['garcons'] ?? 0) / max($totalStudents ?? 1, 1)) * 100, 1) }}%</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Féminin</div>
                <div class="stat-value">{{ number_format($studentStats['filles'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($studentStats['filles'] ?? 0) / max($totalStudents ?? 1, 1)) * 100, 1) }}%</div>
            </div>
        </div>
    </div>
    
    <!-- Inscriptions par Cycle -->
    <div class="section">
        <div class="section-title">🎓 Inscriptions par Cycle</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Collège</div>
                <div class="stat-value">{{ number_format($enrollmentsByCycle['college'] ?? 0) }}</div>
                <div class="stat-subtitle">Élèves inscrits</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Lycée</div>
                <div class="stat-value">{{ number_format($enrollmentsByCycle['lycee'] ?? 0) }}</div>
                <div class="stat-subtitle">Élèves inscrits</div>
            </div>
        </div>
    </div>
    
    <!-- Statut des Inscriptions -->
    <div class="section">
        <div class="section-title">📋 Statut des Inscriptions</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Nouveaux</div>
                <div class="stat-value">{{ number_format($enrollmentStats['nouveau'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($enrollmentStats['nouveau'] ?? 0) / max($enrollmentStats['total'] ?? 1, 1)) * 100, 1) }}%</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Passants</div>
                <div class="stat-value">{{ number_format($enrollmentStats['passant'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($enrollmentStats['passant'] ?? 0) / max($enrollmentStats['total'] ?? 1, 1)) * 100, 1) }}%</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Redoublants</div>
                <div class="stat-value">{{ number_format($enrollmentStats['redoublant'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($enrollmentStats['redoublant'] ?? 0) / max($enrollmentStats['total'] ?? 1, 1)) * 100, 1) }}%</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Inscriptions</div>
                <div class="stat-value">{{ number_format($enrollmentStats['total'] ?? 0) }}</div>
                <div class="stat-subtitle">Année {{ $currentYear->name ?? '2024-2025' }}</div>
            </div>
        </div>
    </div>
    
    <!-- Top 5 Classes -->
    @if(isset($classesByEnrollment) && count($classesByEnrollment) > 0)
    <div class="section">
        <div class="section-title">🏆 Top 5 Classes (Effectifs)</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Classe</th>
                    <th>Effectif</th>
                    <th>Capacité</th>
                    <th>Taux d'Occupation</th>
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
                    <td class="text-center">{{ $class->enrollments_count }}</td>
                    <td class="text-center">{{ $class->capacity ?? 30 }}</td>
                    <td class="text-center">
                        @php
                            $rate = $class->capacity > 0 ? round(($class->enrollments_count / $class->capacity) * 100) : 0;
                        @endphp
                        {{ $rate }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <div class="footer">
        <p>Rapport généré automatiquement par le système de gestion scolaire Egesco | {{ $exportDate }}</p>
    </div>
</body>
</html>
