@extends('layouts.app')

@section('title', 'Nouvelle vente')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="mb-0"><i class="bi bi-cart-plus"></i> Nouvelle vente</h2>
    <span class="badge bg-secondary fs-6" id="cartCount">0 article</span>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('cashier.sales.store') }}" method="POST" id="saleForm">
    @csrf

    {{-- 1. Barre client ──────────────────────────────────────── --}}
    <div class="card mb-2 border-0 shadow-sm">
        <div class="card-body py-2">
            <div class="row align-items-center g-2">
                <div class="col-auto">
                    <select class="form-select form-select-sm" name="client_type" id="clientType" style="width:auto;">
                        <option value="walk-in">Client comptoir</option>
                        <option value="customer">Client enregistré</option>
                        <option value="reseller">Réparateur</option>
                    </select>
                </div>
                <div class="col-md-4 d-none" id="customerSection">
                    <div class="input-group input-group-sm">
                        <select class="form-select" name="customer_id" id="customerSelect">
                            <option value="">— Choisir un client —</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->full_name }} — {{ $customer->phone }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#newCustomerModal">
                            <i class="bi bi-person-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4 d-none" id="resellerSection">
                    <div class="position-relative">
                        <div class="input-group input-group-sm">
                            <input type="text" id="resellerSearch" class="form-control"
                                   placeholder="Rechercher réparateur..." autocomplete="off">
                            <span class="input-group-text fw-bold" id="availableCredit"
                                  style="min-width:110px; font-size:.82rem;">—</span>
                        </div>
                        <input type="hidden" name="reseller_id" id="resellerIdInput">
                        <div id="resellerDropdown"
                             style="display:none; position:absolute; top:100%; left:0; right:0; z-index:1050;
                                    background:#fff; border:1px solid #dee2e6; border-radius:0 0 6px 6px;
                                    max-height:240px; overflow-y:auto; box-shadow:0 4px 12px rgba(0,0,0,.1);">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Barre saisie produit (style Sage) ─────────────────── --}}
    <div class="card mb-2 border-0 shadow-sm">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="position-relative" id="productSearchWrapper">
                        <input type="text" id="productSearch" class="form-control"
                               placeholder="Référence, nom ou catégorie..." autofocus autocomplete="off">
                        <div id="productDropdown"
                             style="display:none; position:absolute; top:100%; left:0; right:0; z-index:1050;
                                    background:#fff; border:1px solid #dee2e6; border-radius:0 0 6px 6px;
                                    max-height:320px; overflow-y:auto; box-shadow:0 4px 12px rgba(0,0,0,.1);">
                        </div>
                    </div>
                </div>
                <div class="col-auto d-flex align-items-center gap-2">
                    <label class="mb-0 text-muted small fw-semibold">Qté</label>
                    <input type="number" id="qtyInput" class="form-control text-center"
                           value="1" min="1" style="width:75px;">
                </div>
            </div>
        </div>
    </div>

    {{-- Alerte panier ────────────────────────────────────────── --}}
    <div id="cartAlert" class="alert alert-danger alert-dismissible py-2 small mb-2" style="display:none">
        <button type="button" class="btn-close btn-sm" onclick="hideCartAlert()"></button>
        <span id="cartAlertMsg"></span>
    </div>

    {{-- 3. Tableau articles (style Sage) ─────────────────────── --}}
    <div class="card mb-2 border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead style="background:#2c3e50; color:#fff;">
                    <tr>
                        <th style="width:12%;">Référence</th>
                        <th>Désignation</th>
                        <th class="text-center" style="width:95px;">Quantité</th>
                        <th class="text-end" style="width:130px;">P.U. TTC</th>
                        <th class="text-end" style="width:140px;">Montant TTC</th>
                        <th style="width:42px;"></th>
                    </tr>
                </thead>
                <tbody id="cartItems">
                    <tr id="emptyCartRow">
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-cart3 fs-2 d-block mb-2"></i>
                            Aucun article — recherchez un produit ci-dessus
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- 4. Barre paiement + Total TTC (style Sage) ───────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="row g-3 align-items-start">

                {{-- Remise + mode + notes --}}
                <div class="col-md-4">
                    <div class="mb-2">
                        <label class="form-label form-label-sm mb-1">Remise globale (FCFA)</label>
                        <input type="number" class="form-control form-control-sm" name="discount_amount"
                               id="discountAmount" value="0" min="0" step="100" oninput="calculateTotals()">
                        <div id="discountError" class="text-danger small mt-1" style="display:none"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm mb-1">Mode de paiement</label>
                        <select class="form-select form-select-sm" name="payment_method_id" id="paymentMethod" required>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check mb-2 d-none" id="creditSection">
                        <input class="form-check-input" type="checkbox" name="is_credit" id="isCredit" value="1">
                        <label class="form-check-label small">Vente à crédit</label>
                    </div>
                    <div>
                        <label class="form-label form-label-sm mb-1">Notes</label>
                        <textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Notes optionnelles…"></textarea>
                    </div>
                </div>

                {{-- Montant reçu + monnaie --}}
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1 fw-semibold">Montant reçu (FCFA)</label>
                    <input type="number" class="form-control form-control-lg" name="paid_amount"
                           id="paidAmount" value="0" min="0">
                    <div id="changeSection" class="mt-3 p-3 rounded text-center" style="display:none;">
                        <div class="text-muted small mb-1">A rendre</div>
                        <div class="fw-bold text-success" style="font-size:1.8rem;" id="changeAmount">0 FCFA</div>
                    </div>
                </div>

                {{-- Total TTC (grand affichage Sage) --}}
                <div class="col-md-3">
                    <div id="subtotalRow" style="display:none;">
                        <div class="d-flex justify-content-between text-muted small mb-1">
                            <span>Sous-total</span><span id="subtotalAmount">0 FCFA</span>
                        </div>
                        <div class="d-flex justify-content-between text-success small mb-2" id="discountRow" style="display:none;">
                            <span>Remise</span><span id="discountDisplay">- 0 FCFA</span>
                        </div>
                    </div>
                    <div class="rounded p-4 text-center" style="background:#e8f4fd; border:2px solid #0d6efd;">
                        <div class="text-primary small fw-semibold mb-1 text-uppercase tracking-wide">Total TTC</div>
                        <div class="fw-bold text-primary" style="font-size:2.2rem; line-height:1;" id="totalAmount">0 FCFA</div>
                    </div>
                    <input type="hidden" name="total_amount" id="totalAmountInput" value="0">
                </div>

                {{-- Boutons --}}
                <div class="col-md-2 d-flex flex-column gap-2 pt-md-4">
                    <button type="submit" class="btn btn-success btn-lg fw-bold" id="submitSale" disabled>
                        <i class="bi bi-check-lg"></i> Valider
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                        <i class="bi bi-trash"></i> Vider
                    </button>
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
const resellersData = @json($resellersData);
let cart = [];

/* ── Formatage ──────────────────────────────────────────── */
function fmt(n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n)); }
function esc(str) {
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
function showCartAlert(msg, type = 'danger') {
    const el = document.getElementById('cartAlert');
    el.className = `alert alert-${type} alert-dismissible mx-2 mt-2 py-2 small mb-0`;
    document.getElementById('cartAlertMsg').textContent = msg;
    el.style.display = 'block';
}
function hideCartAlert() { document.getElementById('cartAlert').style.display = 'none'; }

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

/* ── Recherche produit (dropdown) ───────────────────────── */
(function() {
    const searchEl  = document.getElementById('productSearch');
    const dropdown  = document.getElementById('productDropdown');

    function clientType() { return document.getElementById('clientType').value; }

    function renderProductDropdown(list) {
        if (list.length === 0) {
            dropdown.innerHTML = '<div class="px-3 py-2 text-muted small">Aucun produit trouvé</div>';
        } else {
            const type = clientType();
            dropdown.innerHTML = list.slice(0, 30).map(p => {
                const price    = getPriceForQuantity(p, 1, type);
                const inCart   = cart.find(i => i.product_id === p.id);
                const stockCls = p.quantity_in_stock > 5 ? 'text-success' : p.quantity_in_stock > 0 ? 'text-warning' : 'text-danger';
                const catName  = (p.category && p.category.name) ? esc(p.category.name) : '';
                const disabled = p.quantity_in_stock < 1;
                return `<div class="px-3 py-2 product-dd-item d-flex justify-content-between align-items-center"
                             style="cursor:${disabled ? 'not-allowed' : 'pointer'}; border-bottom:1px solid #f0f0f0;
                                    opacity:${disabled ? '.5' : '1'}; ${inCart ? 'background:#f0fff4;' : ''}"
                             data-id="${p.id}" data-disabled="${disabled ? '1' : '0'}">
                    <div>
                        <div class="fw-semibold small">${esc(p.name)}${inCart ? ' <span class="badge bg-success ms-1">✓ panier</span>' : ''}</div>
                        <div class="text-muted" style="font-size:.78rem;">
                            ${p.sku ? `<span class="me-2 font-monospace">${esc(p.sku)}</span>` : ''}
                            ${catName ? `<span class="badge bg-secondary me-1">${catName}</span>` : ''}
                            <span class="${stockCls}"><i class="bi bi-layers"></i> ${p.quantity_in_stock}</span>
                        </div>
                    </div>
                    <div class="text-end text-nowrap ms-3">
                        <div class="fw-semibold small">${fmt(price)} FCFA</div>
                        <span class="badge ${disabled ? 'bg-secondary' : 'bg-primary'}">${disabled ? 'Rupture' : '+ Ajouter'}</span>
                    </div>
                </div>`;
            }).join('');
            dropdown.querySelectorAll('.product-dd-item').forEach(el => {
                el.addEventListener('mousedown', function() {
                    if (this.dataset.disabled === '1') return;
                    const product = products.find(p => p.id === parseInt(this.dataset.id));
                    if (product) {
                        const qtyEl = document.getElementById('qtyInput');
                        const qty   = Math.max(1, parseInt(qtyEl?.value) || 1);
                        addToCart(product, qty);
                        if (qtyEl) qtyEl.value = 1;
                        searchEl.value = '';
                        dropdown.style.display = 'none';
                        searchEl.focus();
                    }
                });
            });
        }
        dropdown.style.display = 'block';
    }

    window.refreshProductDropdown = function() {
        const q = searchEl.value.trim();
        if (q.length > 0) renderProductDropdown(filterProducts(q));
    };

    searchEl.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        if (!q) { dropdown.style.display = 'none'; return; }
        renderProductDropdown(filterProducts(q));
    });

    searchEl.addEventListener('focus', function() {
        if (this.value.trim()) renderProductDropdown(filterProducts(this.value.trim().toLowerCase()));
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('#productSearchWrapper')) dropdown.style.display = 'none';
    });

    document.head.insertAdjacentHTML('beforeend', `<style>
        .product-dd-item:hover { background:#f8f9fa !important; }
    </style>`);
})();

