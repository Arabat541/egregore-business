@extends('layouts.app')

@section('title', 'Nouvelle Facture Fournisseur')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="mb-0"><i class="bi bi-file-earmark-plus"></i> Nouvelle Facture Fournisseur</h2>
    <span class="badge bg-secondary fs-6" id="cartCount">0 article</span>
</div>

<form action="{{ route('admin.suppliers.orders.store') }}" method="POST" id="orderForm">
    @csrf

    {{-- 1. Barre informations facture ──────────────────────── --}}
    <div class="card mb-2 border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Fournisseur <span class="text-danger">*</span></label>
                    <select name="supplier_id" id="supplierSelect"
                            class="form-select form-select-lg @error('supplier_id') is-invalid @enderror" required>
                        <option value="">— Sélectionner —</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}"
                                {{ old('supplier_id', $selectedSupplier?->id) == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->company_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Boutique destination <span class="text-danger">*</span></label>
                    <select name="shop_id" id="shopSelect"
                            class="form-select form-select-lg @error('shop_id') is-invalid @enderror" required>
                        <option value="">— Sélectionner —</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}"
                                {{ old('shop_id', auth()->user()->shop_id) == $shop->id ? 'selected' : '' }}>
                                {{ $shop->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('shop_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">N° Facture fournisseur</label>
                    <input type="text" name="invoice_number"
                           class="form-control form-control-lg @error('invoice_number') is-invalid @enderror"
                           value="{{ old('invoice_number') }}" placeholder="Auto-généré si vide">
                    @error('invoice_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date de facture <span class="text-danger">*</span></label>
                    <input type="date" name="order_date"
                           class="form-control form-control-lg @error('order_date') is-invalid @enderror"
                           value="{{ old('order_date', date('Y-m-d')) }}" required>
                    @error('order_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Barre recherche produit ──────────────────────────── --}}
    <div class="card mb-2 border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="row g-3 align-items-center">
                <div class="col">
                    <div class="position-relative" id="productSearchWrapper">
                        <input type="text" id="productSearch" class="form-control form-control-lg"
                               placeholder="Référence ou nom du produit..." autocomplete="off"
                               style="font-size:1.1rem;">
                        <div id="productDropdown"
                             style="display:none; position:absolute; top:100%; left:0; right:0; z-index:1050;
                                    background:#fff; border:1px solid #dee2e6; border-radius:0 0 6px 6px;
                                    max-height:360px; overflow-y:auto; box-shadow:0 4px 12px rgba(0,0,0,.1);">
                        </div>
                    </div>
                </div>
                <div class="col-auto d-flex align-items-center gap-2">
                    <label class="mb-0 fw-semibold text-muted">Qté</label>
                    <input type="number" id="qtyInput" class="form-control form-control-lg text-center"
                           value="1" min="1" style="width:90px;">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-outline-success btn-lg" onclick="showCreateProductModal()">
                        <i class="bi bi-plus-lg"></i> Nouveau produit
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerte ──────────────────────────────────────────────── --}}
    <div id="cartAlert" class="alert alert-danger alert-dismissible py-2 small mb-2" style="display:none">
        <button type="button" class="btn-close btn-sm" onclick="this.closest('#cartAlert').style.display='none'"></button>
        <span id="cartAlertMsg"></span>
    </div>

    {{-- 3. Tableau articles (style Sage) ─────────────────────── --}}
    <div class="card mb-2 border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:1rem;" id="itemsTable">
                <thead style="background:#2c3e50; color:#fff;">
                    <tr>
                        <th style="width:12%; padding:.75rem 1rem;">Référence</th>
                        <th style="padding:.75rem 1rem;">Désignation</th>
                        <th class="text-center" style="width:100px; padding:.75rem 1rem;">Stock</th>
                        <th class="text-center" style="width:120px; padding:.75rem 1rem;">Quantité</th>
                        <th class="text-end"    style="width:200px; padding:.75rem 1rem;">Prix unitaire (FCFA)</th>
                        <th class="text-end"    style="width:160px; padding:.75rem 1rem;">Montant</th>
                        <th style="width:52px;"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <tr id="emptyCartRow">
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Aucun article — recherchez un produit ci-dessus
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- 4. Barre bas : notes + total + bouton ────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="row g-3 align-items-start">

                {{-- Notes --}}
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"
                              placeholder="Notes additionnelles...">{{ old('notes') }}</textarea>
                </div>

                {{-- Résumé --}}
                <div class="col-md-3 offset-md-1">
                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>Articles</span><span id="totalItems">0</span>
                    </div>
                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>Quantité totale</span><span id="totalQuantity">0</span>
                    </div>
                </div>

                {{-- Total (grand affichage Sage) --}}
                <div class="col-md-3">
                    <div class="rounded p-4 text-center mb-3" style="background:#e8f4fd; border:2px solid #0d6efd;">
                        <div class="text-primary small fw-semibold mb-1 text-uppercase">Total facture</div>
                        <div class="fw-bold text-primary" style="font-size:2.2rem; line-height:1;" id="totalAmount">0 FCFA</div>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold" id="submitBtn" disabled>
                            <i class="bi bi-check-lg"></i> Enregistrer la facture
                        </button>
                        <a href="{{ route('admin.suppliers.orders') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> Annuler
                        </a>
                    </div>
                </div>

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
const products   = JSON.parse(document.getElementById('productsData').textContent);
const categories = JSON.parse(document.getElementById('categoriesData').textContent);
const suppliers  = JSON.parse(document.getElementById('suppliersData').textContent);
const shops      = JSON.parse(document.getElementById('shopsData').textContent);

/* ── Utilitaires ─────────────────────────────────────────── */
function fmt(n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n)); }
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
                    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function showAlert(msg) {
    document.getElementById('cartAlertMsg').textContent = msg;
    document.getElementById('cartAlert').style.display = 'block';
}

/* ── Recherche produit (dropdown style Sage) ─────────────── */
(function() {
    const searchEl  = document.getElementById('productSearch');
    const dropdown  = document.getElementById('productDropdown');

    function getShopId() { return parseInt(document.getElementById('shopSelect').value) || 0; }

    function renderDropdown(list) {
        if (!getShopId()) {
            dropdown.innerHTML = '<div class="px-3 py-3 text-warning small"><i class="bi bi-exclamation-triangle"></i> Sélectionnez d\'abord une boutique destination</div>';
            dropdown.style.display = 'block';
            return;
        }
        if (list.length === 0) {
            dropdown.innerHTML = '<div class="px-3 py-3 text-muted">Aucun produit trouvé</div>';
        } else {
            dropdown.innerHTML = list.slice(0, 30).map(p => {
                const alreadyAdded = !!document.getElementById(`row-${p.id}`);
                const stockCls = p.quantity_in_stock > 5 ? 'text-success' : p.quantity_in_stock > 0 ? 'text-warning' : 'text-danger';
                return `<div class="px-3 py-3 product-dd-item d-flex justify-content-between align-items-center"
                             style="cursor:pointer; border-bottom:1px solid #f0f0f0; ${alreadyAdded ? 'background:#f0fff4;' : ''}"
                             data-id="${p.id}">
                    <div>
                        <div class="fw-semibold" style="font-size:1rem;">${esc(p.name)}${alreadyAdded ? ' <span class="badge bg-success ms-1">✓ ajouté</span>' : ''}</div>
                        <div class="text-muted" style="font-size:.85rem;">
                            ${p.sku ? `<span class="me-2 font-monospace">${esc(p.sku)}</span>` : ''}
                            <span class="${stockCls}"><i class="bi bi-layers"></i> ${p.quantity_in_stock} en stock</span>
                        </div>
                    </div>
                    <div class="text-end ms-3 text-nowrap">
                        <div class="fw-semibold" style="font-size:1rem;">${fmt(p.purchase_price)} FCFA</div>
                        <span class="badge bg-primary fs-6">+ Ajouter</span>
                    </div>
                </div>`;
            }).join('');

            dropdown.querySelectorAll('.product-dd-item').forEach(el => {
                el.addEventListener('mousedown', function() {
                    const pid = parseInt(this.dataset.id);
                    const qtyEl = document.getElementById('qtyInput');
                    const qty   = Math.max(1, parseInt(qtyEl?.value) || 1);
                    addProductRow(pid, qty);
                    if (qtyEl) qtyEl.value = 1;
                    searchEl.value = '';
                    dropdown.style.display = 'none';
                    searchEl.focus();
                });
            });
        }
        dropdown.style.display = 'block';
    }

    function filterProducts(q) {
        const shopId = getShopId();
        return products.filter(p =>
            (!shopId || parseInt(p.shop_id) === shopId) &&
            (p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q)))
        );
    }

    searchEl.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        if (!q) { dropdown.style.display = 'none'; return; }
        renderDropdown(filterProducts(q));
    });

    searchEl.addEventListener('focus', function() {
        if (this.value.trim()) renderDropdown(filterProducts(this.value.toLowerCase().trim()));
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('#productSearchWrapper')) dropdown.style.display = 'none';
    });

    document.head.insertAdjacentHTML('beforeend', `<style>
        .product-dd-item:hover { background:#f8f9fa !important; }
    </style>`);
})();

