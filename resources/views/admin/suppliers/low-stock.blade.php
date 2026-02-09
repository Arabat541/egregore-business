@extends('layouts.app')

@section('title', 'Produits à Commander')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-exclamation-triangle text-warning me-2"></i>Produits à Commander</h2>
    <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Retour aux fournisseurs
    </a>
</div>

<!-- Statistiques -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-danger text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $outOfStock }}</h3>
                <small>Produits en rupture</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-warning text-dark">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $lowStock }}</h3>
                <small>Produits stock faible</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-info text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $products->count() }}</h3>
                <small>Total à commander</small>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de commande -->
<form action="{{ route('admin.suppliers.generate-order') }}" method="POST" id="orderForm">
    @csrf
    
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <label class="form-label mb-0">Fournisseur destinataire</label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">Sélectionner un fournisseur</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">
                                {{ $supplier->company_name }}
                                @if($supplier->email) - {{ $supplier->email }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-0">Notes pour la commande</label>
                    <textarea name="notes" class="form-control" rows="1" 
                              placeholder="Instructions spéciales..."></textarea>
                </div>
                <div class="col-md-4 text-end">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="save_order" id="saveOrder" value="1">
                        <label class="form-check-label" for="saveOrder">Sauvegarder la commande</label>
                    </div>
                    <button type="submit" class="btn btn-danger" id="generateBtn" disabled>
                        <i class="bi bi-file-pdf me-1"></i>Générer PDF (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des produits -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-box me-2"></i>Sélectionner les produits</h5>
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="selectAll">
                    <i class="bi bi-check-all me-1"></i>Tout sélectionner
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">
                    <i class="bi bi-x-lg me-1"></i>Tout désélectionner
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Produit</th>
                            <th>Catégorie</th>
                            <th class="text-center">Stock actuel</th>
                            <th class="text-center">Seuil alerte</th>
                            <th class="text-center" style="width: 150px;">Quantité à commander</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $index => $product)
                            <tr class="{{ $product->quantity_in_stock == 0 ? 'table-danger' : 'table-warning' }}">
                                <td>
                                    <input type="checkbox" class="form-check-input product-checkbox" 
                                           data-index="{{ $index }}"
                                           onchange="toggleProduct(this, {{ $index }})">
                                </td>
                                <td>
                                    <strong>{{ $product->name }}</strong>
                                    @if($product->sku)
                                        <br><small class="text-muted">{{ $product->sku }}</small>
                                    @endif
                                </td>
                                <td>{{ $product->category->name ?? '-' }}</td>
                                <td class="text-center">
                                    @if($product->quantity_in_stock == 0)
                                        <span class="badge bg-danger">Rupture</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ $product->quantity_in_stock }}</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $product->stock_alert_threshold }}</td>
                                <td>
                                    <input type="hidden" name="products[{{ $index }}][id]" value="{{ $product->id }}" disabled>
                                    <input type="number" name="products[{{ $index }}][quantity]" 
                                           class="form-control form-control-sm text-center quantity-input"
                                           data-index="{{ $index }}"
                                           value="{{ max(10, $product->stock_alert_threshold * 2) }}"
                                           min="1" disabled>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-check-circle text-success fs-1 d-block mb-2"></i>
                                    Tous les produits ont un stock suffisant !
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    let selectedCount = 0;

    function toggleProduct(checkbox, index) {
        const idInput = document.querySelector(`input[name="products[${index}][id]"]`);
        const qtyInput = document.querySelector(`input[name="products[${index}][quantity]"]`);
        
        if (checkbox.checked) {
            idInput.disabled = false;
            qtyInput.disabled = false;
            selectedCount++;
        } else {
            idInput.disabled = true;
            qtyInput.disabled = true;
            selectedCount--;
        }
        
        updateButton();
    }

    function updateButton() {
        document.getElementById('selectedCount').textContent = selectedCount;
        document.getElementById('generateBtn').disabled = selectedCount === 0;
    }

    document.getElementById('selectAll').addEventListener('click', function() {
        document.querySelectorAll('.product-checkbox').forEach(cb => {
            if (!cb.checked) {
                cb.checked = true;
                toggleProduct(cb, cb.dataset.index);
            }
        });
    });

    document.getElementById('deselectAll').addEventListener('click', function() {
        document.querySelectorAll('.product-checkbox').forEach(cb => {
            if (cb.checked) {
                cb.checked = false;
                toggleProduct(cb, cb.dataset.index);
            }
        });
    });
</script>
@endpush
@endsection
