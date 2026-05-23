@extends('layouts.app')

@section('title', 'Comparaison des Prix Fournisseurs')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-bar-chart me-2"></i>Comparaison des Prix Fournisseurs
            </h1>
            <p class="text-muted mb-0">Trouvez le fournisseur le moins cher pour chaque produit</p>
        </div>
        <div>
            <a href="{{ route('admin.suppliers.low-stock') }}" class="btn btn-outline-secondary">
                <i class="bi bi-cart-plus me-1"></i>Commandes
            </a>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Boutique</label>
                    <select name="shop_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Toutes les boutiques</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                                {{ $shop->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filtrer par fournisseur</label>
                    <select name="supplier_filter" class="form-select" onchange="filterBySupplier(this.value)">
                        <option value="">Tous</option>
                        <option value="with_prices">Avec prix enregistrés</option>
                        <option value="without_prices">Sans prix enregistrés</option>
                        <option value="cheapest">Montrer le moins cher uniquement</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- Statistiques --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h2 class="mb-0">{{ $products->where('quantity_in_stock', 0)->count() }}</h2>
                    <small>Rupture de stock</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h2 class="mb-0">{{ $products->where('quantity_in_stock', '>', 0)->count() }}</h2>
                    <small>Stock faible</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h2 class="mb-0">{{ $products->filter(fn($p) => $p->cheapest_supplier)->count() }}</h2>
                    <small>Avec prix fournisseur</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h2 class="mb-0">{{ $products->filter(fn($p) => !$p->cheapest_supplier)->count() }}</h2>
                    <small>Sans prix fournisseur</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau des produits --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Produits à commander</h5>
            <span class="badge bg-primary">{{ $products->count() }} produits</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produit</th>
                            <th class="text-center">Stock</th>
                            <th>Fournisseur le moins cher</th>
                            <th class="text-end">Prix unitaire</th>
                            <th>Autres fournisseurs</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ $product->name }}</strong>
                                        @if($product->sku)
                                            <small class="text-muted d-block">SKU: {{ $product->sku }}</small>
                                        @endif
                                        @if($product->category)
                                            <span class="badge bg-light text-dark">{{ $product->category->name }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($product->quantity_in_stock == 0)
                                        <span class="badge bg-danger">Rupture</span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            {{ $product->quantity_in_stock }}
                                        </span>
                                    @endif
                                    <small class="text-muted d-block">Min: {{ $product->stock_alert_threshold }}</small>
                                </td>
                                <td>
                                    @if($product->cheapest_supplier)
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-trophy-fill text-warning me-2"></i>
                                            <div>
                                                <strong class="text-success">{{ $product->cheapest_supplier->supplier->company_name }}</strong>
                                                @if($product->cheapest_supplier->supplier->whatsapp)
                                                    <a href="https://wa.me/{{ $product->cheapest_supplier->supplier->whatsapp }}" 
                                                       target="_blank" class="ms-1 text-success">
                                                        <i class="bi bi-whatsapp"></i>
                                                    </a>
                                                @endif
                                                @if($product->cheapest_supplier->has_decreased)
                                                    <i class="bi bi-arrow-down-circle-fill text-success ms-1" 
                                                       title="Prix en baisse"></i>
                                                @elseif($product->cheapest_supplier->has_increased)
                                                    <i class="bi bi-arrow-up-circle-fill text-danger ms-1" 
                                                       title="Prix en hausse"></i>
                                                @endif
                                                @if($product->cheapest_supplier->price_updated_at)
                                                    <small class="text-muted d-block">
                                                        Mis à jour {{ $product->cheapest_supplier->price_updated_at->diffForHumans() }}
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted fst-italic">
                                            <i class="bi bi-question-circle me-1"></i>Aucun prix enregistré
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($product->cheapest_supplier)
                                        <strong class="text-success fs-5">
                                            {{ number_format($product->cheapest_supplier->unit_price, 0, ',', ' ') }} F
                                        </strong>
                                        @if($product->cheapest_supplier->last_price)
                                            <small class="text-muted d-block text-decoration-line-through">
                                                {{ number_format($product->cheapest_supplier->last_price, 0, ',', ' ') }} F
                                            </small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($product->all_prices && $product->all_prices->count() > 1)
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                {{ $product->all_prices->count() - 1 }} autre(s)
                                            </button>
                                            <ul class="dropdown-menu">
                                                @foreach($product->all_prices->skip(1) as $priceInfo)
                                                    <li class="px-3 py-1">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span>{{ $priceInfo->supplier->company_name }}</span>
                                                            <strong class="ms-3">
                                                                {{ number_format($priceInfo->unit_price, 0, ',', ' ') }} F
                                                            </strong>
                                                        </div>
                                                        @if($priceInfo->price_updated_at)
                                                            <small class="text-muted">
                                                                {{ $priceInfo->price_updated_at->format('d/m/Y') }}
                                                            </small>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @elseif($product->all_prices && $product->all_prices->count() == 0)
                                        <span class="text-muted">-</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                data-bs-toggle="modal" data-bs-target="#addPriceModal"
                                                onclick="setProductForPrice({{ $product->id }}, '{{ addslashes($product->name) }}')">
                                            <i class="bi bi-plus-circle"></i>
                                        </button>
                                        <a href="{{ route('admin.products.supplier-prices', $product) }}" 
                                           class="btn btn-outline-info" title="Historique des prix">
                                            <i class="bi bi-clock-history"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="bi bi-check-circle display-4 text-success"></i>
                                    <p class="mt-2 mb-0">Tous les produits ont un stock suffisant !</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Ajouter Prix --}}
<div class="modal fade" id="addPriceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.suppliers.store-price') }}">
                @csrf
                <input type="hidden" name="product_id" id="priceProductId">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-tag me-2"></i>Ajouter un prix fournisseur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <strong>Produit:</strong> <span id="priceProductName"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fournisseur <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Sélectionner un fournisseur</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Prix unitaire (FCFA) <span class="text-danger">*</span></label>
                        <input type="number" name="unit_price" class="form-control" required min="0" step="1">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantité min. commande</label>
                                <input type="number" name="min_order_quantity" class="form-control" value="1" min="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Délai livraison (jours)</label>
                                <input type="number" name="lead_time_days" class="form-control" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function setProductForPrice(productId, productName) {
    document.getElementById('priceProductId').value = productId;
    document.getElementById('priceProductName').textContent = productName;
}

function filterBySupplier(filter) {
    // Simple client-side filter
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const hasPrices = row.querySelector('.text-success strong') !== null;
        
        if (filter === 'with_prices' && !hasPrices) {
            row.style.display = 'none';
        } else if (filter === 'without_prices' && hasPrices) {
            row.style.display = 'none';
        } else {
            row.style.display = '';
        }
    });
}
</script>
@endpush
@endsection

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection
