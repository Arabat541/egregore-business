@extends('layouts.app')

@section('title', 'Détail produit')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-seam"></i> {{ $product->name }}</h2>
    <div>
        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Modifier
        </a>
        <a href="{{ route('admin.products.stock-entry', $product) }}" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Entrée stock
        </a>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th>Catégorie</th>
                        <td><span class="badge bg-secondary">{{ $product->category->name }}</span></td>
                    </tr>
                    <tr>
                        <th>Code-barres</th>
                        <td><code>{{ $product->barcode ?: '-' }}</code></td>
                    </tr>
                    <tr>
                        <th>SKU</th>
                        <td><code>{{ $product->sku ?: '-' }}</code></td>
                    </tr>
                    <tr>
                        <th>Marque</th>
                        <td>{{ $product->brand ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Emplacement</th>
                        <td>{{ $product->location ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Statut</th>
                        <td>
                            @if($product->is_active)
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-danger">Inactif</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-currency-dollar"></i> Prix
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th>Prix d'achat</th>
                        <td>{{ number_format($product->purchase_price, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <th>Prix de vente</th>
                        <td class="fw-bold">{{ number_format($product->selling_price, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <th>Prix revendeur</th>
                        <td>{{ $product->reseller_price ? number_format($product->reseller_price, 0, ',', ' ') . ' FCFA' : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Marge</th>
                        <td>
                            @if($product->purchase_price > 0)
                                {{ number_format($product->selling_price - $product->purchase_price, 0, ',', ' ') }} FCFA
                                <span class="text-muted">({{ number_format(($product->selling_price - $product->purchase_price) / $product->purchase_price * 100, 1) }}%)</span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card {{ $product->quantity_in_stock <= $product->stock_alert_threshold ? 'border-warning' : '' }}">
            <div class="card-header {{ $product->quantity_in_stock <= 0 ? 'bg-danger text-white' : ($product->quantity_in_stock <= $product->stock_alert_threshold ? 'bg-warning' : '') }}">
                <i class="bi bi-box"></i> Stock
            </div>
            <div class="card-body text-center">
                <h1 class="display-4 {{ $product->quantity_in_stock <= 0 ? 'text-danger' : ($product->quantity_in_stock <= $product->stock_alert_threshold ? 'text-warning' : 'text-success') }}">
                    {{ $product->quantity_in_stock }}
                </h1>
                <p class="text-muted mb-0">unités en stock</p>
                <hr>
                <small class="text-muted">Seuil d'alerte: {{ $product->stock_alert_threshold }}</small>
            </div>
        </div>
    </div>
</div>

<!-- Historique des mouvements de stock -->
<div class="card mt-4">
    <div class="card-header">
        <i class="bi bi-clock-history"></i> Historique des mouvements de stock
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Quantité</th>
                        <th>Stock avant</th>
                        <th>Stock après</th>
                        <th>Référence</th>
                        <th>Utilisateur</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($product->stockMovements()->latest()->take(20)->get() as $movement)
                    <tr>
                        <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($movement->type === 'entry')
                                <span class="badge bg-success">Entrée</span>
                            @elseif($movement->type === 'sale')
                                <span class="badge bg-primary">Vente</span>
                            @elseif($movement->type === 'repair')
                                <span class="badge bg-warning">Réparation</span>
                            @elseif($movement->type === 'adjustment')
                                <span class="badge bg-info">Ajustement</span>
                            @elseif($movement->type === 'return')
                                <span class="badge bg-secondary">Retour</span>
                            @else
                                <span class="badge bg-dark">{{ $movement->type }}</span>
                            @endif
                        </td>
                        <td class="{{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                        </td>
                        <td>{{ $movement->quantity_before }}</td>
                        <td>{{ $movement->quantity_after }}</td>
                        <td>{{ $movement->reference ?: '-' }}</td>
                        <td>{{ $movement->user->name ?? '-' }}</td>
                        <td>{{ $movement->notes ?: '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">Aucun mouvement</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($product->description)
<div class="card mt-4">
    <div class="card-header">
        <i class="bi bi-card-text"></i> Description
    </div>
    <div class="card-body">
        {{ $product->description }}
    </div>
</div>
@endif

<!-- Fournisseurs de ce produit -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-truck me-2"></i>Fournisseurs</span>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
            <i class="bi bi-plus-lg me-1"></i>Ajouter fournisseur
        </button>
    </div>
    <div class="card-body p-0">
        @php
            $supplierPrices = $product->supplierPrices()->with('supplier')->orderBy('unit_price', 'asc')->get();
        @endphp
        @if($supplierPrices->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th></th>
                            <th>Fournisseur</th>
                            <th>Contact</th>
                            <th class="text-end">Prix actuel</th>
                            <th class="text-end">Ancien prix</th>
                            <th class="text-center">Dernière MAJ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($supplierPrices as $index => $priceInfo)
                            <tr>
                                <td class="text-center" style="width: 40px;">
                                    @if($index === 0)
                                        <i class="bi bi-trophy-fill text-warning" title="Moins cher"></i>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.suppliers.show', $priceInfo->supplier) }}">
                                        <strong>{{ $priceInfo->supplier->company_name }}</strong>
                                    </a>
                                </td>
                                <td>
                                    @if($priceInfo->supplier->phone)
                                        <a href="tel:{{ $priceInfo->supplier->phone }}" class="text-decoration-none">
                                            <i class="bi bi-telephone"></i> {{ $priceInfo->supplier->phone }}
                                        </a>
                                    @endif
                                    @if($priceInfo->supplier->whatsapp)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $priceInfo->supplier->whatsapp) }}" 
                                           target="_blank" class="ms-2 text-success">
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <strong class="{{ $index === 0 ? 'text-success' : '' }}">
                                        {{ number_format($priceInfo->unit_price, 0, ',', ' ') }} F
                                    </strong>
                                    @if($priceInfo->has_decreased)
                                        <i class="bi bi-arrow-down-circle-fill text-success ms-1"></i>
                                    @elseif($priceInfo->has_increased)
                                        <i class="bi bi-arrow-up-circle-fill text-danger ms-1"></i>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($priceInfo->last_price)
                                        <span class="text-muted text-decoration-line-through">
                                            {{ number_format($priceInfo->last_price, 0, ',', ' ') }} F
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($priceInfo->price_updated_at)
                                        <small>{{ $priceInfo->price_updated_at->format('d/m/Y') }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="bi bi-truck display-4 text-muted"></i>
                <p class="text-muted mt-2 mb-0">Aucun fournisseur associé à ce produit</p>
                <button type="button" class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                    <i class="bi bi-plus-lg me-1"></i>Ajouter un fournisseur
                </button>
            </div>
        @endif
    </div>
    @if($supplierPrices->count() > 0)
        <div class="card-footer text-end">
            <a href="{{ route('admin.products.supplier-prices', $product) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-clock-history me-1"></i>Voir l'historique des prix
            </a>
        </div>
    @endif
</div>

<!-- Modal Ajouter Fournisseur -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.suppliers.store-price') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-truck me-2"></i>Ajouter un fournisseur pour {{ $product->name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Fournisseur <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Sélectionner un fournisseur...</option>
                            @php
                                $associatedSupplierIds = $supplierPrices->pluck('supplier_id')->toArray();
                                $availableSuppliers = \App\Models\Supplier::active()
                                    ->whereNotIn('id', $associatedSupplierIds)
                                    ->orderBy('company_name')
                                    ->get();
                            @endphp
                            @foreach($availableSuppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Prix unitaire (FCFA) <span class="text-danger">*</span></label>
                        <input type="number" name="unit_price" class="form-control" required min="0" step="1"
                               value="{{ $product->purchase_price }}" placeholder="Prix d'achat chez ce fournisseur">
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
@endsection
