@extends('layouts.app')

@section('title', 'Nouvelle vente')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="bi bi-cart-plus"></i> Nouvelle vente</h2>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('cashier.sales.store') }}" method="POST" id="saleForm">
    @csrf

    <div class="row g-3">
        {{-- ── Colonne gauche : catalogue ─────────────────────── --}}
        <div class="col-md-8">

            {{-- Type de client --}}
            <div class="card mb-3">
                <div class="card-body py-2">
                    <div class="row align-items-end g-3">
                        <div class="col-md-5">
                            <label class="form-label mb-1">Type de client</label>
                            <select class="form-select" name="client_type" id="clientType">
                                <option value="walk-in">Client comptoir</option>
                                <option value="customer">Client enregistré</option>
                                <option value="reseller">Réparateur</option>
                            </select>
                        </div>
                        <div class="col-md-7 d-none" id="customerSection">
                            <label class="form-label mb-1">Client</label>
                            <div class="input-group">
                                <select class="form-select" name="customer_id" id="customerSelect">
                                    <option value="">Rechercher un client...</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->full_name }} — {{ $customer->phone }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#newCustomerModal">
                                    <i class="bi bi-person-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-7 d-none" id="resellerSection">
                            <label class="form-label mb-1">Réparateur</label>
                            <div class="input-group">
                                <select class="form-select" name="reseller_id" id="resellerSelect">
                                    <option value="">Choisir un réparateur...</option>
                                    @foreach($resellers as $reseller)
                                        <option value="{{ $reseller->id }}" data-credit="{{ $reseller->credit_limit - $reseller->current_debt }}">
                                            {{ $reseller->company_name }} — Crédit: {{ number_format($reseller->credit_limit - $reseller->current_debt, 0, ',', ' ') }} FCFA
                                        </option>
                                    @endforeach
                                </select>
                                <span class="input-group-text text-success fw-bold" id="availableCredit" style="min-width: 110px;">—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Catalogue produits --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-seam"></i> Produits disponibles</span>
                    <input type="text" id="productSearch" class="form-control form-control-sm"
                           style="width: 260px;" placeholder="Rechercher nom, SKU, catégorie..." autofocus autocomplete="off">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="sticky-top bg-white border-bottom">
                                <tr>
                                    <th>Produit</th>
                                    <th>Catégorie</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-end">Prix</th>
                                    <th class="text-center" style="width: 70px;">Qté</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="productsList">
                                <tr><td colspan="6" class="text-center text-muted py-4">
                                    <div class="spinner-border spinner-border-sm me-2"></div>Chargement…
                                </td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Colonne droite : panier + paiement ─────────────── --}}
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 16px;">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-cart"></i> Panier</span>
                    <span class="badge bg-white text-dark" id="cartCount">0 article</span>
                </div>

                {{-- Articles du panier --}}
                <div class="card-body p-2" id="cartBody">
                    <div class="text-center text-muted py-3" id="emptyCart">
                        <i class="bi bi-cart3 fs-3"></i>
                        <p class="mb-0 small mt-1">Aucun article</p>
                    </div>
                    <div id="cartItemsContainer" style="display:none; max-height: 220px; overflow-y: auto;">
                        <table class="table table-sm mb-0">
                            <tbody id="cartItems"></tbody>
                        </table>
                    </div>
                </div>

                {{-- Récapitulatif + paiement --}}
                <div class="card-body border-top pt-2">
                    <div class="mb-2">
                        <label class="form-label form-label-sm mb-1">Remise globale (FCFA)</label>
                        <input type="number" class="form-control form-control-sm" name="discount_amount"
                               id="discountAmount" value="0" min="0" step="100" oninput="calculateTotals()">
                        <div id="discountError" class="text-danger small mt-1" style="display:none"></div>
                    </div>
                    <div class="d-flex justify-content-between mb-1" id="subtotalRow" style="display:none!important">
                        <span class="text-muted small">Sous-total:</span>
                        <span class="text-muted small" id="subtotalAmount">0 FCFA</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 text-success" id="discountRow" style="display:none">
                        <span class="small">Remise:</span>
                        <span class="small" id="discountDisplay">- 0 FCFA</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <strong>TOTAL:</strong>
                        <strong class="text-primary fs-5" id="totalAmount">0 FCFA</strong>
                    </div>
                    <input type="hidden" name="total_amount" id="totalAmountInput" value="0">

                    <hr class="my-2">

                    <div class="mb-2">
                        <label class="form-label form-label-sm mb-1">Mode de paiement</label>
                        <select class="form-select form-select-sm" name="payment_method_id" id="paymentMethod" required>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label form-label-sm mb-1">Montant reçu</label>
                        <input type="number" class="form-control" name="paid_amount" id="paidAmount" value="0" min="0">
                    </div>

                    <div class="d-flex justify-content-between mb-2" id="changeSection" style="display:none!important">
                        <span class="small">Monnaie à rendre:</span>
                        <span class="text-success fw-bold" id="changeAmount">0 FCFA</span>
                    </div>

                    <div class="form-check mb-2 d-none" id="creditSection">
                        <input class="form-check-input" type="checkbox" name="is_credit" id="isCredit" value="1">
                        <label class="form-check-label small">Vente à crédit</label>
                    </div>

                    <div class="mb-2">
                        <label class="form-label form-label-sm mb-1">Notes</label>
                        <textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Notes optionnelles…"></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success" id="submitSale" disabled>
                            <i class="bi bi-check-lg"></i> Valider la vente
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                            <i class="bi bi-trash"></i> Vider le panier
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="cartInputs"></div>
</form>

