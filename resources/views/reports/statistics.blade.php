@extends('layouts.app')

@section('title', 'Statistiques - Egesco')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Statistiques Globales</h1>
                <p class="text-muted mb-0">@if($currentYear) Année scolaire: <strong>{{ $currentYear->name }}</strong> @else Aucune année académique courante @endif</p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50">Élèves actifs</div>
                            <div class="fs-3 fw-bold">{{ number_format($activeStudents) }}</div>
                        </div>
                        <i class="bi bi-people-fill stats-icon"></i>
                    </div>
                    <div class="text-white-50 small mt-1">Total: {{ number_format($totalStudents) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50">Enseignants actifs</div>
                            <div class="fs-3 fw-bold">{{ number_format($activeTeachers) }}</div>
                        </div>
                        <i class="bi bi-person-workspace stats-icon"></i>
                    </div>
                    <div class="text-white-50 small mt-1">Total: {{ number_format($totalTeachers) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50">Classes actives</div>
                            <div class="fs-3 fw-bold">{{ number_format($activeClasses) }}</div>
                        </div>
                        <i class="bi bi-building stats-icon"></i>
                    </div>
                    <div class="text-white-50 small mt-1">Total: {{ number_format($totalClasses) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50">Revenus (mois)</div>
                            <div class="fs-3 fw-bold">{{ number_format($monthlyRevenue, 0, ',', ' ') }} FCFA</div>
                        </div>
                        <i class="bi bi-currency-exchange stats-icon"></i>
                    </div>
                    <div class="text-white-50 small mt-1">Total année: {{ number_format($totalRevenue, 0, ',', ' ') }} FCFA</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-wallet2 me-2"></i>Statut des paiements (inscriptions)</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="text-warning small">En attente</div>
                            <div class="fs-4 fw-bold">{{ number_format($enrollmentPaymentStats['pending']) }}</div>
                        </div>
                        <div class="col-3">
                            <div class="text-info small">Partiel</div>
                            <div class="fs-4 fw-bold">{{ number_format($enrollmentPaymentStats['partial']) }}</div>
                        </div>
                        <div class="col-3">
                            <div class="text-success small">Complet</div>
                            <div class="fs-4 fw-bold">{{ number_format($enrollmentPaymentStats['completed']) }}</div>
                        </div>
                        <div class="col-3">
                            <div class="text-danger small">En retard</div>
                            <div class="fs-4 fw-bold">{{ number_format($enrollmentPaymentStats['overdue']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-receipt me-2"></i>Paiements</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="text-success small">Terminés</div>
                            <div class="fs-4 fw-bold">{{ number_format($paymentsCompleted) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-warning small">En attente</div>
                            <div class="fs-4 fw-bold">{{ number_format($paymentsPending) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-secondary small">Annulés</div>
                            <div class="fs-4 fw-bold">{{ number_format($paymentsCancelled) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Inscriptions par cycle</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="text-primary small">Pré-primaire</div>
                            <div class="fs-4 fw-bold">{{ number_format($enrollmentsByCycle['preprimaire']) }}</div>
                        </div>
                        <div class="col-3">
                            <div class="text-primary small">Primaire</div>
                            <div class="fs-4 fw-bold">{{ number_format($enrollmentsByCycle['primaire']) }}</div>
                        </div>
                        <div class="col-3">
                            <div class="text-primary small">Collège</div>
                            <div class="fs-4 fw-bold">{{ number_format($enrollmentsByCycle['college']) }}</div>
                        </div>
                        <div class="col-3">
                            <div class="text-primary small">Lycée</div>
                            <div class="fs-4 fw-bold">{{ number_format($enrollmentsByCycle['lycee']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-3 mt-3">


        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Comparatif paiements</h6>
                </div>
                <div class="card-body">
                    <canvas id="paiementsRadarChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Répartition des inscriptions par cycle</h6>
                </div>
                <div class="card-body">
                    <canvas id="inscriptionsChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-3 mt-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Revenus mensuels (barres)</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenusBarChart" height="120"></canvas>
                </div>
            </div>
        </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Évolution des revenus mensuels</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="revenusChart" height="120"></canvas>
                    </div>
                </div>
            </div>
    </div>

<div class="row g-3 mt-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-disc me-2"></i>Répartition paiements (doughnut)</h6>
            </div>
            <div class="card-body">
                <canvas id="paiementsDoughnutChart" height="120"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-compass me-2"></i>Répartition par cycle (polar area)</h6>
            </div>
            <div class="card-body">
                <canvas id="cyclesPolarChart" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Revenus par mois (exemple avec données passées depuis le contrôleur)
        const revenusCtx = document.getElementById('revenusChart').getContext('2d');
        new Chart(revenusCtx, {
            type: 'line',
            data: {
                labels: @json($monthlyRevenueLabels),
                datasets: [{
                    label: 'Revenus (FCFA)',
                    data: @json($monthlyRevenueData),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,0.2)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true }
                }
            }
        });

        // Inscriptions par cycle
        const inscriptionsCtx = document.getElementById('inscriptionsChart').getContext('2d');
        new Chart(inscriptionsCtx, {
            type: 'pie',
            data: {
                labels: ['Pré-primaire', 'Primaire', 'Collège', 'Lycée'],
                datasets: [{
                    data: [
                        {{ $enrollmentsByCycle['preprimaire'] }},
                        {{ $enrollmentsByCycle['primaire'] }},
                        {{ $enrollmentsByCycle['college'] }},
                        {{ $enrollmentsByCycle['lycee'] }}
                    ],
                    backgroundColor: [
                        '#0d6efd', '#198754', '#ffc107', '#dc3545'
                    ]
                }]
            }
        });
        // Revenus mensuels - Bar chart
        const revenusBarCtx = document.getElementById('revenusBarChart').getContext('2d');
        new Chart(revenusBarCtx, {
            type: 'bar',
            data: {
                labels: @json($monthlyRevenueLabels),
                datasets: [{
                    label: 'Revenus (FCFA)',
                    data: @json($monthlyRevenueData),
                    backgroundColor: 'rgba(13,110,253,0.5)',
                    borderColor: '#0d6efd',
                    borderWidth: 1
                }]
            }
        });

        // Paiements Radar
        const radarCtx = document.getElementById('paiementsRadarChart').getContext('2d');
        new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: ['Terminés', 'En attente', 'Annulés'],
                datasets: [{
                    label: 'Paiements',
                    data: [{{ $paymentsCompleted }}, {{ $paymentsPending }}, {{ $paymentsCancelled }}],
                    backgroundColor: 'rgba(25,135,84,0.2)',
                    borderColor: '#198754',
                    pointBackgroundColor: '#198754'
                }]
            }
        });

        // Paiements Doughnut
        const doughnutCtx = document.getElementById('paiementsDoughnutChart').getContext('2d');
        new Chart(doughnutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Terminés', 'En attente', 'Annulés'],
                datasets: [{
                    data: [{{ $paymentsCompleted }}, {{ $paymentsPending }}, {{ $paymentsCancelled }}],
                    backgroundColor: ['#198754', '#ffc107', '#6c757d']
                }]
            }
        });

        // Cycles Polar Area
        const polarCtx = document.getElementById('cyclesPolarChart').getContext('2d');
        new Chart(polarCtx, {
            type: 'polarArea',
            data: {
                labels: ['Pré-primaire', 'Primaire', 'Collège', 'Lycée'],
                datasets: [{
                    data: [
                        {{ $enrollmentsByCycle['preprimaire'] }},
                        {{ $enrollmentsByCycle['primaire'] }},
                        {{ $enrollmentsByCycle['college'] }},
                        {{ $enrollmentsByCycle['lycee'] }}
                    ],
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545']
                }]
            }
        });

    });
</script>

@endsection


