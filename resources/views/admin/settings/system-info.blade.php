@extends('layouts.app')

@section('title', 'Informations Système - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Administration</a></li>
<li class="breadcrumb-item active">Informations système</li>
@endsection

@push('styles')
<style>
:root {
    --primary-blue: #2563eb;
    --success-green: #059669;
    --warning-orange: #d97706;
    --danger-red: #dc2626;
    --info-cyan: #0891b2;
    --gray-neutral: #6b7280;
}

.system-header {
    background: linear-gradient(135deg, var(--primary-blue), #1e40af);
    color: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.info-card {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.info-card-header {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border-bottom: 1px solid #e5e7eb;
    padding: 1rem 1.5rem;
    font-weight: 600;
    border-radius: 12px 12px 0 0;
}

.info-item {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: var(--gray-neutral);
    min-width: 150px;
}

.info-value {
    flex: 1;
    text-align: right;
}

.status-good { color: var(--success-green); }
.status-warning { color: var(--warning-orange); }
.status-error { color: var(--danger-red); }

.badge-status {
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-good { background-color: var(--success-green); color: white; }
.badge-warning { background-color: var(--warning-orange); color: white; }
.badge-error { background-color: var(--danger-red); color: white; }

.extension-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.5rem;
}

.extension-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: #f8fafc;
    border-radius: 6px;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="system-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 mb-2 fw-bold">
                    <i class="bi bi-info-circle me-3"></i>
                    Informations Système
                </h1>
                <p class="mb-0 opacity-75">Détails sur l'installation et performances</p>
            </div>
            <div>
                <button class="btn btn-light btn-lg" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i>
                    Imprimer
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informations PHP -->
        <div class="col-lg-6 mb-4">
            <div class="card info-card">
                <div class="info-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-code-square text-primary me-2"></i>
                        Configuration PHP
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <span class="info-label">Version PHP</span>
                        <span class="info-value fw-bold">{{ $systemInfo['php']['version'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Limite mémoire</span>
                        <span class="info-value">{{ $systemInfo['php']['memory_limit'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Temps d'exécution max</span>
                        <span class="info-value">{{ $systemInfo['php']['max_execution_time'] }}s</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Taille max upload</span>
                        <span class="info-value">{{ $systemInfo['php']['upload_max_filesize'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Taille max POST</span>
                        <span class="info-value">{{ $systemInfo['php']['post_max_size'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations Serveur -->
        <div class="col-lg-6 mb-4">
            <div class="card info-card">
                <div class="info-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-server text-info me-2"></i>
                        Informations Serveur
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <span class="info-label">Logiciel serveur</span>
                        <span class="info-value">{{ $systemInfo['server']['software'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Système d'exploitation</span>
                        <span class="info-value">{{ $systemInfo['server']['os'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nom d'hôte</span>
                        <span class="info-value">{{ $systemInfo['server']['hostname'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Racine du document</span>
                        <span class="info-value small">{{ Str::limit($systemInfo['server']['document_root'], 30) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations Laravel -->
        <div class="col-lg-6 mb-4">
            <div class="card info-card">
                <div class="info-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-gear text-warning me-2"></i>
                        Configuration Laravel
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <span class="info-label">Version Laravel</span>
                        <span class="info-value fw-bold">{{ $systemInfo['laravel']['version'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Environnement</span>
                        <span class="info-value">
                            <span class="badge badge-status {{ $systemInfo['laravel']['environment'] === 'production' ? 'badge-good' : 'badge-warning' }}">
                                {{ ucfirst($systemInfo['laravel']['environment']) }}
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Mode debug</span>
                        <span class="info-value">
                            <span class="badge badge-status {{ $systemInfo['laravel']['debug_mode'] ? 'badge-warning' : 'badge-good' }}">
                                {{ $systemInfo['laravel']['debug_mode'] ? 'Activé' : 'Désactivé' }}
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Fuseau horaire</span>
                        <span class="info-value">{{ $systemInfo['laravel']['timezone'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Locale</span>
                        <span class="info-value">{{ $systemInfo['laravel']['locale'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Driver cache</span>
                        <span class="info-value">{{ $systemInfo['laravel']['cache_driver'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Driver session</span>
                        <span class="info-value">{{ $systemInfo['laravel']['session_driver'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Driver queue</span>
                        <span class="info-value">{{ $systemInfo['laravel']['queue_driver'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations Base de données -->
        <div class="col-lg-6 mb-4">
            <div class="card info-card">
                <div class="info-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-database text-success me-2"></i>
                        Base de données
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <span class="info-label">Connexion</span>
                        <span class="info-value">{{ $systemInfo['database']['connection'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Hôte</span>
                        <span class="info-value">{{ $systemInfo['database']['host'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Base de données</span>
                        <span class="info-value">{{ $systemInfo['database']['database'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Version</span>
                        <span class="info-value">{{ $systemInfo['database']['version'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Taille</span>
                        <span class="info-value fw-bold">{{ $systemInfo['database']['size'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nombre de tables</span>
                        <span class="info-value">{{ $systemInfo['database']['tables_count'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance et stockage -->
        <div class="col-12 mb-4">
            <div class="card info-card">
                <div class="info-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-speedometer2 text-danger me-2"></i>
                        Performance et Stockage
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h3 text-primary">{{ $systemInfo['performance']['storage_used'] }}</div>
                                <div class="text-muted">Stockage utilisé</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h3 text-info">{{ $systemInfo['performance']['cache_size'] }}</div>
                                <div class="text-muted">Taille du cache</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h3 text-warning">{{ $systemInfo['performance']['logs_size'] }}</div>
                                <div class="text-muted">Taille des logs</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h3 text-success">{{ $systemInfo['performance']['memory_usage'] }}</div>
                                <div class="text-muted">Mémoire utilisée</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Extensions PHP -->
        <div class="col-12">
            <div class="card info-card">
                <div class="info-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-puzzle text-secondary me-2"></i>
                        Extensions PHP Importantes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="extension-grid">
                        @foreach($systemInfo['php']['extensions'] as $extension => $loaded)
                        <div class="extension-item">
                            <span>{{ $extension }}</span>
                            <span class="badge badge-status {{ $loaded ? 'badge-good' : 'badge-error' }}">
                                <i class="bi bi-{{ $loaded ? 'check' : 'x' }}-circle me-1"></i>
                                {{ $loaded ? 'Chargée' : 'Manquante' }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
