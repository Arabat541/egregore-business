@extends('layouts.app')

@section('title', 'Prix Fournisseurs - ' . $product->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Produits</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.suppliers.price-comparison') }}">Comparaison prix</a></li>
                    <li class="breadcrumb-item active">{{ $product->name }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">
                <i class="bi bi-tag me-2"></i>Prix Fournisseurs
            </h1>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPriceModal">
            <i class="bi bi-plus-lg me-1"></i>Ajouter un prix
        </button>
    </div>

    {{-- Infos produit --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4>{{ $product->name }}</h4>
                    @if($product->sku)
                        <p class="text-muted mb-1">SKU: {{ $product->sku }}</p>
                    @endif
                    @if($product->category)
                        <span class="badge bg-secondary">{{ $product->category->name }}</span>
                    @endif
                </div>
                <div class="col-md-2 text-center">
                    <small class="text-muted d-block">Stock actuel</small>
                    <span class="fs-4 {{ $product->quantity_in_stock == 0 ? 'text-danger' : ($product->is_low_stock ? 'text-warning' : 'text-success') }}">
                        {{ $product->quantity_in_stock }}
                    </span>
                </div>
                <div class="col-md-2 text-center">
                    <small class="text-muted d-block">Prix d'achat actuel</small>
                    <span class="fs-4">{{ number_format($product->purchase_price ?? 0, 0, ',', ' ') }} F</span>
                </div>
                <div class="col-md-2 text-center">
                    <small class="text-muted d-block">Prix de vente</small>
                    <span class="fs-4">{{ number_format($product->selling_price ?? 0, 0, ',', ' ') }} F</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Prix actuels par fournisseur --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ol me-2"></i>Prix par fournisseur</h5>
                    <span class="badge bg-primary">{{ $prices->count() }} fournisseur(s)</span>
                </div>
                <div class="card-body p-0">
                    @if($prices->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($prices as $index => $price)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            @if($index == 0)
                                                <i class="bi bi-trophy-fill text-warning me-2"></i>
                                            @endif
                                            <strong>{{ $price->supplier->company_name }}</strong>
                                            @if($price->supplier->phone)
                                                <small class="text-muted ms-2">
                                                    <i class="bi bi-telephone"></i> {{ $price->supplier->phone }}
                                                </small>
                                            @endif
                                            @if($price->supplier->whatsapp)
                                                <a href="https://wa.me/{{ $price->supplier->whatsapp }}" 
                                                   target="_blank" class="ms-1 text-success">
                                                    <i class="bi bi-whatsapp"></i>
                                                </a>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <span class="fs-5 {{ $index == 0 ? 'text-success fw-bold' : '' }}">
                                                {{ number_format($price->unit_price, 0, ',', ' ') }} F
                                            </span>
                                            @if($price->has_decreased)
                                                <i class="bi bi-arrow-down-circle-fill text-success ms-1"></i>
                                            @elseif($price->has_increased)
                                                <i class="bi bi-arrow-up-circle-fill text-danger ms-1"></i>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-2 small text-muted">
                                        @if($price->last_price)
                                            <span class="me-3">
                                                Ancien prix: <span class="text-decoration-line-through">{{ number_format($price->last_price, 0, ',', ' ') }} F</span>
                                                @php
                                                    $variation = $price->price_variation;
                                                @endphp
                                                @if($variation !== null)
                                                    <span class="{{ $variation > 0 ? 'text-danger' : 'text-success' }}">
                                                        ({{ $variation > 0 ? '+' : '' }}{{ number_format($variation, 1) }}%)
                                                    </span>
                                                @endif
                                            </span>
                                        @endif
                                        @if($price->min_order_quantity > 1)
                                            <span class="me-3">
                                                <i class="bi bi-box"></i> Min: {{ $price->min_order_quantity }}
                                            </span>
                                        @endif
                                        @if($price->lead_time_days)
                                            <span class="me-3">
                                                <i class="bi bi-clock"></i> {{ $price->lead_time_days }}j
                                            </span>
                                        @endif
                                        @if($price->price_updated_at)
                                            <span>
                                                <i class="bi bi-calendar"></i> {{ $price->price_updated_at->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <p class="mt-2 mb-0">Aucun prix enregistré pour ce produit</p>
                            <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addPriceModal">
                                <i class="bi bi-plus-lg me-1"></i>Ajouter un prix
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Historique des prix --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Historique des prix</h5>
                </div>
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                    @if($priceHistory->count() > 0)
                        <table class="table table-sm mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Date</th>
                                    <th>Fournisseur</th>
                                    <th class="text-end">Prix</th>
                                    <th>Commande</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($priceHistory as $history)
                                    <tr>
                                        <td>{{ $history->recorded_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $history->supplier->company_name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($history->unit_price, 0, ',', ' ') }} F</td>
                                        <td>
                                            @if($history->order)
                                                <a href="{{ route('admin.suppliers.orders.show', $history->order) }}">
                                                    {{ $history->order->reference }}
                                                </a>
                                            @else
                                                <span class="text-muted">Manuel</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-clock display-4 text-muted"></i>
                            <p class="mt-2 mb-0">Aucun historique disponible</p>
                        </div>
                    @endif
                </div>
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
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-tag me-2"></i>Ajouter un prix fournisseur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Fournisseur <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Sélectionner un fournisseur</option>
                            @php
                                $allSuppliers = \App\Models\Supplier::active()->orderBy('company_name')->get();
                            @endphp
                            @foreach($allSuppliers as $supplier)
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
@endsection

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection
