<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inscription en ligne - Egesco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2563eb;
            --secondary-blue: #1e40af;
            --accent-cyan: #06b6d4;
            --success-green: #059669;
            --warning-orange: #d97706;
            --danger-red: #dc2626;
            --dark-color: #1e293b;
            --light-color: #ffffff;
            --light-gray: #f8fafc;
            --border-color: #e2e8f0;
        }

        body {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .enrollment-container {
            min-height: 100vh;
            padding: 2rem 0;
        }

        .enrollment-card {
            background: var(--light-color);
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1200px;
            margin: 0 auto;
        }

        .enrollment-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: var(--light-color);
            padding: 2rem;
            text-align: center;
        }

        .enrollment-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .enrollment-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .enrollment-body {
            padding: 3rem;
        }

        .form-section {
            background: var(--light-gray);
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-section h3 {
            color: var(--primary-blue);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .form-section h3 i {
            margin-right: 0.75rem;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .payment-method {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--light-color);
        }

        .payment-method:hover {
            border-color: var(--primary-blue);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .payment-method.selected {
            border-color: var(--primary-blue);
            background: rgba(37, 99, 235, 0.05);
        }

        .payment-method img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-bottom: 1rem;
        }

        .payment-method h5 {
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .payment-method p {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            border-radius: 0.75rem;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary-blue);
            color: var(--light-color);
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 0.75rem;
            border: none;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3rem;
            position: relative;
        }

        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .progress-step::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: var(--border-color);
            transform: translateX(-50%);
            z-index: 1;
        }

        .progress-step:last-child::before {
            display: none;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--border-color);
            color: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: 600;
            position: relative;
            z-index: 2;
        }

        .step-number.active {
            background: var(--primary-blue);
        }

        .step-number.completed {
            background: var(--success-green);
        }

        .step-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }

        .step-label.active {
            color: var(--primary-blue);
        }

        .step-label.completed {
            color: var(--success-green);
        }

        .enrollment-type-card {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--light-color);
            height: 100%;
        }

        .enrollment-type-card:hover {
            border-color: var(--primary-blue);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .enrollment-type-card.selected {
            border-color: var(--primary-blue);
            background: rgba(37, 99, 235, 0.05);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.2);
        }

        .enrollment-type-card h5 {
            margin: 1rem 0 0.5rem;
            color: var(--dark-color);
            font-weight: 600;
        }

        .enrollment-type-card p {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .type-icon {
            margin-bottom: 1rem;
        }

        .student-info-display {
            background: rgba(5, 150, 105, 0.1);
            border: 2px solid var(--success-green);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .student-info-display h5 {
            color: var(--success-green);
            margin-bottom: 1rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .info-label {
            font-weight: 600;
            color: var(--dark-color);
        }

        .info-value {
            color: #6c757d;
        }

        .alert-already-enrolled {
            background: rgba(220, 38, 38, 0.1);
            border: 2px solid var(--danger-red);
            color: var(--danger-red);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .enrollment-body {
                padding: 2rem 1rem;
            }
            
            .enrollment-header {
                padding: 1.5rem 1rem;
            }
            
            .enrollment-header h1 {
                font-size: 2rem;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="enrollment-container">
        <div class="enrollment-card">
            <div class="enrollment-header">
                <h1><i class="bi bi-mortarboard-fill me-3"></i>Inscription en ligne</h1>
                <p>Inscrivez votre enfant en quelques étapes simples</p>
            </div>
            
            <div class="enrollment-body">
                <!-- Étapes de progression -->
                <div class="progress-steps">
                    <div class="progress-step">
                        <div class="step-number active">1</div>
                        <div class="step-label active">Type d'inscription</div>
                    </div>
                    <div class="progress-step">
                        <div class="step-number">2</div>
                        <div class="step-label">Informations</div>
                    </div>
                    <div class="progress-step">
                        <div class="step-number">3</div>
                        <div class="step-label">Paiement</div>
                    </div>
                    <div class="progress-step">
                        <div class="step-number">4</div>
                        <div class="step-label">Confirmation</div>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <h5><i class="bi bi-exclamation-triangle me-2"></i>Erreurs de validation</h5>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Choix du type d'inscription -->
                <div class="form-section" id="enrollmentTypeSection">
                    <h3><i class="bi bi-list-check"></i>Type d'inscription</h3>
                    <p class="text-muted mb-4">Sélectionnez le type d'inscription que vous souhaitez effectuer :</p>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="enrollment-type-card" data-type="new">
                                <div class="type-icon">
                                    <i class="bi bi-person-plus" style="font-size: 3rem; color: var(--primary-blue);"></i>
                                </div>
                                <h5>Nouvelle inscription</h5>
                                <p>Pour un élève qui n'a jamais été inscrit dans l'établissement</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="enrollment-type-card" data-type="renewal">
                                <div class="type-icon">
                                    <i class="bi bi-arrow-repeat" style="font-size: 3rem; color: var(--success-green);"></i>
                                </div>
                                <h5>Réinscription</h5>
                                <p>Pour un élève déjà inscrit qui souhaite renouveler son inscription</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section vérification matricule (pour réinscription) -->
                <div class="form-section" id="matriculeSection" style="display: none;">
                    <h3><i class="bi bi-search"></i>Vérification du matricule</h3>
                    <div class="form-floating">
                        <input type="text" class="form-control" id="student_matricule" name="student_matricule" 
                               placeholder="Matricule de l'élève">
                        <label for="student_matricule">Matricule de l'élève</label>
                    </div>
                    <button type="button" class="btn btn-primary" id="checkMatriculeBtn">
                        <i class="bi bi-search me-2"></i>Vérifier le matricule
                    </button>
                    <div id="matriculeResult" class="mt-3"></div>
                </div>

                <form method="POST" action="{{ route('parent-portal.process-enrollment') }}" id="enrollmentForm" style="display: none;">
                    @csrf
                    <input type="hidden" name="enrollment_type" id="enrollment_type">
                    <input type="hidden" name="existing_student_id" id="existing_student_id">
                    
                    <!-- Informations de l'élève -->
                    <div class="form-section">
                        <h3><i class="bi bi-person-badge"></i>Informations de l'élève</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control @error('student_first_name') is-invalid @enderror" 
                                           id="student_first_name" name="student_first_name" 
                                           placeholder="Prénom" value="{{ old('student_first_name') }}" required>
                                    <label for="student_first_name">Prénom de l'élève</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control @error('student_last_name') is-invalid @enderror" 
                                           id="student_last_name" name="student_last_name" 
                                           placeholder="Nom" value="{{ old('student_last_name') }}" required>
                                    <label for="student_last_name">Nom de l'élève</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="date" class="form-control @error('student_date_of_birth') is-invalid @enderror" 
                                           id="student_date_of_birth" name="student_date_of_birth" 
                                           value="{{ old('student_date_of_birth') }}" required>
                                    <label for="student_date_of_birth">Date de naissance</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select @error('student_gender') is-invalid @enderror" 
                                            id="student_gender" name="student_gender" required>
                                        <option value="">Sélectionner</option>
                                        <option value="male" {{ old('student_gender') == 'male' ? 'selected' : '' }}>Masculin</option>
                                        <option value="female" {{ old('student_gender') == 'female' ? 'selected' : '' }}>Féminin</option>
                                    </select>
                                    <label for="student_gender">Genre</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations du parent -->
                    <div class="form-section">
                        <h3><i class="bi bi-person"></i>Informations du parent/tuteur</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control @error('parent_first_name') is-invalid @enderror" 
                                           id="parent_first_name" name="parent_first_name" 
                                           placeholder="Prénom" value="{{ old('parent_first_name') }}" required>
                                    <label for="parent_first_name">Prénom du parent</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control @error('parent_last_name') is-invalid @enderror" 
                                           id="parent_last_name" name="parent_last_name" 
                                           placeholder="Nom" value="{{ old('parent_last_name') }}" required>
                                    <label for="parent_last_name">Nom du parent</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="tel" class="form-control @error('parent_phone') is-invalid @enderror" 
                                           id="parent_phone" name="parent_phone" 
                                           placeholder="Téléphone" value="{{ old('parent_phone') }}" required>
                                    <label for="parent_phone">Téléphone</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control @error('parent_email') is-invalid @enderror" 
                                           id="parent_email" name="parent_email" 
                                           placeholder="Email" value="{{ old('parent_email') }}" required>
                                    <label for="parent_email">Email</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations académiques -->
                    <div class="form-section">
                        <h3><i class="bi bi-book"></i>Informations académiques</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select @error('class_id') is-invalid @enderror" 
                                            id="class_id" name="class_id" required>
                                        <option value="">Sélectionner une classe</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                                {{ $class->name }} - {{ $class->level->name ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="class_id">Classe</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select @error('academic_year_id') is-invalid @enderror" 
                                            id="academic_year_id" name="academic_year_id" required>
                                        <option value="">Sélectionner une année</option>
                                        @foreach($academicYears as $year)
                                            <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
                                                {{ $year->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="academic_year_id">Année scolaire</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Méthode de paiement -->
                    <div class="form-section">
                        <h3><i class="bi bi-credit-card"></i>Méthode de paiement</h3>
                        <p class="text-muted mb-3" id="amountDisplay">
                            Montant : <strong id="enrollmentAmount">50 000 FCFA</strong>
                        </p>
                        
                        <div class="payment-methods">
                            @foreach($paymentGateways as $gateway)
                                <div class="payment-method" data-method="{{ $gateway->code }}">
                                    <div class="payment-icon">
                                        @if($gateway->code == 'moov_money')
                                            <i class="bi bi-phone" style="font-size: 3rem; color: #007BFF;"></i>
                                        @elseif($gateway->code == 'airtel_money')
                                            <i class="bi bi-phone" style="font-size: 3rem; color: #DC3545;"></i>
                                        @endif
                                    </div>
                                    <h5>{{ $gateway->name }}</h5>
                                    <p>Frais: {{ number_format($gateway->fixed_fee) }} FCFA + {{ $gateway->transaction_fee }}%</p>
                                </div>
                            @endforeach
                        </div>
                        
                        <input type="hidden" name="payment_method" id="payment_method" required>
                        <input type="hidden" name="payer_phone" id="payer_phone">
                        
                        <div class="form-floating mt-3">
                            <input type="tel" class="form-control" id="payer_phone_input" 
                                   placeholder="Numéro de téléphone pour le paiement">
                            <label for="payer_phone_input">Numéro de téléphone pour le paiement</label>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('parent-portal.login') }}" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Retour
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-credit-card me-2"></i>
                            Procéder au paiement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedEnrollmentType = null;
        let studentData = null;

        // Sélection du type d'inscription
        document.querySelectorAll('.enrollment-type-card').forEach(card => {
            card.addEventListener('click', function() {
                // Retirer la sélection précédente
                document.querySelectorAll('.enrollment-type-card').forEach(c => c.classList.remove('selected'));
                
                // Ajouter la sélection à l'élément cliqué
                this.classList.add('selected');
                
                selectedEnrollmentType = this.dataset.type;
                document.getElementById('enrollment_type').value = selectedEnrollmentType;
                
                if (selectedEnrollmentType === 'renewal') {
                    // Afficher la section matricule pour réinscription
                    document.getElementById('matriculeSection').style.display = 'block';
                    document.getElementById('enrollmentForm').style.display = 'none';
                    // Mettre à jour le montant pour réinscription
                    document.getElementById('enrollmentAmount').textContent = '30 000 FCFA';
                    document.getElementById('amountDisplay').innerHTML = 'Montant de la réinscription : <strong id="enrollmentAmount">30 000 FCFA</strong>';
                } else {
                    // Nouvelle inscription - afficher directement le formulaire
                    document.getElementById('matriculeSection').style.display = 'none';
                    document.getElementById('enrollmentForm').style.display = 'block';
                    // Mettre à jour le montant pour nouvelle inscription
                    document.getElementById('enrollmentAmount').textContent = '50 000 FCFA';
                    document.getElementById('amountDisplay').innerHTML = 'Montant de l\'inscription : <strong id="enrollmentAmount">50 000 FCFA</strong>';
                    updateProgressSteps(2);
                }
            });
        });

        // Vérification du matricule
        document.getElementById('checkMatriculeBtn').addEventListener('click', function() {
            const matricule = document.getElementById('student_matricule').value.trim();
            
            if (!matricule) {
                alert('Veuillez saisir un matricule.');
                return;
            }

            // Afficher un spinner
            this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Vérification...';
            this.disabled = true;

            // Appel AJAX pour vérifier le matricule
            fetch('/api/students/check-matricule', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ matricule: matricule })
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('matriculeResult');
                
                if (data.success) {
                    if (data.already_enrolled) {
                        // Élève déjà inscrit pour l'année en cours
                        resultDiv.innerHTML = `
                            <div class="alert-already-enrolled">
                                <h5><i class="bi bi-exclamation-triangle me-2"></i>Élève déjà inscrit</h5>
                                <p><strong>${data.student.first_name} ${data.student.last_name}</strong> est déjà inscrit(e) pour l'année scolaire en cours dans la classe <strong>${data.current_class}</strong>.</p>
                                <p>Aucune action supplémentaire n'est nécessaire.</p>
                            </div>
                        `;
                    } else {
                        // Élève trouvé, peut être réinscrit
                        studentData = data.student;
                        document.getElementById('existing_student_id').value = data.student.id;
                        
                        resultDiv.innerHTML = `
                            <div class="student-info-display">
                                <h5><i class="bi bi-person-check me-2"></i>Élève trouvé</h5>
                                <div class="info-row">
                                    <span class="info-label">Nom complet :</span>
                                    <span class="info-value">${data.student.first_name} ${data.student.last_name}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Date de naissance :</span>
                                    <span class="info-value">${new Date(data.student.date_of_birth).toLocaleDateString('fr-FR')}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Dernière classe :</span>
                                    <span class="info-value">${data.last_class || 'Non disponible'}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Statut :</span>
                                    <span class="info-value">Peut être réinscrit</span>
                                </div>
                            </div>
                        `;
                        
                        // Pré-remplir le formulaire
                        document.getElementById('student_first_name').value = data.student.first_name;
                        document.getElementById('student_last_name').value = data.student.last_name;
                        document.getElementById('student_date_of_birth').value = data.student.date_of_birth;
                        document.getElementById('student_gender').value = data.student.gender;
                        
                        // Rendre les champs en lecture seule
                        document.getElementById('student_first_name').readOnly = true;
                        document.getElementById('student_last_name').readOnly = true;
                        document.getElementById('student_date_of_birth').readOnly = true;
                        document.getElementById('student_gender').disabled = true;
                        
                        // Afficher le formulaire
                        document.getElementById('enrollmentForm').style.display = 'block';
                        updateProgressSteps(3);
                    }
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Matricule non trouvé. Veuillez vérifier le matricule ou effectuer une nouvelle inscription.
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                document.getElementById('matriculeResult').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Erreur lors de la vérification. Veuillez réessayer.
                    </div>
                `;
            })
            .finally(() => {
                this.innerHTML = '<i class="bi bi-search me-2"></i>Vérifier le matricule';
                this.disabled = false;
            });
        });

        // Sélection de la méthode de paiement
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // Retirer la sélection précédente
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                
                // Ajouter la sélection à l'élément cliqué
                this.classList.add('selected');
                
                // Mettre à jour le champ caché
                document.getElementById('payment_method').value = this.dataset.method;
            });
        });

        // Synchroniser le téléphone du parent avec le téléphone de paiement
        document.getElementById('parent_phone').addEventListener('input', function() {
            document.getElementById('payer_phone_input').value = this.value;
            document.getElementById('payer_phone').value = this.value;
        });

        document.getElementById('payer_phone_input').addEventListener('input', function() {
            document.getElementById('payer_phone').value = this.value;
        });

        // Fonction pour mettre à jour les étapes de progression
        function updateProgressSteps(activeStep) {
            document.querySelectorAll('.step-number').forEach((step, index) => {
                step.classList.remove('active', 'completed');
                const label = step.nextElementSibling;
                label.classList.remove('active', 'completed');
                
                if (index + 1 < activeStep) {
                    step.classList.add('completed');
                    label.classList.add('completed');
                } else if (index + 1 === activeStep) {
                    step.classList.add('active');
                    label.classList.add('active');
                }
            });
        }

        // Validation du formulaire
        document.getElementById('enrollmentForm').addEventListener('submit', function(e) {
            const paymentMethod = document.getElementById('payment_method').value;
            const payerPhone = document.getElementById('payer_phone').value;
            
            if (!paymentMethod) {
                e.preventDefault();
                alert('Veuillez sélectionner une méthode de paiement.');
                return;
            }
            
            if (!payerPhone) {
                e.preventDefault();
                alert('Veuillez saisir un numéro de téléphone pour le paiement.');
                return;
            }
        });
    </script>
</body>
</html>
