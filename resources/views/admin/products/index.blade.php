@extends('layouts.app')

@section('title', 'Gestion des produits')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-seam"></i> Gestion des produits</h2>
    <div>
        <a href="{{ route('admin.products.low-stock') }}" class="btn btn-warning me-2">
            <i class="bi bi-exclamation-triangle"></i> Stock faible
        </a>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nouveau produit
        </a>
    </div>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('admin.products.index') }}" method="GET" class="row g-3">
            <div class="col-md-2">
                <input type="text" class="form-control" name="search" 
                       placeholder="Rechercher..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="shop_id">
                    <option value="">Toutes boutiques</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                            {{ $shop->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="category">
                    <option value="">Toutes catégories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="stock_status">
                    <option value="">Tous stocks</option>
                    <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>En stock</option>
                    <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>Stock faible</option>
                    <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Rupture</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="supplier_id">
                    <option value="">Tous fournisseurs</option>
                    <option value="none" {{ request('supplier_id') === 'none' ? 'selected' : '' }}>Sans fournisseur</option>
                    @php
                        $allSuppliers = \App\Models\Supplier::active()->orderBy('company_name')->get();
                    @endphp
                    @foreach($allSuppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->company_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filtrer
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Nom</th>
                        <th>Boutique</th>
                        <th>Catégorie</th>
                        <th>Fournisseur</th>
                        <th>Prix vente</th>
                        <th>Stock</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td><code>{{ $product->sku ?: $product->barcode ?: '-' }}</code></td>
                        <td>
                            <strong>{{ $product->name }}</strong>
                            @if($product->barcode)
                                <br><small class="text-muted"><i class="bi bi-upc"></i> {{ $product->barcode }}</small>
                            @endif
                        </td>
                        <td>
                            @if($product->shop)
                                <span class="badge bg-info">{{ $product->shop->name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $product->category->name ?? '-' }}</span>
                        </td>
                        <td>
                            @php
                                $cheapestSupplier = $product->supplierPrices()->with('supplier')->orderBy('unit_price', 'asc')->first();
                            @endphp
                            @if($cheapestSupplier)
                                <a href="{{ route('admin.suppliers.show', $cheapestSupplier->supplier) }}" 
                                   class="text-decoration-none" title="Voir le fournisseur">
                                    <i class="bi bi-trophy-fill text-warning me-1"></i>
                                    <small>{{ Str::limit($cheapestSupplier->supplier->company_name, 15) }}</small>
                                </a>
                                <small class="text-success d-block">{{ number_format($cheapestSupplier->unit_price, 0, ',', ' ') }} F</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ number_format($product->selling_price, 0, ',', ' ') }} F</td>
                        <td>
                            <span class="badge bg-{{ $product->quantity_in_stock <= 0 ? 'danger' : ($product->quantity_in_stock <= $product->stock_alert_threshold ? 'warning' : 'success') }}">
                                {{ $product->quantity_in_stock }}
                            </span>
                        </td>
                        <td>
                            @if($product->is_active)
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-danger">Inactif</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.products.show', $product) }}" class="btn btn-sm btn-outline-info" title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="{{ route('admin.products.stock-entry', $product) }}" class="btn btn-sm btn-outline-success" title="Entrée stock">
                                    <i class="bi bi-plus-circle"></i>
                                </a>
                                <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"
                                            onclick="return confirm('Supprimer ce produit ?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">Aucun produit trouvé</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $products->appends(request()->query())->links() }}
    </div>
</div>
@endsection
