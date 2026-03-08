@extends('layouts.app')

@section('title', 'Nouvelle Facture Fournisseur')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-file-earmark-plus"></i> Nouvelle Facture Fournisseur</h2>
    <a href="{{ route('admin.suppliers.orders') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<form action="{{ route('admin.suppliers.orders.store') }}" method="POST" id="orderForm">
    @csrf
    
    <div class="row">
        <!-- Colonne gauche - Infos facture -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Informations facture
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Fournisseur <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplierSelect" class="form-select @error('supplier_id') is-invalid @enderror" required>
                            <option value="">Sélectionner un fournisseur</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id', $selectedSupplier?->id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->company_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Boutique destination <span class="text-danger">*</span></label>
                        <select name="shop_id" id="shopSelect" class="form-select @error('shop_id') is-invalid @enderror" required>
                            <option value="">Sélectionner une boutique</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" {{ old('shop_id', auth()->user()->shop_id) == $shop->id ? 'selected' : '' }}>
                                    {{ $shop->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('shop_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">N° Facture fournisseur</label>
                        <input type="text" name="invoice_number" class="form-control @error('invoice_number') is-invalid @enderror" 
                               value="{{ old('invoice_number') }}" placeholder="Optionnel - Auto-généré si vide">
                        @error('invoice_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date de facture <span class="text-danger">*</span></label>
                        <input type="date" name="order_date" class="form-control @error('order_date') is-invalid @enderror" 
                               value="{{ old('order_date', date('Y-m-d')) }}" required>
                        @error('order_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Notes additionnelles...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Résumé -->
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-calculator"></i> Résumé</h5>
                    <hr class="bg-white">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Nombre d'articles:</span>
                        <strong id="totalItems">0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Quantité totale:</span>
                        <strong id="totalQuantity">0</strong>
                    </div>
                    <hr class="bg-white">
                    <div class="d-flex justify-content-between">
                        <span class="fs-5">TOTAL:</span>
                        <strong class="fs-4" id="totalAmount">0 FCFA</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne droite - Articles -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box"></i> Articles</span>
                    <button type="button" class="btn btn-success btn-sm" onclick="addRow()">
                        <i class="bi bi-plus-lg"></i> Ajouter article
                    </button>
                </div>
                <div class="card-body">
                    <!-- Recherche rapide -->
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="productSearch" class="form-control" placeholder="Rechercher un produit par nom...">
                        </div>
                        <div id="searchResults" class="list-group position-absolute w-100" style="z-index: 1000; max-height: 300px; overflow-y: auto; display: none;"></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Produit</th>
                                    <th style="width: 15%;">Stock actuel</th>
                                    <th style="width: 15%;">Quantité</th>
                                    <th style="width: 20%;">Prix unitaire</th>
                                    <th style="width: 10%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <!-- Lignes ajoutées dynamiquement -->
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="3" class="text-end fw-bold">Sous-total:</td>
                                    <td class="fw-bold" id="subtotalCell">0 FCFA</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="text-center py-4" id="emptyMessage">
                        <i class="bi bi-inbox display-4 text-muted"></i>
                        <p class="text-muted mt-2">Aucun article ajouté. Recherchez un produit ou cliquez sur "Ajouter article".</p>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <a href="{{ route('admin.suppliers.orders') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Annuler
                </a>
                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                    <i class="bi bi-check-lg"></i> Enregistrer la facture
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script type="application/json" id="productsData">@json($products)</script>
<script type="application/json" id="categoriesData">@json($categories ?? [])</script>
<script type="application/json" id="suppliersData">@json($suppliers)</script>
<script type="application/json" id="shopsData">@json($shops)</script>
<script>
const products = JSON.parse(document.getElementById('productsData').textContent);
const categories = JSON.parse(document.getElementById('categoriesData').textContent);
const suppliers = JSON.parse(document.getElementById('suppliersData').textContent);
const shops = JSON.parse(document.getElementById('shopsData').textContent);
let rowIndex = 0;

// Recherche de produit
const searchInput = document.getElementById('productSearch');
const searchResults = document.getElementById('searchResults');

searchInput.addEventListener('input', function() {
    const query = this.value.toLowerCase();
    if (query.length < 2) {
        searchResults.style.display = 'none';
        return;
    }

    const filtered = products.filter(p => 
        p.name.toLowerCase().includes(query) || 
        (p.sku && p.sku.toLowerCase().includes(query))
    ).slice(0, 10);

    if (filtered.length === 0) {
        searchResults.innerHTML = '<div class="list-group-item text-muted">Aucun produit trouvé</div>';
    } else {
        searchResults.innerHTML = filtered.map(p => `
            <button type="button" class="list-group-item list-group-item-action" onclick="addProductRow(${p.id})">
                <strong>${p.name}</strong>
                ${p.sku ? `<span class="badge bg-secondary ms-2">${p.sku}</span>` : ''}
                <span class="float-end text-muted">Stock: ${p.quantity_in_stock} | PA: ${formatNumber(p.purchase_price)} FCFA</span>
            </button>
        `).join('');
    }
    searchResults.style.display = 'block';
});

// Fermer les résultats quand on clique ailleurs
document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.style.display = 'none';
    }
});

function addProductRow(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;

    // Vérifier si le produit est déjà ajouté
    const existingRow = document.querySelector(`input[name="items[${productId}][product_id]"]`);
    if (existingRow) {
        alert('Ce produit est déjà dans la liste!');
        searchInput.value = '';
        searchResults.style.display = 'none';
        return;
    }

    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.id = `row-${productId}`;
    row.innerHTML = `
        <td>
            <strong>${product.name}</strong>
            ${product.sku ? `<br><small class="text-muted">${product.sku}</small>` : ''}
            <input type="hidden" name="items[${productId}][product_id]" value="${product.id}">
        </td>
        <td>
            <span class="badge ${product.quantity_in_stock <= 5 ? 'bg-danger' : 'bg-secondary'}">${product.quantity_in_stock}</span>
        </td>
        <td>
            <input type="number" name="items[${productId}][quantity]" class="form-control form-control-sm quantity-input" 
                   value="1" min="1" required onchange="updateTotals()">
        </td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" name="items[${productId}][unit_price]" class="form-control price-input" 
                       value="${product.purchase_price}" min="0" step="1" required onchange="updateTotals()">
                <span class="input-group-text">FCFA</span>
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(${productId})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);

    searchInput.value = '';
    searchResults.style.display = 'none';
    document.getElementById('emptyMessage').style.display = 'none';
    
    updateTotals();
}

function addRow() {
    // Afficher un modal ou une liste pour sélectionner un produit
    let modalHtml = '<div class="modal fade" id="productModal" tabindex="-1">' +
        '<div class="modal-dialog modal-lg">' +
        '<div class="modal-content">' +
        '<div class="modal-header">' +
        '<h5 class="modal-title">Sélectionner ou créer un produit</h5>' +
        '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
        '</div>' +
        '<div class="modal-body">' +
        '<div class="d-flex justify-content-between mb-3">' +
        '<input type="text" class="form-control me-2" id="modalSearch" placeholder="Rechercher un produit...">' +
        '<button type="button" class="btn btn-success" onclick="showCreateProductModal()">' +
        '<i class="bi bi-plus-lg"></i> Nouveau produit</button>' +
        '</div>' +
        '<div class="list-group" id="modalProductList" style="max-height: 400px; overflow-y: auto;">';
    
    products.forEach(function(p) {
        modalHtml += '<button type="button" class="list-group-item list-group-item-action product-item" ' +
            'data-name="' + p.name.toLowerCase() + '" data-sku="' + (p.sku || '').toLowerCase() + '" ' +
            'onclick="addProductRow(' + p.id + '); bootstrap.Modal.getInstance(document.getElementById(\'productModal\')).hide();">' +
            '<div class="d-flex justify-content-between">' +
            '<span><strong>' + p.name + '</strong> ' + (p.sku ? '<span class="badge bg-secondary">' + p.sku + '</span>' : '') + '</span>' +
            '<span>Stock: ' + p.quantity_in_stock + ' | ' + formatNumber(p.purchase_price) + ' FCFA</span>' +
            '</div></button>';
    });
    
    modalHtml += '</div></div></div></div></div>';

    // Supprimer modal existant
    const existingModal = document.getElementById('productModal');
    if (existingModal) existingModal.remove();

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modalEl = new bootstrap.Modal(document.getElementById('productModal'));
    modalEl.show();

    // Recherche dans le modal
    document.getElementById('modalSearch').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.product-item').forEach(item => {
            const name = item.dataset.name;
            const sku = item.dataset.sku;
            item.style.display = (name.includes(query) || sku.includes(query)) ? '' : 'none';
        });
    });
}

function removeRow(productId) {
    document.getElementById(`row-${productId}`).remove();
    updateTotals();
    
    // Afficher message si plus d'articles
    const tbody = document.getElementById('itemsBody');
    if (tbody.children.length === 0) {
        document.getElementById('emptyMessage').style.display = 'block';
    }
}

function updateTotals() {
    const quantities = document.querySelectorAll('.quantity-input');
    const prices = document.querySelectorAll('.price-input');
    
    let totalItems = quantities.length;
    let totalQuantity = 0;
    let totalAmount = 0;

    quantities.forEach((qtyInput, index) => {
        const qty = parseInt(qtyInput.value) || 0;
        const price = parseFloat(prices[index].value) || 0;
        totalQuantity += qty;
        totalAmount += qty * price;
    });

    document.getElementById('totalItems').textContent = totalItems;
    document.getElementById('totalQuantity').textContent = totalQuantity;
    document.getElementById('totalAmount').textContent = formatNumber(totalAmount) + ' FCFA';
    document.getElementById('subtotalCell').textContent = formatNumber(totalAmount) + ' FCFA';

    // Activer/désactiver le bouton
    document.getElementById('submitBtn').disabled = totalItems === 0;
}

function formatNumber(num) {
    return new Intl.NumberFormat('fr-FR').format(num);
}

// Modal de création de produit
function showCreateProductModal() {
    // Fermer le modal de sélection
    const selectModal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
    if (selectModal) selectModal.hide();

    const selectedShopId = document.getElementById('shopSelect').value;
    const selectedSupplierId = document.getElementById('supplierSelect').value;

    // Construire les options de catégories
    let categoryOptions = '<option value="">Sélectionner...</option>';
    categories.forEach(function(c) {
        categoryOptions += '<option value="' + c.id + '">' + c.name + '</option>';
    });
    
    // Construire les options de boutiques
    let shopOptions = '';
    shops.forEach(function(s) {
        shopOptions += '<option value="' + s.id + '"' + (s.id == selectedShopId ? ' selected' : '') + '>' + s.name + '</option>';
    });

    const createModal = '<div class="modal fade" id="createProductModal" tabindex="-1">' +
        '<div class="modal-dialog modal-lg">' +
        '<div class="modal-content">' +
        '<div class="modal-header bg-success text-white">' +
        '<h5 class="modal-title"><i class="bi bi-plus-circle"></i> Créer un nouveau produit</h5>' +
        '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>' +
        '</div>' +
        '<div class="modal-body">' +
        '<form id="quickProductForm">' +
        '<div class="row">' +
        '<div class="col-md-8 mb-3">' +
        '<label class="form-label">Nom du produit <span class="text-danger">*</span></label>' +
        '<input type="text" name="name" class="form-control" required placeholder="Ex: iPhone 15 Pro Max 256GB">' +
        '</div>' +
        '<div class="col-md-4 mb-3">' +
        '<label class="form-label">Code SKU</label>' +
        '<input type="text" name="sku" class="form-control" placeholder="Code unique (optionnel)">' +
        '</div>' +
        '</div>' +
        '<div class="row">' +
        '<div class="col-md-4 mb-3">' +
        '<label class="form-label">Catégorie <span class="text-danger">*</span></label>' +
        '<select name="category_id" class="form-select" required>' + categoryOptions + '</select>' +
        '</div>' +
        '<div class="col-md-4 mb-3">' +
        '<label class="form-label">Type <span class="text-danger">*</span></label>' +
        '<select name="type" class="form-select" required>' +
        '<option value="phone">Téléphone</option>' +
        '<option value="accessory" selected>Accessoire</option>' +
        '<option value="spare_part">Pièce détachée</option>' +
        '</select>' +
        '</div>' +
        '<div class="col-md-4 mb-3">' +
        '<label class="form-label">Marque</label>' +
        '<input type="text" name="brand" class="form-control" placeholder="Ex: Apple, Samsung...">' +
        '</div>' +
        '</div>' +
        '<div class="row">' +
        '<div class="col-md-6 mb-3">' +
        '<label class="form-label">Prix d\'achat <span class="text-danger">*</span></label>' +
        '<div class="input-group">' +
        '<input type="number" name="purchase_price" class="form-control" required min="0" step="1" value="0">' +
        '<span class="input-group-text">FCFA</span>' +
        '</div>' +
        '</div>' +
        '<div class="col-md-6 mb-3">' +
        '<label class="form-label">Quantité initiale <span class="text-danger">*</span></label>' +
        '<input type="number" name="quantity_in_stock" class="form-control" required min="0" value="0">' +
        '<small class="text-muted">Stock initial dans cette commande</small>' +
        '</div>' +
        '</div>' +
        '<div class="row">' +
        '<div class="col-md-6 mb-3">' +
        '<label class="form-label">Prix normal (FCFA) <span class="text-danger">*</span></label>' +
        '<div class="input-group">' +
        '<input type="number" name="normal_price" class="form-control" required min="0" step="1" value="0">' +
        '<span class="input-group-text">FCFA</span>' +
        '</div>' +
        '<small class="text-muted">Client boutique (1-2 pcs)</small>' +
        '</div>' +
        '<div class="col-md-6 mb-3">' +
        '<label class="form-label">Prix réparateur (FCFA)</label>' +
        '<div class="input-group">' +
        '<input type="number" name="reseller_price" class="form-control" min="0" step="1" value="0">' +
        '<span class="input-group-text">FCFA</span>' +
        '</div>' +
        '<small class="text-muted">Réparateur (1-2 pcs)</small>' +
        '</div>' +
        '</div>' +
        '<div class="row">' +
        '<div class="col-md-6 mb-3">' +
        '<label class="form-label">Prix demi-gros (FCFA)</label>' +
        '<div class="input-group">' +
        '<input type="number" name="semi_wholesale_price" class="form-control" min="0" step="1" value="0">' +
        '<span class="input-group-text">FCFA</span>' +
        '</div>' +
        '<small class="text-muted">Réparateur (3-9 pcs)</small>' +
        '</div>' +
        '<div class="col-md-6 mb-3">' +
        '<label class="form-label">Prix de gros (FCFA)</label>' +
        '<div class="input-group">' +
        '<input type="number" name="wholesale_price" class="form-control" min="0" step="1" value="0">' +
        '<span class="input-group-text">FCFA</span>' +
        '</div>' +
        '<small class="text-muted">Réparateur (10+ pcs)</small>' +
        '</div>' +
        '</div>' +
        '<div class="row">' +
        '<div class="col-md-6 mb-3">' +
        '<label class="form-label">Boutique <span class="text-danger">*</span></label>' +
        '<select name="shop_id" class="form-select" required>' + shopOptions + '</select>' +
        '</div>' +
        '</div>' +
        '<input type="hidden" name="supplier_id" value="' + selectedSupplierId + '">' +
        '</form>' +
        '</div>' +
        '<div class="modal-footer">' +
        '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>' +
        '<button type="button" class="btn btn-success" onclick="saveQuickProduct()">' +
        '<i class="bi bi-check-lg"></i> Créer et ajouter</button>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>';

    // Supprimer modal existant
    const existingModal = document.getElementById('createProductModal');
    if (existingModal) existingModal.remove();

    document.body.insertAdjacentHTML('beforeend', createModal);
    const modalEl = new bootstrap.Modal(document.getElementById('createProductModal'));
    modalEl.show();
}

// Sauvegarder le produit via AJAX
function saveQuickProduct() {
    const form = document.getElementById('quickProductForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // Validation basique
    if (!data.name || !data.category_id || !data.shop_id) {
        alert('Veuillez remplir tous les champs obligatoires.');
        return;
    }

    const btn = document.querySelector('#createProductModal .btn-success');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Création...';

    fetch('{{ route("admin.suppliers.orders.quick-product") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Ajouter le produit à la liste locale
            const newProduct = result.product;
            products.push(newProduct);

            // Fermer le modal
            bootstrap.Modal.getInstance(document.getElementById('createProductModal')).hide();

            // Ajouter directement le produit à la commande
            addProductRow(newProduct.id);

            // Notification
            showNotification('Produit créé et ajouté à la facture!', 'success');
        } else {
            alert('Erreur: ' + result.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Créer et ajouter';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de la création du produit.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Créer et ajouter';
    });
}

// Notification toast
function showNotification(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    document.body.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}
</script>
@endpush
@endsection
