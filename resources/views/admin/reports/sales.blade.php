@extends('layouts.app')

@section('title', 'Rapport des Ventes')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-cart-check me-2"></i>Rapport des Ventes
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Rapports</a></li>
                    <li class="breadcrumb-item active">Ventes</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.reports.export', ['type' => 'sales', 'start_date' => $startDate, 'end_date' => $endDate, 'shop_id' => $shopId, 'customer_id' => $customerId, 'category_id' => $categoryId, 'product_id' => $productId]) }}"
               class="btn btn-outline-success">
                <i class="bi bi-download me-2"></i>Exporter CSV
            </a>
            <a href="{{ route('admin.reports.sales.products-pdf', ['start_date' => $startDate, 'end_date' => $endDate, 'shop_id' => $shopId, 'customer_id' => $customerId, 'category_id' => $categoryId, 'product_id' => $productId]) }}"
               class="btn btn-outline-danger" target="_blank">
                <i class="bi bi-file-earmark-pdf me-2"></i>PDF produits
            </a>
            <a href="{{ route('admin.reports.sales.pdf', ['start_date' => $startDate, 'end_date' => $endDate, 'shop_id' => $shopId]) }}"
               class="btn btn-danger" target="_blank">
                <i class="bi bi-file-earmark-pdf me-2"></i>PDF rapport
            </a>
            <button class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer me-2"></i>Imprimer
            </button>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end" id="reportFilterForm">
                {{-- Ligne 1 : boutique + période --}}
                @if(isset($shops) && $shops->count() > 0)
                <div class="col-md-2">
                    <label class="form-label">Boutique</label>
                    <select name="shop_id" class="form-select">
                        <option value="">Toutes</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" {{ (isset($shopId) && $shopId == $shop->id) ? 'selected' : '' }}>
                                {{ $shop->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label">Date début</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-1"></i>Filtrer
                    </button>
                    <a href="{{ route('admin.reports.sales') }}" class="btn btn-outline-secondary" title="Réinitialiser">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
                <div class="col-md-4 text-end d-flex align-items-end justify-content-end">
                    <div class="btn-group btn-group-sm">
                        <a href="?start_date={{ now()->startOfDay()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}" class="btn btn-outline-primary">Aujourd'hui</a>
                        <a href="?start_date={{ now()->startOfWeek()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}" class="btn btn-outline-primary">Semaine</a>
                        <a href="?start_date={{ now()->startOfMonth()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}" class="btn btn-outline-primary">Mois</a>
                        <a href="?start_date={{ now()->startOfYear()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}" class="btn btn-outline-primary">Année</a>
                    </div>
                </div>

                {{-- Ligne 2 : client · catégorie · produit --}}
                <div class="col-12"><hr class="my-1"></div>

                <div class="col-md-4">
                    <label class="form-label">
                        <i class="bi bi-person me-1 text-muted"></i>Client / Revendeur
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="customerSearch"
                               placeholder="Rechercher…" autocomplete="off"
                               oninput="filterSelect('customerSelect', this.value)">
                        <select name="client_filter" id="customerSelect" class="form-select" style="max-width:60%">
                            <option value="">Tous</option>
                            @if($customers->isNotEmpty())
                            <optgroup label="— Particuliers">
                                @foreach($customers as $c)
                                    <option value="c_{{ $c->id }}"
                                        data-search="{{ strtolower($c->first_name . ' ' . $c->last_name . ' ' . $c->phone) }}"
                                        {{ $clientFilter === 'c_'.$c->id ? 'selected' : '' }}>
                                        {{ $c->first_name }} {{ $c->last_name }}
                                        @if($c->phone) — {{ $c->phone }} @endif
                                    </option>
                                @endforeach
                            </optgroup>
                            @endif
                            @if($resellers->isNotEmpty())
                            <optgroup label="— Revendeurs">
                                @foreach($resellers as $r)
                                    <option value="r_{{ $r->id }}"
                                        data-search="{{ strtolower($r->company_name . ' ' . $r->contact_name . ' ' . $r->phone) }}"
                                        {{ $clientFilter === 'r_'.$r->id ? 'selected' : '' }}>
                                        🏢 {{ $r->company_name }}
                                        @if($r->phone) — {{ $r->phone }} @endif
                                    </option>
                                @endforeach
                            </optgroup>
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        <i class="bi bi-tag me-1 text-muted"></i>Catégorie
                    </label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">Toutes les catégories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        <i class="bi bi-box-seam me-1 text-muted"></i>Produit
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="productSearch"
                               placeholder="Rechercher…" autocomplete="off"
                               oninput="filterSelect('productSelect', this.value)">
                        <select name="product_id" id="productSelect" class="form-select" style="max-width:60%">
                            <option value="">Tous les produits</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}"
                                    data-search="{{ strtolower($p->name . ' ' . $p->sku) }}"
                                    {{ $productId == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }}
                                    @if($p->sku) ({{ $p->sku }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>

            {{-- Badges des filtres actifs --}}
            @if($clientFilter || $categoryId || $productId)
            <div class="mt-2 d-flex gap-2 flex-wrap">
                <span class="text-muted small">Filtres actifs :</span>
                @if($customerId)
                    @php $c = $customers->find($customerId) @endphp
                    <span class="badge bg-primary">
                        <i class="bi bi-person me-1"></i>{{ $c ? $c->first_name.' '.$c->last_name : $customerId }}
                    </span>
                @elseif($resellerId)
                    @php $r = $resellers->find($resellerId) @endphp
                    <span class="badge bg-warning text-dark">
                        <i class="bi bi-shop me-1"></i>{{ $r ? $r->company_name : $resellerId }}
                    </span>
                @endif
                @if($categoryId)
                    @php $cat = $categories->find($categoryId) @endphp
                    <span class="badge bg-secondary">
                        <i class="bi bi-tag me-1"></i>{{ $cat ? $cat->name : $categoryId }}
                    </span>
                @endif
                @if($productId)
                    @php $prod = $products->find($productId) @endphp
                    <span class="badge bg-info text-dark">
                        <i class="bi bi-box-seam me-1"></i>{{ $prod ? $prod->name : $productId }}
                    </span>
                @endif
            </div>
            @endif
        </div>
    </div>

    {{-- ── Bandeau comparaison N-1 ───────────────────────────────────── --}}
    @if(isset($n1Revenue))
    <div class="alert alert-light border d-flex flex-wrap gap-4 align-items-center py-2 mb-3">
        <span class="text-muted small fw-semibold"><i class="bi bi-calendar2-minus me-1"></i>Même période N-1</span>
        <span>
            CA :
            <strong>{{ number_format($n1Revenue, 0, ',', ' ') }} F</strong>
            @if($n1Growth !== null)
                <span class="ms-1 {{ $n1Growth >= 0 ? 'text-success' : 'text-danger' }}">
                    <i class="bi bi-arrow-{{ $n1Growth >= 0 ? 'up' : 'down' }}-short"></i>{{ abs($n1Growth) }}%
                </span>
            @else
                <span class="text-muted ms-1">(pas de données)</span>
            @endif
        </span>
        <span class="text-muted">|</span>
        <span>Ventes : <strong>{{ $n1Sales }}</strong></span>
        <span class="text-muted">|</span>
        <span class="text-muted small">
            Panier N-1 : {{ $n1Sales > 0 ? number_format($n1Revenue / $n1Sales, 0, ',', ' ') : '–' }} F
        </span>
    </div>
    @endif

    <!-- KPIs Principaux -->
    <div class="row g-4 mb-4">
        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Chiffre d'Affaires</h6>
                            <h3 class="mb-0 text-primary">{{ number_format($totalRevenue, 0, ',', ' ') }} F</h3>
                            <small class="{{ $revenueGrowth >= 0 ? 'text-success' : 'text-danger' }}">
                                <i class="bi bi-{{ $revenueGrowth >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                                {{ abs($revenueGrowth) }}% vs période précédente
                            </small>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle align-self-start">
                            <i class="bi bi-currency-exchange fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Nombre de Ventes</h6>
                            <h3 class="mb-0 text-success">{{ $totalSales }}</h3>
                            <small class="text-muted">
                                Panier moyen: {{ $totalSales > 0 ? number_format($totalRevenue / $totalSales, 0, ',', ' ') : 0 }} F
                            </small>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle align-self-start">
                            <i class="bi bi-receipt fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Encaissé</h6>
                            <h3 class="mb-0 text-info">{{ number_format($totalPaid, 0, ',', ' ') }} F</h3>
                            <small class="text-muted">
                                {{ $totalRevenue > 0 ? round(($totalPaid / $totalRevenue) * 100, 1) : 0 }}% du CA
                            </small>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle align-self-start">
                            <i class="bi bi-cash-stack fs-4 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">À Crédit</h6>
                            <h3 class="mb-0 text-danger">{{ number_format($totalCredit, 0, ',', ' ') }} F</h3>
                            <small class="text-muted">
                                {{ $totalRevenue > 0 ? round(($totalCredit / $totalRevenue) * 100, 1) : 0 }}% du CA
                            </small>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle align-self-start">
                            <i class="bi bi-credit-card fs-4 text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Remises accordées</h6>
                            <h3 class="mb-0 text-warning">{{ number_format($totalDiscount, 0, ',', ' ') }} F</h3>
                            <small class="text-muted">
                                {{ $totalRevenue > 0 ? round(($totalDiscount / ($totalRevenue + $totalDiscount)) * 100, 1) : 0 }}% du brut
                            </small>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle align-self-start">
                            <i class="bi bi-tag fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="row g-4 mb-4">
        <!-- Évolution des ventes -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Évolution du Chiffre d'Affaires
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 300px; position: relative;">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Répartition par type de client -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-pie-chart me-2"></i>Répartition par Type
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 200px; position: relative;">
                        <canvas id="clientTypeChart"></canvas>
                    </div>
                    <div class="mt-3">
                        @foreach($salesByClientType as $type)
                            <div class="d-flex justify-content-between mb-2">
                                <span>
                                    <i class="bi bi-circle-fill text-{{ $type->client_type == 'customer' ? 'primary' : 'warning' }} me-2" style="font-size: 8px;"></i>
                                    {{ $type->client_type == 'customer' ? 'Particuliers' : 'Revendeurs' }}
                                </span>
                                <strong>{{ number_format($type->total, 0, ',', ' ') }} F</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Ventes par mode de paiement -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-credit-card-2-back me-2"></i>Par Mode de Paiement
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 200px; position: relative;">
                        <canvas id="paymentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance des caissières -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-person-badge me-2"></i>Performance par Vendeur
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Vendeur</th>
                                    <th class="text-center">Ventes</th>
                                    <th class="text-end">CA</th>
                                    <th class="text-end">Panier Moyen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salesByUser as $user)
                                    <tr>
                                        <td>
                                            <i class="bi bi-person-circle me-2 text-muted"></i>
                                            {{ $user->user->name ?? 'N/A' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $user->count }}</span>
                                        </td>
                                        <td class="text-end fw-bold">{{ number_format($user->total, 0, ',', ' ') }} F</td>
                                        <td class="text-end">{{ number_format($user->total / $user->count, 0, ',', ' ') }} F</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Aucune vente sur cette période</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Top 10 Produits -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-trophy me-2"></i>Top 10 Produits
                    </h5>
                    <span class="badge bg-success">Best sellers</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Produit</th>
                                    <th class="text-center">Qté</th>
                                    <th class="text-end">CA</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProducts as $index => $item)
                                    <tr>
                                        <td>
                                            @if($index < 3)
                                                <span class="badge bg-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'danger') }}">
                                                    {{ $index + 1 }}
                                                </span>
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </td>
                                        <td>{{ $item->product->name ?? 'Produit supprimé' }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $item->total_qty }}</span>
                                        </td>
                                        <td class="text-end fw-bold">{{ number_format($item->total_revenue, 0, ',', ' ') }} F</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Aucune donnée</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 10 Clients -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-star me-2"></i>Top 10 Clients Particuliers
                    </h5>
                    <span class="badge bg-primary">VIP</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th class="text-center">Achats</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topCustomers as $index => $sale)
                                    <tr>
                                        <td>
                                            @if($index < 3)
                                                <span class="badge bg-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'danger') }}">
                                                    {{ $index + 1 }}
                                                </span>
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </td>
                                        <td>{{ $sale->customer->full_name ?? 'Client supprimé' }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $sale->count }}</span>
                                        </td>
                                        <td class="text-end fw-bold">{{ number_format($sale->total, 0, ',', ' ') }} F</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Aucune donnée</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Revendeurs -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-shop me-2"></i>Top 10 Revendeurs
                    </h5>
                    <span class="badge bg-warning text-dark">B2B</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Entreprise</th>
                                    <th>Contact</th>
                                    <th class="text-center">Commandes</th>
                                    <th class="text-end">CA Total</th>
                                    <th class="text-end">Panier Moyen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topResellers as $index => $sale)
                                    <tr>
                                        <td>
                                            @if($index < 3)
                                                <span class="badge bg-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'danger') }}">
                                                    {{ $index + 1 }}
                                                </span>
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </td>
                                        <td><strong>{{ $sale->reseller->company_name ?? 'Revendeur supprimé' }}</strong></td>
                                        <td>{{ $sale->reseller->contact_name ?? 'N/A' }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $sale->count }}</span>
                                        </td>
                                        <td class="text-end fw-bold text-success">{{ number_format($sale->total, 0, ',', ' ') }} F</td>
                                        <td class="text-end">{{ number_format($sale->total / $sale->count, 0, ',', ' ') }} F</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Aucune vente revendeur sur cette période</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Filtre les options d'un <select> selon la saisie dans le champ de recherche
