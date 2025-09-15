<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', ($schoolSettings->school_name ?? 'Egesco') . ' - Gestion Scolaire')</title>
    
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
            --danger-red: #dc2626;
            --dark-color: #1e293b;
            --light-color: #ffffff;
            --light-gray: #f8fafc;
            --border-color: #e2e8f0;
            --border-radius: 0.75rem;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --box-shadow-lg: 0 10px 25px -3px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-gray);
            color: #475569;
            line-height: 1.6;
        }

        /* Custom Navbar */
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            box-shadow: var(--box-shadow-lg);
            border-bottom: 3px solid var(--accent-cyan);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            position: relative;
            z-index: 1030;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--light-color) !important;
            display: flex;
            align-items: center;
        }

        .navbar-brand img {
            max-height: 35px;
            width: auto;
            object-fit: contain;
            transition: all 0.3s ease;
            /* Supprimer le filtre qui cause les carrés blancs */
        }

        .navbar-brand:hover img {
            transform: scale(1.05);
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            color: var(--accent-cyan) !important;
            transform: scale(1.05);
            transition: all 0.3s ease;
        }

        .navbar-brand i {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Offcanvas Sidebar */
        .offcanvas-custom {
            width: 320px !important;
            background: linear-gradient(180deg, var(--primary-blue), var(--secondary-blue));
            color: var(--light-color);
            box-shadow: var(--box-shadow-lg);
        }

        .offcanvas-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1.5rem;
        }

        .offcanvas-title {
            font-weight: 600;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
        }

        .user-avatar img {
            height: 32px;
            width: 32px;
            border-radius: 50%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .dropdown-header img {
            height: 40px;
            width: auto;
            object-fit: contain;
            transition: all 0.3s ease;
        }

        .offcanvas-title img {
            max-height: 25px;
            width: auto;
            object-fit: contain;
            transition: all 0.3s ease;
        }

        .nav-section {
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-section:last-child {
            border-bottom: none;
        }

        .nav-section-title {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
            margin-bottom: 0.75rem;
            padding: 0 1.5rem;
        }

        .nav-link-custom {
            color: rgba(255, 255, 255, 0.9) !important;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-link-custom:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--accent-cyan) !important;
            border-left-color: var(--accent-cyan);
            transform: translateX(5px);
            box-shadow: inset 0 0 20px rgba(6, 182, 212, 0.1);
        }

        .nav-link-custom.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: var(--accent-cyan) !important;
            border-left-color: var(--accent-cyan);
            font-weight: 600;
            box-shadow: inset 0 0 20px rgba(6, 182, 212, 0.2);
        }

        .nav-link-custom i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            min-height: calc(100vh - 76px);
            padding: 2rem 0;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .card:hover {
            box-shadow: var(--box-shadow-lg);
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, var(--light-gray), var(--border-color));
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            padding: 1rem 1.5rem;
            color: var(--dark-color);
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: var(--light-color);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-cyan), var(--success-green));
        }

        .stats-card .card-body {
            padding: 1.5rem;
        }

        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.3;
        }

        /* Buttons */
        .btn {
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            padding: 0.625rem 1.25rem;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            border: none;
            color: var(--light-color);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-blue), var(--primary-blue));
            color: var(--light-color);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-green), #10b981);
            border: none;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-orange), #f59e0b);
            border: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-red), #ef4444);
            border: none;
        }

        .btn-info {
            background: linear-gradient(135deg, var(--accent-cyan), #0891b2);
            border: none;
        }

        /* Tables */
        .table {
            background: var(--light-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .table thead th {
            background: linear-gradient(135deg, var(--light-gray), var(--border-color));
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            color: var(--dark-color);
        }

        .table tbody tr:hover {
            background-color: rgba(37, 99, 235, 0.05);
            transition: background-color 0.2s ease;
        }

        /* Progress bars */
        .progress {
            border-radius: 10px;
            overflow: hidden;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem 0;
            }
            
            .stats-card .stats-icon {
                font-size: 2rem;
            }
        }

        /* Utilities */
        .text-primary-custom {
            color: var(--primary-blue) !important;
        }

        .bg-primary-custom {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue)) !important;
        }

        .border-primary-custom {
            border-color: var(--primary-blue) !important;
        }

        .text-accent {
            color: var(--accent-cyan) !important;
        }

        .bg-accent {
            background-color: var(--accent-cyan) !important;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease;
        }

        /* Toast notifications */
        .toast {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-lg);
        }

        /* Enhanced dropdown */
        .dropdown-menu {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-lg);
            border: none;
            z-index: 1040;
            position: absolute;
        }

        .navbar .dropdown-menu {
            z-index: 1050;
            margin-top: 0.5rem;
        }

        /* Force dropdown positioning */
        .navbar .dropdown {
            position: relative;
            z-index: 1040;
        }

        .navbar .dropdown-menu {
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            left: auto !important;
            transform: none !important;
        }

        .dropdown-item:hover {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-blue);
        }

        /* Navbar enhancements */
        .navbar-toggler {
            border: none;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 8px;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.3);
        }

        /* Sidebar section styling */
        .nav-section-title {
            background: rgba(255, 255, 255, 0.05);
            margin: 0 1rem;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }

        /* Enhanced card hover effects */
        .card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            z-index: 2;
        }

        /* Badge improvements */
        .badge {
            font-weight: 500;
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
        }

        /* Custom scrollbar for sidebar */
        .offcanvas-body::-webkit-scrollbar {
            width: 6px;
        }

        .offcanvas-body::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .offcanvas-body::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .offcanvas-body::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* User avatar styling */
        .user-avatar img {
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .user-avatar img:hover {
            border-color: var(--accent-cyan);
            transform: scale(1.1);
        }

        /* Dropdown header styling */
        .dropdown-header img {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
    </style>

    @stack('styles')
    @yield('head')
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                <i class="bi bi-list"></i>
            </button>
            
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-mortarboard me-2"></i>
                Egesco
            </a>

            <div class="navbar-nav ms-auto">
                <!-- User Dropdown -->
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar me-2">
                            @if($schoolSettings && $schoolSettings->school_logo)
                                <img src="{{ $schoolSettings->logo_url }}" alt="Logo {{ $schoolSettings->school_name }}">
                            @else
                                <i class="bi bi-person-circle fs-4"></i>
                            @endif
                        </div>
                        <div class="user-info d-none d-md-block">
                            <div class="fw-semibold">{{ Auth::user()->name ?? 'Utilisateur' }}</div>
                            <small class="text-light opacity-75">{{ ucfirst(Auth::user()->role ?? 'user') }}</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width: 200px;">
                        <li class="dropdown-header">
                            <div class="text-center">
                                @if($schoolSettings && $schoolSettings->school_logo)
                                    <img src="{{ $schoolSettings->logo_url }}" alt="Logo {{ $schoolSettings->school_name }}" class="mb-2">
                                @else
                                    <i class="bi bi-person-circle fs-1 text-primary"></i>
                                @endif
                                <div class="fw-bold">{{ Auth::user()->name ?? 'Utilisateur' }}</div>
                                <small class="text-muted">{{ Auth::user()->email ?? '' }}</small>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar Offcanvas -->
    <div class="offcanvas offcanvas-start offcanvas-custom" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="sidebarLabel">
                <i class="bi bi-mortarboard me-2"></i>
                Egesco - Menu Principal
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        
        <div class="offcanvas-body p-0">
            <!-- Dashboard (Accessible à tous) -->
            <div class="nav-section">
                <div class="nav-section-title">Tableau de bord</div>
                <a href="{{ route('dashboard') }}" class="nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    Accueil
                </a>
            </div>

            @if(Auth::check())
                @php $userRole = Auth::user()->role; @endphp

                {{-- MENU POUR ENSEIGNANTS --}}
                @if($userRole === 'teacher')
                    <!-- Mes Classes et Matières -->
                    <div class="nav-section">
                        <div class="nav-section-title">Mes Classes</div>
                        <a href="{{ route('classes.index') }}" class="nav-link-custom {{ request()->routeIs('classes.*') ? 'active' : '' }}">
                            <i class="bi bi-building"></i>
                            Classes
                        </a>
                        <a href="{{ route('subjects.index') }}" class="nav-link-custom {{ request()->routeIs('subjects.*') ? 'active' : '' }}">
                            <i class="bi bi-book"></i>
                            Matières
                        </a>
                        <a href="{{ route('schedules.index') }}" class="nav-link-custom {{ request()->routeIs('schedules.*') ? 'active' : '' }}">
                            <i class="bi bi-calendar-week"></i>
                            Emplois du Temps
                        </a>
                    </div>

                    <!-- Évaluations -->
                    <div class="nav-section">
                        <div class="nav-section-title">Évaluations</div>
                        <a href="{{ route('grades.index') }}" class="nav-link-custom {{ request()->routeIs('grades.*') ? 'active' : '' }}">
                            <i class="bi bi-journal-text"></i>
                            Notes
                        </a>
                        <a href="{{ route('attendances.index') }}" class="nav-link-custom {{ request()->routeIs('attendances.*') ? 'active' : '' }}">
                            <i class="bi bi-calendar-check"></i>
                            Présences
                        </a>
                    </div>

                    <!-- Mes Élèves -->
                    <div class="nav-section">
                        <div class="nav-section-title">Élèves</div>
                        <a href="{{ route('students.index') }}" class="nav-link-custom {{ request()->routeIs('students.*') ? 'active' : '' }}">
                            <i class="bi bi-people"></i>
                            Liste des élèves
                        </a>
                    </div>

                {{-- MENU POUR SECRÉTAIRES --}}
                @elseif($userRole === 'secretary')
                    <!-- Gestion des Inscriptions -->
                    <div class="nav-section">
                        <div class="nav-section-title">Inscriptions</div>
                        <a href="{{ route('enrollments.index') }}" class="nav-link-custom {{ request()->routeIs('enrollments.*') ? 'active' : '' }}">
                            <i class="bi bi-clipboard-check"></i>
                            Inscriptions
                        </a>
                        <a href="{{ route('enrollments.pending-students') }}" class="nav-link-custom {{ request()->routeIs('enrollments.pending-students') ? 'active' : '' }}" style="padding-left: 3rem; font-size: 0.9rem;">
                            <i class="bi bi-clock-history"></i>
                            En attente
                        </a>
                        <a href="{{ route('students.index') }}" class="nav-link-custom {{ request()->routeIs('students.*') ? 'active' : '' }}">
                            <i class="bi bi-people"></i>
                            Élèves
                        </a>
                        <a href="{{ route('parents.index') }}" class="nav-link-custom {{ request()->routeIs('parents.*') ? 'active' : '' }}">
                            <i class="bi bi-person-hearts"></i>
                            Parents
                        </a>
                    </div>

                    <!-- Finances -->
                    <div class="nav-section">
                        <div class="nav-section-title">Finances</div>
                        <a href="{{ route('fees.index') }}" class="nav-link-custom {{ request()->routeIs('fees.*') ? 'active' : '' }}">
                            <i class="bi bi-cash-stack"></i>
                            Frais scolaires
                        </a>
                        <a href="{{ route('payments.index') }}" class="nav-link-custom {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                            <i class="bi bi-credit-card"></i>
                            Paiements
                        </a>
                    </div>

                    <!-- Consultation -->
                    <div class="nav-section">
                        <div class="nav-section-title">Consultation</div>
                        <a href="{{ route('classes.index') }}" class="nav-link-custom {{ request()->routeIs('classes.*') ? 'active' : '' }}">
                            <i class="bi bi-building"></i>
                            Classes
                        </a>
                        <a href="{{ route('teachers.index') }}" class="nav-link-custom {{ request()->routeIs('teachers.*') ? 'active' : '' }}">
                            <i class="bi bi-person-workspace"></i>
                            Enseignants
                        </a>
                    </div>

                                         <!-- Rapports -->
                     <div class="nav-section">
                         <div class="nav-section-title">Rapports</div>
                         <a href="{{ route('statistics') }}" class="nav-link-custom {{ request()->routeIs('statistics') ? 'active' : '' }}">
                             <i class="bi bi-graph-up"></i>
                             Statistiques
                         </a>
                     </div>

                {{-- MENU POUR ADMINISTRATEURS ET SUPERADMINS --}}
                @elseif(in_array($userRole, ['admin', 'superadmin']))
                    <!-- Gestion Académique Complète -->
                    <div class="nav-section">
                        <div class="nav-section-title">Gestion Académique</div>
                        <a href="{{ route('students.index') }}" class="nav-link-custom {{ request()->routeIs('students.*') ? 'active' : '' }}">
                            <i class="bi bi-people"></i>
                            Élèves
                        </a>
                        <a href="{{ route('teachers.index') }}" class="nav-link-custom {{ request()->routeIs('teachers.*') ? 'active' : '' }}">
                            <i class="bi bi-person-workspace"></i>
                            Enseignants
                        </a>
                        <a href="{{ route('parents.index') }}" class="nav-link-custom {{ request()->routeIs('parents.*') ? 'active' : '' }}">
                            <i class="bi bi-person-hearts"></i>
                            Parents
                        </a>
                        <a href="{{ route('classes.index') }}" class="nav-link-custom {{ request()->routeIs('classes.*') ? 'active' : '' }}">
                            <i class="bi bi-building"></i>
                            Classes
                        </a>
                        <a href="{{ route('subjects.index') }}" class="nav-link-custom {{ request()->routeIs('subjects.*') ? 'active' : '' }}">
                            <i class="bi bi-book"></i>
                            Matières
                        </a>
                    </div>

                    <!-- Inscriptions -->
                    <div class="nav-section">
                        <div class="nav-section-title">Inscriptions</div>
                        <a href="{{ route('enrollments.index') }}" class="nav-link-custom {{ request()->routeIs('enrollments.*') ? 'active' : '' }}">
                            <i class="bi bi-clipboard-check"></i>
                            Inscriptions
                        </a>
                        <a href="{{ route('enrollments.pending-students') }}" class="nav-link-custom {{ request()->routeIs('enrollments.pending-students') ? 'active' : '' }}" style="padding-left: 3rem; font-size: 0.9rem;">
                            <i class="bi bi-clock-history"></i>
                            En attente
                        </a>
                    </div>

                    <!-- Évaluations -->
                    <div class="nav-section">
                        <div class="nav-section-title">Évaluations</div>
                        <a href="{{ route('grades.index') }}" class="nav-link-custom {{ request()->routeIs('grades.*') ? 'active' : '' }}">
                            <i class="bi bi-journal-text"></i>
                            Notes
                        </a>
                        <a href="{{ route('attendances.index') }}" class="nav-link-custom {{ request()->routeIs('attendances.*') ? 'active' : '' }}">
                            <i class="bi bi-calendar-check"></i>
                            Présences
                        </a>
                        <a href="{{ route('schedules.index') }}" class="nav-link-custom {{ request()->routeIs('schedules.*') ? 'active' : '' }}">
                            <i class="bi bi-calendar-week"></i>
                            Emplois du Temps
                        </a>
                    </div>

                    <!-- Finances -->
                    <div class="nav-section">
                        <div class="nav-section-title">Finances</div>
                        <a href="{{ route('fees.index') }}" class="nav-link-custom {{ request()->routeIs('fees.*') ? 'active' : '' }}">
                            <i class="bi bi-cash-stack"></i>
                            Frais scolaires
                        </a>
                        <a href="{{ route('payments.index') }}" class="nav-link-custom {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                            <i class="bi bi-credit-card"></i>
                            Paiements
                        </a>
                    </div>

                                         <!-- Rapports -->
                     <div class="nav-section">
                         <div class="nav-section-title">Rapports</div>
                         <a href="{{ route('statistics') }}" class="nav-link-custom {{ request()->routeIs('statistics') ? 'active' : '' }}">
                             <i class="bi bi-graph-up"></i>
                             Statistiques
                         </a>
                     </div>

                    <!-- Administration -->
                    <div class="nav-section">
                        <div class="nav-section-title">Administration</div>
                        <a href="{{ route('admin.settings.index') }}" class="nav-link-custom {{ request()->routeIs('admin.settings.index') ? 'active' : '' }}">
                            <i class="bi bi-gear-fill"></i>
                            Paramètres Généraux
                        </a>
                        <a href="{{ route('admin.school-settings.index') }}" class="nav-link-custom {{ request()->routeIs('admin.school-settings.*') ? 'active' : '' }}">
                            <i class="bi bi-building"></i>
                            Paramètres Établissement
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="nav-link-custom {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <i class="bi bi-people-fill"></i>
                            Utilisateurs
                        </a>
                        @if(Auth::user()->isSuperAdmin())
                        <a href="{{ route('admin.system-info') }}" class="nav-link-custom {{ request()->routeIs('admin.system-info') ? 'active' : '' }}">
                            <i class="bi bi-info-circle-fill"></i>
                            Informations système
                        </a>
                        <a href="{{ route('admin.security') }}" class="nav-link-custom {{ request()->routeIs('admin.security') ? 'active' : '' }}">
                            <i class="bi bi-shield-fill"></i>
                            Sécurité
                        </a>
                        <a href="{{ route('admin.maintenance') }}" class="nav-link-custom {{ request()->routeIs('admin.maintenance') ? 'active' : '' }}">
                            <i class="bi bi-tools"></i>
                            Maintenance
                        </a>
                        @endif
                    </div>
                @endif


            @endif
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        @yield('content')
    </main>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <strong class="me-auto">{{ $schoolSettings->school_name ?? 'Egesco' }}</strong>
                <small>À l'instant</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Action effectuée avec succès !
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Global JavaScript functions
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Initialize popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });

            // Auto-close alerts after 5 seconds
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);

            // Add fade-in animation to cards
            var cards = document.querySelectorAll('.card');
            cards.forEach(function(card, index) {
                card.style.animationDelay = (index * 0.1) + 's';
                card.classList.add('fade-in-up');
            });
        });

        // Show toast notification
        function showToast(message, type = 'success') {
            var toastEl = document.getElementById('liveToast');
            var toastBody = toastEl.querySelector('.toast-body');
            var toastIcon = toastEl.querySelector('.toast-header i');
            
            toastBody.textContent = message;
            
            // Update icon based on type
            toastIcon.className = 'bi me-2';
            switch(type) {
                case 'success':
                    toastIcon.classList.add('bi-check-circle-fill', 'text-success');
                    break;
                case 'error':
                    toastIcon.classList.add('bi-exclamation-triangle-fill', 'text-danger');
                    break;
                case 'warning':
                    toastIcon.classList.add('bi-exclamation-circle-fill', 'text-warning');
                    break;
                default:
                    toastIcon.classList.add('bi-info-circle-fill', 'text-info');
            }
            
            var toast = new bootstrap.Toast(toastEl);
            toast.show();
        }

        // Confirm delete action
        function confirmDelete(message = 'Êtes-vous sûr de vouloir supprimer cet élément ?') {
            return confirm(message);
        }

        // Format numbers
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        }
    </script>

    @stack('scripts')
</body>
</html>
