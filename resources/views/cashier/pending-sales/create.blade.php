@extends('layouts.app')

@section('title', 'Ajouter articles - Vente en attente')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@push('styles')
<style>
    .suggestion-item:hover {
        background-color: #f8f9fa;
    }
    #productSuggestions {
        top: 100%;
        left: 0;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cart-plus"></i> Vente en attente - Réparateur</h2>
    <a href="{{ route('cashier.pending-sales.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <!-- Colonne gauche - Formulaire d'ajout -->
    <div class="col-md-7">
        <!-- Sélection du réparateur -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-person-badge"></i> Réparateur
            </div>
            <div class="card-body">
                <form method="GET" id="resellerForm">
                    <div class="row">
                        <div class="col-md-8">
                            <select name="reseller_id" class="form-select form-select-lg" id="resellerSelect" required>
                                <option value="">-- Sélectionner un réparateur --</option>
                                @foreach($resellers as $reseller)
                                    <option value="{{ $reseller->id }}" 
                                            data-credit="{{ $reseller->available_credit }}"
                                            {{ $selectedReseller && $selectedReseller->id == $reseller->id ? 'selected' : '' }}>
                                        {{ $reseller->company_name }} - {{ $reseller->contact_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-check"></i> Sélectionner
                            </button>
                        </div>
                    </div>
                </form>

                @if($selectedReseller)
                    <div class="mt-3 p-3 bg-light rounded">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Réparateur</small>
                                <div class="fw-bold">{{ $selectedReseller->company_name }}</div>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-muted">Crédit disponible</small>
                                <div class="fw-bold text-success">{{ number_format($selectedReseller->available_credit, 0, ',', ' ') }} FCFA</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if($selectedReseller)
        <!-- Formulaire d'ajout d'article -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-plus-circle"></i> Ajouter un article
            </div>
            <div class="card-body">
                <form action="{{ route('cashier.pending-sales.add-item') }}" method="POST">
                    @csrf
                    <input type="hidden" name="reseller_id" value="{{ $selectedReseller->id }}">

                    <div class="mb-3">
                        <label class="form-label">Produit</label>
                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control form-control-lg" id="productSearch" 
                                       placeholder="Tapez pour rechercher un produit..." autofocus autocomplete="off">
                            </div>
                            <div id="productSuggestions" class="position-absolute w-100 bg-white border rounded-bottom shadow-lg d-none" style="z-index: 1050; max-height: 400px; overflow-y: auto;"></div>
                        </div>
                        <input type="hidden" name="product_id" id="productId" required>
                        <div id="selectedProduct" class="mt-2 p-2 bg-light rounded d-none">
                            <div class="d-flex justify-content-between align-items-center">
                                <span id="selectedProductName" class="fw-bold"></span>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearSelectedProduct()">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            <small class="text-muted" id="selectedProductInfo"></small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Quantité</label>
                            <input type="number" name="quantity" class="form-control" id="quantityInput" value="1" min="1" required>
                            <small class="text-muted" id="stockInfo"></small>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Prix unitaire</label>
                            <input type="number" name="unit_price" class="form-control" id="priceInput" required readonly>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100">
                        <i class="bi bi-plus-lg"></i> Ajouter à la vente
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>

    <!-- Colonne droite - Articles en attente -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-cart"></i> Articles en attente
                @if($pendingSale)
                    <span class="badge bg-dark float-end">{{ $pendingSale->items->count() }}</span>
                @endif
            </div>
            <div class="card-body">
                @if(!$selectedReseller)
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-arrow-left-circle" style="font-size: 3rem;"></i>
                        <p class="mt-2">Sélectionnez d'abord un réparateur</p>
                    </div>
                @elseif(!$pendingSale || $pendingSale->items->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-cart-x" style="font-size: 3rem;"></i>
                        <p class="mt-2">Aucun article pour l'instant</p>
                    </div>
                @else
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-center">Qté</th>
                                    <th class="text-end">Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingSale->items as $item)
                                    <tr>
                                        <td>
                                            <small>{{ $item->product->name }}</small>
                                            <br><small class="text-muted">{{ number_format($item->unit_price, 0, ',', ' ') }}/u</small>
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('cashier.pending-sales.update-item', $item) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="number" name="quantity" value="{{ $item->quantity }}" 
                                                       class="form-control form-control-sm text-center" 
                                                       style="width: 60px;" min="1"
                                                       onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ number_format($item->total_price, 0, ',', ' ') }}</strong>
                                        </td>
                                        <td>
                                            <form action="{{ route('cashier.pending-sales.remove-item', $item) }}" method="POST"
                                                  onsubmit="return confirm('Retirer cet article ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-top pt-3 mt-3">
                        <h4 class="text-end">
                            Total: <strong class="text-success">{{ number_format($pendingSale->total_amount, 0, ',', ' ') }} FCFA</strong>
                        </h4>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('cashier.pending-sales.show', $pendingSale) }}" class="btn btn-success w-100 btn-lg">
                            <i class="bi bi-check-circle"></i> Valider cette vente
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const products = @json($products);
let selectedProduct = null;