/* ── Ajouter / supprimer une ligne ───────────────────────── */
function addProductRow(productId, qty = 1) {
    const product = products.find(p => p.id === productId);
    if (!product) return;

    const shopId = parseInt(document.getElementById('shopSelect').value);
    if (shopId && parseInt(product.shop_id) !== shopId) {
        showAlert('⚠️ Ce produit appartient à une autre boutique !');
        return;
    }

    if (document.getElementById(`row-${productId}`)) {
        // Incrémenter la quantité si déjà présent
        const qtyInput = document.querySelector(`#row-${productId} .quantity-input`);
        if (qtyInput) {
            qtyInput.value = parseInt(qtyInput.value) + qty;
            qtyInput.dispatchEvent(new Event('change'));
        }
        return;
    }

    const tbody = document.getElementById('itemsBody');
    const emptyRow = document.getElementById('emptyCartRow');
    if (emptyRow) emptyRow.remove();

    const row = document.createElement('tr');
    row.id = `row-${productId}`;
    const stockCls = product.quantity_in_stock <= 5 ? 'danger' : 'secondary';
    row.innerHTML = `
        <td class="font-monospace small text-muted" style="padding:.75rem 1rem;">${esc(product.sku || '—')}</td>
        <td style="padding:.75rem 1rem;">
            <div class="fw-semibold">${esc(product.name)}</div>
            <input type="hidden" name="items[${productId}][product_id]" value="${product.id}">
        </td>
        <td class="text-center" style="padding:.75rem 1rem;">
            <span class="badge bg-${stockCls}">${product.quantity_in_stock}</span>
        </td>
        <td class="text-center" style="padding:.75rem 1rem;">
            <input type="number" name="items[${productId}][quantity]"
                   class="form-control form-control-lg text-center quantity-input"
                   value="${qty}" min="1" required onchange="updateTotals()" style="width:80px; margin:auto;">
        </td>
        <td class="text-end" style="padding:.75rem 1rem;">
            <input type="number" name="items[${productId}][unit_price]"
                   class="form-control form-control-lg text-end price-input"
                   value="${product.purchase_price}" min="0" step="1" required onchange="updateTotals()"
                   style="min-width:140px;">
        </td>
        <td class="text-end fw-bold text-nowrap" id="line-total-${productId}" style="padding:.75rem 1rem;">
            ${fmt(qty * product.purchase_price)} FCFA
        </td>
        <td class="text-center" style="padding:.75rem 1rem;">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(${productId})">
                <i class="bi bi-x"></i>
            </button>
        </td>`;
    tbody.prepend(row);

    // Recalcul en temps réel du total de ligne
    row.querySelector('.quantity-input').addEventListener('input', () => updateLineTotal(productId));
    row.querySelector('.price-input').addEventListener('input',    () => updateLineTotal(productId));

    updateTotals();
}

