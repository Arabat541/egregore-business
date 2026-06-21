@extends('layouts.app')

@section('title', 'Ajouter articles — Vente en attente')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cart-plus"></i> Vente en attente — Réparateur</h2>
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

<div class="row g-3">
    {{-- ── Colonne gauche : catalogue ─────────────────────── --}}
    <div class="col-md-7">

        {{-- Sélection du réparateur --}}
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-person-badge"></i> Réparateur
            </div>
            <div class="card-body">
                <form method="GET" id="resellerForm">
                    <div class="row g-2">
                        <div class="col-md-8 position-relative">
                            <input type="hidden" name="reseller_id" id="resellerIdInput" value="{{ $selectedReseller?->id }}">
                            <input type="text"
                                   class="form-control"
                                   id="resellerSearch"
                                   placeholder="Tapez un nom de réparateur..."
                                   autocomplete="off"
                                   value="{{ $selectedReseller ? $selectedReseller->company_name . ' — ' . $selectedReseller->contact_name : '' }}">
                            <div id="resellerDropdown" class="list-group position-absolute w-100 shadow" style="z-index:1050;max-height:220px;overflow-y:auto;display:none;"></div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100" id="resellerSubmitBtn" {{ $selectedReseller ? '' : 'disabled' }}>
                                <i class="bi bi-check"></i> Sélectionner
                            </button>
                        </div>
                    </div>
                </form>
                @php
                    $resellersJson = $resellers->map(fn($r) => [
                        'id' => $r->id,
                        'name' => $r->company_name . ' — ' . $r->contact_name,
                        'credit' => $r->available_credit,
                    ]);
                @endphp

                @if($selectedReseller)
                    <div class="mt-2 p-2 bg-light rounded d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $selectedReseller->company_name }}</strong>
                            <small class="text-muted ms-2">{{ $selectedReseller->contact_name }}</small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Crédit disponible</small>
                            <div class="fw-bold text-success">{{ number_format($selectedReseller->available_credit, 0, ',', ' ') }} FCFA</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if($selectedReseller)
        {{-- Catalogue produits --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-box-seam"></i> Produits disponibles</span>
                <input type="text" id="productSearch" class="form-control form-control-sm"
                       style="width: 260px;" placeholder="Rechercher nom, SKU, catégorie…" autofocus autocomplete="off">
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
                                <th class="text-center" style="width: 80px;">Remise</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="productsList">
                            <tr><td colspan="7" class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm me-2"></div>Chargement…
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Colonne droite : articles en attente ────────────── --}}
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cart"></i> Articles en attente</span>
                @if($pendingSale)
                    <span class="badge bg-dark">{{ $pendingSale->items->count() }}</span>
                @endif
            </div>
            <div class="card-body">
                @if(!$selectedReseller)
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-arrow-left-circle fs-1"></i>
                        <p class="mt-2">Sélectionnez d'abord un réparateur</p>
                    </div>
                @elseif(!$pendingSale || $pendingSale->items->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-cart-x fs-1"></i>
                        <p class="mt-2">Aucun article pour l'instant</p>
                    </div>
                @else
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-center">Qté</th>
                                    <th class="text-center">Remise</th>
                                    <th class="text-end">Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingSale->items as $item)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $item->product->name }}</span>
                                            <br><small class="text-muted">{{ number_format($item->unit_price, 0, ',', ' ') }}/u</small>
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('cashier.pending-sales.update-item', $item) }}" method="POST" class="d-inline" id="updateForm-{{ $item->id }}">
                                                @csrf
                                                @method('PUT')
                                                <input type="number" name="quantity" value="{{ $item->quantity }}"
                                                       class="form-control form-control-sm text-center"
                                                       style="width: 55px;" min="1"
                                                       onchange="document.getElementById('updateForm-{{ $item->id }}').submit()">
                                        </td>
                                        <td class="text-center">
                                                <input type="number" name="discount" value="{{ (int) $item->discount }}"
                                                       class="form-control form-control-sm text-center"
                                                       style="width: 65px;" min="0"
                                                       onchange="document.getElementById('updateForm-{{ $item->id }}').submit()">
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

                    @php
                        $lineDiscounts = $pendingSale->items->sum('discount');
                        $grossSubtotal = $pendingSale->items->sum(fn($i) => $i->unit_price * $i->quantity);
                    @endphp
                    <div class="border-top pt-2 mt-2">
                        @if($lineDiscounts > 0)
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Sous-total:</span>
                                <span>{{ number_format($grossSubtotal, 0, ',', ' ') }} FCFA</span>
                            </div>
                            <div class="d-flex justify-content-between text-success small mb-1">
                                <span>Remises:</span>
                                <span>- {{ number_format($lineDiscounts, 0, ',', ' ') }} FCFA</span>
                            </div>
                        @endif
                        <h4 class="text-end mb-2">
                            Total: <strong class="text-success">{{ number_format($pendingSale->total_amount, 0, ',', ' ') }} FCFA</strong>
                        </h4>
                        <a href="{{ route('cashier.pending-sales.show', $pendingSale) }}" class="btn btn-success w-100 btn-lg">
                            <i class="bi bi-check-circle"></i> Valider cette vente
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Formulaire caché pour l'ajout d'un article (soumis par JS) --}}
@if($selectedReseller)
<form id="addItemForm" action="{{ route('cashier.pending-sales.add-item') }}" method="POST" class="d-none">
    @csrf
    <input type="hidden" name="reseller_id" value="{{ $selectedReseller->id }}">
    <input type="hidden" name="product_id"  id="formProductId">
    <input type="hidden" name="quantity"    id="formQuantity">
    <input type="hidden" name="unit_price"  id="formUnitPrice">
    <input type="hidden" name="discount"    id="formDiscount">
