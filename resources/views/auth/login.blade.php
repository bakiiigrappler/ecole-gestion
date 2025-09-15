<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion - Egesco</title>
    
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
            background: white;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
        }

        /* Section Cover (Gauche) */
        .cover-section {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cover-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .cover-content {
            position: relative;
            z-index: 10;
            text-align: center;
            color: white;
            padding: 3rem;
        }

        .cover-logo {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .cover-logo::before {
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

        .cover-logo i {
            font-size: 4rem;
            color: white;
            z-index: 2;
        }

        .cover-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
        }

        .cover-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            font-weight: 400;
        }

        .cover-features {
            max-width: 400px;
            margin: 0 auto;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            text-align: left;
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .feature-text {
            font-size: 1rem;
            opacity: 0.9;
        }

        /* Section Formulaire (Droite) */
        .form-section {
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
        }

        .login-form-container {
            width: 100%;
            max-width: 450px;
        }

        .form-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .form-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-subtitle {
            color: #64748b;
            font-size: 1.1rem;
            font-weight: 500;
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
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
            color: var(--primary-blue);
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1.5rem 0;
        }

        .form-check-input:checked {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            border: none;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.4);
        }

        .btn-login:active {
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

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .register-link a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: var(--secondary-blue);
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

        /* Responsive design */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .cover-section {
                min-height: 40vh;
            }
            
            .form-section {
                padding: 2rem 1rem;
            }
            
            .cover-content {
                padding: 2rem;
            }
            
            .cover-title {
                font-size: 2rem;
            }
            
            .form-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .cover-section {
                min-height: 30vh;
            }
            
            .form-section {
                padding: 1.5rem 1rem;
            }
            
            .cover-logo {
                width: 80px;
                height: 80px;
            }
            
            .cover-logo i {
                font-size: 2.5rem;
            }
        }

        /* Loading animation */
        .btn-login.loading {
            pointer-events: none;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Section Cover (Gauche) -->
        <div class="cover-section col-lg-6">
            <div class="cover-content">
                <div class="cover-logo">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <h1 class="cover-title">Egesco</h1>
                <p class="cover-subtitle">Système de Gestion Scolaire Moderne</p>
                
                <div class="cover-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="feature-text">Sécurisé et fiable</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="feature-text">Gestion complète des utilisateurs</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="feature-text">Suivi des performances</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="feature-text">Emplois du temps optimisés</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Formulaire (Droite) -->
        <div class="form-section col-lg-6">
            <div class="login-form-container">
                <!-- Header du formulaire -->
                <div class="form-header">
                    <h1 class="form-title">Connexion</h1>
                    <p class="form-subtitle">Connectez-vous à votre compte</p>
                </div>

                <!-- Formulaire -->
                <form method="POST" action="{{ route('login') }}" id="loginForm">
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
                                   placeholder="Votre mot de passe"
                                   required>
                            <i class="bi bi-lock input-icon"></i>
                        </div>
                    </div>

                    <!-- Se souvenir de moi -->
                    <div class="remember-me">
                        <input type="checkbox" 
                               class="form-check-input" 
                               id="remember" 
                               name="remember">
                        <label class="form-check-label" for="remember">
                            Se souvenir de moi
                        </label>
                    </div>

                    <!-- Bouton de connexion -->
                    <button type="submit" class="btn btn-login" id="loginBtn">
                        <span class="btn-text">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Se connecter
                        </span>
                        <span class="d-none loading-text">
                            <span class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </span>
                            Connexion en cours...
                        </span>
                    </button>


                </form>

                <!-- Footer -->
                <div class="text-center mt-4">
                    <small class="text-muted">&copy; {{ date('Y') }} Egesco. Tous droits réservés.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const btnText = loginBtn.querySelector('.btn-text');
            const loadingText = loginBtn.querySelector('.loading-text');

            form.addEventListener('submit', function() {
                // Show loading state
                loginBtn.classList.add('loading');
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
