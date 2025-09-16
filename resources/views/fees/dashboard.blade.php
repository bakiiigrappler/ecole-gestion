@extends('layouts.app')

@section('title', 'Tableau de Bord - Frais Scolaires - StudiaGabon')

@section('breadcrumb')
<li class="breadcrumb-item active">Frais Scolaires</li>
<li class="breadcrumb-item active">Tableau de Bord</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Tableau de Bord - Frais Scolaires</h1>
                    <p class="text-muted">Vue d'ensemble de la gestion des frais pour {{ $academicYear->name }}</p>
                </div>
                <div>
                    <div class="btn-group" role="group">
                        <a href="{{ route('fees.level-fees') }}" class="btn btn-outline-primary">
                            <i class="bi bi-layers me-2"></i>Frais de Niveau
                        </a>
                        <a href="{{ route('fees.class-fees') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-collection me-2"></i>Frais de Classe
                        </a>
                        <a href="{{ route('fees.enrollment-fees') }}" class="btn btn-outline-info">
                            <i class="bi bi-people me-2"></i>Frais d'Inscription
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card stats-card revenue-card shadow-lg">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stats-icon-wrapper">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="card-title text-dark mb-1">Total des Frais</h6>
                            <h3 class="text-dark mb-0">{{ number_format($statistics['total_fees'], 0, ',', ' ') }} FCFA</h3>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-white-50">
                            <i class="bi bi-arrow-up me-1"></i>
                            Montant total configuré
                        </small>
                        <div class="stats-trend">
                            <i class="bi bi-graph-up text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card stats-card success-card shadow-lg">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stats-icon-wrapper">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="card-title text-dark mb-1">Montant Collecté</h6>
                            <h3 class="text-dark mb-0">{{ number_format($statistics['total_collected'], 0, ',', ' ') }} FCFA</h3>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-white-50">
                            <i class="bi bi-arrow-up me-1"></i>
                            Paiements effectués
                        </small>
                        <div class="stats-trend">
                            <i class="bi bi-shield-check text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card stats-card pending-card shadow-lg">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stats-icon-wrapper">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="card-title text-dark mb-1">Taux de Collecte</h6>
                            <h3 class="text-dark mb-0">{{ $statistics['collection_rate'] }}%</h3>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-white-50">
                            <i class="bi bi-percent me-1"></i>
                            Efficacité de collecte
                        </small>
                        <div class="stats-trend">
                            <i class="bi bi-speedometer2 text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card stats-card @if($statistics['overdue_count'] > 0) overdue-card @else success-card @endif shadow-lg">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stats-icon-wrapper">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="card-title text-dark mb-1">Frais Échus</h6>
                            <h3 class="text-dark mb-0">{{ $statistics['overdue_count'] }}</h3>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-white-50">
                            <i class="bi bi-clock me-1"></i>
                            @if($statistics['overdue_count'] > 0) Attention requise @else Aucun retard @endif
                        </small>
                        <div class="stats-trend">
                            <i class="bi bi-{{ $statistics['overdue_count'] > 0 ? 'exclamation-triangle' : 'check-circle' }} text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Frais par Niveau -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex align-items-center">
                        <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="bi bi-layers"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0 text-dark">Frais par Niveau</h5>
                            <small class="text-dark">Configuration des frais par cycle</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($feesByLevel) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Niveau</th>
                                        <th>Frais</th>
                                        <th>Montant Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($feesByLevel as $levelData)
                                        <tr>
                                            <td>
                                                <strong class="text-dark">{{ $levelData['level']->name }}</strong>
                                                <br>
                                                <small class="text-dark">{{ $levelData['level']->cycle }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $levelData['fees_count'] }}</span>
                                            </td>
                                            <td>
                                                <strong class="text-dark">{{ number_format($levelData['total_amount'], 0, ',', ' ') }} FCFA</strong>
                                            </td>
                                            <td>
                                                <a href="{{ route('fees.level-fees') }}?level={{ $levelData['level']->id }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <p class="text-muted mt-2">Aucun frais de niveau configuré</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Frais par Classe -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <div class="d-flex align-items-center">
                        <div class="bg-white text-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="bi bi-collection"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0 text-dark">Frais par Classe</h5>
                            <small class="text-dark">Personnalisation par classe</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($feesByClass) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Classe</th>
                                        <th>Frais</th>
                                        <th>Montant Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($feesByClass as $classData)
                                        <tr>
                                            <td>
                                                <strong class="text-dark">{{ $classData['class']->name }}</strong>
                                                <br>
                                                <small class="text-dark">{{ $classData['class']->level->name ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $classData['fees_count'] }}</span>
                                            </td>
                                            <td>
                                                <strong class="text-dark">{{ number_format($classData['total_amount'], 0, ',', ' ') }} FCFA</strong>
                                            </td>
                                            <td>
                                                <a href="{{ route('fees.class-fees') }}?class={{ $classData['class']->id }}" 
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <p class="text-muted mt-2">Aucun frais de classe configuré</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Frais Échus -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header @if($overdueFees->count() > 0) bg-danger @else bg-success @endif text-white">
                    <div class="d-flex align-items-center">
                        <div class="bg-white @if($overdueFees->count() > 0) text-danger @else text-success @endif rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0 text-dark">Frais Échus</h5>
                            <small class="text-dark">@if($overdueFees->count() > 0) Attention requise @else Aucun retard @endif</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($overdueFees->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Étudiant</th>
                                        <th>Frais</th>
                                        <th>Montant</th>
                                        <th>Échu depuis</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($overdueFees as $fee)
                                        <tr>
                                            <td>
                                                <strong class="text-dark">{{ $fee->enrollment->student->first_name }} {{ $fee->enrollment->student->last_name }}</strong>
                                                <br>
                                                <small class="text-dark">{{ $fee->enrollment->schoolClass->name }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">{{ $fee->fee_type_label }}</span>
                                            </td>
                                            <td>
                                                <strong class="text-dark">{{ $fee->formatted_amount }}</strong>
                                            </td>
                                            <td>
                                                <span class="text-danger">
                                                    {{ $fee->days_overdue }} jour(s)
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ route('fees.enrollment-fees', ['is_overdue' => 1]) }}" 
                               class="btn btn-outline-danger">
                                Voir tous les frais échus
                            </a>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-check-circle display-4 text-success"></i>
                            <p class="text-success mt-2">Aucun frais échu !</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Paiements Récents -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <div class="d-flex align-items-center">
                        <div class="bg-white text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0 text-dark">Paiements Récents</h5>
                            <small class="text-dark">Dernières transactions</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($recentFees->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Étudiant</th>
                                        <th>Frais</th>
                                        <th>Montant</th>
                                        <th>Payé le</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentFees as $fee)
                                        <tr>
                                            <td>
                                                <strong class="text-dark">{{ $fee->enrollment->student->first_name }} {{ $fee->enrollment->student->last_name }}</strong>
                                                <br>
                                                <small class="text-dark">{{ $fee->enrollment->schoolClass->name }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">{{ $fee->fee_type_label }}</span>
                                            </td>
                                            <td>
                                                <strong class="text-dark">{{ $fee->formatted_amount }}</strong>
                                            </td>
                                            <td>
                                                <small class="text-dark">
                                                    {{ $fee->paid_at ? $fee->paid_at->format('d/m/Y') : 'N/A' }}
                                                </small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ route('fees.enrollment-fees', ['is_paid' => 1]) }}" 
                               class="btn btn-outline-success">
                                Voir tous les paiements
                            </a>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <p class="text-muted mt-2">Aucun paiement récent</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour les cartes de statistiques */
