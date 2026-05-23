@extends('layouts.app')

@section('title', 'Initier un transfert de stock')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-arrow-right text-warning me-2"></i> Initier un transfert de stock</h2>
    <a href="{{ route('cashier.stock-transfers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('cashier.stock-transfers.store') }}" method="POST" id="transferForm">
    @csrf

    <div class="row g-3">
        {{-- ── Colonne gauche : catalogue ─────────────────────── --}}
        <div class="col-md-8">

            {{-- Boutiques --}}
            <div class="card mb-3">
                <div class="card-header fw-semibold">
                    <i class="bi bi-shop"></i> Boutiques
                </div>
                <div class="card-body">
                    <div class="row align-items-end g-3">
                        <div class="col-md-5">
                            <label class="form-label">Boutique source</label>
                            <div class="form-control bg-light fw-semibold text-primary">
                                <i class="bi bi-shop me-1"></i> {{ Auth::user()->shop->name }}
                            </div>
                        </div>
                        <div class="col-md-2 text-center pb-1">
                            <i class="bi bi-arrow-right fs-2 text-primary"></i>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Boutique destination <span class="text-danger">*</span></label>
                            <select name="to_shop_id" id="to_shop_id"
                                    class="form-select @error('to_shop_id') is-invalid @enderror" required>
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

            {{-- Catalogue produits --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-seam"></i> Produits disponibles</span>
                    <input type="text" id="productSearch" class="form-control form-control-sm"
                           style="width: 260px;" placeholder="Rechercher nom, SKU, catégorie…" autocomplete="off">
                </div>
                <div class="card-body p-0">
                    <div id="productsLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Chargement des produits…</p>
                    </div>
                    <div id="productsContainer" class="d-none">
                        <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="sticky-top bg-white border-bottom">
                                    <tr>
                                        <th>Produit</th>
                                        <th>Catégorie</th>
                                        <th class="text-center">Stock</th>
                                        <th class="text-center" style="width: 80px;">Qté</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="productsList"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div class="card mt-3">
                <div class="card-header fw-semibold"><i class="bi bi-chat-left-text"></i> Notes</div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="2"
                              placeholder="Motif du transfert, instructions particulières…">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── Colonne droite : sélection + résumé ──────────────── --}}
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 16px;">
                <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-check"></i> Articles sélectionnés</span>
                    <span class="badge bg-primary" id="itemCount">0</span>
                </div>
                <div class="card-body p-2">
                    <div class="text-center text-muted py-4" id="summaryEmpty">
                        <i class="bi bi-cart fs-2"></i>
                        <p class="mb-0 small mt-1">Aucun produit sélectionné</p>
                    </div>
                    <div id="summaryContainer" style="display:none; max-height: 320px; overflow-y: auto;">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-center">Qté</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="summaryItems"></tbody>
                        </table>
                    </div>
                </div>
                <div class="card-body border-top py-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Lignes:</span>
                        <span id="summaryLines">0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted small">Quantité totale:</span>
                        <strong id="summaryQty">0</strong>
                    </div>

                    <div class="alert alert-info small py-2 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        La demande sera transmise à l'administrateur pour validation.
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg" id="submitBtn" disabled>
                        <i class="bi bi-send me-1"></i> Envoyer la demande
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Champs cachés générés par JS --}}
    <div id="hiddenInputs"></div>
</form>

@push('scripts')
<script>
let products    = [];
let selected    = {};   // { productId: { id, name, category, quantity, max } }

const toShopSelect      = document.getElementById('to_shop_id');
const productSearch     = document.getElementById('productSearch');
const productsLoading   = document.getElementById('productsLoading');
const productsContainer = document.getElementById('productsContainer');
const productsList      = document.getElementById('productsList');
const submitBtn         = document.getElementById('submitBtn');
const hiddenInputs      = document.getElementById('hiddenInputs');

/* ── Chargement produits via AJAX ───────────────────────── */
fetch('{{ route('cashier.stock-transfers.my-products') }}')
    .then(r => r.json())
    .then(data => {
        products = data;
        productsLoading.classList.add('d-none');
        productsContainer.classList.remove('d-none');
        renderProducts('');
    })
    .catch(() => {
        productsLoading.innerHTML = '<div class="text-danger py-3 text-center"><i class="bi bi-exclamation-triangle"></i> Erreur de chargement</div>';
    });

/* ── Filtre search ──────────────────────────────────────── */
productSearch.addEventListener('input', function () {
    renderProducts(this.value);
});

