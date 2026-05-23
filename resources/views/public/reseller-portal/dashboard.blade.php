<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace — {{ $reseller->company_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', sans-serif;
        }
        .top-nav {
            background: linear-gradient(135deg, #1a1a2e, #0f3460);
            color: white;
            padding: 0.75rem 0;
        }
        .top-nav .brand { font-weight: 700; font-size: 1.1rem; }
        .top-nav .reseller-name { font-size: 0.9rem; opacity: 0.85; }
        .kpi-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            padding: 1.5rem;
            height: 100%;
        }
        .kpi-card .kpi-icon {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .kpi-card .kpi-value { font-size: 1.6rem; font-weight: 700; line-height: 1; }
        .kpi-card .kpi-label { font-size: 0.82rem; color: #6c757d; margin-bottom: 0.2rem; }
        .section-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .section-card .card-header {
            background: white;
            border-bottom: 2px solid #f0f2f5;
            font-weight: 600;
            padding: 1rem 1.25rem;
        }
        .movement-table th {
            background: #1a1a2e;
            color: white;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 0.6rem 0.75rem;
        }
        .movement-table td {
            font-size: 0.85rem;
            padding: 0.55rem 0.75rem;
            vertical-align: middle;
        }
        .row-sale { background: #fff8e1; }
        .row-payment { background: #e8f5e9; }
        .row-product { background: #fffde7; font-size: 0.8rem; color: #555; }
        .row-product td { padding: 0.3rem 0.75rem; border-bottom: none; }
        .row-opening, .row-closing { background: #e9ecef; font-weight: 600; }
        .badge-achat { background: #ff8f00; color: white; }
        .badge-paiement { background: #2e7d32; color: white; }
        .credit-bar .progress { height: 12px; border-radius: 6px; }
        .filter-form { background: white; border-radius: 15px; padding: 1.25rem; margin-bottom: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        @media (max-width: 576px) {
            .kpi-value { font-size: 1.3rem; }
            .movement-table { font-size: 0.78rem; }
        }
    </style>
</head>
<body>

<!-- Barre de navigation -->
<nav class="top-nav mb-4">
    <div class="container-lg d-flex justify-content-between align-items-center">
        <div>
            <div class="brand"><i class="bi bi-shop me-2"></i>Espace Réparateur</div>
            <div class="reseller-name"><i class="bi bi-person-circle me-1"></i>{{ $reseller->company_name }}</div>
        </div>
        <a href="{{ route('reseller-portal.logout') }}" class="btn btn-sm btn-outline-light">
            <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
        </a>
    </div>
</nav>

<div class="container-lg pb-5">

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background:#fff3e0;">
                    <i class="bi bi-currency-exchange text-warning"></i>
                </div>
                <div>
                    <div class="kpi-label">Créance actuelle</div>
                    <div class="kpi-value text-{{ $reseller->current_debt > 0 ? 'danger' : 'success' }}">
                        {{ number_format($reseller->current_debt, 0, ',', ' ') }}<small class="fs-6"> F</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background:#e8f5e9;">
                    <i class="bi bi-check-circle text-success"></i>
                </div>
                <div>
                    <div class="kpi-label">Crédit disponible</div>
                    <div class="kpi-value text-success">
                        {{ number_format($reseller->available_credit, 0, ',', ' ') }}<small class="fs-6"> F</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background:#e3f2fd;">
                    <i class="bi bi-cart-check text-primary"></i>
                </div>
                <div>
                    <div class="kpi-label">Achats (période)</div>
                    <div class="kpi-value text-primary">
                        {{ number_format($summary['total_purchases'], 0, ',', ' ') }}<small class="fs-6"> F</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background:#fce4ec;">
                    <i class="bi bi-cash-stack text-danger"></i>
                </div>
                <div>
                    <div class="kpi-label">Paiements (période)</div>
                    <div class="kpi-value text-danger">
                        {{ number_format($summary['total_payments'], 0, ',', ' ') }}<small class="fs-6"> F</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barre de crédit -->
    @if($reseller->credit_limit > 0)
    <div class="section-card mb-4 p-3 credit-bar">
        <div class="d-flex justify-content-between mb-1">
            <span class="small fw-semibold">Utilisation du crédit</span>
            <span class="small text-muted">
                {{ number_format($reseller->current_debt, 0, ',', ' ') }} F
                / {{ number_format($reseller->credit_limit, 0, ',', ' ') }} F
            </span>
        </div>
        @php $pct = min(100, $reseller->credit_limit > 0 ? ($reseller->current_debt / $reseller->credit_limit) * 100 : 0); @endphp
        <div class="progress">
            <div class="progress-bar bg-{{ $pct >= 90 ? 'danger' : ($pct >= 60 ? 'warning' : 'success') }}"
                 style="width: {{ $pct }}%"></div>
        </div>
        <div class="d-flex justify-content-between mt-1">
            <small class="text-muted">0 F</small>
            <small class="text-muted">Plafond : {{ number_format($reseller->credit_limit, 0, ',', ' ') }} F</small>
        </div>
    </div>
    @endif

    <!-- Filtre de période -->
    <form method="GET" class="filter-form">
        <div class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label small fw-semibold mb-1">Date début</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div class="col-sm-4">
                <label class="form-label small fw-semibold mb-1">Date fin</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <div class="col-sm-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-fill">
                    <i class="bi bi-filter me-1"></i>Filtrer
                </button>
                <a href="{{ route('reseller-portal.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </div>
        <div class="mt-2 d-flex gap-1 flex-wrap">
            <a href="?start_date={{ now()->startOfMonth()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}" class="btn btn-xs btn-outline-secondary" style="font-size:.75rem;padding:.2rem .5rem;">Ce mois</a>
            <a href="?start_date={{ now()->subMonths(3)->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}" class="btn btn-xs btn-outline-secondary" style="font-size:.75rem;padding:.2rem .5rem;">3 mois</a>
            <a href="?start_date={{ now()->startOfYear()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}" class="btn btn-xs btn-outline-secondary" style="font-size:.75rem;padding:.2rem .5rem;">Cette année</a>
        </div>
    </form>

    <!-- Tableau des mouvements -->
    <div class="section-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-ul me-2"></i>Relevé de compte</span>
            <span class="small text-muted">
                {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} →
                {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered mb-0 movement-table">
                <thead>
                    <tr>
                        <th style="width:95px">Date</th>
                        <th style="width:85px">Type</th>
                        <th>Référence / Produit</th>
                        <th class="text-center" style="width:55px">Qté</th>
                        <th class="text-end" style="width:110px">Prix unit.</th>
                        <th class="text-end" style="width:110px">Achat</th>
                        <th class="text-end" style="width:110px">Paiement</th>
                        <th class="text-end" style="width:110px">Solde</th>
                    </tr>
                </thead>
                <tbody>
                    @php $runningBalance = $openingBalance; @endphp

                    <!-- Solde d'ouverture -->
                    <tr class="row-opening">
                        <td>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</td>
                        <td><span class="badge bg-secondary">Ouverture</span></td>
                        <td>Solde d'ouverture</td>
                        <td></td><td></td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end fw-bold">{{ number_format($openingBalance, 0, ',', ' ') }} F</td>
                    </tr>

                    @forelse($movements as $movement)
                        @php
                            $isSale = $movement['type'] === 'sale';
                            if ($isSale) {
                                $runningBalance += $movement['debit'];
                            } else {
                                $runningBalance -= $movement['credit'];
                            }
                            $hasProducts = $isSale && !empty($movement['products']);
                        @endphp

                        <!-- Ligne principale -->
                        <tr class="{{ $isSale ? 'row-sale' : 'row-payment' }} fw-semibold">
                            <td>{{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }}</td>
                            <td>
                                @if($isSale)
                                    <span class="badge badge-achat">Achat</span>
                                @else
                                    <span class="badge badge-paiement">Paiement</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold">{{ $movement['reference'] }}</span>
                                <span class="fw-normal text-muted ms-1">— {{ $movement['description'] }}</span>
                            </td>
                            <td></td>
                            <td></td>
                            <td class="text-end text-danger">
                                @if($movement['debit'] > 0)
                                    {{ number_format($movement['debit'], 0, ',', ' ') }} F
                                @else —
                                @endif
                            </td>
                            <td class="text-end text-success">
                                @if($movement['credit'] > 0)
                                    {{ number_format($movement['credit'], 0, ',', ' ') }} F
                                @else —
                                @endif
                            </td>
                            <td class="text-end {{ $runningBalance > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($runningBalance, 0, ',', ' ') }} F
                            </td>
                        </tr>

                        <!-- Sous-lignes produits -->
                        @if($hasProducts)
                            @foreach($movement['products'] as $product)
                            <tr class="row-product">
                                <td class="text-muted">{{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }}</td>
                                <td><i class="bi bi-box-seam text-muted"></i></td>
                                <td class="ps-4">
                                    └ {{ $product['name'] }}
                                    @if(isset($product['discount']) && $product['discount'] > 0)
                                        <small class="text-warning ms-1">(-{{ number_format($product['discount'], 0, ',', ' ') }} F)</small>
                                    @endif
                                </td>
                                <td class="text-center">{{ $product['quantity'] }}</td>
                                <td class="text-end">{{ number_format($product['unit_price'], 0, ',', ' ') }} F</td>
                                <td class="text-end">{{ number_format($product['total'], 0, ',', ' ') }} F</td>
                                <td></td>
                                <td></td>
                            </tr>
                            @endforeach
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Aucun mouvement sur cette période
                            </td>
                        </tr>
                    @endforelse

                    <!-- Clôture -->
                    <tr class="row-closing">
                        <td>{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</td>
                        <td><span class="badge bg-dark">Clôture</span></td>
                        <td>Solde de clôture</td>
                        <td></td><td></td>
                        <td class="text-end">{{ number_format($summary['total_purchases'], 0, ',', ' ') }} F</td>
                        <td class="text-end">{{ number_format($summary['total_payments'], 0, ',', ' ') }} F</td>
                        <td class="text-end {{ $runningBalance > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($runningBalance, 0, ',', ' ') }} F
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Info compte -->
    <div class="row g-3 mt-1">
        <div class="col-md-6">
            <div class="section-card p-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-person-badge me-2"></i>Informations du compte</h6>
                <table class="table table-sm table-borderless mb-0" style="font-size:.88rem;">
                    <tr><td class="text-muted">Société</td><td class="fw-semibold">{{ $reseller->company_name }}</td></tr>
                    <tr><td class="text-muted">Contact</td><td>{{ $reseller->contact_name }}</td></tr>
                    <tr><td class="text-muted">Téléphone</td><td>{{ $reseller->phone }}</td></tr>
                    @if($reseller->email)
                    <tr><td class="text-muted">Email</td><td>{{ $reseller->email }}</td></tr>
                    @endif
                    @if($reseller->loyalty_tier !== 'Nouveau')
                    <tr>
                        <td class="text-muted">Palier fidélité</td>
                        <td>
                            <span class="badge bg-{{ $reseller->loyalty_tier_color }} {{ in_array($reseller->loyalty_tier, ['Standard','Nouveau']) ? 'text-dark' : '' }}">
                                {{ $reseller->loyalty_tier }}
                            </span>
                            @if($reseller->loyalty_bonus_rate > 0)
                                <small class="text-muted ms-1">({{ $reseller->loyalty_bonus_rate }}% bonus)</small>
                            @endif
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <div class="section-card p-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-graph-up-arrow me-2"></i>Résumé de la période</h6>
                <table class="table table-sm table-borderless mb-0" style="font-size:.88rem;">
                    <tr>
                        <td class="text-muted">Total achats</td>
                        <td class="text-end fw-semibold text-danger">{{ number_format($summary['total_purchases'], 0, ',', ' ') }} F</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total paiements</td>
                        <td class="text-end fw-semibold text-success">{{ number_format($summary['total_payments'], 0, ',', ' ') }} F</td>
                    </tr>
                    @if($summary['total_discount'] > 0)
                    <tr>
                        <td class="text-muted">Remises obtenues</td>
                        <td class="text-end fw-semibold text-info">{{ number_format($summary['total_discount'], 0, ',', ' ') }} F</td>
                    </tr>
                    @endif
                    <tr class="border-top">
                        <td class="fw-bold">Créance période</td>
                        <td class="text-end fw-bold {{ $summary['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($summary['balance'], 0, ',', ' ') }} F
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <p class="text-center text-muted small mt-4">
        <i class="bi bi-lock me-1"></i>
        Données confidentielles — Accès réservé au titulaire du compte
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