.stats-card {
    border: none;
    border-radius: 16px;
    transition: all 0.3s ease;
    overflow: hidden;
    height: 180px;
    display: flex;
    flex-direction: column;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15) !important;
}

.revenue-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.success-card {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.pending-card {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
}

.overdue-card {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    color: white;
}

.stats-icon-wrapper {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.stats-trend {
    font-size: 1.2rem;
}

/* Styles pour les cartes de contenu */
.card {
    border: none;
    border-radius: 12px;
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.card-header {
    border: none;
    border-radius: 12px 12px 0 0 !important;
    padding: 1.5rem;
}

.card-body {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* Styles pour les tableaux */
.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    vertical-align: middle;
    border-top: 1px solid #f1f3f4;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Styles pour les badges */
.badge {
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Styles pour les boutons */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Styles pour les icônes dans les en-têtes */
.card-header .bg-white {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

/* Animation pour les icônes */
.bi {
    transition: transform 0.3s ease;
}

.card:hover .bi {
    transform: scale(1.1);
}

/* Responsive */
@media (max-width: 768px) {
    .stats-icon-wrapper {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
    
    .card-header {
        padding: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .stats-card .card-body {
    padding: 1.25rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
}

/* Styles pour les états vides */
.text-center.py-4 {
    padding: 2rem 1rem;
}

.text-center.py-4 .display-4 {
    font-size: 3rem;
    opacity: 0.5;
}

/* Styles pour les ombres */
.shadow-sm {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}

.shadow-lg {
    box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175) !important;
}
</style>
@endsection