/* ── Rendu du tableau produits ──────────────────────────── */
function renderProducts(filter) {
    const search   = (filter || '').toLowerCase().trim();
    const filtered = search.length === 0 ? products : products.filter(p =>
        p.name.toLowerCase().includes(search) ||
        (p.sku  && p.sku.toLowerCase().includes(search)) ||
        (p.category && p.category.toLowerCase().includes(search))
    );

    if (filtered.length === 0) {
        productsList.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">'
            + '<i class="bi bi-search"></i> Aucun produit trouvé</td></tr>';
        return;
    }

    productsList.innerHTML = filtered.map(p => {
        const inSel    = selected[p.id];
        const stockCls = p.quantity > 5 ? 'bg-success' : p.quantity > 0 ? 'bg-warning text-dark' : 'bg-danger';
        return `<tr class="${inSel ? 'table-success' : ''}">
            <td>
                <div class="fw-semibold">${p.name}</div>
                ${p.sku ? `<small class="text-muted">[${p.sku}]</small>` : ''}
            </td>
            <td><span class="badge bg-secondary">${p.category || '—'}</span></td>
            <td class="text-center"><span class="badge ${stockCls}">${p.quantity}</span></td>
            <td class="text-center">
                <input type="number" id="qty-${p.id}"
                       value="${inSel ? inSel.quantity : 1}"
                       min="1" max="${p.quantity}"
                       class="form-control form-control-sm text-center"
                       style="width:65px;" ${p.quantity === 0 ? 'disabled' : ''}>
            </td>
            <td>
                <button type="button"
                        class="btn btn-sm ${inSel ? 'btn-success' : 'btn-outline-primary'}"
                        onclick="addProduct(${p.id})" ${p.quantity === 0 ? 'disabled' : ''}>
                    <i class="bi ${inSel ? 'bi-check-lg' : 'bi-plus-lg'}"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

/* ── Ajouter / mettre à jour un produit ─────────────────── */
function addProduct(productId) {
    const p   = products.find(p => p.id === productId);
    if (!p) return;
    const qty = parseInt(document.getElementById(`qty-${productId}`)?.value) || 1;
    if (qty < 1 || qty > p.quantity) return;

    selected[productId] = { id: p.id, name: p.name, category: p.category, quantity: qty, max: p.quantity };
    renderSummary();
    renderProducts(productSearch.value);
}

/* ── Retirer du résumé ──────────────────────────────────── */
function removeProduct(productId) {
    delete selected[productId];
    renderSummary();
    renderProducts(productSearch.value);
}

/* ── Modifier la quantité depuis le résumé ──────────────── */
function updateSummaryQty(productId, val) {
    const qty = parseInt(val);
    if (selected[productId] && qty >= 1 && qty <= selected[productId].max) {
        selected[productId].quantity = qty;
        renderSummary();
        renderProducts(productSearch.value);
    }
}

/* ── Rendu du résumé (colonne droite) ───────────────────── */
function renderSummary() {
    const items = Object.values(selected);
    const count = items.length;
    const total = items.reduce((s, i) => s + i.quantity, 0);

    document.getElementById('itemCount').textContent   = count;
    document.getElementById('summaryLines').textContent = count;
    document.getElementById('summaryQty').textContent  = total;

    const empty     = document.getElementById('summaryEmpty');
    const container = document.getElementById('summaryContainer');
    const tbody     = document.getElementById('summaryItems');

    if (count === 0) {
        empty.style.display     = 'block';
        container.style.display = 'none';
        hiddenInputs.innerHTML  = '';
        submitBtn.disabled      = true;
        return;
    }

    empty.style.display     = 'none';
    container.style.display = 'block';
    submitBtn.disabled      = !toShopSelect.value;

    tbody.innerHTML = items.map(item => `
        <tr>
            <td class="small">
                <div class="fw-semibold">${item.name}</div>
                <small class="text-muted">${item.category || ''}</small>
            </td>
            <td class="text-center">
                <input type="number" class="form-control form-control-sm text-center"
                       value="${item.quantity}" min="1" max="${item.max}"
                       onchange="updateSummaryQty(${item.id}, this.value)"
                       style="width:60px;">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeProduct(${item.id})">
                    <i class="bi bi-x"></i>
                </button>
            </td>
        </tr>
    `).join('');

    hiddenInputs.innerHTML = items.map((item, i) => `
        <input type="hidden" name="items[${i}][product_id]" value="${item.id}">
        <input type="hidden" name="items[${i}][quantity]" value="${item.quantity}">
    `).join('');
}

/* ── Boutique destination ───────────────────────────────── */
toShopSelect.addEventListener('change', function () {
    submitBtn.disabled = !this.value || Object.keys(selected).length === 0;
});

/* ── Init ───────────────────────────────────────────────── */
renderSummary();
</script>
@endpush
@endsection
