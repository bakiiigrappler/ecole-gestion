@extends('layouts.app')

@section('title', 'Modifier Utilisateur - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Administration</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Utilisateurs</a></li>
<li class="breadcrumb-item active">Modifier {{ $user->name }}</li>
@endsection

@push('styles')
<style>
:root {
    --primary-blue: #2563eb;
    --success-green: #059669;
    --warning-orange: #d97706;
    --danger-red: #dc2626;
    --gray-neutral: #6b7280;
}

.user-edit-header {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--warning-orange) 100%);
    color: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.form-card {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.form-modern {
    background: white;
}

.form-control-modern, .form-select-modern {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control-modern:focus, .form-select-modern:focus {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-control-modern.is-invalid, .form-select-modern.is-invalid {
    border-color: var(--danger-red);
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.btn-modern {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-primary-modern {
    background-color: var(--primary-blue);
    border-color: var(--primary-blue);
    color: white;
}

.btn-primary-modern:hover {
    background-color: #1d4ed8;
    border-color: #1d4ed8;
    transform: translateY(-1px);
}

.btn-secondary-modern {
    background-color: var(--gray-neutral);
    border-color: var(--gray-neutral);
    color: white;
}

.btn-secondary-modern:hover {
    background-color: #4b5563;
    border-color: #4b5563;
}

.form-group-modern {
    margin-bottom: 1.5rem;
}

.form-label-modern {
    font-weight: 600;
    color: var(--gray-neutral);
    margin-bottom: 0.5rem;
}

.user-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    background-color: var(--primary-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
    color: white;
    margin: 0 auto 1rem;
    transition: background-color 0.3s ease;
}

.role-info {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1rem;
    border-left: 4px solid var(--primary-blue);
    margin-bottom: 1rem;
}

.password-toggle {
    cursor: pointer;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-left: none;
    border-radius: 0 8px 8px 0;
    background: #f8fafc;
    transition: background-color 0.2s ease;
}

.password-toggle:hover {
    background-color: #e5e7eb;
}

.role-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.75rem;
    color: white;
    display: inline-block;
    margin-bottom: 0.5rem;
}

.role-superadmin { background-color: var(--danger-red); }
.role-admin { background-color: var(--primary-blue); }
.role-teacher { background-color: var(--success-green); }
.role-secretary { background-color: var(--warning-orange); }

.permissions-preview {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    margin-top: 1rem;
}

.permission-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.permission-item:last-child {
    border-bottom: none;
}

.permission-icon {
    width: 20px;
    margin-right: 0.5rem;
}

.permission-allowed { color: var(--success-green); }
.permission-denied { color: var(--danger-red); }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="user-edit-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 mb-2 fw-bold">
                    <i class="bi bi-person-gear me-3"></i>
                    Modifier l'Utilisateur
                </h1>
                <p class="mb-0 opacity-75">Modifiez les informations et permissions de "{{ $user->name }}"</p>
            </div>
            <div>
                <a href="{{ route('admin.users.index') }}" class="btn btn-light btn-lg">
                    <i class="bi bi-arrow-left me-2"></i>
                    Retour à la liste
                </a>
            </div>
        </div>
    </div>

    <!-- Messages d'alerte -->
    <div id="alertContainer"></div>

    <!-- Form -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="row">
                <!-- Formulaire principal -->
                <div class="col-lg-8">
                    <div class="card form-card">
                        <div class="card-header bg-light border-0">
                            <div class="text-center">
                                <div class="user-avatar-large" id="userAvatar">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <h5 class="mb-1">{{ $user->name }}</h5>
                                <span class="role-badge role-{{ $user->role }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                                <p class="text-muted small mb-0">Membre depuis {{ $user->created_at->format('d/m/Y') }}</p>
                            </div>
                        </div>
                        <div class="card-body form-modern">
                            <form id="editUserForm">
                                @csrf
                                @method('PUT')

                                <!-- Informations de base -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group-modern">
                                            <label for="name" class="form-label form-label-modern">
                                                <i class="bi bi-person me-1"></i>
                                                Nom complet <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" 
                                                   class="form-control form-control-modern" 
                                                   id="name" 
                                                   name="name" 
                                                   value="{{ $user->name }}" 
                                                   required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group-modern">
                                            <label for="email" class="form-label form-label-modern">
                                                <i class="bi bi-envelope me-1"></i>
                                                Adresse email <span class="text-danger">*</span>
                                            </label>
                                            <input type="email" 
                                                   class="form-control form-control-modern" 
                                                   id="email" 
                                                   name="email" 
                                                   value="{{ $user->email }}" 
                                                   required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rôle et statut -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group-modern">
                                            <label for="role" class="form-label form-label-modern">
                                                <i class="bi bi-person-badge me-1"></i>
                                                Rôle <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select form-select-modern" id="role" name="role" required>
                                                <option value="teacher" {{ $user->role == 'teacher' ? 'selected' : '' }}>Enseignant</option>
                                                <option value="secretary" {{ $user->role == 'secretary' ? 'selected' : '' }}>Secrétaire</option>
                                                @if(auth()->user()->isAdmin())
                                                <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Administrateur</option>
                                                @endif
                                                @if(auth()->user()->isSuperAdmin())
                                                <option value="superadmin" {{ $user->role == 'superadmin' ? 'selected' : '' }}>Super Administrateur</option>
                                                @endif
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group-modern">
                                            <label for="is_active" class="form-label form-label-modern">
                                                <i class="bi bi-toggle-on me-1"></i>
                                                Statut du compte
                                            </label>
                                            <div class="mt-2">
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           id="is_active" 
                                                           name="is_active" 
                                                           value="1" 
                                                           {{ $user->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="is_active">
                                                        Compte actif
                                                    </label>
                                                </div>
                                                <small class="text-muted">Un compte inactif ne peut pas se connecter</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mot de passe -->
                                <div class="form-group-modern">
                                    <label for="password" class="form-label form-label-modern">
                                        <i class="bi bi-key me-1"></i>
                                        Nouveau mot de passe
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control form-control-modern" 
                                               id="password" 
                                               name="password"
                                               placeholder="Laissez vide pour conserver l'actuel">
                                        <span class="password-toggle" onclick="togglePassword('password')">
                                            <i class="bi bi-eye" id="password-icon"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted">Minimum 8 caractères (laissez vide pour ne pas changer)</small>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="form-group-modern">
                                    <label for="password_confirmation" class="form-label form-label-modern">
                                        <i class="bi bi-key me-1"></i>
                                        Confirmer le mot de passe
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control form-control-modern" 
                                               id="password_confirmation" 
                                               name="password_confirmation"
                                               placeholder="Confirmer le nouveau mot de passe">
                                        <span class="password-toggle" onclick="togglePassword('password_confirmation')">
                                            <i class="bi bi-eye" id="password_confirmation-icon"></i>
                                        </span>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Informations sur les rôles -->
                                <div class="role-info">
                                    <h6 class="mb-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Impact du changement de rôle
                                    </h6>
                                    <p class="small text-muted mb-2">
                                        Le changement de rôle prendra effet immédiatement et modifiera les permissions d'accès.
                                    </p>
                                    @if($user->id === auth()->id())
                                    <div class="alert alert-warning py-2">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        <strong>Attention :</strong> Vous modifiez votre propre compte.
                                    </div>
                                    @endif
                                </div>

                                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary-modern btn-modern">
                                        <i class="bi bi-x-circle me-2"></i>
                                        Annuler
                                    </a>
                                    <button type="submit" class="btn btn-primary-modern btn-modern" id="submitBtn">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <span class="btn-text">Enregistrer les modifications</span>
                                        <span class="spinner-border spinner-border-sm d-none" role="status">
                                            <span class="visually-hidden">Chargement...</span>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Aperçu des permissions -->
                <div class="col-lg-4">
                    <div class="card form-card">
                        <div class="card-header bg-light border-0">
                            <h5 class="mb-0">
                                <i class="bi bi-shield-check me-2"></i>
                                Permissions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="permissions-preview" id="permissionsPreview">
                                <!-- Contenu dynamique basé sur le rôle sélectionné -->
                            </div>
                        </div>
                    </div>

                    <!-- Informations utilisateur -->
                    <div class="card form-card mt-3">
                        <div class="card-header bg-light border-0">
                            <h5 class="mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                Informations
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="small">
                                <div class="mb-2">
                                    <strong>ID utilisateur :</strong> {{ $user->id }}
                                </div>
                                <div class="mb-2">
                                    <strong>Créé le :</strong> {{ $user->created_at->format('d/m/Y à H:i') }}
                                </div>
                                <div class="mb-2">
                                    <strong>Modifié le :</strong> {{ $user->updated_at->format('d/m/Y à H:i') }}
                                </div>
                                @if($user->last_login_at)
                                <div class="mb-2">
                                    <strong>Dernière connexion :</strong> {{ $user->last_login_at->format('d/m/Y à H:i') }}
                                </div>
                                @endif
                                <div class="mb-0">
                                    <strong>Statut :</strong> 
                                    <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $user->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editUserForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const spinner = submitBtn.querySelector('.spinner-border');
    const nameInput = document.getElementById('name');
    const roleSelect = document.getElementById('role');
    const avatar = document.getElementById('userAvatar');
    const permissionsPreview = document.getElementById('permissionsPreview');
    
    // Définition des permissions par rôle
    const rolePermissions = {
        teacher: {
            name: 'Enseignant',
            color: 'var(--success-green)',
            permissions: [
                { icon: '✅', text: 'Gestion des classes assignées', allowed: true },
                { icon: '✅', text: 'Saisie des notes', allowed: true },
                { icon: '✅', text: 'Gestion des présences', allowed: true },
                { icon: '✅', text: 'Consultation emplois du temps', allowed: true },
                { icon: '❌', text: 'Gestion administrative', allowed: false },
                { icon: '❌', text: 'Gestion des utilisateurs', allowed: false }
            ]
        },
        secretary: {
            name: 'Secrétaire',
            color: 'var(--warning-orange)',
            permissions: [
                { icon: '✅', text: 'Gestion des inscriptions', allowed: true },
                { icon: '✅', text: 'Gestion des paiements', allowed: true },
                { icon: '✅', text: 'Communication parents', allowed: true },
                { icon: '✅', text: 'Génération de documents', allowed: true },
                { icon: '❌', text: 'Gestion pédagogique', allowed: false },
                { icon: '❌', text: 'Gestion des utilisateurs', allowed: false }
            ]
        },
        admin: {
            name: 'Administrateur',
            color: 'var(--primary-blue)',
            permissions: [
                { icon: '✅', text: 'Accès à tous les modules', allowed: true },
                { icon: '✅', text: 'Gestion des utilisateurs', allowed: true },
                { icon: '✅', text: 'Paramètres généraux', allowed: true },
                { icon: '✅', text: 'Rapports et statistiques', allowed: true },
                { icon: '❌', text: 'Maintenance système', allowed: false },
                { icon: '❌', text: 'Gestion superadmin', allowed: false }
            ]
        },
        superadmin: {
            name: 'Super Administrateur',
            color: 'var(--danger-red)',
            permissions: [
                { icon: '✅', text: 'Accès complet au système', allowed: true },
                { icon: '✅', text: 'Gestion de tous les utilisateurs', allowed: true },
                { icon: '✅', text: 'Maintenance système', allowed: true },
                { icon: '✅', text: 'Sauvegarde et restauration', allowed: true },
                { icon: '✅', text: 'Configuration avancée', allowed: true },
                { icon: '✅', text: 'Tous les modules', allowed: true }
            ]
        }
    };
    
    // Auto-update user avatar when name changes
    nameInput.addEventListener('input', function() {
        const firstLetter = this.value.charAt(0).toUpperCase();
        avatar.textContent = firstLetter || '{{ strtoupper(substr($user->name, 0, 1)) }}';
    });
    
    // Update permissions preview when role changes
    roleSelect.addEventListener('change', function() {
        updatePermissionsPreview(this.value);
        updateAvatarColor(this.value);
    });
    
    function updatePermissionsPreview(role) {
        const roleData = rolePermissions[role];
        if (!roleData) return;
        
        let html = `
            <h6 class="mb-3" style="color: ${roleData.color}">
                ${roleData.name}
            </h6>
        `;
        
        roleData.permissions.forEach(permission => {
            html += `
                <div class="permission-item">
                    <span class="permission-icon">${permission.icon}</span>
                    <span class="${permission.allowed ? 'permission-allowed' : 'permission-denied'}">
                        ${permission.text}
                    </span>
                </div>
            `;
        });
        
        permissionsPreview.innerHTML = html;
    }
    
    function updateAvatarColor(role) {
        const colors = {
            teacher: 'var(--success-green)',
            secretary: 'var(--warning-orange)',
            admin: 'var(--primary-blue)',
            superadmin: 'var(--danger-red)'
        };
        
        avatar.style.backgroundColor = colors[role] || 'var(--primary-blue)';
    }
    
    // Initialize permissions preview
    updatePermissionsPreview(roleSelect.value);
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Reset previous errors
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        
        // Show loading state
        submitBtn.disabled = true;
        btnText.classList.add('d-none');
        spinner.classList.remove('d-none');
        
        const formData = new FormData(form);
        
        fetch('{{ route("admin.users.update", $user) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                setTimeout(() => {
                    window.location.href = '{{ route("admin.users.index") }}';
                }, 1500);
            } else {
                // Handle validation errors
                if (data.errors) {
                    for (const [field, messages] of Object.entries(data.errors)) {
                        const input = document.getElementById(field);
                        if (input) {
                            input.classList.add('is-invalid');
                            const feedback = input.parentNode.querySelector('.invalid-feedback') || 
                                           input.parentNode.parentNode.querySelector('.invalid-feedback');
                            if (feedback) {
                                feedback.textContent = messages[0];
                            }
                        }
                    }
                    showAlert('danger', 'Veuillez corriger les erreurs dans le formulaire.');
                } else {
                    showAlert('danger', data.message || 'Une erreur est survenue');
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showAlert('danger', 'Une erreur est survenue lors de la mise à jour.');
        })
        .finally(() => {
            // Reset loading state
            submitBtn.disabled = false;
            btnText.classList.remove('d-none');
            spinner.classList.add('d-none');
        });
    });
    
    function showAlert(type, message) {
        const alertContainer = document.getElementById('alertContainer');
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        alertContainer.innerHTML = alertHtml;
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
});

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>
@endsection
