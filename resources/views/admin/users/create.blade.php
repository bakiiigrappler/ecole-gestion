@extends('layouts.app')

@section('title', 'Créer Utilisateur - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Administration</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Utilisateurs</a></li>
<li class="breadcrumb-item active">Créer</li>
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

.user-create-header {
    background: linear-gradient(135deg, var(--success-green) 0%, var(--primary-blue) 100%);
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

.user-avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    background-color: var(--success-green);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
    color: white;
    margin: 0 auto 1rem;
}

.role-info {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1rem;
    border-left: 4px solid var(--success-green);
}


</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="user-create-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 mb-2 fw-bold">
                    <i class="bi bi-person-plus me-3"></i>
                    Créer un Utilisateur
                </h1>
                <p class="mb-0 opacity-75">Ajoutez un nouveau compte utilisateur au système</p>
            </div>
            <div>
                <a href="{{ route('admin.users.index') }}" class="btn btn-light btn-lg">
                    <i class="bi bi-arrow-left me-2"></i>
                    Retour à la liste
                </a>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card form-card">
                <div class="card-header bg-light border-0">
                    <div class="text-center">
                        <div class="user-avatar-placeholder" id="userAvatar">
                            <i class="bi bi-person"></i>
                        </div>
                        <h5 class="mb-0">Nouveau compte utilisateur</h5>
                        <small class="text-muted">Saisissez les informations du nouvel utilisateur</small>
                    </div>
                </div>
                <div class="card-body form-modern">
                    <form id="createUserForm">
                        @csrf

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
                                           placeholder="Ex: Jean Dupont"
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
                                           placeholder="Ex: jean.dupont@Egesco.com"
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
                                        <option value="">Sélectionner un rôle</option>
                                        <option value="teacher">Enseignant</option>
                                        <option value="secretary">Secrétaire</option>
                                        @if(auth()->user()->isAdmin())
                                        <option value="admin">Administrateur</option>
                                        @endif
                                        @if(auth()->user()->isSuperAdmin())
                                        <option value="superadmin">Super Administrateur</option>
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
                                                   checked>
                                            <label class="form-check-label" for="is_active">
                                                Compte actif
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section permanente - Mot de passe par défaut -->
                        <div class="form-group-modern">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="bi bi-key me-2"></i>
                                        Génération automatique du mot de passe
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="text-primary mb-2">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Formules de génération
                                            </h6>
                                            <p class="mb-2">Le matricule et mot de passe seront générés automatiquement :</p>
                                            <div class="bg-light p-3 rounded border">
                                                <div class="mb-2">
                                                    <strong>Matricule :</strong> 
                                                    <code class="text-primary fs-6 fw-bold">2 lettres prénom + 2 lettres nom + année + numéro</code>
                                                </div>
                                                <div>
                                                    <strong>Mot de passe :</strong> 
                                                    <code class="text-success fs-6 fw-bold">Premier prénom + 1234</code>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3">
                                                <h6 class="text-success mb-2">
                                                    <i class="bi bi-lightbulb me-1"></i>
                                                    Exemple concret
                                                </h6>
                                                                                            <div class="bg-success bg-opacity-10 p-3 rounded border border-success">
                                                <strong>Nom saisi :</strong> <span id="previewName">"Jean Baptiste MBALLA"</span><br>
                                                <strong>Matricule généré :</strong> <code class="text-primary" id="previewMatricule">JEMB25001</code><br>
                                                <strong>Mot de passe généré :</strong> <code class="text-success" id="previewPassword">Jean1234</code>
                                            </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <div class="bg-primary bg-opacity-10 rounded-circle p-4 d-inline-block">
                                                <i class="bi bi-shield-check text-primary" style="font-size: 3rem;"></i>
                                            </div>
                                            <div class="mt-3">
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i>
                                                    Génération instantanée
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center text-warning">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                                    <div>
                                                        <strong>Sécurité recommandée</strong><br>
                                                        <small>L'utilisateur devra changer ce mot de passe lors de sa première connexion</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center text-info">
                                                    <i class="bi bi-eye me-2"></i>
                                                    <div>
                                                        <strong>Visibilité</strong><br>
                                                        <small>Le mot de passe généré sera affiché dans le message de confirmation</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informations sur les rôles -->
                        <div class="role-info">
                            <h6 class="mb-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Informations sur les rôles
                            </h6>
                            <ul class="mb-0 small">
                                <li><strong>Enseignant</strong> : Accès aux classes, notes et présences</li>
                                <li><strong>Secrétaire</strong> : Gestion des inscriptions et paiements</li>
                                @if(auth()->user()->isAdmin())
                                <li><strong>Administrateur</strong> : Accès complet sauf maintenance système</li>
                                @endif
                                @if(auth()->user()->isSuperAdmin())
                                <li><strong>Super Admin</strong> : Accès complet y compris maintenance</li>
                                @endif
                            </ul>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary-modern btn-modern">
                                <i class="bi bi-x-circle me-2"></i>
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary-modern btn-modern" id="submitBtn">
                                <i class="bi bi-person-plus me-2"></i>
                                <span class="btn-text">Créer l'utilisateur</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createUserForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const spinner = submitBtn.querySelector('.spinner-border');
    const nameInput = document.getElementById('name');
    const avatar = document.getElementById('userAvatar');
    
    // Auto-update user avatar and password preview when name changes
    nameInput.addEventListener('input', function() {
        const firstLetter = this.value.charAt(0).toUpperCase();
        if (firstLetter) {
            avatar.innerHTML = firstLetter;
        } else {
            avatar.innerHTML = '<i class="bi bi-person"></i>';
        }
        
        // Update password preview
        updatePasswordPreview(this.value);
    });
    
    // Function to update password and matricule preview
    function updatePasswordPreview(fullName) {
        const previewName = document.getElementById('previewName');
        const previewPassword = document.getElementById('previewPassword');
        const previewMatricule = document.getElementById('previewMatricule');
        
        if (fullName.trim()) {
            const firstName = fullName.trim().split(' ')[0];
            const generatedPassword = firstName + '1234';
            
            // Générer le matricule (simulation côté client)
            const nameParts = fullName.trim().toUpperCase().split(' ');
            const firstNamePart = nameParts[0] || 'USER';
            const lastNamePart = nameParts[nameParts.length - 1] || firstNamePart;
            
            const prefix = firstNamePart.substring(0, 2) + lastNamePart.substring(0, 2);
            const year = new Date().getFullYear().toString().slice(-2);
            const matricule = prefix + year + '001'; // Simulation avec 001
            
            previewName.textContent = `"${fullName}"`;
            previewMatricule.textContent = matricule;
            previewPassword.textContent = generatedPassword;
        } else {
            previewName.textContent = '"Jean Baptiste MBALLA"';
            previewMatricule.textContent = 'JEMB25001';
            previewPassword.textContent = 'Jean1234';
        }
    }
    
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
        
        fetch('{{ route("admin.users.store") }}', {
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
                // Success - redirect with message
                window.location.href = '{{ route("admin.users.index") }}?success=' + encodeURIComponent(data.message);
            } else {
                // Handle validation errors
                if (data.errors) {
                    for (const [field, messages] of Object.entries(data.errors)) {
                        const input = document.getElementById(field);
                        const feedback = input.parentNode.parentNode.querySelector('.invalid-feedback');
                        
                        if (input && feedback) {
                            input.classList.add('is-invalid');
                            feedback.textContent = messages[0];
                        }
                    }
                } else {
                    alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la création.');
        })
        .finally(() => {
            // Reset loading state
            submitBtn.disabled = false;
            btnText.classList.remove('d-none');
            spinner.classList.add('d-none');
        });
    });
});


</script>
@endsection
