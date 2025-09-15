@extends('layouts.app')

@section('title', 'Détails Utilisateur - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Administration</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Utilisateurs</a></li>
<li class="breadcrumb-item active">{{ $user->name }}</li>
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

.user-show-header {
    background: linear-gradient(135deg, var(--info-cyan) 0%, var(--primary-blue) 100%);
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

.user-avatar-large {
    width: 120px;
    height: 120px;
    border-radius: 20px;
    background-color: var(--primary-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: bold;
    color: white;
    margin: 0 auto 1rem;
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.875rem;
}

.status-active {
    background-color: var(--success-green);
    color: white;
}

.status-inactive {
    background-color: var(--gray-neutral);
    color: white;
}

.role-badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.875rem;
    color: white;
}

.role-superadmin { background-color: var(--danger-red); }
.role-admin { background-color: var(--primary-blue); }
.role-teacher { background-color: var(--success-green); }
.role-secretary { background-color: var(--warning-orange); }

.info-item {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.2s ease;
}

.info-item:hover {
    background-color: #f8fafc;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: var(--gray-neutral);
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 1rem;
    color: #1f2937;
}

.btn-action-large {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
    border: 2px solid;
}

.btn-edit {
    background-color: white;
    border-color: var(--warning-orange);
    color: var(--warning-orange);
}

.btn-edit:hover {
    background-color: var(--warning-orange);
    color: white;
    transform: translateY(-1px);
}

.btn-toggle {
    background-color: white;
    border-color: var(--gray-neutral);
    color: var(--gray-neutral);
}

.btn-toggle:hover {
    background-color: var(--gray-neutral);
    color: white;
    transform: translateY(-1px);
}

.btn-delete {
    background-color: white;
    border-color: var(--danger-red);
    color: var(--danger-red);
}

.btn-delete:hover {
    background-color: var(--danger-red);
    color: white;
    transform: translateY(-1px);
}

.btn-back {
    background-color: white;
    border-color: var(--gray-neutral);
    color: var(--gray-neutral);
}

.btn-back:hover {
    background-color: var(--gray-neutral);
    color: white;
}

.activity-item {
    padding: 1rem;
    border-left: 3px solid var(--primary-blue);
    background: #f8fafc;
    border-radius: 0 8px 8px 0;
    margin-bottom: 1rem;
}

.permissions-list {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1rem;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="user-show-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 mb-2 fw-bold">
                    <i class="bi bi-person-circle me-3"></i>
                    Détails de l'Utilisateur
                </h1>
                <p class="mb-0 opacity-75">Consultez toutes les informations de "{{ $user->name }}"</p>
            </div>
            <div>
                <a href="{{ route('admin.users.index') }}" class="btn btn-light btn-lg">
                    <i class="bi bi-arrow-left me-2"></i>
                    Retour à la liste
                </a>
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
        <!-- Informations principales -->
        <div class="col-lg-8">
            <div class="card info-card mb-4">
                <div class="card-header bg-light border-0">
                    <div class="text-center">
                        <div class="user-avatar-large">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <h3 class="mb-1">{{ $user->name }}</h3>
                        <div class="d-flex justify-content-center gap-2 mb-2">
                            <span class="role-badge role-{{ $user->role }}">
                                {{ ucfirst($user->role) }}
                            </span>
                            <span class="status-badge {{ $user->is_active ? 'status-active' : 'status-inactive' }}">
                                <i class="bi bi-{{ $user->is_active ? 'check-circle' : 'x-circle' }} me-1"></i>
                                {{ $user->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-envelope me-1"></i>
                            Adresse email
                        </div>
                        <div class="info-value">
                            <a href="mailto:{{ $user->email }}" class="text-decoration-none">{{ $user->email }}</a>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-person-badge me-1"></i>
                            Rôle dans le système
                        </div>
                        <div class="info-value">
                            {{ ucfirst($user->role) }}
                            @switch($user->role)
                                @case('superadmin')
                                    <small class="text-muted d-block">Accès complet au système y compris la maintenance</small>
                                    @break
                                @case('admin')
                                    <small class="text-muted d-block">Accès administratif complet sauf maintenance</small>
                                    @break
                                @case('teacher')
                                    <small class="text-muted d-block">Accès aux classes, notes et présences</small>
                                    @break
                                @case('secretary')
                                    <small class="text-muted d-block">Gestion des inscriptions et paiements</small>
                                    @break
                            @endswitch
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-clock-history me-1"></i>
                            Dernière connexion
                        </div>
                        <div class="info-value">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->format('d/m/Y à H:i') }}
                                <small class="text-muted d-block">{{ $user->last_login_at->diffForHumans() }}</small>
                            @else
                                <span class="text-muted">Jamais connecté</span>
                            @endif
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-calendar-plus me-1"></i>
                            Date de création
                        </div>
                        <div class="info-value">
                            {{ $user->created_at->format('d/m/Y à H:i') }}
                            <small class="text-muted d-block">{{ $user->created_at->diffForHumans() }}</small>
                        </div>
                    </div>

                    @if($user->updated_at != $user->created_at)
                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-calendar-check me-1"></i>
                            Dernière modification
                        </div>
                        <div class="info-value">
                            {{ $user->updated_at->format('d/m/Y à H:i') }}
                            <small class="text-muted d-block">{{ $user->updated_at->diffForHumans() }}</small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Permissions -->
            <div class="card info-card">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-check me-2"></i>
                        Permissions et Accès
                    </h5>
                </div>
                <div class="card-body">
                    <div class="permissions-list">
                        @switch($user->role)
                            @case('superadmin')
                                <h6 class="text-danger mb-2">Super Administrateur - Accès Complet</h6>
                                <ul class="mb-0">
                                    <li>✅ Gestion complète des utilisateurs</li>
                                    <li>✅ Accès à tous les modules</li>
                                    <li>✅ Maintenance système</li>
                                    <li>✅ Sauvegarde et restauration</li>
                                    <li>✅ Configuration avancée</li>
                                </ul>
                                @break
                            @case('admin')
                                <h6 class="text-primary mb-2">Administrateur</h6>
                                <ul class="mb-0">
                                    <li>✅ Gestion des utilisateurs (sauf superadmin)</li>
                                    <li>✅ Accès à tous les modules pédagogiques</li>
                                    <li>✅ Gestion des paramètres généraux</li>
                                    <li>❌ Maintenance système</li>
                                </ul>
                                @break
                            @case('teacher')
                                <h6 class="text-success mb-2">Enseignant</h6>
                                <ul class="mb-0">
                                    <li>✅ Gestion des classes assignées</li>
                                    <li>✅ Saisie des notes</li>
                                    <li>✅ Gestion des présences</li>
                                    <li>✅ Consultation des emplois du temps</li>
                                    <li>❌ Gestion administrative</li>
                                </ul>
                                @break
                            @case('secretary')
                                <h6 class="text-warning mb-2">Secrétaire</h6>
                                <ul class="mb-0">
                                    <li>✅ Gestion des inscriptions</li>
                                    <li>✅ Gestion des paiements</li>
                                    <li>✅ Communication avec les parents</li>
                                    <li>✅ Génération de documents</li>
                                    <li>❌ Gestion pédagogique</li>
                                </ul>
                                @break
                        @endswitch
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions et informations complémentaires -->
        <div class="col-lg-4">
            <!-- Actions rapides -->
            <div class="card info-card mb-4">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-gear me-2"></i>
                        Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($user->id !== auth()->id() && (!$user->isSuperAdmin() || auth()->user()->isSuperAdmin()))
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-action-large btn-edit">
                            <i class="bi bi-pencil me-2"></i>
                            Modifier l'utilisateur
                        </a>
                        
                        <button type="button" class="btn btn-action-large btn-toggle toggle-status-btn" 
                                data-user-id="{{ $user->id }}" 
                                data-user-name="{{ $user->name }}"
                                data-current-status="{{ $user->is_active }}">
                            <i class="bi bi-toggle-{{ $user->is_active ? 'off' : 'on' }} me-2"></i>
                            {{ $user->is_active ? 'Désactiver' : 'Activer' }} le compte
                        </button>

                        <button type="button" class="btn btn-action-large btn-delete delete-user-btn" 
                                data-user-id="{{ $user->id }}" 
                                data-user-name="{{ $user->name }}">
                            <i class="bi bi-trash me-2"></i>
                            Supprimer l'utilisateur
                        </button>
                        @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Vous ne pouvez pas modifier votre propre compte ou un compte superadmin.
                        </div>
                        @endif

                        <a href="{{ route('admin.users.index') }}" class="btn btn-action-large btn-back">
                            <i class="bi bi-arrow-left me-2"></i>
                            Retour à la liste
                        </a>
                    </div>
                </div>
            </div>

            <!-- Activité récente -->
            <div class="card info-card">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-activity me-2"></i>
                        Activité
                    </h5>
                </div>
                <div class="card-body">
                    <div class="activity-item">
                        <div class="d-flex justify-content-between">
                            <strong>Compte créé</strong>
                            <small class="text-muted">{{ $user->created_at->format('d/m/Y') }}</small>
                        </div>
                        <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                    </div>
                    
                    @if($user->updated_at != $user->created_at)
                    <div class="activity-item">
                        <div class="d-flex justify-content-between">
                            <strong>Dernière modification</strong>
                            <small class="text-muted">{{ $user->updated_at->format('d/m/Y') }}</small>
                        </div>
                        <small class="text-muted">{{ $user->updated_at->diffForHumans() }}</small>
                    </div>
                    @endif
                    
                    @if($user->last_login_at)
                    <div class="activity-item">
                        <div class="d-flex justify-content-between">
                            <strong>Dernière connexion</strong>
                            <small class="text-muted">{{ $user->last_login_at->format('d/m/Y') }}</small>
                        </div>
                        <small class="text-muted">{{ $user->last_login_at->diffForHumans() }}</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@include('admin.users._modals')
@endsection
