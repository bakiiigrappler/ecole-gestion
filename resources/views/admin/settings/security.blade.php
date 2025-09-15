@extends('layouts.app')

@section('title', 'Sécurité et Journaux - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Administration</a></li>
<li class="breadcrumb-item active">Sécurité</li>
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

.security-header {
    background: linear-gradient(135deg, var(--danger-red), #991b1b);
    color: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.security-card {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.security-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.security-card-header {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border-bottom: 1px solid #e5e7eb;
    padding: 1rem 1.5rem;
    font-weight: 600;
    border-radius: 12px 12px 0 0;
}

.security-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.security-item:last-child {
    border-bottom: none;
}

.security-label {
    font-weight: 600;
    color: var(--gray-neutral);
    min-width: 180px;
}

.security-value {
    flex: 1;
    text-align: right;
}

.status-good { color: var(--success-green); }
.status-warning { color: var(--warning-orange); }
.status-error { color: var(--danger-red); }

.badge-security {
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-good { background-color: var(--success-green); color: white; }
.badge-warning { background-color: var(--warning-orange); color: white; }
.badge-error { background-color: var(--danger-red); color: white; }

.log-entry {
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    padding: 0.5rem;
    background: #f8fafc;
    border-left: 3px solid var(--primary-blue);
    margin-bottom: 0.5rem;
    border-radius: 0 6px 6px 0;
    white-space: pre-wrap;
    word-break: break-all;
}

.log-error { border-left-color: var(--danger-red); }
.log-warning { border-left-color: var(--warning-orange); }
.log-info { border-left-color: var(--info-cyan); }

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-item {
    text-align: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-blue);
}

.log-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-log {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 4px;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="security-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 mb-2 fw-bold">
                    <i class="bi bi-shield-check me-3"></i>
                    Sécurité et Journaux
                </h1>
                <p class="mb-0 opacity-75">Paramètres de sécurité et gestion des logs</p>
            </div>
            <div>
                <button class="btn btn-light btn-lg" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise me-2"></i>
                    Actualiser
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Paramètres de sécurité -->
        <div class="col-lg-6 mb-4">
            <div class="card security-card">
                <div class="security-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-fill-check text-success me-2"></i>
                        Paramètres de Sécurité
                    </h5>
                </div>
                <div class="card-body">
                    <div class="security-item">
                        <span class="security-label">Mode debug</span>
                        <span class="security-value">
                            <span class="badge badge-security {{ $securityInfo['security_settings']['app_debug'] ? 'badge-error' : 'badge-good' }}">
                                <i class="bi bi-{{ $securityInfo['security_settings']['app_debug'] ? 'exclamation-triangle' : 'check-circle' }} me-1"></i>
                                {{ $securityInfo['security_settings']['app_debug'] ? 'Activé (Dangereux)' : 'Désactivé' }}
                            </span>
                        </span>
                    </div>
                    <div class="security-item">
                        <span class="security-label">HTTPS</span>
                        <span class="security-value">
                            <span class="badge badge-security {{ $securityInfo['security_settings']['https_enabled'] ? 'badge-good' : 'badge-warning' }}">
                                <i class="bi bi-{{ $securityInfo['security_settings']['https_enabled'] ? 'lock' : 'unlock' }} me-1"></i>
                                {{ $securityInfo['security_settings']['https_enabled'] ? 'Activé' : 'Désactivé' }}
                            </span>
                        </span>
                    </div>
                    <div class="security-item">
                        <span class="security-label">Protection CSRF</span>
                        <span class="security-value">
                            <span class="badge badge-security {{ $securityInfo['security_settings']['csrf_protection'] ? 'badge-good' : 'badge-error' }}">
                                <i class="bi bi-{{ $securityInfo['security_settings']['csrf_protection'] ? 'shield-check' : 'shield-x' }} me-1"></i>
                                {{ $securityInfo['security_settings']['csrf_protection'] ? 'Activée' : 'Désactivée' }}
                            </span>
                        </span>
                    </div>
                    <div class="security-item">
                        <span class="security-label">Session sécurisée</span>
                        <span class="security-value">
                            <span class="badge badge-security {{ $securityInfo['security_settings']['session_secure'] ? 'badge-good' : 'badge-warning' }}">
                                <i class="bi bi-{{ $securityInfo['security_settings']['session_secure'] ? 'check' : 'x' }}-circle me-1"></i>
                                {{ $securityInfo['security_settings']['session_secure'] ? 'Oui' : 'Non' }}
                            </span>
                        </span>
                    </div>
                    <div class="security-item">
                        <span class="security-label">HttpOnly cookies</span>
                        <span class="security-value">
                            <span class="badge badge-security {{ $securityInfo['security_settings']['session_http_only'] ? 'badge-good' : 'badge-warning' }}">
                                <i class="bi bi-{{ $securityInfo['security_settings']['session_http_only'] ? 'check' : 'x' }}-circle me-1"></i>
                                {{ $securityInfo['security_settings']['session_http_only'] ? 'Activé' : 'Désactivé' }}
                            </span>
                        </span>
                    </div>
                    <div class="security-item">
                        <span class="security-label">SameSite</span>
                        <span class="security-value">
                            <span class="badge badge-security badge-good">
                                {{ ucfirst($securityInfo['security_settings']['session_same_site']) }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques de sécurité -->
        <div class="col-lg-6 mb-4">
            <div class="card security-card">
                <div class="security-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up text-info me-2"></i>
                        Statistiques de Sécurité
                    </h5>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number">{{ $securityInfo['security_stats']['active_sessions'] }}</div>
                            <div class="text-muted">Sessions actives</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">{{ $securityInfo['security_stats']['failed_logins_today'] }}</div>
                            <div class="text-muted">Échecs aujourd'hui</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">{{ $securityInfo['security_stats']['admin_users_count'] }}</div>
                            <div class="text-muted">Administrateurs</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">{{ $securityInfo['security_stats']['inactive_users_count'] }}</div>
                            <div class="text-muted">Utilisateurs inactifs</div>
                        </div>
                    </div>

                    @if($securityInfo['security_stats']['last_login_activity'])
                    <div class="mt-3 p-3 bg-light rounded">
                        <h6 class="mb-2">
                            <i class="bi bi-clock-history me-1"></i>
                            Dernière activité
                        </h6>
                        <div class="d-flex justify-content-between">
                            <span><strong>{{ $securityInfo['security_stats']['last_login_activity']['user'] }}</strong></span>
                            <span class="text-muted">{{ $securityInfo['security_stats']['last_login_activity']['time'] }}</span>
                        </div>
                        <small class="text-muted">{{ $securityInfo['security_stats']['last_login_activity']['email'] }}</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Gestion des logs -->
        <div class="col-12 mb-4">
            <div class="card security-card">
                <div class="security-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-file-text text-warning me-2"></i>
                        Gestion des Journaux
                    </h5>
                </div>
                <div class="card-body">
                    @if(count($securityInfo['logs_summary']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fichier</th>
                                    <th>Taille</th>
                                    <th>Dernière modification</th>
                                    <th>Lignes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($securityInfo['logs_summary'] as $log)
                                <tr>
                                    <td>
                                        <i class="bi bi-file-text me-2"></i>
                                        <strong>{{ $log['name'] }}.log</strong>
                                    </td>
                                    <td>{{ $log['size'] }}</td>
                                    <td>{{ $log['modified'] }}</td>
                                    <td>{{ number_format($log['lines']) }}</td>
                                    <td>
                                        <div class="log-actions">
                                            <a href="{{ route('admin.logs.download', ['type' => $log['name']]) }}" 
                                               class="btn btn-outline-primary btn-log">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            @if(auth()->user()->isSuperAdmin())
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-log"
                                                    onclick="clearLog('{{ $log['name'] }}')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        <p>Aucun fichier de log trouvé</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Logs récents -->
        @if(count($securityInfo['recent_logs']) > 0)
        <div class="col-12">
            <div class="card security-card">
                <div class="security-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-journal-text text-secondary me-2"></i>
                        Journaux Récents (20 dernières entrées)
                    </h5>
                </div>
                <div class="card-body">
                    <div style="max-height: 500px; overflow-y: auto;">
                        @foreach($securityInfo['recent_logs'] as $logEntry)
                            @php
                                $logClass = 'log-entry';
                                if (strpos($logEntry, 'ERROR') !== false) $logClass .= ' log-error';
                                elseif (strpos($logEntry, 'WARNING') !== false) $logClass .= ' log-warning';
                                elseif (strpos($logEntry, 'INFO') !== false) $logClass .= ' log-info';
                            @endphp
                            <div class="{{ $logClass }}">{{ $logEntry }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal de confirmation pour la suppression des logs -->
<div class="modal fade" id="clearLogModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Confirmer la suppression
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir vider le fichier de log <strong id="logFileName"></strong> ?</p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Cette action est irréversible !
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmClearLog">Vider le log</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentLogType = '';

function clearLog(logType) {
    currentLogType = logType;
    document.getElementById('logFileName').textContent = logType + '.log';
    
    const modal = new bootstrap.Modal(document.getElementById('clearLogModal'));
    modal.show();
}

document.getElementById('confirmClearLog').addEventListener('click', function() {
    if (!currentLogType) return;
    
    fetch('{{ route("admin.logs.clear") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            type: currentLogType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue.');
    });
});
</script>
@endsection