function filterSelect(selectId, query) {
    const q = query.toLowerCase().trim();
    const select = document.getElementById(selectId);
    Array.from(select.options).forEach(opt => {
        if (!opt.value) return; // garder "Tous"
        const text = (opt.dataset.search || opt.text).toLowerCase();
        opt.hidden = q.length > 0 && !text.includes(q);
    });
    // Si la valeur sélectionnée est maintenant cachée, réinitialiser
    const selected = select.options[select.selectedIndex];
    if (selected && selected.hidden) select.value = '';
}

// Pré-remplir les champs de recherche si un filtre est actif
document.addEventListener('DOMContentLoaded', function() {
    const customerSel = document.getElementById('customerSelect');
    const productSel  = document.getElementById('productSelect');
    if (customerSel && customerSel.value) {
        document.getElementById('customerSearch').value =
            customerSel.options[customerSel.selectedIndex].text
                .replace(/^🏢\s*/, '').split('—')[0].trim();
    }
    if (productSel && productSel.value) {
        document.getElementById('productSearch').value =
            productSel.options[productSel.selectedIndex].text.split('(')[0].trim();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Données pour le graphique d'évolution
    const salesData = @json($salesByDay);
    
    // Graphique évolution des ventes
    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: salesData.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
            }),
            datasets: [{
                label: 'Chiffre d\'Affaires (FCFA)',
                data: salesData.map(d => d.total),
                borderColor: 'rgb(13, 110, 253)',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.3,
                fill: true
            }, {
                label: 'Nombre de ventes',
                data: salesData.map(d => d.count),
                borderColor: 'rgb(25, 135, 84)',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.3,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    ticks: {
                        callback: value => value.toLocaleString('fr-FR') + ' F'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: { drawOnChartArea: false }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                return context.parsed.y.toLocaleString('fr-FR') + ' FCFA';
                            }
                            return context.parsed.y + ' ventes';
                        }
                    }
                }
            }
        }
    });

    // Graphique répartition par type de client
    const clientData = @json($salesByClientType);
    new Chart(document.getElementById('clientTypeChart'), {
        type: 'doughnut',
        data: {
            labels: clientData.map(d => d.client_type === 'customer' ? 'Particuliers' : 'Revendeurs'),
            datasets: [{
                data: clientData.map(d => d.total),
                backgroundColor: ['rgb(13, 110, 253)', 'rgb(255, 193, 7)'],
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Graphique par mode de paiement
    const paymentData = @json($salesByPayment);
    const colors = {
        'cash': '#198754',
        'Espèces': '#198754',
        'orange_money': '#ff7900',
        'Orange Money': '#ff7900',
        'wave': '#1dc3e3',
        'Wave': '#1dc3e3',
        'mtn_money': '#ffcc00',
        'MTN Money': '#ffcc00',
        'moov_money': '#0066b3',
        'Moov Money': '#0066b3',
        'card': '#6f42c1',
        'Carte Bancaire': '#6f42c1',
        'bank_transfer': '#0d6efd',
        'Virement Bancaire': '#0d6efd'
    };
    
    new Chart(document.getElementById('paymentChart'), {
        type: 'bar',
        data: {
            labels: paymentData.map(d => d.payment_method),
            datasets: [{
                label: 'Montant (FCFA)',
                data: paymentData.map(d => d.total),
                backgroundColor: paymentData.map(d => colors[d.payment_method] || '#6c757d'),
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => value.toLocaleString('fr-FR') + ' F'
                    }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>
@endpush

<style>
@media print {
    .btn, .form-control, .btn-group, nav {
        display: none !important;
    }
    .card {
        break-inside: avoid;
    }
}
</style>
@endsection
