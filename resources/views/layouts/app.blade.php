<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EGREGORE BUSINESS') - CRM</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #22c55e;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f1f5f9;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: #94a3b8;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: var(--primary-color);
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            min-height: 100vh;
        }

        .navbar-top {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
        }

        .btn {
            border-radius: 8px;
            padding: 8px 16px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .table {
            border-radius: 12px;
            overflow: hidden;
        }

        .table thead {
            background-color: #f8fafc;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 6px;
        }

        .stat-card {
            border-left: 4px solid var(--primary-color);
        }

        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.danger { border-left-color: var(--danger-color); }
        .stat-card.info { border-left-color: var(--info-color); }

        .logo-text {
            color: #fff;
            font-weight: bold;
            font-size: 1.2rem;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .user-info {
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #94a3b8;
        }

        .user-info .name {
            color: #fff;
            font-weight: 500;
        }

        .user-info .role {
            font-size: 0.8rem;
            color: var(--primary-color);
        }

        /* Print styles */
        @media print {
            .sidebar, .navbar-top, .no-print {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 px-0 sidebar d-print-none">
                <div class="logo-text text-center">
                    <i class="bi bi-phone"></i> EGREGORE BUSINESS
                </div>
                
                <nav class="nav flex-column py-3">
                    @yield('sidebar')
                </nav>

                <div class="user-info mt-auto">
                    <div class="name">{{ auth()->user()->name }}</div>
                    <div class="role">
                        @if(auth()->user()->hasRole('admin'))
                            <i class="bi bi-shield-check"></i> Administrateur
                        @elseif(auth()->user()->hasRole('caissiere'))
                            <i class="bi bi-cash-stack"></i> Caissière
                        @elseif(auth()->user()->hasRole('technicien'))
                            <i class="bi bi-tools"></i> Technicien
                        @endif
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content px-0">
                <!-- Top Navbar -->
                <nav class="navbar navbar-top navbar-expand-lg px-4 py-3 d-print-none">
                    <div class="container-fluid">
                        <span class="navbar-text">
                            <i class="bi bi-calendar3"></i> {{ now()->format('l d F Y') }}
                            @if(auth()->user()->shop)
                                <span class="badge bg-dark ms-2">
                                    <i class="bi bi-shop"></i> {{ auth()->user()->shop->name }}
                                </span>
                            @endif
                        </span>
                        
                        <div class="d-flex align-items-center">
                            @yield('navbar-actions')
                            
                            {{-- Dropdown Notifications --}}
                            @include('components.notification-dropdown')
                            
                            <div class="dropdown ms-3">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                            <i class="bi bi-person"></i> Mon profil
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('notifications.index') }}">
                                            <i class="bi bi-bell"></i> Notifications
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('logout') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-box-arrow-right"></i> Déconnexion
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Page Content -->
                <div class="p-4">
                    <!-- Alerts -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle"></i> {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="bi bi-info-circle"></i> {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // CSRF Token for AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Format number as currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'decimal',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount) + ' FCFA';
        }

        // Format date
        function formatDate(date) {
            return new Intl.DateTimeFormat('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            }).format(new Date(date));
        }
    </script>
    @stack('scripts')
</body>
</html>
