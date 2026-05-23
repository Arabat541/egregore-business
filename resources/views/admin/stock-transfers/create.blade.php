@extends('layouts.app')

@section('title', 'Nouveau transfert de stock')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-arrow-left-right"></i> Nouveau transfert de stock</h2>
    <a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<form action="{{ route('admin.stock-transfers.store') }}" method="POST" id="transferForm">
    @csrf
    
    <div class="row">
        <div class="col-md-8">
            <!-- Sélection des boutiques -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-shop"></i> Boutiques
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <label class="form-label">Boutique source <span class="text-danger">*</span></label>
                            <select name="from_shop_id" id="from_shop_id" class="form-select @error('from_shop_id') is-invalid @enderror" required>
                                <option value="">Sélectionner...</option>
                                @foreach($shops as $shop)
                                    <option value="{{ $shop->id }}" {{ old('from_shop_id') == $shop->id ? 'selected' : '' }}>
                                        {{ $shop->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('from_shop_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2 d-flex align-items-center justify-content-center">
                            <i class="bi bi-arrow-right fs-2 text-primary"></i>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Boutique destination <span class="text-danger">*</span></label>
                            <select name="to_shop_id" id="to_shop_id" class="form-select @error('to_shop_id') is-invalid @enderror" required>
                                <option value="">Sélectionner...</option>
                                @foreach($shops as $shop)
                                    <option value="{{ $shop->id }}" {{ old('to_shop_id') == $shop->id ? 'selected' : '' }}>
                                        {{ $shop->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('to_shop_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Produits disponibles -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-seam"></i> Produits disponibles</span>
                    <input type="text" id="productSearch" class="form-control form-control-sm" 
                           style="width: 250px;" placeholder="Rechercher un produit...">
                </div>
                <div class="card-body">
                    <div id="productsLoading" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement des produits...</p>
                    </div>
                    
                    <div id="productsEmpty" class="text-center text-muted py-4">
                        <i class="bi bi-shop display-4"></i>
                        <p class="mt-2">Sélectionnez une boutique source pour voir les produits disponibles</p>
                    </div>
                    
                    <div id="productsContainer" class="d-none">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th>Produit</th>
                                        <th>Catégorie</th>
                                        <th class="text-center">Stock</th>
                                        <th class="text-center">Transférer</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="productsList">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="card mb-4">
                <div class="card-body">
                    <label class="form-label">Notes (optionnel)</label>
                    <textarea name="notes" class="form-control" rows="2" 
                              placeholder="Notes sur ce transfert...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Récapitulatif -->
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-list-check"></i> Récapitulatif du transfert
                </div>
                <div class="card-body">
                    <div id="transferSummaryEmpty" class="text-center text-muted py-4">
                        <i class="bi bi-cart display-4"></i>
                        <p class="mt-2">Aucun produit sélectionné</p>
                    </div>
                    
                    <div id="transferSummary" class="d-none">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th class="text-center">Qté</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="transferItems">
                                </tbody>
                            </table>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total articles:</strong>
                            <span id="totalItems">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <strong>Valeur estimée:</strong>
                            <span id="totalValue">0 FCFA</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100" id="submitBtn" disabled>
                        <i class="bi bi-check-lg"></i> Créer le transfert
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Champs cachés pour les items -->
    <div id="hiddenItems"></div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fromShopSelect = document.getElementById('from_shop_id');
    const toShopSelect = document.getElementById('to_shop_id');
    const productsLoading = document.getElementById('productsLoading');
    const productsEmpty = document.getElementById('productsEmpty');
    const productsContainer = document.getElementById('productsContainer');
    const productsList = document.getElementById('productsList');
    const productSearch = document.getElementById('productSearch');
    const transferSummary = document.getElementById('transferSummary');
    const transferSummaryEmpty = document.getElementById('transferSummaryEmpty');
    const transferItems = document.getElementById('transferItems');
    const hiddenItems = document.getElementById('hiddenItems');
    const submitBtn = document.getElementById('submitBtn');
    const totalItemsEl = document.getElementById('totalItems');
    const totalValueEl = document.getElementById('totalValue');
    
    let products = [];
    let selectedItems = {};
    
    // Charger les produits de la boutique source
    fromShopSelect.addEventListener('change', function() {
        const shopId = this.value;
        
        // Masquer la même boutique dans destination
        Array.from(toShopSelect.options).forEach(option => {
            option.style.display = option.value === shopId ? 'none' : '';
        });
        if (toShopSelect.value === shopId) {
            toShopSelect.value = '';
        }
        
        if (!shopId) {
            productsEmpty.classList.remove('d-none');
            productsContainer.classList.add('d-none');
            return;
        }
        
        productsLoading.classList.remove('d-none');
        productsEmpty.classList.add('d-none');
        productsContainer.classList.add('d-none');
        
        fetch(`/admin/stock-transfers/shop/${shopId}/products`)
            .then(response => response.json())
            .then(data => {
                products = data;
                renderProducts();
                productsLoading.classList.add('d-none');
                productsContainer.classList.remove('d-none');
            })
            .catch(error => {
                console.error('Error:', error);
                productsLoading.classList.add('d-none');
                productsEmpty.classList.remove('d-none');
            });
    });
    
    // Recherche de produits
    productSearch.addEventListener('input', function() {
        renderProducts(this.value.toLowerCase());
    });
    
    function renderProducts(filter = '') {
        productsList.innerHTML = '';
        
        const filtered = products.filter(p => 
            p.name.toLowerCase().includes(filter)
        );
        
        if (filtered.length === 0) {
            productsList.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Aucun produit trouvé</td></tr>';
            return;
        }
        
        filtered.forEach(product => {
            const selected = selectedItems[product.id];
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <strong>${product.name}</strong>
                </td>
                <td>${product.category}</td>
                <td class="text-center"><span class="badge bg-secondary">${product.quantity}</span></td>
                <td class="text-center" style="width: 100px;">
                    <input type="number" class="form-control form-control-sm transfer-qty" 
                           data-id="${product.id}" data-name="${product.name}" 
                           data-price="${product.purchase_price}" data-max="${product.quantity}"
                           value="${selected ? selected.quantity : ''}" 
                           min="1" max="${product.quantity}" placeholder="0">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-primary add-product" 
                            data-id="${product.id}">
                        <i class="bi bi-plus"></i>
                    </button>
                </td>
            `;
            productsList.appendChild(row);
        });
        
        // Event listeners
        document.querySelectorAll('.add-product').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const input = document.querySelector(`.transfer-qty[data-id="${id}"]`);
                const qty = parseInt(input.value) || 1;
                const max = parseInt(input.dataset.max);
                
                if (qty > 0 && qty <= max) {
                    selectedItems[id] = {
                        product_id: id,
                        name: input.dataset.name,
                        quantity: qty,
                        price: parseFloat(input.dataset.price) || 0
                    };
                    updateSummary();
                }
            });
        });
        
        document.querySelectorAll('.transfer-qty').forEach(input => {
            input.addEventListener('change', function() {
                const id = this.dataset.id;
                const qty = parseInt(this.value);
                const max = parseInt(this.dataset.max);
                
                if (qty > max) {
                    this.value = max;
                }
                
                if (selectedItems[id]) {
                    if (qty > 0) {
                        selectedItems[id].quantity = Math.min(qty, max);
                    } else {
                        delete selectedItems[id];
                    }
                    updateSummary();
                }
            });
        });
    }
    
    function updateSummary() {
        const items = Object.values(selectedItems);
        
        if (items.length === 0) {
            transferSummary.classList.add('d-none');
            transferSummaryEmpty.classList.remove('d-none');
            submitBtn.disabled = true;
            hiddenItems.innerHTML = '';
            return;
        }
        
        transferSummary.classList.remove('d-none');
        transferSummaryEmpty.classList.add('d-none');
        submitBtn.disabled = !toShopSelect.value;
        
        let totalQty = 0;
        let totalValue = 0;
        
        transferItems.innerHTML = '';
        hiddenItems.innerHTML = '';
        
        items.forEach((item, index) => {
            totalQty += item.quantity;
            totalValue += item.quantity * item.price;
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.name}</td>
                <td class="text-center">${item.quantity}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item" data-id="${item.product_id}">
                        <i class="bi bi-x"></i>
                    </button>
                </td>
            `;
            transferItems.appendChild(row);
            
            // Champs cachés
            hiddenItems.innerHTML += `
                <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
            `;
        });
        
        totalItemsEl.textContent = totalQty;
        totalValueEl.textContent = new Intl.NumberFormat('fr-FR').format(totalValue) + ' FCFA';
        
        // Event listener pour supprimer
        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', function() {
                delete selectedItems[this.dataset.id];
                updateSummary();
                renderProducts(productSearch.value.toLowerCase());
            });
        });
    }
    
    // Validation boutique destination
    toShopSelect.addEventListener('change', function() {
        submitBtn.disabled = !this.value || Object.keys(selectedItems).length === 0;
    });
});
</script>
@endpush
@endsection
