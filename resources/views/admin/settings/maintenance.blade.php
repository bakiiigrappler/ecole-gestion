@extends('layouts.app')

@section('title', 'Maintenance Système - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Administration</a></li>
<li class="breadcrumb-item active">Maintenance</li>
@endsection

@push('styles')
<style>
:root {
    --primary-blue: #2563eb;
    --success-green: #059669;
    --warning-orange: #d97706;
    --danger-red: #dc2626;
    --info-cyan: #0891b2;
    --purple-violet: #7c3aed;
    --gray-neutral: #6b7280;
}

.maintenance-header {
    background: linear-gradient(135deg, var(--danger-red) 0%, var(--warning-orange) 100%);
    color: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.tool-card {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow: hidden;
}

.tool-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.tool-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    margin-bottom: 1rem;
}

.tool-icon.cache { background: linear-gradient(135deg, var(--info-cyan), var(--primary-blue)); }
.tool-icon.optimize { background: linear-gradient(135deg, var(--success-green), var(--info-cyan)); }
.tool-icon.backup { background: linear-gradient(135deg, var(--warning-orange), var(--danger-red)); }
.tool-icon.logs { background: linear-gradient(135deg, var(--purple-violet), var(--primary-blue)); }

.btn-maintenance {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
    border: 2px solid;
}

.btn-cache {
    background-color: white;
    border-color: var(--info-cyan);
    color: var(--info-cyan);
}

.btn-cache:hover {
    background-color: var(--info-cyan);
    color: white;
    transform: translateY(-1px);
}

.btn-optimize {
    background-color: white;
    border-color: var(--success-green);
    color: var(--success-green);
}

.btn-optimize:hover {
    background-color: var(--success-green);
    color: white;
    transform: translateY(-1px);
}

.btn-backup {
    background-color: white;
    border-color: var(--warning-orange);
    color: var(--warning-orange);
}

.btn-backup:hover {
    background-color: var(--warning-orange);
    color: white;
    transform: translateY(-1px);
}

.btn-danger-maintenance {
    background-color: white;
    border-color: var(--danger-red);
    color: var(--danger-red);
}

.btn-danger-maintenance:hover {
    background-color: var(--danger-red);
    color: white;
    transform: translateY(-1px);
}

.system-info {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.system-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.system-info-item:last-child {
    border-bottom: none;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 0.5rem;
}

.status-good { background-color: var(--success-green); }
.status-warning { background-color: var(--warning-orange); }
.status-error { background-color: var(--danger-red); }

.warning-zone {
    border: 2px dashed var(--warning-orange);
    border-radius: 12px;
    padding: 1.5rem;
    background-color: #fff7ed;
}

.danger-zone {
    border: 2px dashed var(--danger-red);
    border-radius: 12px;
    padding: 1.5rem;
    background-color: #fef2f2;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="maintenance-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 mb-2 fw-bold">
                    <i class="bi bi-tools me-3"></i>
                    Maintenance Système
                </h1>
                <p class="mb-0 opacity-75">Outils de maintenance et optimisation pour superadmin</p>
            </div>
            <div>
                <span class="badge bg-danger fs-6">
                    <i class="bi bi-shield-exclamation me-1"></i>
                    SuperAdmin Only
                </span>
            </div>
        </div>
    </div>

    <!-- Messages de statut -->
    @if(session('success'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <!-- Informations système -->
        <div class="col-lg-6">
            <div class="card tool-card mb-4">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        État du Système
                    </h5>
                </div>
                <div class="card-body">
                    <div class="system-info">
                        <div class="system-info-item">
                            <span>
                                <span class="status-indicator {{ $maintenanceInfo['cache_status'] ? 'status-good' : 'status-error' }}"></span>
                                Cache Application
                            </span>
                            <strong>{{ $maintenanceInfo['cache_status'] ? 'Actif' : 'Inactif' }}</strong>
                        </div>
                        <div class="system-info-item">
                            <span>
                                <i class="bi bi-clock-history me-2"></i>
                                Dernière sauvegarde
                            </span>
                            <strong>{{ $maintenanceInfo['last_backup'] ?: 'Jamais' }}</strong>
                        </div>
                        <div class="system-info-item">
                            <span>
                                <i class="bi bi-file-text me-2"></i>
                                Taille des logs
                            </span>
                            <strong>{{ $maintenanceInfo['logs_size'] }} MB</strong>
                        </div>
                        <div class="system-info-item">
                            <span>
                                <span class="status-indicator {{ $maintenanceInfo['failed_jobs'] > 0 ? 'status-error' : 'status-good' }}"></span>
                                Jobs échoués
                            </span>
                            <strong>{{ $maintenanceInfo['failed_jobs'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Outils de maintenance -->
        <div class="col-lg-6">
            <div class="card tool-card mb-4">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-wrench me-2 text-primary"></i>
                        Outils Rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="tool-icon cache mx-auto">
                                    <i class="bi bi-lightning"></i>
                                </div>
                                <h6>Vider Cache</h6>
                                <button class="btn btn-cache btn-maintenance w-100" onclick="clearCache()">
                                    <i class="bi bi-trash me-1"></i>
                                    Vider
                                </button>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="tool-icon optimize mx-auto">
                                    <i class="bi bi-speedometer2"></i>
                                </div>
                                <h6>Optimiser</h6>
                                <button class="btn btn-optimize btn-maintenance w-100" onclick="optimizeApp()">
                                    <i class="bi bi-gear me-1"></i>
                                    Optimiser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions avancées -->
    <div class="row">
        <div class="col-lg-6">
            <div class="warning-zone mb-4">
                <h6 class="text-warning mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Actions Avancées
                </h6>
                <p class="text-muted small mb-3">
                    Ces actions peuvent temporairement affecter les performances du système.
                </p>
                <div class="d-grid gap-2">
                    <button class="btn btn-backup btn-maintenance" onclick="createBackup()">
                        <i class="bi bi-download me-2"></i>
                        Créer une sauvegarde
                    </button>
                    <button class="btn btn-warning" onclick="clearLogs()">
                        <i class="bi bi-file-earmark-x me-2"></i>
                        Nettoyer les logs
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="danger-zone">
                <h6 class="text-danger mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Zone de Danger
                </h6>
                <p class="text-muted small mb-3">
                    <strong>Attention :</strong> Ces actions peuvent affecter le fonctionnement de l'application.
                </p>
                <div class="d-grid gap-2">
                    <button class="btn btn-danger-maintenance" onclick="restartServices()">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        Redémarrer les services
                    </button>
                    <button class="btn btn-danger-maintenance" onclick="resetApplication()">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Reset application
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Monitoring détaillé -->
    <div class="row">
        <div class="col-12">
            <div class="card tool-card">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2 text-primary"></i>
                        Monitoring Système
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="tool-icon logs mx-auto mb-2">
                                    <i class="bi bi-server"></i>
                                </div>
                                <h6>Serveur</h6>
                                <p class="text-success mb-0">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Opérationnel
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="tool-icon cache mx-auto mb-2">
                                    <i class="bi bi-database"></i>
                                </div>
                                <h6>Base de données</h6>
                                <p class="text-success mb-0">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Connectée
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="tool-icon optimize mx-auto mb-2">
                                    <i class="bi bi-hdd"></i>
                                </div>
                                <h6>Stockage</h6>
                                <p class="text-warning mb-0">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    75% utilisé
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="tool-icon backup mx-auto mb-2">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <h6>Sécurité</h6>
                                <p class="text-success mb-0">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Sécurisé
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay d-none" id="loadingOverlay">
    <div class="loading-content">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Chargement...</span>
        </div>
        <h5 id="loadingText">Opération en cours...</h5>
        <p class="text-muted mb-0">Veuillez patienter</p>
    </div>
</div>

<script>
function showLoading(text = 'Opération en cours...') {
    document.getElementById('loadingText').textContent = text;
    document.getElementById('loadingOverlay').classList.remove('d-none');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('d-none');
}

function clearCache() {
    if (!confirm('Vider le cache peut temporairement ralentir l\'application. Continuer ?')) {
        return;
    }
    
    showLoading('Vidage du cache en cours...');
    
    fetch('{{ route("admin.maintenance.clear-cache") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Erreur:', error);
        alert('❌ Une erreur est survenue.');
    });
}

function optimizeApp() {
    if (!confirm('Optimiser l\'application peut prendre quelques minutes. Continuer ?')) {
        return;
    }
    
    showLoading('Optimisation en cours...');
    
    fetch('{{ route("admin.maintenance.optimize") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Erreur:', error);
        alert('❌ Une erreur est survenue.');
    });
}

function createBackup() {
    if (!confirm('Créer une sauvegarde peut prendre plusieurs minutes selon la taille de la base de données. Continuer ?')) {
        return;
    }
    
    showLoading('Création de la sauvegarde...');
    
    fetch('{{ route("admin.maintenance.backup") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Erreur:', error);
        alert('❌ Une erreur est survenue.');
    });
}

function clearLogs() {
    if (!confirm('⚠️ Supprimer les logs effacera l\'historique des erreurs. Continuer ?')) {
        return;
    }
    
    alert('ℹ️ Fonctionnalité en développement');
}

function restartServices() {
    if (!confirm('⚠️ ATTENTION : Redémarrer les services peut interrompre temporairement l\'accès à l\'application. Continuer ?')) {
        return;
    }
    
    alert('ℹ️ Fonctionnalité en développement');
}

function resetApplication() {
    if (!confirm('🚨 DANGER : Reset complet de l\'application ! Cette action est IRRÉVERSIBLE ! Continuer ?')) {
        return;
    }
    
    if (!confirm('🚨 DERNIÈRE CONFIRMATION : Êtes-vous ABSOLUMENT certain de vouloir réinitialiser l\'application ?')) {
        return;
    }
    
    alert('ℹ️ Fonctionnalité désactivée pour la sécurité');
}
</script>
@endsection