</form>
@endif

@push('scripts')
<script>
const resellers = @json($resellersJson);
const searchInput = document.getElementById('resellerSearch');
const dropdown = document.getElementById('resellerDropdown');
const idInput = document.getElementById('resellerIdInput');
const submitBtn = document.getElementById('resellerSubmitBtn');

searchInput.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    idInput.value = '';
    submitBtn.disabled = true;
    if (q.length === 0) { dropdown.style.display = 'none'; return; }
    const matches = resellers.filter(r => r.name.toLowerCase().includes(q)).slice(0, 8);
    if (matches.length === 0) { dropdown.innerHTML = '<div class="list-group-item text-muted small">Aucun résultat</div>'; dropdown.style.display = 'block'; return; }
    dropdown.innerHTML = matches.map(r => `<button type="button" class="list-group-item list-group-item-action py-2" data-id="${r.id}" data-name="${r.name}">${r.name}</button>`).join('');
    dropdown.style.display = 'block';
});

searchInput.addEventListener('focus', function() {
    if (this.value.trim().length > 0 && !idInput.value) this.dispatchEvent(new Event('input'));
});

dropdown.addEventListener('click', function(e) {
    const item = e.target.closest('[data-id]');
    if (!item) return;
    idInput.value = item.dataset.id;
    searchInput.value = item.dataset.name;
    dropdown.style.display = 'none';
    submitBtn.disabled = false;
    submitBtn.click();
});

document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) dropdown.style.display = 'none';
});
</script>
@if($selectedReseller)
<script>
const products = @json($products);

/* ── Prix réparateur selon quantité ─────────────────────── */
function getResellerPrice(product, quantity) {
    const qW = parseInt(product.qty_wholesale_min) || 10;
    const qS = parseInt(product.qty_semi_wholesale_min) || 3;
    if (quantity >= qW) return parseFloat(product.wholesale_price || product.semi_wholesale_price || product.reseller_price || product.normal_price);
    if (quantity >= qS) return parseFloat(product.semi_wholesale_price || product.reseller_price || product.normal_price);
    return parseFloat(product.reseller_price || product.normal_price);
}

function fmt(n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n)); }

/* ── Rendu du catalogue ─────────────────────────────────── */
function renderProducts(filter) {
    const tbody  = document.getElementById('productsList');
    const search = (filter || '').toLowerCase().trim();

    const filtered = search.length === 0 ? products : products.filter(p =>
        p.name.toLowerCase().includes(search) ||
        (p.sku && p.sku.toLowerCase().includes(search)) ||
        (p.category && p.category.name && p.category.name.toLowerCase().includes(search))
    );

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-search"></i> Aucun produit trouvé</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(p => {
        const price    = getResellerPrice(p, 1);
        const stockCls = p.quantity_in_stock > 5 ? 'bg-success' : p.quantity_in_stock > 0 ? 'bg-warning text-dark' : 'bg-danger';
        const catName  = (p.category && p.category.name) ? p.category.name : '';
        const disabled = p.quantity_in_stock < 1;

        let tierInfo = '';
        if (p.semi_wholesale_price && p.qty_semi_wholesale_min) {
            tierInfo = `<small class="text-muted d-block">×${p.qty_semi_wholesale_min}: ${fmt(p.semi_wholesale_price)}</small>`;
        }

        return `<tr>
            <td>
                <div class="fw-semibold">${p.name}</div>
                ${p.sku ? `<small class="text-muted">[${p.sku}]</small>` : ''}
            </td>
            <td>${catName ? `<span class="badge bg-secondary">${catName}</span>` : '<span class="text-muted">—</span>'}</td>
            <td class="text-center"><span class="badge ${stockCls}">${p.quantity_in_stock}</span></td>
            <td class="text-end">
                <span id="price-${p.id}">${fmt(price)}</span> FCFA
                ${tierInfo}
            </td>
            <td class="text-center">
                <input type="number" id="qty-${p.id}" value="1" min="1" max="${p.quantity_in_stock}"
                       class="form-control form-control-sm text-center" style="width:60px;"
                       ${disabled ? 'disabled' : ''}
                       oninput="updateRowPrice(${p.id})">
            </td>
            <td class="text-center">
                <input type="number" id="disc-${p.id}" value="0" min="0"
                       class="form-control form-control-sm text-center" style="width:70px;"
                       ${disabled ? 'disabled' : ''}>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-primary"
                        onclick="addItem(${p.id})" ${disabled ? 'disabled' : ''}>
                    <i class="bi bi-plus-lg"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

function updateRowPrice(productId) {
    const p   = products.find(p => p.id === productId);
    const qty = parseInt(document.getElementById(`qty-${productId}`)?.value) || 1;
    const el  = document.getElementById(`price-${productId}`);
    if (p && el) el.textContent = fmt(getResellerPrice(p, qty));
}

function addItem(productId) {
    const p    = products.find(p => p.id === productId);
    if (!p) return;
    const qty      = parseInt(document.getElementById(`qty-${productId}`)?.value) || 1;
    const discount = parseFloat(document.getElementById(`disc-${productId}`)?.value) || 0;
    const price    = getResellerPrice(p, qty);

    document.getElementById('formProductId').value = productId;
    document.getElementById('formQuantity').value  = qty;
    document.getElementById('formUnitPrice').value = price;
    document.getElementById('formDiscount').value  = discount;
    document.getElementById('addItemForm').submit();
}

/* ── Search ─────────────────────────────────────────────── */
document.getElementById('productSearch').addEventListener('input', function() {
    renderProducts(this.value);
});

/* ── Init ───────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    renderProducts('');
});
</script>
@endif
@endpush
@endsection