{{-- Modal nouveau client --}}
<div class="modal fade" id="newCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('cashier.customers.store') }}" method="POST">
                @csrf
                <input type="hidden" name="redirect_back" value="1">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Nouveau client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" name="phone" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const products = @json($products);
let cart = [];

/* ── Formatage ──────────────────────────────────────────── */
function fmt(n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n)); }

/* ── Prix selon type/quantité ───────────────────────────── */
function getPriceForQuantity(product, quantity, clientType = null) {
    const type = clientType || document.getElementById('clientType').value;
    if (type !== 'reseller') return parseFloat(product.normal_price);
    const qW = parseInt(product.qty_wholesale_min) || 10;
    const qS = parseInt(product.qty_semi_wholesale_min) || 3;
    if (quantity >= qW) return parseFloat(product.wholesale_price || product.semi_wholesale_price || product.reseller_price);
    if (quantity >= qS) return parseFloat(product.semi_wholesale_price || product.reseller_price);
    return parseFloat(product.reseller_price);
}

function getPriceLabel(quantity, product) {
    if (document.getElementById('clientType').value !== 'reseller') return '';
    const qW = parseInt(product.qty_wholesale_min) || 10;
    const qS = parseInt(product.qty_semi_wholesale_min) || 3;
    if (quantity >= qW) return '<span class="badge bg-primary ms-1">gros</span>';
    if (quantity >= qS) return '<span class="badge bg-info ms-1">demi-gros</span>';
    return '<span class="badge bg-secondary ms-1">répar.</span>';
}