function updateLineTotal(productId) {
    const row   = document.getElementById(`row-${productId}`);
    if (!row) return;
    const qty   = parseInt(row.querySelector('.quantity-input').value) || 0;
    const price = parseFloat(row.querySelector('.price-input').value)  || 0;
    const cell  = document.getElementById(`line-total-${productId}`);
    if (cell) cell.textContent = fmt(qty * price) + ' FCFA';
    updateTotals();
}

function removeRow(productId) {
    const row = document.getElementById(`row-${productId}`);
    if (row) row.remove();

    const tbody = document.getElementById('itemsBody');
    if (tbody.children.length === 0) {
        tbody.innerHTML = `<tr id="emptyCartRow">
            <td colspan="7" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                Aucun article — recherchez un produit ci-dessus
            </td>
        </tr>`;
    }
    updateTotals();
}

function updateTotals() {
    const qtyInputs   = document.querySelectorAll('.quantity-input');
    const priceInputs = document.querySelectorAll('.price-input');

    let totalItems = qtyInputs.length;
    let totalQty   = 0;
    let totalAmt   = 0;

    qtyInputs.forEach((q, i) => {
        const qty   = parseInt(q.value) || 0;
        const price = parseFloat(priceInputs[i]?.value) || 0;
        totalQty += qty;
        totalAmt += qty * price;
    });

    document.getElementById('totalItems').textContent    = totalItems;
    document.getElementById('totalQuantity').textContent = totalQty;
    document.getElementById('totalAmount').textContent   = fmt(totalAmt) + ' FCFA';
    document.getElementById('cartCount').textContent     = totalItems + (totalItems <= 1 ? ' article' : ' articles');
    document.getElementById('submitBtn').disabled        = totalItems === 0;
}

/* ── Changement boutique : purge les articles incompatibles ─ */
document.getElementById('shopSelect').addEventListener('change', function() {
    const newShopId = parseInt(this.value);
    let removed = 0;
    document.querySelectorAll('#itemsBody tr[id^="row-"]').forEach(row => {
        const hidden = row.querySelector('input[type="hidden"]');
        if (hidden) {
            const p = products.find(x => x.id === parseInt(hidden.value));
            if (p && parseInt(p.shop_id) !== newShopId) { row.remove(); removed++; }
        }
    });
    if (removed > 0) {
        showAlert('⚠️ ' + removed + ' article(s) retiré(s) — ils n\'appartiennent pas à cette boutique.');
    }
    const tbody = document.getElementById('itemsBody');
    if (tbody.children.length === 0) {
        tbody.innerHTML = `<tr id="emptyCartRow">
            <td colspan="7" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                Aucun article — recherchez un produit ci-dessus
            </td>
        </tr>`;
    }
    updateTotals();
});