function filterProducts(q) {
    return products.filter(p =>
        p.name.toLowerCase().includes(q) ||
        (p.sku && p.sku.toLowerCase().includes(q)) ||
        (p.category && p.category.name && p.category.name.toLowerCase().includes(q))
    );
}

/* ── Panier ─────────────────────────────────────────────── */

function addToCart(product, qty = 1) {
    const clientType = document.getElementById('clientType').value;
    if (clientType === 'reseller' && !product.reseller_price) {
        showCartAlert(`"${product.name}" n'a pas de prix réparateur défini.`);
        return;
    }
    const idx = cart.findIndex(i => i.product_id === product.id);
    if (idx !== -1) {
        const item = cart[idx];
        const newQty = item.quantity + qty;
        if (newQty > product.quantity_in_stock) { showCartAlert('Stock insuffisant !'); return; }
        item.quantity   = newQty;
        item.unit_price = getPriceForQuantity(product, newQty);
        cart.splice(idx, 1);
        cart.unshift(item);
    } else {
        if (product.quantity_in_stock < 1) { showCartAlert('Rupture de stock !'); return; }
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
    if (window.refreshProductDropdown) refreshProductDropdown();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
    if (window.refreshProductDropdown) refreshProductDropdown();
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
    cart = [];
    renderCart();
}

function renderCart() {
    const tbody      = document.getElementById('cartItems');
    const cartInputs = document.getElementById('cartInputs');
    const countEl    = document.getElementById('cartCount');

    const total = cart.reduce((s, i) => s + i.quantity, 0);
    countEl.textContent = total + (total <= 1 ? ' article' : ' articles');

    if (cart.length === 0) {
        tbody.innerHTML = `<tr id="emptyCartRow">
            <td colspan="6" class="text-center text-muted py-5">
                <i class="bi bi-cart3 fs-2 d-block mb-2"></i>
                Aucun article — recherchez un produit ci-dessus
            </td>
        </tr>`;
        cartInputs.innerHTML = '';
        document.getElementById('submitSale').disabled = true;
        calculateTotals();
        return;
    }

    document.getElementById('submitSale').disabled = false;

    tbody.innerHTML = cart.map((item, i) => {
        const product = products.find(p => p.id === item.product_id);
        const sku     = product?.sku || '—';
        const label   = product ? getPriceLabel(item.quantity, product) : '';
        return `<tr>
            <td class="font-monospace small text-muted">${esc(sku)}</td>
            <td class="small">${esc(item.name)}${label}</td>
            <td class="text-center">
                <input type="number" class="form-control form-control-sm text-center"
                       value="${item.quantity}" min="1" max="${item.max_stock}"
                       onchange="updateQuantity(${i}, this.value)" style="width:65px;">
            </td>
            <td class="text-end small text-muted text-nowrap">${fmt(item.unit_price)} FCFA</td>
            <td class="text-end small fw-bold text-nowrap">${fmt(item.quantity * item.unit_price)} FCFA</td>
            <td class="text-center">
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
            showCartAlert('Produits sans prix réparateur retirés : ' + missing.map(i => i.name).join(', '));
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
});

/* ── Recherche réparateur ───────────────────────────────── */
(function() {
    const searchEl   = document.getElementById('resellerSearch');
    const dropdown   = document.getElementById('resellerDropdown');
    const hiddenId   = document.getElementById('resellerIdInput');
    const creditEl   = document.getElementById('availableCredit');

    function renderDropdown(list) {
        if (list.length === 0) {
            dropdown.innerHTML = '<div class="px-3 py-2 text-muted small">Aucun réparateur trouvé</div>';
        } else {
            dropdown.innerHTML = list.map(r => `
                <div class="px-3 py-2 reseller-item"
                     style="cursor:pointer; border-bottom:1px solid #f0f0f0;"
                     data-id="${r.id}"
                     data-name="${esc(r.company_name)}"
                     data-credit="${r.credit}">
                    <div class="fw-semibold small">${esc(r.company_name)}</div>
                    <div class="text-muted" style="font-size:.78rem;">
                        ${esc(r.contact_name)} · ${esc(r.phone)}
                        &nbsp;·&nbsp;<span class="text-${r.credit > 0 ? 'success' : 'danger'}">${fmt(r.credit)} FCFA crédit</span>
                    </div>
                </div>`).join('');
            dropdown.querySelectorAll('.reseller-item').forEach(el => {
                el.addEventListener('mousedown', function() {
                    pickReseller(parseInt(this.dataset.id), this.dataset.name, parseFloat(this.dataset.credit));
                });
            });
        }
        dropdown.style.display = 'block';
    }

    function clearSelection() {
        hiddenId.value      = '';
        creditEl.textContent = '—';
        creditEl.className   = 'input-group-text fw-bold';
    }

    searchEl.addEventListener('focus', () => renderDropdown(resellersData));

    searchEl.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        if (!q) { clearSelection(); renderDropdown(resellersData); return; }
        renderDropdown(resellersData.filter(r =>
            r.company_name.toLowerCase().includes(q) ||
            r.contact_name.toLowerCase().includes(q) ||
            r.phone.includes(q)
        ));
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('#resellerSection')) dropdown.style.display = 'none';
    });

    // hover styles via CSS class injection
    document.head.insertAdjacentHTML('beforeend', `<style>
        .reseller-item:hover { background:#f8f9fa; }
    </style>`);
})();

window.pickReseller = function(id, name, credit) {
    document.getElementById('resellerIdInput').value = id;
    document.getElementById('resellerSearch').value  = name;
    document.getElementById('resellerDropdown').style.display = 'none';
    const el = document.getElementById('availableCredit');
    el.textContent  = fmt(credit) + ' FCFA';
    el.className    = 'input-group-text fw-bold ' + (credit > 0 ? 'text-success' : 'text-danger');
};

document.getElementById('paidAmount').addEventListener('input', calculateTotals);

/* ── Init ───────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    renderCart();
});
</script>
@endpush
@endsection
