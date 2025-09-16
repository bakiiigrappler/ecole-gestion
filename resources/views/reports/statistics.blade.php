@extends('layouts.app')

@section('title', 'Statistiques Avancées - Egesco')

@section('content')
<div class="container-fluid">
    <!-- Header avec filtres avancés -->
    <div class="statistics-header mb-4">
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="bi bi-graph-up me-2"></i>Statistiques Avancées
                        </h1>
                        <p class="text-muted mb-0">
                            @if($currentYear) 
                                Année scolaire: <strong>{{ $currentYear->name }}</strong> 
                            @else 
                                Aucune année académique courante 
                            @endif
                        </p>
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="refreshStatistics()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Actualiser
                        </button>
                        <button class="btn btn-outline-secondary" onclick="exportStatistics()">
                            <i class="bi bi-download me-1"></i>Exporter
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filtres dynamiques -->
        <div class="filters-section">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Année académique</label>
                            <select class="form-select" id="academicYear">
                                <option value="current">Année courante</option>
                                @foreach(\App\Models\AcademicYear::orderBy('start_date', 'desc')->get() as $year)
                                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cycle</label>
                            <select class="form-select" id="cycle">
                                <option value="all">Tous les cycles</option>
                                <option value="preprimaire">🍼 Pré-primaire</option>
                                <option value="primaire">📝 Primaire</option>
                                <option value="college">🏫 Collège</option>
                                <option value="lycee">🎓 Lycée</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Période</label>
                            <select class="form-select" id="period">
                                <option value="current">Période courante</option>
                                <option value="month">Ce mois</option>
                                <option value="trimester">Ce trimestre</option>
                                <option value="year">Cette année</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button class="btn btn-outline-primary" onclick="applyFilters()">
                                    <i class="bi bi-funnel me-1"></i>Appliquer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 1: Modules KPI principaux -->
    <div class="row g-4 mb-5">
        <!-- Module Académique -->
        <div class="col-lg-3 col-md-6">
            <div class="stats-module academic-module">
                <div class="module-header">
                    <div class="module-icon">
                        <i class="bi bi-graduation-cap"></i>
                    </div>
                    <div class="module-title">
                        <h6>Performances Académiques</h6>
                        <span class="module-subtitle">Moyenne générale</span>
                    </div>
                </div>
                <div class="module-content">
                    <div class="main-value">{{ $academicStats['gradeStats']['averageGrade'] }}/20</div>
                    <div class="trend-indicator positive">
                        <i class="bi bi-arrow-up"></i>
                        <span>+2.3% vs mois dernier</span>
                    </div>
                </div>
                <div class="module-footer">
                    <div class="progress-indicator">
                        <div class="progress-bar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Module Présence -->
        <div class="col-lg-3 col-md-6">
            <div class="stats-module attendance-module">
                <div class="module-header">
                    <div class="module-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="module-title">
                        <h6>Taux de Présence</h6>
                        <span class="module-subtitle">Aujourd'hui</span>
                    </div>
                </div>
                <div class="module-content">
                    <div class="main-value">{{ $attendanceStats['dailyAttendance']['rate'] }}%</div>
                    <div class="detail-info">
                        <i class="bi bi-people"></i>
                        <span>{{ $attendanceStats['dailyAttendance']['present'] }}/{{ $attendanceStats['dailyAttendance']['total'] }} élèves</span>
                    </div>
                </div>
                <div class="module-footer">
                    <div class="progress-indicator">
                        <div class="progress-bar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Module Financier -->
        <div class="col-lg-3 col-md-6">
            <div class="stats-module financial-module">
                <div class="module-header">
                    <div class="module-icon">
                        <i class="bi bi-currency-exchange"></i>
                    </div>
                    <div class="module-title">
                        <h6>Revenus Mensuels</h6>
                        <span class="module-subtitle">Ce mois</span>
                    </div>
                </div>
                <div class="module-content">
                    <div class="main-value">{{ number_format($financialStats['monthlyRevenue'], 0, ',', ' ') }} FCFA</div>
                    <div class="trend-indicator positive">
                        <i class="bi bi-arrow-up"></i>
                        <span>+15.2% vs mois dernier</span>
                    </div>
                </div>
                <div class="module-footer">
                    <div class="progress-indicator">
                        <div class="progress-bar" style="width: 75%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Module Efficacité -->
        <div class="col-lg-3 col-md-6">
            <div class="stats-module efficiency-module">
                <div class="module-header">
                    <div class="module-icon">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div class="module-title">
                        <h6>Efficacité Opérationnelle</h6>
                        <span class="module-subtitle">Performance globale</span>
                    </div>
                </div>
                <div class="module-content">
                    <div class="main-value">{{ $performanceStats['operationalEfficiency'] }}%</div>
                    <div class="detail-info">
                        <i class="bi bi-check-circle"></i>
                        <span>Performance optimale</span>
                    </div>
                </div>
                <div class="module-footer">
                    <div class="progress-indicator">
                        <div class="progress-bar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Modules d'analyse -->
    <div class="modules-section mb-5">
        <div class="section-header mb-4">
            <h4 class="section-title">
                <i class="bi bi-bar-chart-line me-2"></i>Analyses Détaillées
            </h4>
            <p class="section-subtitle">Graphiques et analyses approfondies des performances</p>
        </div>
        <div class="row g-4">
        <!-- Graphique de progression des notes -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light text-dark d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Évolution des Performances
                    </h6>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary active" data-period="monthly">Mensuel</button>
                        <button class="btn btn-outline-primary" data-period="trimester">Trimestriel</button>
                        <button class="btn btn-outline-primary" data-period="yearly">Annuel</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="performanceEvolutionChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Top performers -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light text-dark">
                    <h6 class="mb-0">
                        <i class="bi bi-trophy me-2"></i>Meilleur élève
                    </h6>
                </div>
                <div class="card-body">
                    <div class="top-performers-list">
                        @forelse($academicStats['gradeStats']['topPerformers'] as $index => $student)
                        <div class="d-flex align-items-center mb-3">
                            <div class="rank-badge rank-{{ $index + 1 }}">
                                {{ $index + 1 }}
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-0">{{ $student['name'] }}</h6>
                                <small class="text-muted">{{ $student['class'] }}</small>
                            </div>
                            <div class="ms-auto">
                                <span class="badge bg-success">{{ $student['average'] }}/20</span>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted">
                            <i class="bi bi-trophy fs-1"></i>
                            <p>Aucune donnée disponible</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Modules de comparaison -->
    <div class="modules-section mb-5">
        <div class="section-header mb-4">
            <h4 class="section-title">
                <i class="bi bi-graph-up-arrow me-2"></i>Comparaisons et Tendances
            </h4>
            <p class="section-subtitle">Analyse comparative des performances par cycle et évolution temporelle</p>
        </div>
        <div class="row g-4">
        <!-- Comparaison par cycle -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light text-dark">
                    <h6 class="mb-0">
                        <i class="bi bi-bar-chart me-2"></i>Performance par Cycle
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="cycleComparisonChart" height="120"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Tendance des présences -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light text-dark">
                    <h6 class="mb-0">
                        <i class="bi bi-calendar3 me-2"></i>Tendance des Présences
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="attendanceTrendChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 4: Modules d'alertes -->
    <div class="modules-section mb-5">
        <div class="section-header mb-4">
            <h4 class="section-title">
                <i class="bi bi-exclamation-triangle me-2"></i>Alertes et Recommandations
            </h4>
            <p class="section-subtitle">Points d'attention et suggestions d'amélioration</p>
        </div>
        <div class="row g-4">
        <div class="col-lg-4">
            <div class="card alert-card">
                <div class="card-header bg-light text-dark">
                    <h6 class="mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>Alertes Académiques
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert-list">
                        @forelse($advancedMetrics['alerts'] as $alert)
                        <div class="alert-item">
                            <i class="bi {{ $alert['icon'] }} text-{{ $alert['type'] }}"></i>
                            <span>{{ $alert['message'] }}</span>
                        </div>
                        @empty
                        <div class="text-center text-success">
                            <i class="bi bi-check-circle fs-3"></i>
                            <p class="mt-2">Aucune alerte</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card alert-card">
                <div class="card-header bg-light text-dark">
                    <h6 class="mb-0">
                        <i class="bi bi-lightbulb me-2"></i>Recommandations
                    </h6>
                </div>
                <div class="card-body">
                    <div class="recommendation-list">
                        @forelse($advancedMetrics['recommendations'] as $recommendation)
                        <div class="recommendation-item">
                            <i class="bi {{ $recommendation['icon'] }} text-{{ $recommendation['type'] }}"></i>
                            <span>{{ $recommendation['message'] }}</span>
                        </div>
                        @empty
                        <div class="text-center text-muted">
                            <i class="bi bi-lightbulb fs-3"></i>
                            <p class="mt-2">Aucune recommandation</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card alert-card">
                <div class="card-header bg-light text-dark">
                    <h6 class="mb-0">
                        <i class="bi bi-check-circle me-2"></i>Points Positifs
                    </h6>
                </div>
                <div class="card-body">
                    <div class="positive-list">
                        <div class="positive-item">
                            <i class="bi bi-trophy text-warning"></i>
                            <span>{{ $advancedMetrics['kpis']['success_rate'] }}% de taux de réussite</span>
                        </div>
                        <div class="positive-item">
                            <i class="bi bi-currency-dollar text-success"></i>
                            <span>{{ $advancedMetrics['kpis']['payment_completion'] }}% paiements à jour</span>
                        </div>
                        <div class="positive-item">
                            <i class="bi bi-people-fill text-info"></i>
                            <span>{{ $advancedMetrics['kpis']['attendance_rate'] }}% taux de présence</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Section 5: Modules financiers -->
    <div class="modules-section mb-5">
        <div class="section-header mb-4">
            <h4 class="section-title">
                <i class="bi bi-currency-exchange me-2"></i>Analyses Financières
            </h4>
            <p class="section-subtitle">Revenus, paiements et analyses financières détaillées</p>
        </div>
        <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light text-dark">
                    <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Revenus mensuels (barres)</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenusBarChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light text-dark">
                    <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Évolution des revenus mensuels</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenusChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 6: Modules de répartition -->
    <div class="modules-section mb-5">
        <div class="section-header mb-4">
            <h4 class="section-title">
                <i class="bi bi-pie-chart me-2"></i>Répartitions et Distributions
            </h4>
            <p class="section-subtitle">Visualisation des données par catégories et cycles</p>
        </div>
        <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light text-dark">
                    <h6 class="mb-0"><i class="bi bi-disc me-2"></i>Répartition paiements (doughnut)</h6>
                </div>
                <div class="card-body">
                    <canvas id="paiementsDoughnutChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light text-dark">
                    <h6 class="mb-0"><i class="bi bi-compass me-2"></i>Répartition par cycle (polar area)</h6>
                </div>
                <div class="card-body">
                    <canvas id="cyclesPolarChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles CSS personnalisés -->
