<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Parent - Egesco</title>
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
            background-color: var(--light-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-custom {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-bottom: 3px solid var(--accent-cyan);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--accent-cyan) !important;
            transform: translateY(-1px);
        }

        .main-container {
            padding: 2rem 0;
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: var(--light-color);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .stats-card {
            background: var(--light-color);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: none;
            transition: all 0.3s ease;
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: #6c757d;
            font-weight: 500;
        }

        .child-card {
            background: var(--light-color);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: none;
            transition: all 0.3s ease;
            height: 100%;
        }

        .child-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .child-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--light-color);
            margin: 0 auto 1rem;
        }

        .child-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .child-class {
            color: #6c757d;
            text-align: center;
            margin-bottom: 1rem;
        }

        .child-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 0.5rem;
        }

        .recent-payments {
            background: var(--light-color);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .payment-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.3s ease;
        }

        .payment-item:last-child {
            border-bottom: none;
        }

        .payment-item:hover {
            background-color: var(--light-gray);
        }

        .payment-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .payment-details {
            flex: 1;
        }

        .payment-amount {
            font-weight: 600;
            color: var(--success-green);
        }

        .payment-date {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            border: none;
            border-radius: 0.75rem;
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
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary-blue);
            color: var(--light-color);
            transform: translateY(-2px);
        }

        .section-title {
            color: var(--dark-color);
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 0.75rem;
            color: var(--primary-blue);
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem 0;
            }
            
            .welcome-section {
                padding: 1.5rem;
            }
            
            .child-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand text-white" href="#">
                <i class="bi bi-mortarboard-fill me-2"></i>
                Egesco - Portail Parent
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('parent-portal.dashboard') }}">
                            <i class="bi bi-house me-1"></i>Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('parent-portal.payment-history') }}">
                            <i class="bi bi-credit-card me-1"></i>Paiements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('parent-portal.profile') }}">
                            <i class="bi bi-person me-1"></i>Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('parent-portal.logout') }}">
                            <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="container">
            <!-- Section de bienvenue -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1 class="mb-2">Bienvenue, {{ $parent->first_name }} {{ $parent->last_name }} !</h1>
                    <p class="mb-0">Accédez aux informations de vos enfants et gérez vos paiements en ligne.</p>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue)); color: var(--light-color);">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stats-number">{{ $stats['total_children'] }}</div>
                        <div class="stats-label">Enfants inscrits</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: linear-gradient(135deg, var(--success-green), #047857); color: var(--light-color);">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stats-number">{{ $stats['active_enrollments'] }}</div>
                        <div class="stats-label">Inscriptions actives</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: linear-gradient(135deg, var(--warning-orange), #b45309); color: var(--light-color);">
                            <i class="bi bi-credit-card"></i>
                        </div>
                        <div class="stats-number">{{ $stats['total_payments'] }}</div>
                        <div class="stats-label">Paiements effectués</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: linear-gradient(135deg, var(--accent-cyan), #0891b2); color: var(--light-color);">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <div class="stats-number">{{ $stats['completed_payments'] }}</div>
                        <div class="stats-label">Paiements validés</div>
                    </div>
                </div>
            </div>

            <!-- Enfants -->
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="section-title">
                        <i class="bi bi-people-fill"></i>
                        Mes enfants
                    </h3>
                </div>
                
                @forelse($children as $child)
                    <div class="col-md-4 mb-3">
                        <div class="child-card">
                            <div class="child-avatar">
                                <i class="bi bi-person"></i>
                            </div>
                            <div class="child-name">{{ $child->first_name }} {{ $child->last_name }}</div>
                            <div class="child-class">
                                @if($child->getCurrentClass())
                                    {{ $child->getCurrentClass()->name }}
                                    @if($child->getCurrentLevel())
                                        - {{ $child->getCurrentLevel()->name }}
                                    @endif
                                @else
                                    Non inscrit
                                @endif
                            </div>
                            <div class="child-actions">
                                <a href="{{ route('parent-portal.child-details', $child->id) }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-eye me-1"></i>Détails
                                </a>
                                <a href="{{ route('parent-portal.child-grades', $child->id) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-journal-text me-1"></i>Notes
                                </a>
                                <a href="{{ route('parent-portal.child-attendance', $child->id) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-calendar-check me-1"></i>Présence
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle me-2"></i>
                            Aucun enfant inscrit pour le moment.
                            <a href="{{ route('parent-portal.online-enrollment') }}" class="alert-link ms-2">
                                Inscrire un enfant
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Paiements récents -->
            <div class="row">
                <div class="col-12">
                    <div class="recent-payments">
                        <h3 class="section-title">
                            <i class="bi bi-clock-history"></i>
                            Paiements récents
                        </h3>
                        
                        @forelse($recentPayments as $payment)
                            <div class="payment-item">
                                <div class="payment-icon" style="background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue)); color: var(--light-color);">
                                    <i class="bi bi-credit-card"></i>
                                </div>
                                <div class="payment-details">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $payment->payment_type_label }}</strong>
                                            <div class="payment-date">{{ $payment->created_at->format('d/m/Y H:i') }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="payment-amount">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</div>
                                            <span class="status-badge {{ $payment->status_badge_class }}">{{ $payment->status_label }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-credit-card" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2">Aucun paiement récent</p>
                            </div>
                        @endforelse
                        
                        @if($recentPayments->count() > 0)
                            <div class="text-center mt-3">
                                <a href="{{ route('parent-portal.payment-history') }}" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-right me-2"></i>
                                    Voir tous les paiements
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-lightning me-2"></i>
                                Actions rapides
                            </h5>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('parent-portal.online-enrollment') }}" class="btn btn-primary">
                                    <i class="bi bi-person-plus me-2"></i>
                                    Inscrire un enfant
                                </a>
                                <a href="{{ route('parent-portal.payment-history') }}" class="btn btn-outline-primary">
                                    <i class="bi bi-credit-card me-2"></i>
                                    Historique des paiements
                                </a>
                                <a href="{{ route('parent-portal.profile') }}" class="btn btn-outline-primary">
                                    <i class="bi bi-person me-2"></i>
                                    Modifier mon profil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
