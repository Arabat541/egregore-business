@extends('layouts.app')

@section('title', 'Rapport du Stock')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-boxes me-2"></i>Rapport du Stock
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Rapports</a></li>
                    <li class="breadcrumb-item active">Stock</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.reports.export', ['type' => 'stock']) }}" class="btn btn-outline-success">
                <i class="bi bi-download me-2"></i>Exporter CSV
            </a>
            <button class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer me-2"></i>Imprimer
            </button>
        </div>
    </div>

    <!-- Filtre Boutique -->
    @if(isset($shops) && $shops->count() > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Boutique</label>
                    <select name="shop_id" class="form-select">
                        <option value="">Toutes les boutiques</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" {{ (isset($shopId) && $shopId == $shop->id) ? 'selected' : '' }}>
                                {{ $shop->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter me-2"></i>Filtrer
                    </button>
                    <a href="{{ route('admin.reports.stock') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- KPIs Principaux -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-2 text-white-50">Valeur du Stock</h6>
                            <h3 class="mb-0">{{ number_format($totalStockValue, 0, ',', ' ') }} F</h3>
                            <small>Coût d'achat</small>
                        </div>
                        <i class="bi bi-safe fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-2 text-white-50">Valeur Vente</h6>
                            <h3 class="mb-0">{{ number_format($totalSellingValue, 0, ',', ' ') }} F</h3>
                            <small>Prix de vente</small>
                        </div>
                        <i class="bi bi-cash-stack fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-2 text-white-50">Marge Potentielle</h6>
                            <h3 class="mb-0">{{ number_format($potentialProfit, 0, ',', ' ') }} F</h3>
                            <small>{{ $totalStockValue > 0 ? round(($potentialProfit / $totalStockValue) * 100, 1) : 0 }}% de marge</small>
                        </div>
                        <i class="bi bi-graph-up-arrow fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <h4 class="mb-0 text-primary">{{ $totalProducts }}</h4>
                            <small class="text-muted">Total Produits</small>
                        </div>
                        <div class="col-6">
                            <h4 class="mb-0 text-success">{{ $activeProducts }}</h4>
                            <small class="text-muted">Actifs</small>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <h4 class="mb-0 text-danger">{{ $outOfStock }}</h4>
                            <small class="text-muted">Rupture</small>
                        </div>
                        <div class="col-6">
                            <h4 class="mb-0 text-warning">{{ $lowStock }}</h4>
                            <small class="text-muted">Stock Faible</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertes Stock -->
    @if($outOfStock > 0 || $lowStock > 0)
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-octagon fs-3 me-3"></i>
                <div class="flex-grow-1">
                    <h6 class="alert-heading mb-1">⚠️ Attention Stock Critique</h6>
                    <p class="mb-0">
                        <strong>{{ $outOfStock }}</strong> produit(s) en rupture de stock |
                        <strong>{{ $lowStock }}</strong> produit(s) avec stock faible
                    </p>
                </div>
                <a href="#productsToOrder" class="btn btn-danger">
                    <i class="bi bi-arrow-down me-2"></i>Voir la liste
                </a>
            </div>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <!-- Stock par catégorie -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-pie-chart me-2"></i>Répartition par Catégorie
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 250px; position: relative;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Valeur par catégorie -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart me-2"></i>Valeur par Catégorie
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Catégorie</th>
                                    <th class="text-center">Produits</th>
                                    <th class="text-center">Quantité</th>
                                    <th class="text-end">Valeur</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockByCategory as $cat)
                                    <tr>
                                        <td>
                                            <i class="bi bi-folder me-2 text-warning"></i>
                                            {{ $cat->category->name ?? 'Sans catégorie' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ $cat->count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $cat->total_qty }}</span>
                                        </td>
                                        <td class="text-end fw-bold">{{ number_format($cat->total_value, 0, ',', ' ') }} F</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <th>Total</th>
                                    <th class="text-center">{{ $stockByCategory->sum('count') }}</th>
                                    <th class="text-center">{{ $stockByCategory->sum('total_qty') }}</th>
                                    <th class="text-end">{{ number_format($stockByCategory->sum('total_value'), 0, ',', ' ') }} F</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Rotation du stock -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-arrow-repeat me-2"></i>Rotation du Stock (30 jours)
                    </h5>
                    <span class="badge bg-success">Best sellers</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>#</th>
                                    <th>Produit</th>
                                    <th class="text-center">Vendus</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-center">Rotation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockRotation as $index => $item)
                                    @php
                                        $rotation = $item->product && $item->product->quantity_in_stock > 0 
                                            ? round($item->sold_qty / ($item->product->quantity_in_stock + $item->sold_qty) * 100, 1)
                                            : 100;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <small>{{ $item->product->name ?? 'Produit supprimé' }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success">{{ $item->sold_qty }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ ($item->product->quantity_in_stock ?? 0) > 0 ? 'info' : 'danger' }}">
                                                {{ $item->product->quantity_in_stock ?? 0 }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="progress" style="height: 15px;">
                                                <div class="progress-bar bg-success" style="width: {{ $rotation }}%">
                                                    {{ $rotation }}%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Aucune vente sur 30 jours</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Produits les plus rentables -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-currency-dollar me-2"></i>Produits les Plus Rentables
                    </h5>
                    <span class="badge bg-warning text-dark">Marge élevée</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-end">Achat</th>
                                    <th class="text-end">Vente</th>
                                    <th class="text-end">Marge</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mostProfitable as $product)
                                    <tr>
                                        <td><small>{{ Str::limit($product->name, 25) }}</small></td>
                                        <td class="text-end text-muted">{{ number_format($product->purchase_price, 0, ',', ' ') }}</td>
                                        <td class="text-end">{{ number_format($product->selling_price, 0, ',', ' ') }}</td>
                                        <td class="text-end text-success fw-bold">{{ number_format($product->profit_margin, 0, ',', ' ') }}</td>
                                        <td class="text-end">
                                            <span class="badge bg-{{ $product->profit_percentage >= 50 ? 'success' : ($product->profit_percentage >= 25 ? 'warning' : 'secondary') }}">
                                                {{ round($product->profit_percentage) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Aucun produit en stock</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Produits à commander -->
    <div class="row g-4 mb-4" id="productsToOrder">
        <div class="col-12">
            <div class="card border-0 shadow-sm border-top border-danger border-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>Produits à Commander (Stock Faible/Rupture)
                    </h5>
                    <span class="badge bg-danger">{{ $productsToOrder->count() }} produits</span>
                </div>
                <div class="card-body">
                    @if($productsToOrder->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>SKU</th>
                                        <th>Produit</th>
                                        <th>Catégorie</th>
                                        <th class="text-center">Stock Actuel</th>
                                        <th class="text-center">Seuil Alerte</th>
                                        <th class="text-center">État</th>
                                        <th class="text-end">Prix Achat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($productsToOrder as $product)
                                        <tr class="{{ $product->quantity_in_stock == 0 ? 'table-danger' : 'table-warning' }}">
                                            <td><code>{{ $product->sku }}</code></td>
                                            <td>
                                                <strong>{{ $product->name }}</strong>
                                                @if($product->barcode)
                                                    <br><small class="text-muted">{{ $product->barcode }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $product->category->name ?? 'N/A' }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $product->quantity_in_stock == 0 ? 'danger' : 'warning' }} fs-6">
                                                    {{ $product->quantity_in_stock }}
                                                </span>
                                            </td>
                                            <td class="text-center">{{ $product->stock_alert_threshold }}</td>
                                            <td class="text-center">
                                                @if($product->quantity_in_stock == 0)
                                                    <span class="badge bg-danger">RUPTURE</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">STOCK FAIBLE</span>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ number_format($product->purchase_price, 0, ',', ' ') }} F</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                            <p class="mt-3">Tous les produits ont un stock suffisant !</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Produits dormants -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-moon me-2"></i>Produits Dormants (Sans vente depuis 30 jours)
                    </h5>
                    <span class="badge bg-secondary">{{ $dormantProducts->count() }} produits</span>
                </div>
                <div class="card-body">
                    @if($dormantProducts->count() > 0)
                        <div class="alert alert-info border-0 mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            Ces produits n'ont pas été vendus depuis 30 jours. Envisagez des promotions ou une révision des prix.
                        </div>
                        <div class="row">
                            @foreach($dormantProducts->take(12) as $product)
                                <div class="col-md-4 col-lg-3 mb-3">
                                    <div class="card border">
                                        <div class="card-body py-2">
                                            <h6 class="card-title mb-1">{{ Str::limit($product->name, 25) }}</h6>
                                            <p class="text-muted mb-0 small">
                                                Stock: <span class="badge bg-info">{{ $product->quantity_in_stock }}</span>
                                                | {{ number_format($product->selling_price, 0, ',', ' ') }} F
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($dormantProducts->count() > 12)
                            <p class="text-center text-muted">... et {{ $dormantProducts->count() - 12 }} autres produits</p>
                        @endif
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-emoji-smile fs-1 text-success"></i>
                            <p class="mt-2">Tous les produits se vendent bien !</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Mouvements récents -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>Derniers Mouvements de Stock
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Produit</th>
                                    <th>Type</th>
                                    <th class="text-center">Quantité</th>
                                    <th>Motif</th>
                                    <th>Par</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentMovements as $movement)
                                    <tr>
                                        <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $movement->product->name ?? 'Produit supprimé' }}</td>
                                        <td>
                                            @if($movement->type == 'in')
                                                <span class="badge bg-success">Entrée</span>
                                            @elseif($movement->type == 'out')
                                                <span class="badge bg-danger">Sortie</span>
                                            @else
                                                <span class="badge bg-warning">Ajustement</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <strong class="{{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                            </strong>
                                        </td>
                                        <td>{{ $movement->reason ?? '-' }}</td>
                                        <td>{{ $movement->user->name ?? 'Système' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Aucun mouvement récent</td>
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
document.addEventListener('DOMContentLoaded', function() {
    const categoryData = @json($stockByCategory);
    
    const colors = [
        '#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0', 
        '#6f42c1', '#fd7e14', '#20c997', '#6610f2', '#d63384'
    ];

    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: categoryData.map(c => c.category?.name || 'Sans catégorie'),
            datasets: [{
                data: categoryData.map(c => c.total_value),
                backgroundColor: colors.slice(0, categoryData.length)
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed.toLocaleString('fr-FR') + ' FCFA';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush

<style>
@media print {
    .btn, .form-control, .btn-group, nav, .alert {
        display: none !important;
    }
    .card {
        break-inside: avoid;
    }
}
</style>
@endsection