document.addEventListener('DOMContentLoaded', function() {
    const productSearch = document.getElementById('productSearch');
    const suggestionsDiv = document.getElementById('productSuggestions');
    const productIdInput = document.getElementById('productId');
    const priceInput = document.getElementById('priceInput');
    const quantityInput = document.getElementById('quantityInput');
    const stockInfo = document.getElementById('stockInfo');
    const selectedProductDiv = document.getElementById('selectedProduct');
    const selectedProductName = document.getElementById('selectedProductName');
    const selectedProductInfo = document.getElementById('selectedProductInfo');

    // Formater les nombres
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    // Calculer le prix selon la quantité pour un réparateur
    function getResellerPrice(product, quantity) {
        if (quantity >= 10) {
            return parseFloat(product.wholesale_price || product.semi_wholesale_price || product.reseller_price || product.normal_price);
        }
        if (quantity >= 3) {
            return parseFloat(product.semi_wholesale_price || product.reseller_price || product.normal_price);
        }
        return parseFloat(product.reseller_price || product.normal_price);
    }

    function updatePrice() {
        if (selectedProduct) {
            const quantity = parseInt(quantityInput.value) || 1;
            const price = getResellerPrice(selectedProduct, quantity);
            priceInput.value = price;
        }
    }

    // Sélectionner un produit
    window.selectProduct = function(product) {
        selectedProduct = product;
        productIdInput.value = product.id;
        productSearch.value = '';
        suggestionsDiv.classList.add('d-none');
        
        // Afficher le produit sélectionné
        selectedProductName.textContent = product.name;
        selectedProductInfo.textContent = `Stock: ${product.quantity_in_stock} - Prix: ${formatNumber(product.reseller_price || product.normal_price)} FCFA`;
        selectedProductDiv.classList.remove('d-none');
        
        // Mettre à jour le stock et le prix
        stockInfo.textContent = 'Stock disponible: ' + product.quantity_in_stock;
        quantityInput.max = product.quantity_in_stock;
        updatePrice();
    };

    // Effacer le produit sélectionné
    window.clearSelectedProduct = function() {
        selectedProduct = null;
        productIdInput.value = '';
        selectedProductDiv.classList.add('d-none');
        priceInput.value = '';
        stockInfo.textContent = '';
        productSearch.focus();
    };

    // Afficher les suggestions pendant la saisie
    productSearch.addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase().trim();
        
        if (search.length < 1) {
            suggestionsDiv.classList.add('d-none');
            suggestionsDiv.innerHTML = '';
            return;
        }
        
        // Filtrer les produits
        const matches = products.filter(p => 
            p.name.toLowerCase().includes(search) ||
            (p.sku && p.sku.toLowerCase().includes(search)) ||
            (p.category && p.category.name && p.category.name.toLowerCase().includes(search))
        ).slice(0, 15); // Limiter à 15 résultats
        
        if (matches.length === 0) {
            suggestionsDiv.innerHTML = '<div class="p-3 text-muted"><i class="bi bi-search"></i> Aucun produit trouvé</div>';
            suggestionsDiv.classList.remove('d-none');
            return;
        }
        
        // Générer le HTML des suggestions
        let html = '';
        matches.forEach((p, index) => {
            const stockClass = p.quantity_in_stock > 0 ? 'text-success' : 'text-danger';
            const stockIcon = p.quantity_in_stock > 0 ? 'bi-check-circle' : 'bi-x-circle';
            const categoryName = p.category ? p.category.name : 'Non catégorisé';
            const price = p.reseller_price || p.normal_price;
            html += `
                <div class="suggestion-item p-2 border-bottom" style="cursor: pointer;" data-index="${index}">
                    <div class="fw-bold">${p.name}</div>
                    <small class="text-muted">
                        <span class="badge bg-secondary">${categoryName}</span>
                        ${p.sku ? '<span class="ms-1">[' + p.sku + ']</span>' : ''}
                        ${formatNumber(price)} FCFA - 
                        <span class="${stockClass}"><i class="bi ${stockIcon}"></i> Stock: ${p.quantity_in_stock}</span>
                    </small>
                </div>
            `;
        });
        
        suggestionsDiv.innerHTML = html;
        suggestionsDiv.classList.remove('d-none');
        
        // Attacher les événements de clic
        suggestionsDiv.querySelectorAll('.suggestion-item').forEach((item, idx) => {
            item.addEventListener('click', function() {
                selectProduct(matches[idx]);
            });
        });
    });

    // Gérer Enter pour sélectionner le premier résultat
    productSearch.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const search = e.target.value.toLowerCase();
            const found = products.find(p => 
                p.name.toLowerCase().includes(search) ||
                (p.sku && p.sku.toLowerCase().includes(search))
            );
            if (found) {
                selectProduct(found);
            }
        }
    });

    // Fermer les suggestions quand on clique ailleurs
    document.addEventListener('click', function(e) {
        if (!productSearch.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.classList.add('d-none');
        }
    });

    if (quantityInput) {
        quantityInput.addEventListener('change', updatePrice);
        quantityInput.addEventListener('input', updatePrice);
    }
});
</script>
@endpush
@endsection