<style>
/* Modules KPI améliorés */
.stats-module {
    background: white;
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    position: relative;
    height: 200px;
    display: flex;
    flex-direction: column;
}

.stats-module:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.stats-module::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--module-color), var(--module-color-light));
}

.module-header {
    display: flex;
    align-items: center;
    padding: 20px 20px 15px;
    gap: 15px;
}

.module-icon {
    width: 50px;
    height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    background: var(--module-color);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.module-title h6 {
    margin: 0;
    font-weight: 600;
    color: #1f2937;
    font-size: 16px;
}

.module-subtitle {
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
}

.module-content {
    padding: 0 20px 15px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.main-value {
    font-size: 32px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
    margin-bottom: 8px;
}

.trend-indicator {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
}

.trend-indicator.positive {
    color: #059669;
}

.trend-indicator.negative {
    color: #dc2626;
}

.detail-info {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
}

.module-footer {
    padding: 0 20px 20px;
}

.progress-indicator {
    height: 6px;
    background: #f3f4f6;
    border-radius: 3px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: var(--module-color);
    border-radius: 3px;
    transition: width 0.6s ease;
}

/* Styles pour les modules d'alertes et recommandations */
.alert-card {
    height: 300px;
    display: flex;
    flex-direction: column;
}

.alert-card .card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.alert-list, .recommendation-list, .positive-list {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.alert-item, .recommendation-item, .positive-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.alert-item:last-child, .recommendation-item:last-child, .positive-item:last-child {
    border-bottom: none;
}

.alert-item i, .recommendation-item i, .positive-item i {
    margin-right: 0.75rem;
    font-size: 1.1rem;
}

.alert-item span, .recommendation-item span, .positive-item span {
    flex: 1;
    font-size: 0.9rem;
    line-height: 1.4;
}

/* Couleurs des modules */
.academic-module {
    --module-color: #8b5cf6;
    --module-color-light: #a78bfa;
}

.attendance-module {
    --module-color: #eab308;
    --module-color-light: #fbbf24;
}

.financial-module {
    --module-color: #22c55e;
    --module-color-light: #4ade80;
}

.efficiency-module {
    --module-color: #3b82f6;
    --module-color-light: #60a5fa;
}

/* Sections de modules */
.modules-section {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    border: 1px solid #e2e8f0;
}

.section-header {
    text-align: center;
    margin-bottom: 30px;
}

.section-title {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.section-subtitle {
    color: #64748b;
    font-size: 16px;
    margin: 0;
    font-weight: 400;
}

/* Responsive design amélioré */
@media (max-width: 768px) {
    .stats-module {
        height: 180px;
        margin-bottom: 20px;
    }
    
    .main-value {
        font-size: 28px;
    }
    
    .module-header {
        padding: 15px 15px 10px;
        gap: 12px;
    }
    
    .module-icon {
        width: 40px;
        height: 40px;
        font-size: 20px;
    }
    
    .alert-card {
        height: auto;
        min-height: 250px;
    }
    
    .alert-list, .recommendation-list, .positive-list {
        justify-content: flex-start;
    }
    
    .modules-section {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .section-title {
        font-size: 20px;
    }
    
    .section-subtitle {
        font-size: 14px;
    }
}

/* Anciens styles pour compatibilité */
.stats-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.academic-card {
    background-color: #8b5cf6;
    color: white;
}

.attendance-card {
    background-color: #eab308;
    color: white;
}

.financial-card {
    background-color: #22c55e;
    color: white;
}

.efficiency-card {
    background-color: #3b82f6;
    color: white;
}

.stats-icon {
    font-size: 2.5rem;
    opacity: 0.8;
}

.rank-badge {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
}

.rank-1 { background-color: #ffd700; }
.rank-2 { background-color: #c0c0c0; }
.rank-3 { background-color: #cd7f32; }
.rank-4, .rank-5 { background-color: #6c757d; }

.alert-item, .recommendation-item, .positive-item {
    display: flex;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.alert-item:last-child, .recommendation-item:last-child, .positive-item:last-child {
    border-bottom: none;
}

.alert-item i, .recommendation-item i, .positive-item i {
    margin-right: 10px;
    font-size: 1.2rem;
}

.filters-section .card {
    border: 1px solid #e3e6f0;
    border-radius: 10px;
}

.btn-group .btn {
    border-radius: 5px;
}

.btn-group .btn.active {
    background-color: #007bff;
    color: white;
}
</style>

<!-- Scripts JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Initialiser les graphiques
    initializeCharts();
    
    // Configurer les événements
    setupEventListeners();
});

function initializeCharts() {
    // Vérifier que les données existent
    console.log('Academic Trends:', []);
    console.log('Academic Periods:', []);
    console.log('Academic Grades:', []);
    
    // Graphique de progression des performances
    const performanceCtx = document.getElementById('performanceEvolutionChart').getContext('2d');
    
    // Données simplifiées pour éviter les erreurs
    const academicData = [];
    const labels = ['Période 1', 'Période 2', 'Période 3'];
    const grades = [0, 0, 0];
    
    console.log('Processed Labels:', labels);
    console.log('Processed Grades:', grades);
    
    new Chart(performanceCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Moyenne Générale',
                data: grades,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 20,
                    title: {
                        display: true,
                        text: 'Moyenne sur 20'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Période'
                    }
                }
            }
        }
    });

    // Graphique de comparaison par cycle
    const cycleCtx = document.getElementById('cycleComparisonChart').getContext('2d');
    new Chart(cycleCtx, {
        type: 'bar',
        data: {
            labels: ['Pré-primaire', 'Primaire', 'Collège', 'Lycée'],
            datasets: [{
                label: 'Moyenne par cycle',
                data: [0, 0, 0, 0],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 205, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 20
                }
            }
        }
    });

    // Graphique de tendance des présences
    console.log('Monthly Trends:', []);
    console.log('Monthly Periods:', []);
    console.log('Monthly Attendance:', []);
    
    const attendanceCtx = document.getElementById('attendanceTrendChart').getContext('2d');
    
    // Données simplifiées pour les présences
    const monthlyData = [];
    const monthlyLabels = ['Jan', 'Fév', 'Mar'];
    const attendanceData = [0, 0, 0];
    
    console.log('Processed Monthly Labels:', monthlyLabels);
    console.log('Processed Attendance Data:', attendanceData);
    
    new Chart(attendanceCtx, {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'Présences mensuelles',
                data: attendanceData,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true }
            }
        }
    });

    // Graphiques existants (conservés)
    initializeExistingCharts();
}

function initializeExistingCharts() {
    // Revenus par mois (exemple avec données passées depuis le contrôleur)
    console.log('Financial Stats:', {});
    console.log('Monthly Revenue Labels:', []);
    console.log('Monthly Revenue Data:', []);
    
    const revenusCtx = document.getElementById('revenusChart').getContext('2d');
    
    // Données simplifiées pour les revenus
    const revenueLabels = ['Jan', 'Fév', 'Mar'];
    const revenueData = [0, 0, 0];
    
    console.log('Processed Revenue Labels:', revenueLabels);
    console.log('Processed Revenue Data:', revenueData);
    
    new Chart(revenusCtx, {
        type: 'line',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Revenus (FCFA)',
                data: revenueData,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.2)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true }
            }
        }
    });

    // Revenus mensuels - Bar chart
    const revenusBarCtx = document.getElementById('revenusBarChart').getContext('2d');
    new Chart(revenusBarCtx, {
        type: 'bar',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Revenus (FCFA)',
                data: revenueData,
                backgroundColor: 'rgba(13,110,253,0.5)',
                borderColor: '#0d6efd',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Paiements Doughnut
    const doughnutCtx = document.getElementById('paiementsDoughnutChart').getContext('2d');
    new Chart(doughnutCtx, {
        type: 'doughnut',
        data: {
            labels: ['Terminés', 'En attente', 'Annulés'],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: ['#198754', '#ffc107', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Cycles Polar Area
    const polarCtx = document.getElementById('cyclesPolarChart').getContext('2d');
    new Chart(polarCtx, {
        type: 'polarArea',
        data: {
            labels: ['Pré-primaire', 'Primaire', 'Collège', 'Lycée'],
            datasets: [{
                data: [0, 0, 0, 0],
                backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function setupEventListeners() {
    // Boutons de période
    document.querySelectorAll('[data-period]').forEach(btn => {
        btn.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            document.querySelectorAll('[data-period]').forEach(b => b.classList.remove('active'));
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Ici vous pouvez ajouter la logique pour changer les données du graphique
            console.log('Période sélectionnée:', this.dataset.period);
        });
    });
}

function applyFilters() {
    const filters = {
        academic_year: document.getElementById('academicYear').value,
        cycle: document.getElementById('cycle').value,
        period: document.getElementById('period').value
    };
    
    console.log('Filtres appliqués:', filters);
    
    // Afficher un indicateur de chargement
    const applyBtn = document.querySelector('button[onclick="applyFilters()"]');
    const originalText = applyBtn.innerHTML;
    applyBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin me-1"></i>Application...';
    applyBtn.disabled = true;
    
    // Appel AJAX vers l'API
    fetch('{{ route("api.statistics") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(filters)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Données reçues:', data);
        updateStatistics(data);
        showNotification('Filtres appliqués avec succès', 'success');
    })
    .catch(error => {
        console.error('Erreur lors de l\'application des filtres:', error);
        showNotification('Erreur lors de l\'application des filtres', 'error');
    })
    .finally(() => {
        // Restaurer le bouton
        applyBtn.innerHTML = originalText;
        applyBtn.disabled = false;
    });
}

function updateStatistics(data) {
    // Mettre à jour les statistiques de base
    if (data.basicStats) {
        updateBasicStats(data.basicStats);
    }
    
    // Mettre à jour les statistiques académiques
    if (data.academicStats) {
        updateAcademicStats(data.academicStats);
    }
    
    // Mettre à jour les statistiques de présence
    if (data.attendanceStats) {
        updateAttendanceStats(data.attendanceStats);
    }
    
    // Mettre à jour les statistiques financières
    if (data.financialStats) {
        updateFinancialStats(data.financialStats);
    }
    
    // Mettre à jour les statistiques de performance
    if (data.performanceStats) {
        updatePerformanceStats(data.performanceStats);
    }
    
    // Recharger les graphiques
    initializeCharts();
}

function updateBasicStats(stats) {
    // Mettre à jour le nombre total d'étudiants
    const totalStudentsEl = document.querySelector('.academic-module .main-value');
    if (totalStudentsEl && stats.totalStudents !== undefined) {
        totalStudentsEl.textContent = stats.totalStudents.toLocaleString();
    }
    
    // Mettre à jour les étudiants actifs
    const activeStudentsEl = document.querySelector('.attendance-module .main-value');
    if (activeStudentsEl && stats.activeStudents !== undefined) {
        activeStudentsEl.textContent = stats.activeStudents.toLocaleString();
    }
}

function updateAcademicStats(stats) {
    // Mettre à jour la moyenne générale
    const averageGradeEl = document.querySelector('.academic-module .main-value');
    if (averageGradeEl && stats.gradeStats && stats.gradeStats.averageGrade !== undefined) {
        averageGradeEl.textContent = stats.gradeStats.averageGrade.toFixed(1) + '/20';
    }
}

function updateAttendanceStats(stats) {
    // Mettre à jour le taux de présence
    const attendanceRateEl = document.querySelector('.attendance-module .main-value');
    if (attendanceRateEl && stats.dailyAttendance && stats.dailyAttendance.rate !== undefined) {
        attendanceRateEl.textContent = stats.dailyAttendance.rate.toFixed(1) + '%';
    }
}

function updateFinancialStats(stats) {
    // Mettre à jour les revenus mensuels
    const monthlyRevenueEl = document.querySelector('.financial-module .main-value');
    if (monthlyRevenueEl && stats.monthlyRevenue !== undefined) {
        monthlyRevenueEl.textContent = new Intl.NumberFormat('fr-FR').format(stats.monthlyRevenue) + ' FCFA';
    }
}

function updatePerformanceStats(stats) {
    // Mettre à jour l'efficacité opérationnelle
    const efficiencyEl = document.querySelector('.efficiency-module .main-value');
    if (efficiencyEl && stats.operationalEfficiency !== undefined) {
        efficiencyEl.textContent = stats.operationalEfficiency.toFixed(1) + '%';
    }
}

function refreshStatistics() {
    // Afficher un indicateur de chargement
    const refreshBtn = document.querySelector('button[onclick="refreshStatistics()"]');
    const originalText = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin me-1"></i>Actualisation...';
    refreshBtn.disabled = true;
    
    // Simuler un délai de chargement
    setTimeout(() => {
        refreshBtn.innerHTML = originalText;
        refreshBtn.disabled = false;
        
        // Afficher une notification de succès
        showNotification('Statistiques actualisées avec succès!', 'success');
    }, 2000);
}

function exportStatistics() {
    // Logique d'export (PDF, Excel, etc.)
    showNotification('Fonction d\'export en cours de développement', 'info');
}

function showNotification(message, type = 'info') {
    // Créer une notification toast
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}

// Ajouter une classe CSS pour l'animation de rotation
const style = document.createElement('style');
style.textContent = `
    .spin {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>
@endsection