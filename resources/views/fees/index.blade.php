@extends('layouts.app')

@section('title', 'Gestion des Frais - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item active">Frais scolaires</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Gestion des Frais Scolaires</h1>
                    <p class="text-muted">Système de gestion des frais modernisé avec architecture hiérarchique</p>
                </div>
                <div>
                    <a href="{{ route('fees.dashboard') }}" class="btn btn-primary">
                        <i class="bi bi-speedometer2 me-2"></i>
                        Nouveau Tableau de Bord
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Migration Notice -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle fs-4 me-3"></i>
                        <div>
                        <h5 class="alert-heading mb-1">Système Modernisé Disponible !</h5>
                        <p class="mb-0">
                            Un nouveau système de gestion des frais avec architecture hiérarchique est maintenant disponible. 
                            Il offre une meilleure organisation : <strong>Niveau → Classe → Étudiant</strong> avec intégration complète des paiements.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New System Cards -->
    <div class="row mb-4">
        <!-- Card principale - Tableau de Bord -->
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card h-100 border-primary shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-speedometer2 fs-3 me-3"></i>
                        <div>
                            <h5 class="card-title mb-0">Tableau de Bord</h5>
                            <small>Vue d'ensemble du système</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text">Vue d'ensemble complète avec statistiques en temps réel, frais échus et paiements récents.</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <i class="bi bi-graph-up me-1"></i>
                            Statistiques en temps réel
                        </div>
                        <a href="{{ route('fees.dashboard') }}" class="btn btn-primary">
                            <i class="bi bi-arrow-right me-2"></i>Accéder
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card principale - Paiements -->
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card h-100 border-success shadow-sm">
                <div class="card-header bg-success text-white">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-credit-card fs-3 me-3"></i>
                        <div>
                            <h5 class="card-title mb-0">Paiements</h5>
                            <small>Gestion des transactions</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text">Gérez les paiements avec intégration complète et traçabilité des frais.</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <i class="bi bi-shield-check me-1"></i>
                            Traçabilité complète
                        </div>
                        <a href="{{ route('payments.index') }}" class="btn btn-success">
                            <i class="bi bi-arrow-right me-2"></i>Gérer
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de gestion des frais -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="bi bi-gear me-2"></i>Configuration des Frais
            </h4>
                        </div>
        
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 border-info shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="bi bi-layers fs-2"></i>
                        </div>
                        </div>
                    <h5 class="card-title">Frais de Niveau</h5>
                    <p class="card-text">Gérez les frais applicables à tous les niveaux (Préprimaire, Primaire, Collège, Lycée).</p>
                    <div class="mt-auto">
                        <a href="{{ route('fees.level-fees') }}" class="btn btn-outline-info">
                            <i class="bi bi-gear me-2"></i>Configurer
                            </a>
                        </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 border-warning shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="bi bi-collection fs-2"></i>
        </div>
    </div>
                    <h5 class="card-title">Frais de Classe</h5>
                    <p class="card-text">Configurez les frais spécifiques à chaque classe avec suppléments et réductions.</p>
                    <div class="mt-auto">
                        <a href="{{ route('fees.class-fees') }}" class="btn btn-outline-warning">
                            <i class="bi bi-sliders me-2"></i>Personnaliser
                        </a>
                    </div>
                </div>
                                                </div>
                                            </div>
        
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 border-secondary shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="bi bi-people fs-2"></i>
                        </div>
                                    </div>
                    <h5 class="card-title">Frais d'Inscription</h5>
                    <p class="card-text">Suivez les frais assignés aux étudiants avec gestion des paiements en temps réel.</p>
                    <div class="mt-auto">
                        <a href="{{ route('fees.enrollment-fees') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-eye me-2"></i>Suivre
                        </a>
                        </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de rapports et analyses -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="bi bi-graph-up me-2"></i>Rapports et Analyses
            </h4>
</div>

        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card h-100 border-dark shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-file-earmark-text fs-4"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">Rapports Détaillés</h5>
                            <small class="text-muted">Analyses financières complètes</small>
                        </div>
                    </div>
                    <p class="card-text">Générez des rapports détaillés avec filtres avancés et analyses financières.</p>
                    <a href="{{ route('fees.report') }}" class="btn btn-dark">
                        <i class="bi bi-download me-2"></i>Générer Rapport
                    </a>
                            </div>
                        </div>
                    </div>
                    
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card h-100 border-primary shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-bar-chart fs-4"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">Statistiques Avancées</h5>
                            <small class="text-muted">Métriques et tendances</small>
                        </div>
                    </div>
                    <p class="card-text">Analysez les tendances de paiement et les performances financières de l'école.</p>
                    <a href="{{ route('reports.statistics') }}" class="btn btn-primary">
                        <i class="bi bi-graph-up me-2"></i>Voir Statistiques
                    </a>
                        </div>
                            </div>
                        </div>
                    </div>
                    
    <!-- Features Comparison -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-arrow-left-right me-2"></i>Comparaison des Systèmes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Ancien Système</h6>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-x-circle text-danger me-2"></i>Frais isolés sans hiérarchie</li>
                                <li><i class="bi bi-x-circle text-danger me-2"></i>Pas de lien direct avec les paiements</li>
                                <li><i class="bi bi-x-circle text-danger me-2"></i>Calculs manuels des montants</li>
                                <li><i class="bi bi-x-circle text-danger me-2"></i>Interface basique</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">Nouveau Système</h6>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-check-circle text-success me-2"></i>Architecture hiérarchique Niveau → Classe → Étudiant</li>
                                <li><i class="bi bi-check-circle text-success me-2"></i>Intégration complète avec les paiements</li>
                                <li><i class="bi bi-check-circle text-success me-2"></i>Calculs automatiques et statistiques en temps réel</li>
                                <li><i class="bi bi-check-circle text-success me-2"></i>Interface moderne avec modals et AJAX</li>
                            </ul>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>Aperçu Rapide du Système
                </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-item">
                                <div class="stat-icon bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                                    <i class="bi bi-layers"></i>
            </div>
                                <h3 class="text-primary mb-1">27</h3>
                                <p class="text-muted mb-0 small">Frais de Niveau</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-item">
                                <div class="stat-icon bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                                    <i class="bi bi-collection"></i>
                                </div>
                                <h3 class="text-success mb-1">6</h3>
                                <p class="text-muted mb-0 small">Frais de Classe</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-item">
                                <div class="stat-icon bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                                    <i class="bi bi-people"></i>
                    </div>
                                <h3 class="text-info mb-1">8</h3>
                                <p class="text-muted mb-0 small">Frais d'Inscription</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-item">
                                <div class="stat-icon bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                                <h3 class="text-warning mb-1">1.12M</h3>
                                <p class="text-muted mb-0 small">FCFA Total</p>
                            </div>
                        </div>
                    </div>
                </div>
                            </div>
                        </div>
                    </div>
                    
    <!-- Migration Guide -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-book me-2"></i>Guide de Migration
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <span class="fs-4">1</span>
                            </div>
                                <h6 class="mt-2">Configuration</h6>
                                <p class="text-muted small">Configurez les frais de niveau pour chaque cycle d'enseignement</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <span class="fs-4">2</span>
                    </div>
                                <h6 class="mt-2">Personnalisation</h6>
                                <p class="text-muted small">Ajustez les frais par classe avec suppléments ou réductions</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <div class="bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <span class="fs-4">3</span>
                                </div>
                                <h6 class="mt-2">Suivi</h6>
                                <p class="text-muted small">Suivez les paiements et générez des rapports détaillés</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour les cartes */
.card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 12px;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.card-header {
    border: none;
    border-radius: 12px 12px 0 0 !important;
    padding: 1.5rem;
}

.card-body {
    padding: 1.5rem;
}

/* Styles pour les icônes circulaires */
.bg-primary, .bg-success, .bg-info, .bg-warning, .bg-secondary, .bg-dark {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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

/* Styles pour les sections */
h4 {
    color: #2c3e50;
    font-weight: 600;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
}

/* Styles pour les cartes principales */
.card.border-primary {
    border-left: 4px solid #007bff !important;
}

.card.border-success {
    border-left: 4px solid #28a745 !important;
}

/* Styles pour les cartes de configuration */
.card.border-info {
    border-top: 4px solid #17a2b8 !important;
}

.card.border-warning {
    border-top: 4px solid #ffc107 !important;
}

.card.border-secondary {
    border-top: 4px solid #6c757d !important;
}

/* Styles pour les cartes de rapports */
.card.border-dark {
    border-left: 4px solid #343a40 !important;
}

/* Styles pour les statistiques */
.border-end {
    border-right: 1px solid #dee2e6 !important;
}

/* Responsive */
@media (max-width: 768px) {
    .border-end {
        border-right: none !important;
        border-bottom: 1px solid #dee2e6 !important;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
    }
    
    .card-header {
        padding: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    h4 {
        font-size: 1.25rem;
    }
}

/* Animation pour les icônes */
.bi {
    transition: transform 0.3s ease;
}

.card:hover .bi {
    transform: scale(1.1);
}

/* Styles pour les descriptions */
.card-text {
    color: #6c757d;
    line-height: 1.6;
}

/* Styles pour les petits textes */
.text-muted.small {
    font-size: 0.875rem;
    font-weight: 500;
}

/* Styles pour les statistiques */
.stat-item {
    transition: transform 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-2px);
}

.stat-icon {
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.stat-item:hover .stat-icon {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

/* Gradient pour l'en-tête des statistiques */
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
}

/* Styles pour les cartes de statistiques */
.card.shadow-sm {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}

.card.shadow-sm:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>

@if(session('error'))
    showToast('{{ session('error') }}', 'error');
@endif
</script>
@endpush
@endsection