/* ── Rendu du catalogue produits ────────────────────────── */
function renderProducts(filter) {
    const tbody = document.getElementById('productsList');
    const clientType = document.getElementById('clientType').value;
    const search = (filter || '').toLowerCase().trim();

    const filtered = search.length === 0 ? products : products.filter(p =>
        p.name.toLowerCase().includes(search) ||
        (p.sku && p.sku.toLowerCase().includes(search)) ||
        (p.category && p.category.name && p.category.name.toLowerCase().includes(search))
    );

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-search"></i> Aucun produit trouvé</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(p => {
        const price     = getPriceForQuantity(p, 1, clientType);
        const inCart    = cart.find(i => i.product_id === p.id);
        const stockCls  = p.quantity_in_stock > 5 ? 'bg-success' : p.quantity_in_stock > 0 ? 'bg-warning text-dark' : 'bg-danger';
        const catName   = (p.category && p.category.name) ? p.category.name : '';
        const disabled  = p.quantity_in_stock < 1;
        return `<tr class="${inCart ? 'table-success' : ''}">
            <td>
                <div class="fw-semibold">${p.name}</div>
                ${p.sku ? `<small class="text-muted">[${p.sku}]</small>` : ''}
            </td>
            <td>${catName ? `<span class="badge bg-secondary">${catName}</span>` : '<span class="text-muted">—</span>'}</td>
            <td class="text-center"><span class="badge ${stockCls}">${p.quantity_in_stock}</span></td>
            <td class="text-end small text-nowrap" id="priceCell-${p.id}">${fmt(price)} FCFA</td>
            <td class="text-center">
                <input type="number" id="qty-${p.id}" value="1" min="1" max="${p.quantity_in_stock}"
                       class="form-control form-control-sm text-center" style="width:60px;"
                       ${disabled ? 'disabled' : ''}
                       oninput="updateCellPrice(${p.id})">
            </td>
            <td>
                <button type="button" class="btn btn-sm ${inCart ? 'btn-success' : 'btn-outline-primary'}"
                        onclick="addToCartFromTable(${p.id})" ${disabled ? 'disabled' : ''}>
                    <i class="bi ${inCart ? 'bi-check' : 'bi-plus-lg'}"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

function updateCellPrice(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    const qty = parseInt(document.getElementById(`qty-${productId}`)?.value) || 1;
    const cell = document.getElementById(`priceCell-${productId}`);
    if (cell) cell.textContent = fmt(getPriceForQuantity(product, qty)) + ' FCFA';
}

/* ── Panier ─────────────────────────────────────────────── */
function addToCartFromTable(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    const qty = parseInt(document.getElementById(`qty-${productId}`)?.value) || 1;
    addToCart(product, qty);
}

function addToCart(product, qty = 1) {
    const clientType = document.getElementById('clientType').value;
    if (clientType === 'reseller' && !product.reseller_price) {
        alert(`⚠️ "${product.name}" n'a pas de prix réparateur défini.`);
        return;
    }
    const idx = cart.findIndex(i => i.product_id === product.id);
    if (idx !== -1) {
        const item = cart[idx];
        const newQty = item.quantity + qty;
        if (newQty > product.quantity_in_stock) { alert('Stock insuffisant!'); return; }
        item.quantity   = newQty;
        item.unit_price = getPriceForQuantity(product, newQty);
        cart.splice(idx, 1);
        cart.unshift(item);
    } else {
        if (product.quantity_in_stock < 1) { alert('Rupture de stock!'); return; }
        cart.unshift({
            product_id:          product.id,
            name:                product.name,
            quantity:            qty,
            unit_price:          getPriceForQuantity(product, qty, clientType),
            normal_price:        product.normal_price,
            reseller_price:      product.reseller_price,
            semi_wholesale_price:product.semi_wholesale_price,
            wholesale_price:     product.wholesale_price,
            qty_semi_wholesale_min: parseInt(product.qty_semi_wholesale_min) || 3,
            qty_wholesale_min:   parseInt(product.qty_wholesale_min) || 10,
            max_stock:           product.quantity_in_stock,
        });
    }
    renderCart();
    renderProducts(document.getElementById('productSearch').value);
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
    renderProducts(document.getElementById('productSearch').value);
}

function updateQuantity(index, qty) {
    const item = cart[index];
    qty = parseInt(qty);
    if (qty > 0 && qty <= item.max_stock) {
        item.quantity   = qty;
        const product   = products.find(p => p.id === item.product_id);
        if (product) item.unit_price = getPriceForQuantity(product, qty);
        renderCart();
    }
}

function clearCart() {
    if (confirm('Vider le panier ?')) {
        cart = [];
        renderCart();
        renderProducts(document.getElementById('productSearch').value);
    }
}

function renderCart() {
    const tbody     = document.getElementById('cartItems');
    const emptyMsg  = document.getElementById('emptyCart');
    const container = document.getElementById('cartItemsContainer');
    const cartInputs= document.getElementById('cartInputs');
    const countEl   = document.getElementById('cartCount');

    const total = cart.reduce((s, i) => s + i.quantity, 0);
    countEl.textContent = total + (total <= 1 ? ' article' : ' articles');

    if (cart.length === 0) {
        emptyMsg.style.display = 'block';
        container.style.display = 'none';
        cartInputs.innerHTML = '';
        document.getElementById('submitSale').disabled = true;
        calculateTotals();
        return;
    }

    emptyMsg.style.display = 'none';
    container.style.display = 'block';
    document.getElementById('submitSale').disabled = false;

    tbody.innerHTML = cart.map((item, i) => {
        const product = products.find(p => p.id === item.product_id);
        const label   = product ? getPriceLabel(item.quantity, product) : '';
        return `<tr>
            <td class="small">
                <div class="fw-semibold">${item.name}${label}</div>
                <span class="text-muted">${fmt(item.unit_price)} FCFA/u</span>
            </td>
            <td class="text-center">
                <input type="number" class="form-control form-control-sm text-center"
                       value="${item.quantity}" min="1" max="${item.max_stock}"
                       onchange="updateQuantity(${i}, this.value)" style="width:55px;">
            </td>
            <td class="text-end small fw-bold text-nowrap">${fmt(item.quantity * item.unit_price)}</td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${i})">
                    <i class="bi bi-x"></i>
                </button>
            </td>
        </tr>`;
    }).join('');

    cartInputs.innerHTML = cart.map((item, i) => `
        <input type="hidden" name="items[${i}][product_id]" value="${item.product_id}">
        <input type="hidden" name="items[${i}][quantity]" value="${item.quantity}">
        <input type="hidden" name="items[${i}][unit_price]" value="${item.unit_price}">
    `).join('');

    calculateTotals();
}

