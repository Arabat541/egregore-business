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
    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
        .spin { display:inline-block; animation: spin .6s linear infinite; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 px-0 sidebar d-print-none">
                <div class="logo-text text-center">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height:75px; width:auto; margin-bottom:8px; border-radius:8px; background:#fff; padding:6px;"><br>
                    EGREGORE BUSINESS
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
                            <i class="bi bi-calendar3"></i> {{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
                            @if(auth()->user()->shop)
                                <span class="badge bg-dark ms-2">
                                    <i class="bi bi-shop"></i> {{ auth()->user()->shop->name }}
                                </span>
                            @endif
                        </span>
                        
                        <div class="d-flex align-items-center">
                            @yield('navbar-actions')

                            {{-- Bouton Recherche globale --}}
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm me-2 d-flex align-items-center gap-1"
                                    id="globalSearchBtn"
                                    data-bs-toggle="modal" data-bs-target="#globalSearchModal"
                                    title="Recherche globale (Ctrl+K)">
                                <i class="bi bi-search"></i>
                                <span class="d-none d-lg-inline text-muted" style="font-size:.75rem;">Ctrl+K</span>
                            </button>

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

    {{-- ══════════════════════════════════════════════════════════════
         Modal Recherche globale (Ctrl+K)
         ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="globalSearchModal" tabindex="-1" aria-label="Recherche globale">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" style="margin-top:8vh;">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header border-0 pb-0">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted" id="gsIcon"></i>
                        </span>
                        <input type="search" id="globalSearchInput"
                               class="form-control border-start-0 ps-0 fs-5"
                               placeholder="Rechercher un produit, client, réparation, facture…"
                               autocomplete="off" autofocus>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body pt-2" id="gsResults" style="min-height:120px; max-height:60vh; overflow-y:auto;">
                    <p class="text-muted text-center py-4" id="gsHint">
                        Tapez au moins 2 caractères…
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-start">
                    <small class="text-muted">
                        <kbd>↑</kbd><kbd>↓</kbd> naviguer &nbsp;
                        <kbd>↵</kbd> ouvrir &nbsp;
                        <kbd>Échap</kbd> fermer
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const searchUrl  = '{{ route('global.search') }}';
        const modal      = document.getElementById('globalSearchModal');
        const input      = document.getElementById('globalSearchInput');
        const resultsEl  = document.getElementById('gsResults');
        const hint       = document.getElementById('gsHint');
        const icon       = document.getElementById('gsIcon');

        let debounceTimer = null;
        let activeIdx     = -1;
        let lastQuery     = '';

        // ── Ouvrir avec Ctrl+K ──────────────────────────────────────────
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                const tag = document.activeElement?.tagName;
                // Autoriser l'ouverture même depuis un champ (sauf dans le modal lui-même)
                if (modal.classList.contains('show')) return;
                e.preventDefault();
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });

        // ── Focus auto à l'ouverture ────────────────────────────────────
        modal.addEventListener('shown.bs.modal', function () {
            input.value = '';
            input.focus();
            resetResults();
        });

        // ── Recherche au fil de la frappe ───────────────────────────────
        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            if (q.length < 2) { resetResults(); return; }
            if (q === lastQuery) return;

            icon.className = 'bi bi-arrow-repeat text-muted spin';
            debounceTimer = setTimeout(() => doSearch(q), 200);
        });

        // ── Navigation clavier dans les résultats ───────────────────────
        input.addEventListener('keydown', function (e) {
            const items = resultsEl.querySelectorAll('a.gs-item');
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIdx = Math.min(activeIdx + 1, items.length - 1);
                items[activeIdx]?.focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (activeIdx <= 0) { activeIdx = -1; input.focus(); return; }
                activeIdx = Math.max(activeIdx - 1, 0);
                items[activeIdx]?.focus();
            } else if (e.key === 'Enter' && activeIdx >= 0) {
                e.preventDefault();
                items[activeIdx]?.click();
            }
        });

        resultsEl.addEventListener('keydown', function (e) {
            const items = resultsEl.querySelectorAll('a.gs-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIdx = Math.min(activeIdx + 1, items.length - 1);
                items[activeIdx]?.focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIdx = Math.max(activeIdx - 1, -1);
                if (activeIdx < 0) input.focus();
                else items[activeIdx]?.focus();
            }
        });

        async function doSearch(q) {
            lastQuery = q;
            try {
                const res  = await fetch(searchUrl + '?q=' + encodeURIComponent(q), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                renderResults(data.results, q);
            } catch {
                resultsEl.innerHTML = '<p class="text-danger text-center py-3">Erreur lors de la recherche.</p>';
            } finally {
                icon.className = 'bi bi-search text-muted';
            }
        }

        function renderResults(results, q) {
            activeIdx = -1;
            if (!results.length) {
                resultsEl.innerHTML = '<p class="text-muted text-center py-4">Aucun résultat pour <strong>' +
                    escHtml(q) + '</strong></p>';
                return;
            }

            // Grouper
            const groups = {};
            results.forEach(r => {
                if (!groups[r.group]) groups[r.group] = [];
                groups[r.group].push(r);
            });

            let html = '';
            Object.entries(groups).forEach(([group, items]) => {
                html += '<div class="mb-2"><div class="px-2 py-1 text-uppercase text-muted" style="font-size:.7rem;letter-spacing:.08em;">' +
                    escHtml(group) + '</div>';
                items.forEach(item => {
                    html += `<a href="${escHtml(item.url)}"
                               class="gs-item d-flex align-items-center gap-3 px-3 py-2 text-decoration-none rounded-2 text-dark"
                               style="transition:background .1s"
                               onmouseenter="this.style.background='#f1f5f9'"
                               onmouseleave="this.style.background=''"
                               data-bs-dismiss="modal">
                        <i class="bi ${escHtml(item.icon)} text-secondary" style="font-size:1.2rem;min-width:1.4rem;"></i>
                        <div>
                            <div class="fw-semibold">${highlight(escHtml(item.label), q)}</div>
                            ${item.sublabel ? '<div class="text-muted small">' + escHtml(item.sublabel) + '</div>' : ''}
                        </div>
                    </a>`;
                });
                html += '</div>';
            });

            resultsEl.innerHTML = html;
        }

        function resetResults() {
            lastQuery = '';
            activeIdx = -1;
            resultsEl.innerHTML = '';
            resultsEl.appendChild(hint);
            hint.style.display = '';
        }

        function escHtml(s) {
            return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function highlight(text, q) {
            const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            return text.replace(new RegExp('(' + escaped + ')', 'gi'), '<mark class="p-0 bg-warning-subtle">$1</mark>');
        }
    })();
    </script>

    {{-- Raccourcis clavier globaux --}}
    <script>
    (function () {
        // Routes disponibles selon le rôle courant
        const shortcuts = {
        @auth
            @if(auth()->user()->hasRole('caissiere'))
            F2: '{{ route('cashier.sales.create') }}',    // Nouvelle vente
            F3: '{{ route('cashier.repairs.create') }}',  // Nouvelle réparation
            @endif
            @if(auth()->user()->hasRole('admin'))
            F2: '{{ route('admin.products.index') }}',    // Catalogue produits
            F3: '{{ route('admin.reports.index') }}',     // Rapports
            @endif
        @endauth
        };

        document.addEventListener('keydown', function (e) {
            // Ne pas intercepter si l'utilisateur tape dans un champ
            const tag = document.activeElement?.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
            // Ne pas intercepter si une modal Bootstrap est ouverte
            if (document.querySelector('.modal.show')) return;

            const url = shortcuts[e.key];
            if (url) {
                e.preventDefault();
                window.location.href = url;
            }
        });

        // Afficher un tooltip d'aide discret au bas de l'écran lors du premier démarrage
        if (Object.keys(shortcuts).length > 0 && !sessionStorage.getItem('shortcutHintShown')) {
            sessionStorage.setItem('shortcutHintShown', '1');
            const hint = document.createElement('div');
            hint.style.cssText = 'position:fixed;bottom:16px;left:50%;transform:translateX(-50%);' +
                'background:rgba(0,0,0,.75);color:#fff;padding:6px 16px;border-radius:20px;' +
                'font-size:.8rem;z-index:9999;pointer-events:none;opacity:1;transition:opacity 1s';
            hint.textContent = Object.entries(shortcuts)
                .map(([k, u]) => k + ' → ' + (new URL(u)).pathname.replace(/.*\//, '').replace(/-/g,' '))
                .join('  |  ');
            document.body.appendChild(hint);
            setTimeout(() => { hint.style.opacity = '0'; }, 4000);
            setTimeout(() => { hint.remove(); }, 5000);
        }
    })();
    </script>

    <script>
    // Efface le zéro dans les champs numériques au focus, le restaure à la sortie si vide
    document.addEventListener('focusin', function(e) {
        const el = e.target;
        if (el.tagName === 'INPUT' && el.type === 'number' && parseFloat(el.value) === 0) {
            el.value = '';
        }
    });
    document.addEventListener('focusout', function(e) {
        const el = e.target;
        if (el.tagName === 'INPUT' && el.type === 'number' && el.value === '') {
            el.value = el.min !== '' ? el.min : '0';
        }
    });
    </script>
</body>
</html>
