<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de Statistiques - {{ $schoolName }}</title>
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
        
        .performance-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .performance-excellent {
            background: #d4edda;
            color: #155724;
        }
        
        .performance-good {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .performance-average {
            background: #fff3cd;
            color: #856404;
        }
        
        .performance-poor {
            background: #f8d7da;
            color: #721c24;
        }
        
        .chart-placeholder {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-style: italic;
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
        
        .highlight {
            background: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
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
        <h2>Rapport de Statistiques - Année {{ $currentYear->name ?? '2024-2025' }}</h2>
    </div>
    
    <div class="export-info">
        Généré le {{ $exportDate }} | Page 1
    </div>
    
    <!-- Statistiques Générales -->
    <div class="section">
        <div class="section-title">📊 Statistiques Générales</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Élèves</div>
                <div class="stat-value">{{ number_format($basicStats['totalStudents'] ?? 0) }}</div>
                <div class="stat-subtitle">Inscrits cette année</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Élèves Actifs</div>
                <div class="stat-value">{{ number_format($basicStats['activeStudents'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($basicStats['activeStudents'] ?? 0) / max($basicStats['totalStudents'] ?? 1, 1)) * 100, 1) }}% du total</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Enseignants</div>
                <div class="stat-value">{{ number_format($basicStats['totalTeachers'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ $basicStats['activeTeachers'] ?? 0 }} actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Classes</div>
                <div class="stat-value">{{ number_format($basicStats['totalClasses'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ $basicStats['activeClasses'] ?? 0 }} actives</div>
            </div>
        </div>
    </div>
    
    <!-- Répartition par Genre -->
    <div class="section">
        <div class="section-title">👥 Répartition par Genre</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Masculin</div>
                <div class="stat-value">{{ number_format($basicStats['maleStudents'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($basicStats['maleStudents'] ?? 0) / max($basicStats['totalStudents'] ?? 1, 1)) * 100, 1) }}%</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Féminin</div>
                <div class="stat-value">{{ number_format($basicStats['femaleStudents'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($basicStats['femaleStudents'] ?? 0) / max($basicStats['totalStudents'] ?? 1, 1)) * 100, 1) }}%</div>
            </div>
        </div>
    </div>
    
    <!-- Performance Académique -->
    <div class="section">
        <div class="section-title">🎓 Performance Académique</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Moyenne Générale</div>
                <div class="stat-value">{{ number_format($academicStats['gradeStats']['averageGrade'] ?? 0, 2) }}/20</div>
                <div class="stat-subtitle">Sur {{ number_format($academicStats['gradeStats']['totalGrades'] ?? 0) }} notes</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Excellents (≥16)</div>
                <div class="stat-value">{{ number_format($academicStats['gradeStats']['gradeDistribution']['excellent'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($academicStats['gradeStats']['gradeDistribution']['excellent'] ?? 0) / max($academicStats['gradeStats']['totalGrades'] ?? 1, 1)) * 100, 1) }}%</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Bien (14-16)</div>
                <div class="stat-value">{{ number_format($academicStats['gradeStats']['gradeDistribution']['bien'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($academicStats['gradeStats']['gradeDistribution']['bien'] ?? 0) / max($academicStats['gradeStats']['totalGrades'] ?? 1, 1)) * 100, 1) }}%</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Insuffisants (<10)</div>
                <div class="stat-value">{{ number_format($academicStats['gradeStats']['gradeDistribution']['insuffisant'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($academicStats['gradeStats']['gradeDistribution']['insuffisant'] ?? 0) / max($academicStats['gradeStats']['totalGrades'] ?? 1, 1)) * 100, 1) }}%</div>
            </div>
        </div>
    </div>
    
    <!-- Top Performers -->
    @if(isset($academicStats['gradeStats']['topPerformers']) && count($academicStats['gradeStats']['topPerformers']) > 0)
    <div class="section">
        <div class="section-title">🏆 Top Performers</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Nom</th>
                    <th>Classe</th>
                    <th>Moyenne</th>
                </tr>
            </thead>
            <tbody>
                @foreach($academicStats['gradeStats']['topPerformers'] as $index => $student)
                <tr>
                    <td class="text-center">
                        @if($index == 0)
                            <span class="performance-badge performance-excellent">🥇</span>
                        @elseif($index == 1)
                            <span class="performance-badge performance-good">🥈</span>
                        @elseif($index == 2)
                            <span class="performance-badge performance-average">🥉</span>
                        @else
                            <span class="performance-badge performance-poor">{{ $index + 1 }}</span>
                        @endif
                    </td>
                    <td>{{ $student['name'] ?? 'N/A' }}</td>
                    <td>{{ $student['class'] ?? 'N/A' }}</td>
                    <td class="text-center"><strong>{{ number_format($student['average'] ?? 0, 2) }}/20</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <div class="page-break"></div>
    
    <!-- Statistiques Financières -->
    <div class="section">
        <div class="section-title">💰 Statistiques Financières</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Revenus Totaux</div>
                <div class="stat-value">{{ number_format($financialStats['totalRevenue'] ?? 0, 0, ',', ' ') }} FCFA</div>
                <div class="stat-subtitle">Année {{ $currentYear->name ?? '2024-2025' }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Revenus Mensuels</div>
                <div class="stat-value">{{ number_format($financialStats['monthlyRevenue'] ?? 0, 0, ',', ' ') }} FCFA</div>
                <div class="stat-subtitle">Moyenne mensuelle</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Paiements Terminés</div>
                <div class="stat-value">{{ number_format($financialStats['paymentsCompleted'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($financialStats['paymentsCompleted'] ?? 0) / max(($financialStats['paymentsCompleted'] ?? 0) + ($financialStats['paymentsPending'] ?? 0) + ($financialStats['paymentsCancelled'] ?? 0), 1)) * 100, 1) }}% du total</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Paiements En Attente</div>
                <div class="stat-value">{{ number_format($financialStats['paymentsPending'] ?? 0) }}</div>
                <div class="stat-subtitle">{{ round((($financialStats['paymentsPending'] ?? 0) / max(($financialStats['paymentsCompleted'] ?? 0) + ($financialStats['paymentsPending'] ?? 0) + ($financialStats['paymentsCancelled'] ?? 0), 1)) * 100, 1) }}% du total</div>
            </div>
        </div>
    </div>
    
    <!-- Présence -->
    <div class="section">
        <div class="section-title">📅 Statistiques de Présence</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Taux de Présence</div>
                <div class="stat-value">{{ number_format($attendanceStats['dailyAttendance']['rate'] ?? 0, 1) }}%</div>
                <div class="stat-subtitle">Aujourd'hui</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Présents</div>
                <div class="stat-value">{{ number_format($attendanceStats['dailyAttendance']['present'] ?? 0) }}</div>
                <div class="stat-subtitle">Sur {{ $attendanceStats['dailyAttendance']['total'] ?? 0 }} élèves</div>
            </div>
        </div>
    </div>
    
    <!-- Performance par Classe -->
    @if(isset($performanceStats['classEfficiency']) && count($performanceStats['classEfficiency']) > 0)
    <div class="section">
        <div class="section-title">📚 Performance par Classe</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Classe</th>
                    <th>Effectif</th>
                    <th>Moyenne</th>
                    <th>Efficacité</th>
                </tr>
            </thead>
            <tbody>
                @foreach($performanceStats['classEfficiency'] as $class)
                <tr>
                    <td><strong>{{ $class['name'] ?? 'N/A' }}</strong></td>
                    <td class="text-center">{{ $class['student_count'] ?? 0 }}</td>
                    <td class="text-center">{{ number_format($class['average_grade'] ?? 0, 2) }}/20</td>
                    <td class="text-center">
                        @php
                            $efficiency = $class['efficiency'] ?? 0;
                            $badgeClass = 'performance-poor';
                            if ($efficiency >= 80) $badgeClass = 'performance-excellent';
                            elseif ($efficiency >= 60) $badgeClass = 'performance-good';
                            elseif ($efficiency >= 40) $badgeClass = 'performance-average';
                        @endphp
                        <span class="performance-badge {{ $badgeClass }}">{{ number_format($efficiency, 1) }}%</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Performance des Enseignants -->
    @if(isset($performanceStats['teacherPerformance']) && count($performanceStats['teacherPerformance']) > 0)
    <div class="section">
        <div class="section-title">👨‍🏫 Performance des Enseignants</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Enseignant</th>
                    <th>Total Notes</th>
                    <th>Moyenne</th>
                    <th>Performance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($performanceStats['teacherPerformance'] as $teacher)
                <tr>
                    <td><strong>{{ $teacher['name'] ?? 'N/A' }}</strong></td>
                    <td class="text-center">{{ $teacher['total_grades'] ?? 0 }}</td>
                    <td class="text-center">{{ number_format($teacher['average_grade'] ?? 0, 2) }}/20</td>
                    <td class="text-center">
                        @php
                            $avgGrade = $teacher['average_grade'] ?? 0;
                            $performance = 'À améliorer';
                            $badgeClass = 'performance-poor';
                            if ($avgGrade > 15) {
                                $performance = 'Excellent';
                                $badgeClass = 'performance-excellent';
                            } elseif ($avgGrade > 12) {
                                $performance = 'Bon';
                                $badgeClass = 'performance-good';
                            } elseif ($avgGrade > 10) {
                                $performance = 'Moyen';
                                $badgeClass = 'performance-average';
                            }
                        @endphp
                        <span class="performance-badge {{ $badgeClass }}">{{ $performance }}</span>
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