function calculateTotals() {
    const subtotal     = cart.reduce((s, i) => s + i.quantity * i.unit_price, 0);
    const discountEl   = document.getElementById('discountAmount');
    const discountErr  = document.getElementById('discountError');
    let discountAmount = parseFloat(discountEl.value) || 0;

    if (subtotal > 0 && discountAmount >= subtotal) {
        const max     = subtotal - 1;
        discountEl.value = max;
        discountAmount   = max;
        discountErr.textContent = 'La remise ne peut pas être supérieure ou égale au sous-total.';
        discountErr.style.display = 'block';
    } else {
        discountErr.style.display = 'none';
    }

    const effectiveDiscount = Math.min(discountAmount, subtotal > 0 ? subtotal - 1 : 0);
    const total  = subtotal - effectiveDiscount;
    const paid   = parseFloat(document.getElementById('paidAmount').value) || 0;
    const change = paid - total;

    document.getElementById('totalAmount').textContent   = fmt(total) + ' FCFA';
    document.getElementById('totalAmountInput').value    = total;

    const discountRow  = document.getElementById('discountRow');
    const subtotalRow  = document.getElementById('subtotalRow');
    if (effectiveDiscount > 0) {
        discountRow.style.display = 'flex';
        subtotalRow.style.removeProperty('display');
        document.getElementById('subtotalAmount').textContent = fmt(subtotal) + ' FCFA';
        document.getElementById('discountDisplay').textContent = '- ' + fmt(effectiveDiscount) + ' FCFA';
    } else {
        discountRow.style.display = 'none';
        subtotalRow.style.display = 'none';
    }

    const changeSection = document.getElementById('changeSection');
    if (paid > 0 && paid >= total) {
        changeSection.style.removeProperty('display');
        document.getElementById('changeAmount').textContent = fmt(change) + ' FCFA';
    } else {
        changeSection.style.display = 'none';
    }
}

/* ── Type de client ─────────────────────────────────────── */
document.getElementById('clientType').addEventListener('change', function() {
    const type = this.value;
    document.getElementById('customerSection').classList.toggle('d-none', type !== 'customer');
    document.getElementById('resellerSection').classList.toggle('d-none', type !== 'reseller');
    document.getElementById('creditSection').classList.toggle('d-none', type !== 'reseller');

    if (type === 'reseller' && cart.length > 0) {
        const missing = cart.filter(i => {
            const p = products.find(p => p.id === i.product_id);
            return p && !p.reseller_price;
        });
        if (missing.length > 0) {
            alert('⚠️ Produits sans prix réparateur retirés:\n' + missing.map(i => i.name).join(', '));
            cart = cart.filter(i => {
                const p = products.find(p => p.id === i.product_id);
                return p && p.reseller_price;
            });
        }
    }

    cart.forEach(item => {
        const p = products.find(p => p.id === item.product_id);
        if (p) item.unit_price = getPriceForQuantity(p, item.quantity);
    });

    renderCart();
    renderProducts(document.getElementById('productSearch').value);
});

document.getElementById('resellerSelect').addEventListener('change', function() {
    const credit = this.options[this.selectedIndex]?.dataset?.credit || 0;
    document.getElementById('availableCredit').textContent = fmt(credit) + ' FCFA';
});

document.getElementById('productSearch').addEventListener('input', function() {
    renderProducts(this.value);
});

document.getElementById('paidAmount').addEventListener('input', calculateTotals);

/* ── Init ───────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    renderProducts('');
    renderCart();
});
</script>
@endpush
@endsection
