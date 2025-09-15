<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Parent - Egesco</title>
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

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-card {
            background: var(--light-color);
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: var(--light-color);
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .login-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .login-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .school-logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            position: relative;
            z-index: 1;
        }

        .login-form {
            padding: 3rem 2rem;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
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

        .features-list {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
        }

        .features-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
        }

        .features-list li:last-child {
            border-bottom: none;
        }

        .features-list i {
            color: var(--success-green);
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .footer-links {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .footer-links a {
            color: var(--primary-blue);
            text-decoration: none;
            margin: 0 1rem;
            font-weight: 500;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        .enrollment-section {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-top: 1rem;
            color: white;
        }

        .enrollment-title {
            color: white;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .enrollment-section .btn-primary {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-weight: 600;
        }

        .enrollment-section .btn-primary:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        /* Section de gauche entièrement bleue */
        .left-section {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: white;
        }

        .left-form {
            color: white;
        }

        .left-form h3 {
            color: white;
        }

        .left-form .features-list li {
            border-bottom-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .left-form .features-list i {
            color: rgba(255, 255, 255, 0.8);
        }

        .left-form .btn-light {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-weight: 600;
        }

        .left-form .btn-light:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .login-card {
                margin: 1rem;
            }
            
            .login-header {
                padding: 2rem 1rem;
            }
            
            .login-form {
                padding: 2rem 1rem;
            }
            
            .login-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="row g-0">
                <!-- Section de gauche - Informations -->
                <div class="col-lg-6 left-section">
                    <div class="login-header">
                        <div class="school-logo">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <h1>Portail Parent</h1>
                        <p>Accédez aux informations de vos enfants</p>
                    </div>
                    
                    <div class="login-form left-form">
                        <h3 class="mb-4">Bienvenue sur le portail parent</h3>
                        
                        <ul class="features-list">
                            <li>
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Suivi des notes et résultats</span>
                            </li>
                            <li>
                                <i class="bi bi-calendar-check"></i>
                                <span>Consultation des présences</span>
                            </li>
                            <li>
                                <i class="bi bi-credit-card"></i>
                                <span>Paiements en ligne sécurisés</span>
                            </li>
                            <li>
                                <i class="bi bi-bell"></i>
                                <span>Notifications en temps réel</span>
                            </li>
                            <li>
                                <i class="bi bi-chat-dots"></i>
                                <span>Communication avec l'école</span>
                            </li>
                        </ul>
                        
                        <div class="text-center">
                            <a href="{{ route('parent-portal.online-enrollment') }}" class="btn btn-light w-100">
                                <i class="bi bi-person-plus me-2"></i>
                                Inscrire un enfant
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Section de droite - Formulaire de connexion -->
                <div class="col-lg-6 d-flex align-items-center">
                    <div class="login-form w-100">
                        <div class="text-center mb-4">
                            <h2 class="text-dark">Connexion</h2>
                            <p class="text-muted">Accédez à votre espace parent</p>
                        </div>
                        
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <form method="POST" action="{{ route('parent-portal.login') }}">
                            @csrf
                            
                            <div class="form-floating">
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" placeholder="Email" 
                                       value="{{ old('email') }}" required>
                                <label for="email">
                                    <i class="bi bi-envelope me-2"></i>
                                    Adresse email
                                </label>
                            </div>
                            
                            <div class="form-floating">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password" placeholder="Mot de passe" required>
                                <label for="password">
                                    <i class="bi bi-lock me-2"></i>
                                    Mot de passe
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Se connecter
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <a href="#" class="text-decoration-none">Mot de passe oublié ?</a>
                        </div>
                        
                        <div class="footer-links">
                            <a href="{{ route('login') }}">Espace administration</a>
                            <a href="{{ route('parent-portal.online-enrollment') }}">Inscription en ligne</a>
                            <a href="#">Aide</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