/* ── Modal création rapide de produit ────────────────────── */
function showCreateProductModal() {
    const selectedShopId     = document.getElementById('shopSelect').value;
    const selectedSupplierId = document.getElementById('supplierSelect').value;

    let catOptions  = '<option value="">Sélectionner...</option>';
    categories.forEach(c => { catOptions += `<option value="${c.id}">${esc(c.name)}</option>`; });

    let shopOptions = '';
    shops.forEach(s => { shopOptions += `<option value="${s.id}"${s.id == selectedShopId ? ' selected' : ''}>${esc(s.name)}</option>`; });

    const existing = document.getElementById('createProductModal');
    if (existing) existing.remove();

    document.body.insertAdjacentHTML('beforeend', `
    <div class="modal fade" id="createProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Créer un nouveau produit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="quickProductForm">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label fw-semibold">Nom du produit <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-lg" required placeholder="Ex: iPhone 15 Pro 256GB">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Code SKU</label>
                                <input type="text" name="sku" class="form-control form-control-lg" placeholder="Optionnel">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Catégorie <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select form-select-lg" required>${catOptions}</select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select form-select-lg" required>
                                    <option value="phone">Téléphone</option>
                                    <option value="accessory" selected>Accessoire</option>
                                    <option value="spare_part">Pièce détachée</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Marque</label>
                                <input type="text" name="brand" class="form-control form-control-lg" placeholder="Apple, Samsung...">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Prix d'achat <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <input type="number" name="purchase_price" class="form-control" required min="0" step="1" value="0">
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Prix normal <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <input type="number" name="normal_price" class="form-control" required min="0" step="1" value="0">
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Prix réparateur</label>
                                <div class="input-group input-group-lg">
                                    <input type="number" name="reseller_price" class="form-control" min="0" step="1" value="0">
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Stock initial</label>
                                <input type="number" name="quantity_in_stock" class="form-control form-control-lg" min="0" step="1" value="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Prix demi-gros</label>
                                <div class="input-group input-group-lg">
                                    <input type="number" name="semi_wholesale_price" class="form-control" min="0" step="1" value="0">
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Prix de gros</label>
                                <div class="input-group input-group-lg">
                                    <input type="number" name="wholesale_price" class="form-control" min="0" step="1" value="0">
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Boutique <span class="text-danger">*</span></label>
                                <select name="shop_id" class="form-select form-select-lg" required>${shopOptions}</select>
                            </div>
                        </div>
                        <input type="hidden" name="supplier_id" value="${selectedSupplierId}">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-success btn-lg" onclick="saveQuickProduct()">
                        <i class="bi bi-check-lg"></i> Créer et ajouter
                    </button>
                </div>
            </div>
        </div>
    </div>`);

    new bootstrap.Modal(document.getElementById('createProductModal')).show();
}

function saveQuickProduct() {
    const form = document.getElementById('quickProductForm');
    const data = Object.fromEntries(new FormData(form).entries());

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
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (ok && data.success) {
            products.push(data.product);
            bootstrap.Modal.getInstance(document.getElementById('createProductModal')).hide();
            addProductRow(data.product.id, 1);
            showNotification('Produit créé et ajouté à la facture !', 'success');
        } else {
            const msg = data.errors
                ? Object.values(data.errors).flat().join('\n')
                : (data.message || 'Erreur inconnue');
            alert('Erreur :\n' + msg);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Créer et ajouter';
        }
    })
    .catch(() => {
        alert('Erreur réseau lors de la création du produit.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Créer et ajouter';
    });
}

function showNotification(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    document.body.appendChild(toast);
    const t = new bootstrap.Toast(toast);
    t.show();
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

/* ── Bloquer Enter (pas de soumission accidentelle) ──────── */
document.getElementById('orderForm').addEventListener('keydown', function(e) {
    if (e.key !== 'Enter') return;
    const isField = e.target.classList.contains('quantity-input') || e.target.classList.contains('price-input');
    if (isField) {
        e.preventDefault();
        const inputs = [...document.querySelectorAll('.quantity-input, .price-input')];
        const idx = inputs.indexOf(e.target);
        if (idx >= 0 && idx < inputs.length - 1) { inputs[idx + 1].focus(); inputs[idx + 1].select(); }
        updateTotals();
    } else if (e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
    }
});
</script>
@endpush
@endsection
