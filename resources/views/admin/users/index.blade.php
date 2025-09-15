@extends('layouts.app')

@section('title', 'Gestion des Utilisateurs - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Administration</a></li>
<li class="breadcrumb-item active">Utilisateurs</li>
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

.users-header {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--purple-violet) 100%);
    color: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.filters-card {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.users-table-card {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.table-modern {
    border-collapse: separate;
    border-spacing: 0;
}

.table-modern thead th {
    background-color: #f8fafc;
    border: none;
    border-bottom: 2px solid #e5e7eb;
    font-weight: 600;
    color: var(--gray-neutral);
    padding: 1rem;
}

.table-modern tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.2s ease;
}

.table-modern tbody tr:hover {
    background-color: #f8fafc;
}

.table-modern tbody td {
    padding: 1rem;
    vertical-align: middle;
    border: none;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: white;
    font-size: 0.875rem;
}

.badge-role {
    padding: 0.375rem 0.75rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.75rem;
    color: white;
}

.role-superadmin { background-color: var(--danger-red); }
.role-admin { background-color: var(--primary-blue); }
.role-teacher { background-color: var(--success-green); }
.role-secretary { background-color: var(--warning-orange); }

.status-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.75rem;
}

.status-active { background-color: var(--success-green); color: white; }
.status-inactive { background-color: var(--gray-neutral); color: white; }

.btn-action {
    padding: 0.375rem;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    background: white;
    transition: all 0.2s ease;
    margin: 0 0.125rem;
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.btn-action.view { border-color: var(--info-cyan); color: var(--info-cyan); }
.btn-action.edit { border-color: var(--warning-orange); color: var(--warning-orange); }
.btn-action.delete { border-color: var(--danger-red); color: var(--danger-red); }
.btn-action.toggle { border-color: var(--gray-neutral); color: var(--gray-neutral); }

.btn-action.view:hover { background-color: var(--info-cyan); color: white; }
.btn-action.edit:hover { background-color: var(--warning-orange); color: white; }
.btn-action.delete:hover { background-color: var(--danger-red); color: white; }
.btn-action.toggle:hover { background-color: var(--gray-neutral); color: white; }

.form-control-modern, .form-select-modern {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 0.625rem 0.875rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control-modern:focus, .form-select-modern:focus {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="users-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 mb-2 fw-bold">
                    <i class="bi bi-people-fill me-3"></i>
                    Gestion des Utilisateurs
                </h1>
                <p class="mb-0 opacity-75">Gérez les comptes utilisateurs et leurs permissions</p>
            </div>
            <div>
                <a href="{{ route('admin.users.create') }}" class="btn btn-light btn-lg">
                    <i class="bi bi-person-plus me-2"></i>
                    Nouvel utilisateur
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

    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card filters-card">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-funnel me-2 text-primary"></i>
                        Filtres et Recherche
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.users.index') }}">
                        <div class="row g-3">
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-search me-1"></i>
                                    Rechercher
                                </label>
                                <input type="text" class="form-control form-control-modern" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       placeholder="Nom ou email...">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-person-badge me-1"></i>
                                    Rôle
                                </label>
                                <select class="form-select form-select-modern" name="role">
                                    <option value="">Tous les rôles</option>
                                    <option value="superadmin" {{ request('role') == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="teacher" {{ request('role') == 'teacher' ? 'selected' : '' }}>Enseignant</option>
                                    <option value="secretary" {{ request('role') == 'secretary' ? 'selected' : '' }}>Secrétaire</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-toggle-on me-1"></i>
                                    Statut
                                </label>
                                <select class="form-select form-select-modern" name="status">
                                    <option value="">Tous les statuts</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label fw-semibold text-transparent">Actions</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search me-1"></i>
                                        Filtrer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Table des utilisateurs -->
    <div class="row">
        <div class="col-12">
            <div class="card users-table-card">
                <div class="card-header bg-light border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="bi bi-table me-2 text-primary"></i>
                                Liste des Utilisateurs
                            </h5>
                            <small class="text-muted">{{ $users->total() }} utilisateur(s) au total</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Statut</th>
                                    <th>Dernière connexion</th>
                                    <th>Créé le</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3" style="background-color: var(--primary-blue);">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">{{ $user->name }}</h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-card-text me-1"></i>
                                                    {{ $user->matricule ?? 'Non défini' }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $user->email }}</span>
                                    </td>
                                    <td>
                                        <span class="badge-role role-{{ $user->role }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge {{ $user->is_active ? 'status-active' : 'status-inactive' }}">
                                            <i class="bi bi-{{ $user->is_active ? 'check-circle' : 'x-circle' }} me-1"></i>
                                            {{ $user->is_active ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($user->last_login_at)
                                            <span class="text-muted">{{ $user->last_login_at->format('d/m/Y H:i') }}</span>
                                        @else
                                            <span class="text-muted">Jamais connecté</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $user->created_at->format('d/m/Y') }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="{{ route('admin.users.show', $user) }}" 
                                               class="btn-action view" 
                                               title="Voir">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            
                                            @if($user->id !== auth()->id() && (!$user->isSuperAdmin() || auth()->user()->isSuperAdmin()))
                                            <a href="{{ route('admin.users.edit', $user) }}" 
                                               class="btn-action edit" 
                                               title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <button type="button" 
                                                    class="btn-action toggle toggle-status-btn" 
                                                    data-user-id="{{ $user->id }}"
                                                    data-user-name="{{ $user->name }}"
                                                    data-current-status="{{ $user->is_active }}"
                                                    title="{{ $user->is_active ? 'Désactiver' : 'Activer' }}">
                                                <i class="bi bi-{{ $user->is_active ? 'toggle-off' : 'toggle-on' }}"></i>
                                            </button>
                                            
                                            <button type="button" 
                                                    class="btn-action delete delete-user-btn" 
                                                    data-user-id="{{ $user->id }}"
                                                    data-user-name="{{ $user->name }}"
                                                    title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                                        <h5 class="text-muted">Aucun utilisateur trouvé</h5>
                                        <p class="text-muted">Ajustez vos filtres ou créez un nouvel utilisateur.</p>
                                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                                            <i class="bi bi-person-plus me-2"></i>
                                            Créer un utilisateur
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                @if($users->hasPages())
                <div class="card-footer bg-light border-0">
                    <div class="d-flex justify-content-center">
                        {{ $users->appends(request()->query())->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@include('admin.users._modals')
@endsection
