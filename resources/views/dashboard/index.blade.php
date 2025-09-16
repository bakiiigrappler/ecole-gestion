@extends('layouts.app')

@section('title', 'Tableau de bord - Egesco')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Tableau de bord</h1>
                    <p class="text-muted">Vue d'ensemble de votre établissement scolaire</p>
                </div>
                <div>
                    <span class="badge bg-success fs-6">
                        <i class="bi bi-calendar3 me-1"></i>
                        Année scolaire 2024-2025
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Élèves</div>
                            <div class="h4 mb-0 font-weight-bold">{{ $totalStudents ?? 0 }}</div>
                            <div class="text-xs mt-1">
                                <span class="text-success"><i class="bi bi-arrow-up"></i> +12%</span> depuis le mois dernier
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card h-100" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Enseignants</div>
                            <div class="h4 mb-0 font-weight-bold">{{ $totalTeachers ?? 0 }}</div>
                            <div class="text-xs mt-1">
                                <span class="text-light"><i class="bi bi-check-circle"></i> Tous actifs</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-workspace stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card h-100" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Classes</div>
                            <div class="h4 mb-0 font-weight-bold">{{ $totalClasses ?? 0 }}</div>
                            <div class="text-xs mt-1">
                                <span class="text-light"><i class="bi bi-door-open"></i> Préprimaire & Primaire</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-building stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card h-100" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Revenus du mois</div>
                            <div class="h4 mb-0 font-weight-bold">{{ number_format($monthlyRevenue ?? 0, 0, ',', ' ') }} FCFA</div>
                            <div class="text-xs mt-1">
                                <span class="text-light"><i class="bi bi-graph-up"></i> {{ $paymentRate ?? 95 }}% collecté</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-cash-stack stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightning-charge me-2"></i>
                        Actions rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('students.create') }}" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="bi bi-person-plus fs-1 mb-2"></i>
                                <span>Nouvel élève</span>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('teachers.create') }}" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="bi bi-person-workspace fs-1 mb-2"></i>
                                <span>Nouvel enseignant</span>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('grades.create') }}" class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="bi bi-journal-plus fs-1 mb-2"></i>
                                <span>Saisir notes</span>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('payments.index') }}" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="bi bi-credit-card-2-front fs-1 mb-2"></i>
                                <span>Gestion paiements</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Recent Activities -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Activités récentes
                    </h5>
                    <a href="#" class="btn btn-sm btn-outline-primary">Voir tout</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <!-- Sample Activities -->
                        <div class="list-group-item border-0 py-3">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-person-plus"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="fw-bold">Nouvel élève inscrit</div>
                                    <div class="text-muted small">Kouassi Marie a été inscrite en CP1</div>
                                </div>
                                <div class="col-auto text-muted small">
                                    il y a 2h
                                </div>
                            </div>
                        </div>

                        <div class="list-group-item border-0 py-3">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-credit-card"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="fw-bold">Paiement reçu</div>
                                    <div class="text-muted small">Frais de scolarité - 50,000 FCFA</div>
                                </div>
                                <div class="col-auto text-muted small">
                                    il y a 3h
                                </div>
                            </div>
                        </div>

                        <div class="list-group-item border-0 py-3">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-journal-text"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="fw-bold">Notes saisies</div>
                                    <div class="text-muted small">Mathématiques - CM1 (25 élèves)</div>
                                </div>
                                <div class="col-auto text-muted small">
                                    il y a 5h
                                </div>
                            </div>
                        </div>

                        <div class="list-group-item border-0 py-3">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-calendar-check"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="fw-bold">Présences marquées</div>
                                    <div class="text-muted small">CE2 - 28/30 élèves présents</div>
                                </div>
                                <div class="col-auto text-muted small">
                                    il y a 1j
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-lg-4 mb-4">
            <!-- Attendance Today -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-calendar-check me-2"></i>
                        Présences aujourd'hui
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <div class="h2 text-success mb-2">{{ $todayAttendance ?? 95 }}%</div>
                        <div class="text-muted">{{ $presentStudents ?? 380 }}/{{ $totalStudents ?? 400 }} élèves présents</div>
                        <div class="progress mt-3" style="height: 10px;">
                            <div class="progress-bar bg-success" data-width="{{ $todayAttendance ?? 95 }}"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-calendar-event me-2"></i>
                        Événements à venir
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item border-0 py-2">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="fw-bold small">Réunion parents</div>
                                    <div class="text-muted small">CM2</div>
                                </div>
                                <div class="text-muted small">25 juil</div>
                            </div>
                        </div>
                        <div class="list-group-item border-0 py-2">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="fw-bold small">Compositions 1er trimestre</div>
                                    <div class="text-muted small">Toutes classes</div>
                                </div>
                                <div class="text-muted small">30 juil</div>
                            </div>
                        </div>
                        <div class="list-group-item border-0 py-2">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="fw-bold small">Rentrée des classes</div>
                                    <div class="text-muted small">Nouvelle année</div>
                                </div>
                                <div class="text-muted small">02 sep</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Class Overview -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bar-chart me-2"></i>
                        Vue d'ensemble par classe
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Classe</th>
                                    <th>Élèves inscrits</th>
                                    <th>Présence moyenne</th>
                                    <th>Enseignant titulaire</th>
                                    <th>Dernière évaluation</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                                                <small class="fw-bold">CP1</small>
                                            </div>
                                            <span class="fw-bold">CP1</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">25/30</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress me-2" style="width: 60px; height: 8px;">
                                                <div class="progress-bar bg-success" style="width: 92%"></div>
                                            </div>
                                            <small>92%</small>
                                        </div>
                                    </td>
                                    <td>Mme. Adjoua Koffi</td>
                                    <td>
                                        <span class="badge bg-warning text-dark">il y a 3j</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                                                <small class="fw-bold">CE1</small>
                                            </div>
                                            <span class="fw-bold">CE1</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">28/30</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress me-2" style="width: 60px; height: 8px;">
                                                <div class="progress-bar bg-success" style="width: 89%"></div>
                                            </div>
                                            <small>89%</small>
                                        </div>
                                    </td>
                                    <td>M. Konan Yao</td>
                                    <td>
                                        <span class="badge bg-success">Aujourd'hui</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                                                <small class="fw-bold">CM1</small>
                                            </div>
                                            <span class="fw-bold">CM1</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">30/30</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress me-2" style="width: 60px; height: 8px;">
                                                <div class="progress-bar bg-success" style="width: 95%"></div>
                                            </div>
                                            <small>95%</small>
                                        </div>
                                    </td>
                                    <td>Mme. Diabaté Fatou</td>
                                    <td>
                                        <span class="badge bg-primary">il y a 1j</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh dashboard every 5 minutes
    setTimeout(function() {
        window.location.reload();
    }, 300000);
    
    // Set progress bar width from data attribute
    const progressBar = document.querySelector('.progress-bar[data-width]');
    if (progressBar) {
        const width = progressBar.getAttribute('data-width');
        progressBar.style.width = width + '%';
    }
});
</script>
@endpush 
