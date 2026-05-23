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

    <div class="row">
        {{-- COLONNE PRINCIPALE --}}
        <div class="col-md-8">
            {{-- Boutiques --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold">
                    <i class="bi bi-shop"></i> Boutiques
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="form-label">Boutique source</label>
                            <div class="form-control bg-light fw-semibold text-primary">
                                <i class="bi bi-shop me-1"></i>
                                {{ Auth::user()->shop->name }}
                            </div>
                            {{-- Valeur utilisée côté JS pour filtrer les produits --}}
                        </div>
                        <div class="col-md-2 text-center">
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

            {{-- Articles --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-ul"></i> Articles à transférer</span>
                    <button type="button" class="btn btn-sm btn-success" id="addItemBtn" disabled>
                        <i class="bi bi-plus-circle"></i> Ajouter un article
                    </button>
                </div>
                <div class="card-body">
                    <div id="itemsContainer">
                        {{-- Lignes générées dynamiquement --}}
                    </div>
                    <div id="emptyMsg" class="text-center text-muted py-3">
                        <i class="bi bi-info-circle"></i> Les produits se chargent automatiquement après sélection de la boutique destination.
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold"><i class="bi bi-chat-left-text"></i> Notes</div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="3" placeholder="Motif du transfert, instructions particulières...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- COLONNE RÉSUMÉ --}}
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 80px;">
                <div class="card-header fw-semibold"><i class="bi bi-receipt"></i> Résumé</div>
                <div class="card-body">
                    <p class="text-muted small mb-1">Nombre d'articles</p>
                    <h4 id="summaryItems" class="mb-3">0 ligne(s)</h4>
                    <p class="text-muted small mb-1">Quantité totale</p>
                    <h4 id="summaryQty" class="mb-3">0</h4>

                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-1"></i>
                        La demande sera transmise à l'administrateur pour validation et expédition. Le stock sera déduit au moment de l'expédition.
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg" id="submitBtn" disabled>
                        <i class="bi bi-send me-1"></i> Envoyer la demande
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Template ligne article (cloné par JS) --}}
<template id="itemRowTemplate">
    <div class="item-row border rounded p-3 mb-3 bg-light position-relative">
        <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 remove-item">
            <i class="bi bi-x-lg"></i>
        </button>
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Produit <span class="text-danger">*</span></label>
                <select name="items[__IDX__][product_id]" class="form-select product-select" required>
                    <option value="">Sélectionner un produit...</option>
                </select>
                <small class="text-muted stock-info"></small>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Quantité <span class="text-danger">*</span></label>
                <input type="number" name="items[__IDX__][quantity]" class="form-control quantity-input" min="1" value="1" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Note</label>
                <input type="text" name="items[__IDX__][notes]" class="form-control" placeholder="Optionnel">
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
let products = [];
let itemIndex = 0;

const addItemBtn   = document.getElementById('addItemBtn');
const submitBtn    = document.getElementById('submitBtn');
const container    = document.getElementById('itemsContainer');
const emptyMsg     = document.getElementById('emptyMsg');
const toShopSelect = document.getElementById('to_shop_id');

// Charger les produits dès la page (boutique source = fixe)
fetch('{{ route('cashier.stock-transfers.my-products') }}')
    .then(r => r.json())
    .then(data => {
        products = data;
        if (toShopSelect.value) enableForm();
    });

toShopSelect.addEventListener('change', function () {
    if (this.value) enableForm();
    else disableForm();
});

function enableForm() {
    addItemBtn.disabled = false;
    submitBtn.disabled  = false;
    emptyMsg.innerHTML  = '<i class="bi bi-plus-circle me-1 text-success"></i> Cliquez sur <strong>Ajouter un article</strong> pour commencer.';
    if (container.querySelectorAll('.item-row').length === 0) addItemRow();
}

function disableForm() {
    addItemBtn.disabled = true;
    submitBtn.disabled  = true;
}

addItemBtn.addEventListener('click', addItemRow);

function addItemRow() {
    const tpl  = document.getElementById('itemRowTemplate').innerHTML.replaceAll('__IDX__', itemIndex++);
    const div  = document.createElement('div');
    div.innerHTML = tpl;
    const row  = div.firstElementChild;

    // Remplir le select produit
    const sel = row.querySelector('.product-select');
    products.forEach(p => {
        const opt = new Option(`${p.name} (${p.sku ?? ''}) — stock: ${p.quantity}`, p.id);
        opt.dataset.stock = p.quantity;
        sel.appendChild(opt);
    });

    // Afficher le stock dispo sur changement
    sel.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const info = row.querySelector('.stock-info');
        if (this.value) {
            info.textContent = `Disponible : ${opt.dataset.stock}`;
            row.querySelector('.quantity-input').max = opt.dataset.stock;
        } else {
            info.textContent = '';
        }
        updateSummary();
    });

    row.querySelector('.quantity-input').addEventListener('input', updateSummary);

    // Supprimer une ligne
    row.querySelector('.remove-item').addEventListener('click', function () {
        row.remove();
        updateSummary();
        if (container.querySelectorAll('.item-row').length === 0) {
            emptyMsg.innerHTML = '<i class="bi bi-plus-circle me-1 text-success"></i> Cliquez sur <strong>Ajouter un article</strong> pour commencer.';
        }
    });

    container.appendChild(row);
    emptyMsg.style.display = 'none';
    updateSummary();
}

function updateSummary() {
    const rows = container.querySelectorAll('.item-row');
    let totalQty = 0;
    rows.forEach(r => {
        const qty = parseInt(r.querySelector('.quantity-input').value) || 0;
        totalQty += qty;
    });
    document.getElementById('summaryItems').textContent = rows.length + ' ligne(s)';
    document.getElementById('summaryQty').textContent   = totalQty;
    submitBtn.disabled = rows.length === 0 || !toShopSelect.value;
}
</script>
@endpush
@endsection
