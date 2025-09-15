@extends('layouts.app')

@section('title', 'Gestion des Élèves - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item active">Élèves</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Gestion des Élèves</h1>
                    <p class="text-muted">Gérez les informations de tous les élèves de l'établissement</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="bi bi-person-plus me-2"></i>
                        Ajouter un élève
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages Flash -->
    @if(session('success'))
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Erreurs de validation :</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $totalStudents ?? 0 }}</h4>
                            <span>Total élèves</span>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $activeStudents ?? 0 }}</h4>
                            <span>Actifs</span>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $newThisMonth ?? 0 }}</h4>
                            <span>Nouveaux</span>
                        </div>
                        <i class="bi bi-person-plus fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $studentsByCycle['college'] ?? 0 }}</h4>
                            <span>Collège</span>
                        </div>
                        <i class="bi bi-mortarboard fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Rechercher</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" placeholder="Nom, prénom, matricule..." id="searchInput" value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Cycle</label>
                            <select class="form-select" id="cycleFilter">
                                <option value="">Tous les cycles</option>
                                <option value="preprimaire" {{ request('cycle') == 'preprimaire' ? 'selected' : '' }}>Pré-primaire</option>
                                <option value="primaire" {{ request('cycle') == 'primaire' ? 'selected' : '' }}>Primaire</option>
                                <option value="college" {{ request('cycle') == 'college' ? 'selected' : '' }}>Collège</option>
                                <option value="lycee" {{ request('cycle') == 'lycee' ? 'selected' : '' }}>Lycée</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Niveau</label>
                            <select class="form-select" id="levelFilter">
                                <option value="">Tous les niveaux</option>
                                @foreach($levels ?? [] as $level)
                                    <option value="{{ $level->id }}" {{ request('level') == $level->id ? 'selected' : '' }}>{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Classe</label>
                            <select class="form-select" id="classFilter">
                                <option value="">Toutes les classes</option>
                                @foreach($classes ?? [] as $class)
                                    <option value="{{ $class->id }}" {{ request('class') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Statut</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">Tous les statuts</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                                <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>Diplômé</option>
                                <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>Transféré</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Students Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-people me-2"></i>
                        Liste des élèves
                    </h5>
                    <span class="badge bg-primary fs-6" id="studentCount">{{ $students->count() ?? 0 }} élèves</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="studentsTable">
                            <thead style="background: linear-gradient(135deg, #007bff, #0056b3); color: white;">
                                <tr>
                                    <th class="border-0" style="width: 50px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                        </div>
                                    </th>
                                    <th class="border-0" style="width: 60px;">Photo</th>
                                    <th class="border-0" style="width: 100px;">Matricule</th>
                                    <th class="border-0" style="min-width: 180px;">Nom complet</th>
                                    <th class="border-0" style="width: 120px;">Classe actuelle</th>
                                    <th class="border-0" style="width: 60px;">Âge</th>
                                    <th class="border-0" style="width: 180px;">Parents liés</th>
                                    <th class="border-0" style="width: 90px;">Statut</th>
                                    <th class="border-0" style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $student)
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="{{ $student->id }}">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($student->photo)
                                                <img src="{{ asset('storage/' . $student->photo) }}" class="rounded-circle" width="40" height="40" alt="Photo">
                                            @else
                                                <div class="avatar-sm">
                                                    <div class="avatar-title bg-primary rounded-circle" style="width: 40px; height: 40px; font-size: 1rem;">
                                                        {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-primary">{{ $student->student_id }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-bold">{{ $student->full_name }}</div>
                                            <small class="text-muted">Né(e) le {{ $student->date_of_birth->format('d/m/Y') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @php 
                                            $currentClass = $student->getCurrentClass(); 
                                            $currentLevel = $student->getCurrentLevel();
                                        @endphp
                                        @if($currentClass)
                                            <span class="badge bg-info">{{ $currentClass->name ?? 'Nom manquant' }}</span>
                                            @if($currentLevel && $currentLevel->name)
                                                <br><small class="text-muted">{{ $currentLevel->name }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">Non inscrit</span>
                                        @endif
                                    </td>
                                    <td>{{ $student->age }} ans</td>
                                    <td>
                                        <div>
                                            @if($student->parents->isNotEmpty())
                                                @if($student->parents->count() == 1)
                                                    @php $parent = $student->parents->first(); @endphp
                                                    <div class="mb-1">
                                                        <small class="text-primary fw-bold">{{ $parent->first_name }} {{ $parent->last_name }}</small>
                                                        @if($parent->is_primary_contact)
                                                            <span class="badge bg-success ms-1" style="font-size: 0.6rem;">Principal</span>
                                                        @endif
                                                    </div>
                                                    <small class="text-muted d-block">{{ $parent->phone ?? 'Téléphone non renseigné' }}</small>
                                                    @if($parent->email)
                                                        <small class="text-muted">{{ $parent->email }}</small>
                                                    @endif
                                                @else
                                                    <small class="text-primary fw-bold">{{ $student->parents->count() }}+ parents</small>
                                                    <br><small class="text-muted">Cliquez pour voir les détails</small>
                                                @endif
                                            @else
                                                <small class="text-warning fw-bold">Aucun parent lié</small>
                                                <br><small class="text-muted">Utilisez la section Parents pour lier</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($student->status === 'active')
                                            <span class="badge bg-success">Actif</span>
                                        @elseif($student->status === 'inactive')
                                            <span class="badge bg-secondary">Inactif</span>
                                        @elseif($student->status === 'graduated')
                                            <span class="badge bg-primary">Diplômé</span>
                                        @else
                                            <span class="badge bg-warning">Transféré</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-info" data-student-id="{{ $student->id }}" onclick="viewStudent(this.dataset.studentId)" title="Voir détails">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <a href="{{ route('students.edit', $student->id) }}" class="btn btn-sm btn-outline-warning" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-student-id="{{ $student->id }}" data-student-name="{{ $student->full_name }}" onclick="confirmDeleteStudent(this.dataset.studentId, this.dataset.studentName)" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                            <h5>Aucun élève trouvé</h5>
                                            <p>Commencez par ajouter un élève en cliquant sur le bouton ci-dessus.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div class="card-footer bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            @if($students->total() > 0)
                                Affichage de {{ $students->firstItem() }} à {{ $students->lastItem() }} sur {{ $students->total() }} élèves
                            @else
                                Aucun élève trouvé
                            @endif
                        </div>
                        @if($students->hasPages())
                        <nav aria-label="Pagination des élèves">
                            {{ $students->links('pagination::bootstrap-5') }}
                        </nav>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addStudentModalLabel">
                    <i class="bi bi-person-plus me-2"></i>
                    Ajouter un nouvel élève
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addStudentForm" method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <!-- Informations personnelles de l'élève -->
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-person me-2"></i>
                                Informations personnelles de l'élève
                            </h6>
                            
                            <div class="mb-3">
                                <label for="student_id" class="form-label">Matricule élève</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                    <input type="text" class="form-control" id="student_id_display" readonly style="background-color: #f8f9fa;">
                                    <input type="hidden" id="student_id" name="student_id">
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-magic me-1 text-primary"></i>
                                    <span class="text-primary">Matricule généré automatiquement</span> - Ce matricule sera attribué à l'élève lors de l'enregistrement
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">Prénom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date_of_birth" class="form-label">Date de naissance <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Sexe <span class="text-danger">*</span></label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="">Sélectionner...</option>
                                        <option value="male">Masculin</option>
                                        <option value="female">Féminin</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="place_of_birth" class="form-label">Lieu de naissance</label>
                                <input type="text" class="form-control" id="place_of_birth" name="place_of_birth" placeholder="Ville, Pays">
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Adresse de l'élève <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="address" name="address" rows="3" required placeholder="Adresse complète de résidence"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="emergency_contact" class="form-label">Contact d'urgence</label>
                                <input type="text" class="form-control" id="emergency_contact" name="emergency_contact" placeholder="+241 XX XX XX XX">
                                <div class="form-text">Téléphone à contacter en cas d'urgence</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="medical_conditions" class="form-label">Conditions médicales particulières</label>
                                <textarea class="form-control" id="medical_conditions" name="medical_conditions" rows="3" placeholder="Allergies, traitements en cours, conditions particulières..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="photo" class="form-label">Photo de l'élève</label>
                                <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                <div class="form-text">Formats acceptés: JPG, PNG, GIF (max 2MB)</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="enrollment_date" class="form-label">Date d'inscription <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="enrollment_date" name="enrollment_date" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Statut</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" selected>Actif</option>
                                        <option value="inactive">Inactif</option>
                                        <option value="graduated">Diplômé</option>
                                        <option value="transferred">Transféré</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Inscription scolaire optionnelle -->
                        <div class="col-md-6">
                            <h6 class="text-warning mb-3">
                                <i class="bi bi-mortarboard me-2"></i>
                                Inscription scolaire (optionnel)
                            </h6>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="create_enrollment" name="create_enrollment" value="on">
                                    <label class="form-check-label" for="create_enrollment">
                                        <strong>Créer une inscription pour cet élève</strong>
                                    </label>
                                </div>
                                <div class="form-text">Cochez pour inscrire directement l'élève dans une classe</div>
                            </div>
                            
                            <div id="enrollment_section" class="d-none">
                                <div class="mb-3">
                                    <label for="cycle" class="form-label">Cycle <span class="text-danger">*</span></label>
                                    <select class="form-select" id="cycle" name="cycle">
                                        <option value="">Sélectionner un cycle</option>
                                        <option value="preprimaire">Pré-primaire</option>
                                        <option value="primaire">Primaire</option>
                                        <option value="college">Collège</option>
                                        <option value="lycee">Lycée</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="level_id" class="form-label">Niveau <span class="text-danger">*</span></label>
                                    <select class="form-select" id="level_id" name="level_id">
                                        <option value="">Sélectionner un niveau</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Classe <span class="text-danger">*</span></label>
                                    <select class="form-select" id="class_id" name="class_id">
                                        <option value="">Sélectionner une classe</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="academic_year_id" class="form-label">Année scolaire <span class="text-danger">*</span></label>
                                    <select class="form-select" id="academic_year_id" name="academic_year_id">
                                        <option value="">Sélectionner une année</option>
                                        @php
                                            $currentYear = date('Y');
                                            $academicYears = \App\Models\AcademicYear::orderBy('name', 'desc')->get();
                                        @endphp
                                        @foreach($academicYears as $year)
                                            <option value="{{ $year->id }}" {{ $year->status === 'active' ? 'selected' : '' }}>
                                                {{ $year->name }} {{ $year->status === 'active' ? '(Actuelle)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Message d'information -->
                    <div class="alert alert-info d-flex align-items-center mt-3" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <div>
                            <strong>Information :</strong> L'élève sera créé uniquement avec ses informations personnelles. Les parents peuvent être ajoutés via la section "Parents" et liés aux élèves. L'inscription scolaire est optionnelle et peut être effectuée plus tard.
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>
                        Enregistrer l'élève
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1" aria-labelledby="viewStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewStudentModalLabel">
                    <i class="bi bi-person-circle me-2"></i>
                    Détails de l'élève
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentDetails">
                <!-- Le contenu sera chargé dynamiquement -->
                <div class="text-center py-4" id="loadingDetails">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2 text-muted">Chargement des détails...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="printStudentCard()">
                    <i class="bi bi-printer me-2"></i>
                    Imprimer la fiche
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteStudentModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Confirmer la suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle me-3 fs-4"></i>
                    <div>
                        <strong>Attention !</strong> Cette action est irréversible.
                    </div>
                </div>
                <p>Êtes-vous sûr de vouloir supprimer l'élève <strong id="studentNameToDelete"></strong> ?</p>
                <p class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Toutes les données associées (inscriptions, notes, présences, etc.) seront également supprimées.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>
                    Annuler
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bi bi-trash me-2"></i>
                    Supprimer définitivement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour proposer l'enregistrement des parents après création d'élève -->
<div class="modal fade" id="parentRegistrationModalStudent" tabindex="-1" aria-labelledby="parentRegistrationModalStudentLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="parentRegistrationModalStudentLabel">
                    <i class="bi bi-people-fill me-2"></i>
                    Enregistrer les parents de l'élève
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-check-circle me-3 fs-4"></i>
                    <div>
                        <strong>Élève créé avec succès !</strong><br>
                        L'élève <strong id="studentNameInModalStudent"></strong> a été enregistré. 
                        Souhaitez-vous maintenant enregistrer les informations de ses parents/tuteurs ?
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <i class="bi bi-clock-history fs-2 text-warning mb-3"></i>
                                <h6>Plus tard</h6>
                                <p class="text-muted small">Vous pourrez ajouter les parents plus tard via la section "Parents" du système.</p>
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-clock me-2"></i>
                                    Reporter à plus tard
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="bi bi-person-plus-fill fs-2 text-success mb-3"></i>
                                <h6>Maintenant</h6>
                                <p class="text-muted small">Enregistrer immédiatement les informations des parents/tuteurs de cet élève.</p>
                                <button type="button" class="btn btn-success" id="addParentNowBtnStudent">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    Ajouter maintenant
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">
                            <i class="bi bi-lightbulb me-2"></i>
                            Avantages de l'enregistrement des parents
                        </h6>
                        <ul class="text-muted small">
                            <li>Accès au portail parent pour consulter les notes et absences</li>
                            <li>Réception de notifications automatiques</li>
                            <li>Gestion des paiements en ligne</li>
                            <li>Communication directe avec l'établissement</li>
                            <li>Lien familial établi dans le système</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Vous pourrez toujours ajouter ou modifier les parents plus tard.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Bibliothèques jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    const cycleSelect = document.getElementById('cycle');
    const levelSelect = document.getElementById('level_id');
    const classSelect = document.getElementById('class_id');
    const createEnrollmentCheckbox = document.getElementById('create_enrollment');
    const enrollmentSection = document.getElementById('enrollment_section');

    // Charger automatiquement le prochain matricule disponible
    function loadNextStudentMatricule() {
        const studentIdDisplay = document.getElementById('student_id_display');
        const studentIdHidden = document.getElementById('student_id');
        
        if (studentIdDisplay && studentIdHidden) {
            // Afficher un indicateur de chargement
            studentIdDisplay.value = 'Chargement...';
            
            fetch('/api/next-student-matricule')
                .then(response => response.json())
                .then(data => {
                    studentIdDisplay.value = data.matricule;
                    studentIdHidden.value = data.matricule;
                })
                .catch(error => {
                    console.error('Erreur lors du chargement du matricule:', error);
                    studentIdDisplay.value = 'Erreur de chargement';
                });
        }
    }

    // Charger le matricule au chargement de la page
    loadNextStudentMatricule();

    // Gérer l'affichage de la section inscription
    if (createEnrollmentCheckbox && enrollmentSection) {
        createEnrollmentCheckbox.addEventListener('change', function() {
            if (this.checked) {
                enrollmentSection.classList.remove('d-none');
                // Rendre les champs requis
                cycleSelect.setAttribute('required', 'required');
                levelSelect.setAttribute('required', 'required');
                classSelect.setAttribute('required', 'required');
                document.getElementById('academic_year_id').setAttribute('required', 'required');
            } else {
                enrollmentSection.classList.add('d-none');
                // Retirer les attributs requis
                cycleSelect.removeAttribute('required');
                levelSelect.removeAttribute('required');
                classSelect.removeAttribute('required');
                document.getElementById('academic_year_id').removeAttribute('required');
                
                // Réinitialiser les sélections
                cycleSelect.value = '';
                levelSelect.innerHTML = '<option value="">Sélectionner un niveau</option>';
                classSelect.innerHTML = '<option value="">Sélectionner une classe</option>';
            }
        });
    }

    // Chargement des niveaux par cycle
    if (cycleSelect) {
        cycleSelect.addEventListener('change', function() {
            const cycle = this.value;
            levelSelect.innerHTML = '<option value="">Sélectionner un niveau</option>';
            classSelect.innerHTML = '<option value="">Sélectionner une classe</option>';
            
            if (cycle) {
                // Charger les niveaux par AJAX
                fetch(`/api/levels-by-cycle?cycle=${cycle}`)
                    .then(response => response.json())
                    .then(levels => {
                        levels.forEach(level => {
                            levelSelect.innerHTML += `<option value="${level.id}">${level.name}</option>`;
                        });
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des niveaux:', error);
                    });
            }
        });
    }

    // Chargement des classes par niveau
    if (levelSelect) {
        levelSelect.addEventListener('change', function() {
            const levelId = this.value;
            classSelect.innerHTML = '<option value="">Sélectionner une classe</option>';
            
            if (levelId) {
                fetch(`/api/students/classes-by-level?level_id=${levelId}`)
                    .then(response => response.json())
                    .then(classes => {
                        classes.forEach(classItem => {
                            classSelect.innerHTML += `<option value="${classItem.id}">${classItem.name}</option>`;
                        });
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des classes:', error);
                    });
            }
        });
    }

    // Gestion de la soumission du formulaire élève avec AJAX
    document.getElementById('addStudentForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Empêcher la soumission normale
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Afficher un indicateur de chargement
        submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>Création en cours...';
        submitBtn.disabled = true;
        
        fetch(this.action, {
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
                // Fermer le modal de création
                bootstrap.Modal.getInstance(document.getElementById('addStudentModal')).hide();
                
                // Afficher un message de succès temporaire
                const successAlert = document.createElement('div');
                successAlert.className = 'alert alert-success alert-dismissible fade show position-fixed';
                successAlert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
                successAlert.innerHTML = `
                    <i class="bi bi-check-circle me-2"></i>
                    <strong>Élève créé avec succès !</strong><br>
                    ${data.student.first_name} ${data.student.last_name} a été enregistré.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.body.appendChild(successAlert);
                
                // Supprimer l'alerte après 5 secondes
                setTimeout(() => {
                    if (successAlert.parentNode) {
                        successAlert.remove();
                    }
                }, 5000);
                
                // Attendre un peu puis proposer l'ajout des parents
                setTimeout(function() {
                    showParentRegistrationModalForStudent(data.student);
                }, 1000);
                
                // Recharger la page pour voir le nouvel élève
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                alert('Erreur lors de la création : ' + (data.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la création de l\'élève.');
        })
        .finally(() => {
            // Restaurer le bouton
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });

    // Réinitialiser le formulaire à la fermeture du modal
    document.getElementById('addStudentModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('addStudentForm').reset();
        enrollmentSection.classList.add('d-none');
        levelSelect.innerHTML = '<option value="">Sélectionner un niveau</option>';
        classSelect.innerHTML = '<option value="">Sélectionner une classe</option>';
        // Recharger un nouveau matricule pour la prochaine utilisation
        loadNextStudentMatricule();
    });

    // Recharger le matricule à l'ouverture du modal
    document.getElementById('addStudentModal').addEventListener('show.bs.modal', function() {
        loadNextStudentMatricule();
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        filterStudents();
    });

    // Filter functionality
    document.getElementById('cycleFilter').addEventListener('change', filterStudents);
    document.getElementById('levelFilter').addEventListener('change', filterStudents);
    document.getElementById('classFilter').addEventListener('change', filterStudents);
    document.getElementById('statusFilter').addEventListener('change', filterStudents);

    // Select all checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Confirmer la suppression quand on clique sur le bouton de confirmation
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (studentToDeleteId) {
            deleteStudent(studentToDeleteId);
        }
    });
});

function filterStudents() {
    const searchInput = document.getElementById('searchInput').value;
    const cycleFilter = document.getElementById('cycleFilter').value;
    const levelFilter = document.getElementById('levelFilter').value;
    const classFilter = document.getElementById('classFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    
    // Construire l'URL avec les paramètres de filtre
    const params = new URLSearchParams();
    
    if (searchInput) params.append('search', searchInput);
    if (cycleFilter) params.append('cycle', cycleFilter);
    if (levelFilter) params.append('level', levelFilter);
    if (classFilter) params.append('class', classFilter);
    if (statusFilter) params.append('status', statusFilter);
    
    // Rediriger avec les filtres
    const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.location.href = url;
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('cycleFilter').value = '';
    document.getElementById('levelFilter').value = '';
    document.getElementById('classFilter').value = '';
    document.getElementById('statusFilter').value = '';
    filterStudents();
}

function exportData() {
    // Implémentation de l'export des données
    alert('Export en cours...');
}

let currentStudentData = null; // Variable globale pour stocker les données de l'étudiant

function viewStudent(id) {
    // Réinitialiser complètement le modal
    const modal = new bootstrap.Modal(document.getElementById('viewStudentModal'));
    const detailsContainer = document.getElementById('studentDetails');
    
    // Réinitialiser le contenu avec le loader
    detailsContainer.innerHTML = `
        <div class="text-center py-4" id="loadingDetails">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-2 text-muted">Chargement des détails...</p>
        </div>
    `;
    
    // Afficher le modal
    modal.show();
    
    // Charger les détails via AJAX
    fetch(`/students/${id}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentStudentData = data.student; // Stocker pour l'impression
                displayStudentDetails(data.student);
            } else {
                detailsContainer.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des détails.</div>';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            detailsContainer.innerHTML = '<div class="alert alert-danger">Erreur de connexion.</div>';
        });
}

function displayStudentDetails(student) {
    const detailsContainer = document.getElementById('studentDetails');
    
    const statusBadge = {
        'active': '<span class="badge bg-success">Actif</span>',
        'inactive': '<span class="badge bg-secondary">Inactif</span>',
        'graduated': '<span class="badge bg-primary">Diplômé</span>',
        'transferred': '<span class="badge bg-warning">Transféré</span>'
    };
    
    const genderLabel = student.gender === 'male' ? 'Masculin' : 'Féminin';
    
    let parentsHtml = '';
    if (student.parents && student.parents.length > 0) {
        parentsHtml = student.parents.map(parent => `
            <div class="border rounded p-3 mb-2">
                <h6 class="mb-2"><i class="bi bi-person-heart me-2"></i>${parent.first_name} ${parent.last_name}</h6>
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">Téléphone:</small><br>
                        <span>${parent.phone || 'Non renseigné'}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Email:</small><br>
                        <span>${parent.email || 'Non renseigné'}</span>
                    </div>
                    <div class="col-12 mt-2">
                        <small class="text-muted">Profession:</small><br>
                        <span>${parent.profession || 'Non renseignée'}</span>
                    </div>
                    ${parent.address ? `
                    <div class="col-12 mt-2">
                        <small class="text-muted">Adresse:</small><br>
                        <span>${parent.address}</span>
                    </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
    } else {
        parentsHtml = '<div class="alert alert-warning">Aucun parent/tuteur enregistré</div>';
    }
    
    let enrollmentHtml = '';
    if (student.current_enrollment) {
        enrollmentHtml = `
            <div class="alert alert-info">
                <h6><i class="bi bi-mortarboard me-2"></i>Inscription actuelle</h6>
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted">Classe:</small><br>
                        <strong>${student.current_enrollment.class_name}</strong>
                    </div>
                    ${student.current_enrollment.level_name && student.current_enrollment.level_name !== 'null' ? `
                    <div class="col-md-4">
                        <small class="text-muted">Niveau:</small><br>
                        <strong>${student.current_enrollment.level_name}</strong>
                    </div>` : ''}
                    <div class="col-md-4">
                        <small class="text-muted">Année scolaire:</small><br>
                        <strong>${student.current_enrollment.academic_year}</strong>
                    </div>
                </div>
            </div>
        `;
    } else {
        enrollmentHtml = '<div class="alert alert-warning">Aucune inscription active</div>';
    }
    
    detailsContainer.innerHTML = `
        <div class="row">
            <div class="col-md-3 text-center">
                ${student.photo ? 
                    `<img src="${student.photo}" class="img-fluid rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;" alt="Photo de ${student.full_name}">` :
                    `<div class="bg-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                        <span class="text-white fs-1">${student.first_name.charAt(0)}${student.last_name.charAt(0)}</span>
                    </div>`
                }
                <h5>${student.full_name}</h5>
                <p class="text-muted">Matricule: <strong>${student.student_id}</strong></p>
                ${statusBadge[student.status] || statusBadge['inactive']}
            </div>
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary"><i class="bi bi-person me-2"></i>Informations personnelles</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Date de naissance:</td>
                                <td><strong>${student.date_of_birth}</strong> (${student.age} ans)</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Sexe:</td>
                                <td>${genderLabel}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Lieu de naissance:</td>
                                <td>${student.place_of_birth || 'Non renseigné'}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Date d'inscription:</td>
                                <td>${student.enrollment_date}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success"><i class="bi bi-geo-alt me-2"></i>Contact & Adresse</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Adresse:</td>
                                <td>${student.address}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Contact d'urgence:</td>
                                <td>${student.emergency_contact || 'Non renseigné'}</td>
                            </tr>
                        </table>
                        
                        ${student.medical_conditions ? `
                        <h6 class="text-warning mt-3"><i class="bi bi-heart-pulse me-2"></i>Conditions médicales</h6>
                        <div class="alert alert-warning">
                            ${student.medical_conditions}
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                <hr class="my-3">
                
                <!-- Inscription -->
                <h6 class="text-info"><i class="bi bi-book me-2"></i>Inscription scolaire</h6>
                ${enrollmentHtml}
                
                <!-- Parents -->
                <h6 class="text-secondary"><i class="bi bi-people me-2"></i>Parents/Tuteurs</h6>
                ${parentsHtml}
            </div>
        </div>
    `;
}



let studentToDeleteId = null; // Variable globale pour stocker l'ID de l'étudiant à supprimer

function confirmDeleteStudent(id, name) {
    // Stocker l'ID et afficher le nom dans le modal
    studentToDeleteId = id;
    document.getElementById('studentNameToDelete').textContent = name;
    
    // Afficher le modal de confirmation
    const modal = new bootstrap.Modal(document.getElementById('deleteStudentModal'));
    modal.show();
}

function deleteStudent(id) {
    // Afficher un indicateur de chargement sur le bouton
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>Suppression...';
    confirmBtn.disabled = true;
    
    // Envoyer la requête de suppression
    fetch(`/students/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal
            bootstrap.Modal.getInstance(document.getElementById('deleteStudentModal')).hide();
            
            // Afficher un message de succès et recharger la page
            alert('Élève supprimé avec succès !');
            window.location.reload();
        } else {
            alert('Erreur lors de la suppression : ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la suppression.');
    })
    .finally(() => {
        // Restaurer le bouton
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
    });
}

async function printStudentCard() {
    if (!currentStudentData) {
        alert('Aucune donnée d\'élève disponible pour l\'impression.');
        return;
    }
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('portrait', 'mm', 'a4');
    
    const student = currentStudentData;
    const currentDate = new Date().toLocaleDateString('fr-FR');
    
    // Dimensions de la page
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 15;
    
    // En-tête avec logo et titre
    doc.setFillColor(41, 128, 185);
    doc.rect(0, 0, pageWidth, 35, 'F');
    
    // Logo de l'établissement (si disponible)
    const schoolLogoUrl = '{{ $schoolSettings->logo_url ?? "" }}';
    
    // Fonction pour charger le logo de manière synchrone
    const loadLogo = async () => {
        if (schoolLogoUrl && schoolLogoUrl !== '') {
            try {
                return new Promise((resolve, reject) => {
                    const logoImg = new Image();
                    logoImg.crossOrigin = 'anonymous';
                    logoImg.onload = function() {
                        // Créer un élément canvas pour le logo
                        const logoCanvas = document.createElement('canvas');
                        const logoCtx = logoCanvas.getContext('2d');
                        
                        // Redimensionner le logo
                        const logoSize = 25;
                        logoCanvas.width = logoSize;
                        logoCanvas.height = logoSize;
                        logoCtx.drawImage(logoImg, 0, 0, logoSize, logoSize);
                        
                        const logoData = logoCanvas.toDataURL('image/png', 0.8);
                        resolve(logoData);
                    };
                    logoImg.onerror = function() {
                        console.error('Erreur lors du chargement du logo');
                        resolve(null);
                    };
                    logoImg.src = schoolLogoUrl;
                });
            } catch (error) {
                console.error('Erreur lors du traitement du logo:', error);
                return null;
            }
        }
        return null;
    };
    
    // Charger le logo et continuer avec la génération du PDF
    const logoData = await loadLogo();
    
    // Ajouter le logo au PDF si disponible
    if (logoData) {
        doc.addImage(logoData, 'PNG', margin, 5, 25, 25);
        console.log('Logo de l\'établissement ajouté au PDF');
    }
    
    // Titre principal
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(20);
    doc.setFont('helvetica', 'bold');
    doc.text('FICHE ÉLÈVE', pageWidth / 2, 15, { align: 'center' });
    
    // Nom de l'établissement
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text('{{ $schoolSettings->school_name ?? "Établissement Scolaire" }}', pageWidth / 2, 25, { align: 'center' });
    
    // Date d'impression
    doc.setFontSize(8);
    doc.text(`Imprimé le: ${currentDate}`, pageWidth - margin, 32, { align: 'right' });
    
    // Ligne décorative
    doc.setDrawColor(52, 152, 219);
    doc.setLineWidth(1);
    doc.line(margin, 37, pageWidth - margin, 37);
    
    // Remettre la couleur du texte en noir
    doc.setTextColor(0, 0, 0);
    
    let currentY = 50;
    
    // Section informations personnelles
    doc.setFillColor(240, 248, 255);
    doc.roundedRect(margin, currentY, pageWidth - (2 * margin), 12, 2, 2, 'F');
    
    doc.setFontSize(14);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(41, 128, 185);
    doc.text('INFORMATIONS PERSONNELLES', margin + 5, currentY + 8);
    
    currentY += 20;
    
    // Photo de l'étudiant - Chargement asynchrone (format carré)
    doc.setDrawColor(200, 200, 200);
    doc.setLineWidth(0.5);
    doc.rect(margin, currentY, 40, 40);
    
    // Debug de la photo
    console.log('Photo data:', student.photo);
    
    if (student.photo) {
        try {
            // Fonction helper pour charger l'image de manière asynchrone
            const loadImage = (src) => {
                return new Promise((resolve, reject) => {
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.onload = () => resolve(img);
                    img.onerror = reject;
                    img.src = src;
                });
            };
            
            // Attendre le chargement de l'image
            const img = await loadImage(student.photo);
            
            // Créer un canvas pour redimensionner l'image
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Redimensionner l'image pour qu'elle soit carrée
            const targetSize = 113; // 40mm en pixels environ (format carré)
            
            canvas.width = targetSize;
            canvas.height = targetSize;
            
            // Dessiner l'image redimensionnée sur le canvas (format carré)
            ctx.drawImage(img, 0, 0, targetSize, targetSize);
            
            // Convertir en format compatible jsPDF
            const imgData = canvas.toDataURL('image/jpeg', 0.8);
            
            // Ajouter l'image carrée au PDF
            doc.addImage(imgData, 'JPEG', margin, currentY, 40, 40);
            
            console.log('Photo ajoutée au PDF avec succès');
            
        } catch (error) {
            console.error('Erreur lors du chargement/traitement de la photo:', error);
            console.error('URL de la photo:', student.photo);
            
            // Fallback vers placeholder d'erreur (carré)
            doc.setFillColor(255, 235, 235);
            doc.rect(margin + 1, currentY + 1, 38, 38, 'F');
            doc.setFontSize(8);
            doc.setTextColor(220, 53, 69);
            doc.text('Erreur', margin + 20, currentY + 18, { align: 'center' });
            doc.text('photo', margin + 20, currentY + 25, { align: 'center' });
        }
    } else {
        // Placeholder si pas de photo (carré)
        doc.setFillColor(250, 250, 250);
        doc.rect(margin + 1, currentY + 1, 38, 38, 'F');
        doc.setFontSize(8);
        doc.setTextColor(100, 100, 100);
        doc.text('Aucune', margin + 20, currentY + 18, { align: 'center' });
        doc.text('photo', margin + 20, currentY + 25, { align: 'center' });
    }
    
    // Informations à côté de la photo (ajustées pour photo carrée)
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.text(`${student.full_name}`, margin + 45, currentY + 8);
    
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    doc.text(`Matricule: ${student.student_id}`, margin + 45, currentY + 15);
    doc.text(`Date de naissance: ${student.date_of_birth} (${student.age} ans)`, margin + 45, currentY + 22);
    doc.text(`Sexe: ${student.gender === 'male' ? 'Masculin' : 'Féminin'}`, margin + 45, currentY + 29);
    doc.text(`Lieu de naissance: ${student.place_of_birth || 'Non renseigné'}`, margin + 45, currentY + 36);
    
    currentY += 50;
    
    // Section statut et inscription
    doc.setFillColor(245, 255, 245);
    doc.roundedRect(margin, currentY, pageWidth - (2 * margin), 12, 2, 2, 'F');
    
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(40, 167, 69);
    doc.text('STATUT ET INSCRIPTION', margin + 5, currentY + 8);
    
    currentY += 20;
    
    doc.setTextColor(0, 0, 0);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    
    const statusLabel = {
        'active': 'Actif',
        'inactive': 'Inactif', 
        'graduated': 'Diplômé',
        'transferred': 'Transféré'
    };
    
    doc.text(`Statut: ${statusLabel[student.status] || 'Inconnu'}`, margin, currentY);
    doc.text(`Date d'inscription: ${student.enrollment_date}`, margin + 70, currentY);
    
    currentY += 10;
    
    if (student.current_enrollment) {
        doc.text(`Classe actuelle: ${student.current_enrollment.class_name}`, margin, currentY);
        if (student.current_enrollment.level_name && student.current_enrollment.level_name !== 'null') {
            doc.text(`Niveau: ${student.current_enrollment.level_name}`, margin + 70, currentY);
        }
        currentY += 7;
        doc.text(`Année scolaire: ${student.current_enrollment.academic_year}`, margin, currentY);
    } else {
        doc.text('Aucune inscription active', margin, currentY);
    }
    
    currentY += 20;
    
    // Section contact et adresse
    doc.setFillColor(255, 248, 220);
    doc.roundedRect(margin, currentY, pageWidth - (2 * margin), 12, 2, 2, 'F');
    
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(255, 193, 7);
    doc.text('CONTACT ET ADRESSE', margin + 5, currentY + 8);
    
    currentY += 20;
    
    doc.setTextColor(0, 0, 0);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    
    // Adresse (peut être longue)
    doc.text('Adresse:', margin, currentY);
    const addressLines = doc.splitTextToSize(student.address, pageWidth - (2 * margin) - 20);
    doc.text(addressLines, margin + 20, currentY);
    currentY += addressLines.length * 5 + 5;
    
    if (student.emergency_contact) {
        doc.text(`Contact d'urgence: ${student.emergency_contact}`, margin, currentY);
        currentY += 7;
    }
    
    currentY += 10;
    
    // Section parents/tuteurs
    if (student.parents && student.parents.length > 0) {
        doc.setFillColor(255, 240, 245);
        doc.roundedRect(margin, currentY, pageWidth - (2 * margin), 12, 2, 2, 'F');
        
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(220, 53, 69);
        doc.text('PARENTS/TUTEURS', margin + 5, currentY + 8);
        
        currentY += 20;
        
        doc.setTextColor(0, 0, 0);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        
        student.parents.forEach((parent, index) => {
            doc.setFont('helvetica', 'bold');
            doc.text(`${index + 1}. ${parent.first_name} ${parent.last_name}`, margin, currentY);
            currentY += 7;
            
            doc.setFont('helvetica', 'normal');
            doc.text(`Téléphone: ${parent.phone || 'Non renseigné'}`, margin + 5, currentY);
            currentY += 5;
            doc.text(`Email: ${parent.email || 'Non renseigné'}`, margin + 5, currentY);
            currentY += 5;
            if (parent.profession) {
                doc.text(`Profession: ${parent.profession}`, margin + 5, currentY);
                currentY += 5;
            }
            currentY += 5;
        });
    }
    
    // Section conditions médicales (si présentes)
    if (student.medical_conditions) {
        currentY += 5;
        
        doc.setFillColor(255, 243, 205);
        doc.roundedRect(margin, currentY, pageWidth - (2 * margin), 12, 2, 2, 'F');
        
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(133, 100, 4);
        doc.text('CONDITIONS MÉDICALES', margin + 5, currentY + 8);
        
        currentY += 20;
        
        doc.setTextColor(0, 0, 0);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        
        const medicalLines = doc.splitTextToSize(student.medical_conditions, pageWidth - (2 * margin));
        doc.text(medicalLines, margin, currentY);
        currentY += medicalLines.length * 5;
    }
    
    // Pied de page
    const footerY = pageHeight - 25;
    doc.setDrawColor(200, 200, 200);
    doc.setLineWidth(0.5);
    doc.line(margin, footerY, pageWidth - margin, footerY);
    
    doc.setFontSize(8);
    doc.setTextColor(100, 100, 100);
    doc.setFont('helvetica', 'italic');
    doc.text('Document généré automatiquement par le système de gestion scolaire', pageWidth / 2, footerY + 5, { align: 'center' });
    doc.text(`Fiche élève - ${student.full_name} (${student.student_id})`, pageWidth / 2, footerY + 10, { align: 'center' });
    
    // Télécharger le PDF
    const filename = `Fiche_Eleve_${student.student_id}_${student.last_name}_${student.first_name}.pdf`;
    doc.save(filename);
}

// Fonction pour afficher le modal d'enregistrement des parents pour un élève créé directement
function showParentRegistrationModalForStudent(student) {
    // Mettre à jour le nom de l'élève dans le modal
    document.getElementById('studentNameInModalStudent').textContent = `${student.first_name} ${student.last_name}`;
    
    // Configurer le bouton "Ajouter maintenant"
    document.getElementById('addParentNowBtnStudent').onclick = function() {
        // Rediriger vers la page de création de parent avec l'ID de l'élève en paramètre
        window.location.href = `/parents/create?student_id=${student.id}&from=student`;
    };
    
    // Afficher le modal
    const parentModal = new bootstrap.Modal(document.getElementById('parentRegistrationModalStudent'));
    parentModal.show();
}
</script>
@endpush 
