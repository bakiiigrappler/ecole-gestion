<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inscription - Egesco</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #2563eb;
            --secondary-blue: #1e40af;
            --accent-cyan: #06b6d4;
            --success-green: #059669;
            --warning-orange: #d97706;
            --light-gray: #f8fafc;
            --border-color: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #059669 0%, #0891b2 50%, #2563eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated background elements */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: float 25s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }

        .register-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 520px;
            padding: 2rem;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            position: relative;
        }

        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--success-green), var(--accent-cyan), var(--primary-blue));
        }

        .register-header {
            text-align: center;
            padding: 3rem 2rem 2rem;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            position: relative;
        }

        .logo-container {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--success-green), var(--accent-cyan));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(5, 150, 105, 0.3);
            position: relative;
            overflow: hidden;
        }

        .logo-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shine 3s ease-in-out infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(30deg); }
            50% { transform: translateX(100%) translateY(100%) rotate(30deg); }
            100% { transform: translateX(-100%) translateY(-100%) rotate(30deg); }
        }

        .logo-container i {
            font-size: 2.5rem;
            color: white;
            z-index: 2;
        }

        .register-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--success-green), var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .register-subtitle {
            color: #64748b;
            font-size: 1rem;
            font-weight: 500;
        }

        .register-form {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-group-modern {
            position: relative;
        }

        .input-group-modern .form-control {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem 1rem 1rem 3rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--light-gray);
            border-color: #e2e8f0;
        }

        .input-group-modern .form-control:focus {
            border-color: var(--success-green);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
            background: white;
            outline: none;
        }

        .input-group-modern .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            z-index: 5;
            transition: color 0.3s ease;
        }

        .input-group-modern .form-control:focus + .input-icon {
            color: var(--success-green);
        }

        .btn-register {
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--success-green), var(--accent-cyan));
            border: none;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-register:hover::before {
            left: 100%;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(5, 150, 105, 0.4);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            margin: 2rem 0;
            position: relative;
            color: #9ca3af;
            font-size: 0.875rem;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--border-color);
            z-index: 1;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            position: relative;
            z-index: 2;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .login-link a {
            color: var(--success-green);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: var(--accent-cyan);
        }

        .alert-modern {
            border-radius: 12px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background: rgba(5, 150, 105, 0.1);
            color: var(--success-green);
            border-left: 4px solid var(--success-green);
        }

        .footer-text {
            text-align: center;
            margin-top: 2rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
        }

        /* Password strength indicator */
        .password-strength {
            margin-top: 0.5rem;
            display: none;
        }

        .strength-bar {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }

        .strength-fill {
            height: 100%;
            transition: width 0.3s ease, background-color 0.3s ease;
            width: 0%;
        }

        .strength-weak { background-color: #dc2626; }
        .strength-medium { background-color: #d97706; }
        .strength-strong { background-color: var(--success-green); }

        /* Responsive design */
        @media (max-width: 576px) {
            .register-container {
                padding: 1rem;
            }
            
            .register-form {
                padding: 1.5rem;
            }
            
            .register-header {
                padding: 2rem 1.5rem 1.5rem;
            }
        }

        /* Loading animation */
        .btn-register.loading {
            pointer-events: none;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <div class="logo-container">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h1 class="register-title">Créer un compte</h1>
                <p class="register-subtitle">Rejoignez Egesco</p>
            </div>

            <!-- Form -->
            <div class="register-form">
                <form method="POST" action="{{ route('register') }}" id="registerForm">
                    @csrf
                    
                    <!-- Messages d'erreur -->
                    @if ($errors->any())
                        <div class="alert alert-danger alert-modern">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <div>
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Messages de succès -->
                    @if (session('success'))
                        <div class="alert alert-success alert-modern">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle me-2"></i>
                                <div>{{ session('success') }}</div>
                            </div>
                        </div>
                    @endif

                    <!-- Nom complet -->
                    <div class="form-group">
                        <label for="name" class="form-label">Nom Complet</label>
                        <div class="input-group-modern">
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   placeholder="Votre nom complet"
                                   value="{{ old('name') }}" 
                                   required>
                            <i class="bi bi-person input-icon"></i>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email" class="form-label">Adresse Email</label>
                        <div class="input-group-modern">
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   placeholder="votre@email.com"
                                   value="{{ old('email') }}" 
                                   required>
                            <i class="bi bi-envelope input-icon"></i>
                        </div>
                    </div>

                    <!-- Mot de passe -->
                    <div class="form-group">
                        <label for="password" class="form-label">Mot de Passe</label>
                        <div class="input-group-modern">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Minimum 8 caractères"
                                   required>
                            <i class="bi bi-lock input-icon"></i>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthBar"></div>
                            </div>
                            <small class="text-muted" id="strengthText">Force du mot de passe</small>
                        </div>
                    </div>

                    <!-- Confirmation du mot de passe -->
                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Confirmer le Mot de Passe</label>
                        <div class="input-group-modern">
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   placeholder="Confirmez votre mot de passe"
                                   required>
                            <i class="bi bi-lock-fill input-icon"></i>
                        </div>
                    </div>

                    <!-- Bouton d'inscription -->
                    <button type="submit" class="btn btn-register" id="registerBtn">
                        <span class="btn-text">
                            <i class="bi bi-person-plus me-2"></i>
                            Créer mon compte
                        </span>
                        <span class="d-none loading-text">
                            <span class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </span>
                            Création en cours...
                        </span>
                    </button>

                    <!-- Divider -->
                    <div class="divider">
                        <span>ou</span>
                    </div>

                    <!-- Lien de connexion -->
                    <div class="login-link">
                        <p class="mb-0">
                            Déjà un compte ? 
                            <a href="{{ route('login') }}">Se connecter</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-text">
            <p>&copy; {{ date('Y') }} Egesco. Tous droits réservés.</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const registerBtn = document.getElementById('registerBtn');
            const btnText = registerBtn.querySelector('.btn-text');
            const loadingText = registerBtn.querySelector('.loading-text');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('password_confirmation');
            const strengthIndicator = document.getElementById('passwordStrength');
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');

            form.addEventListener('submit', function() {
                // Show loading state
                registerBtn.classList.add('loading');
                btnText.classList.add('d-none');
                loadingText.classList.remove('d-none');
            });

            // Input focus animations
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentNode.classList.add('focused');
                });

                input.addEventListener('blur', function() {
                    this.parentNode.classList.remove('focused');
                });
            });

            // Password strength checker
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                checkPasswordStrength(password);
            });

            // Password confirmation validation
            confirmPasswordInput.addEventListener('input', function() {
                if (this.value && this.value !== passwordInput.value) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });

            function checkPasswordStrength(password) {
                let strength = 0;
                let text = '';

                if (password.length >= 8) strength++;
                if (password.match(/[a-z]/)) strength++;
                if (password.match(/[A-Z]/)) strength++;
                if (password.match(/[0-9]/)) strength++;
                if (password.match(/[^a-zA-Z0-9]/)) strength++;

                // Reset classes
                strengthBar.className = 'strength-fill';

                if (password.length === 0) {
                    strengthIndicator.style.display = 'none';
                    strengthBar.style.width = '0%';
                    text = 'Force du mot de passe';
                } else {
                    strengthIndicator.style.display = 'block';
                    if (strength < 3) {
                        strengthBar.classList.add('strength-weak');
                        strengthBar.style.width = '33%';
                        text = 'Faible';
                    } else if (strength < 5) {
                        strengthBar.classList.add('strength-medium');
                        strengthBar.style.width = '66%';
                        text = 'Moyen';
                    } else {
                        strengthBar.classList.add('strength-strong');
                        strengthBar.style.width = '100%';
                        text = 'Fort';
                    }
                }

                strengthText.textContent = text;
            }

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 500);
                });
            }, 5000);
        });
    </script>
</body>
</html>
