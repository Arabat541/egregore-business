@extends('layouts.app')

@section('title', 'Modifier vente ' . $sale->invoice_number)

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-pencil-square text-warning me-2"></i>Modifier la vente</h2>
        <small class="text-muted">
            <code>{{ $sale->invoice_number }}</code> —
            {{ $sale->created_at->format('d/m/Y à H:i') }} —
            <strong>{{ $sale->user->name ?? '-' }}</strong>
        </small>
    </div>
    <a href="{{ route('cashier.sales.show', $sale) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Annuler
    </a>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Attention :</strong> La modification recalcule le stock (remet les anciens articles en stock, déduit les nouveaux).
    Le montant payé <strong>{{ number_format($sale->amount_paid, 0, ',', ' ') }} FCFA</strong> ne change pas.
    Les modifications sont tracées dans les notes.
</div>

<form action="{{ route('cashier.sales.update', $sale) }}" method="POST" id="editForm">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-md-8">
            <!-- Articles -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-cart me-2"></i>Articles</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-center" style="width:100px">Qté</th>
                                    <th class="text-end" style="width:130px">Prix unit.</th>
                                    <th class="text-end" style="width:120px">Remise</th>
                                    <th class="text-end" style="width:130px">Total</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                @foreach($sale->items as $i => $item)
                                <tr class="item-row" data-index="{{ $i }}">
                                    <td>
                                        <strong>{{ $item->product->name ?? $item->product_id }}</strong>
                                        @if($item->product?->sku)
                                            <br><small class="text-muted">{{ $item->product->sku }}</small>
                                        @endif
                                        <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $item->product_id }}">
                                    </td>
                                    <td>
                                        <input type="number"
                                               name="items[{{ $i }}][quantity]"
                                               class="form-control form-control-sm text-center item-qty"
                                               value="{{ $item->quantity }}"
                                               min="1" required>
                                    </td>
                                    <td>
                                        <input type="number"
                                               name="items[{{ $i }}][unit_price]"
                                               class="form-control form-control-sm text-end item-price"
                                               value="{{ (int) $item->unit_price }}"
                                               min="0" required>
                                    </td>
                                    <td>
                                        <input type="number"
                                               name="items[{{ $i }}][discount]"
                                               class="form-control form-control-sm text-end item-discount"
                                               value="{{ (int) $item->discount }}"
                                               min="0">
                                    </td>
                                    <td class="text-end align-middle fw-bold item-total">
                                        {{ number_format($item->total_price, 0, ',', ' ') }}
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item"
                                                title="Supprimer cette ligne">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Ajouter un article -->
                    <hr>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-plus-circle me-1"></i>Ajouter un article</label>
                            <select id="productSelect" class="form-select">
                                <option value="">-- Rechercher un produit --</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}"
                                            data-name="{{ $p->name }}"
                                            data-sku="{{ $p->sku }}"
                                            data-price="{{ (int) $p->normal_price }}"
                                            data-stock="{{ $p->quantity_in_stock }}">
                                        {{ $p->name }}
                                        @if($p->sku) ({{ $p->sku }}) @endif
                                        — {{ number_format($p->normal_price, 0, ',', ' ') }} FCFA
                                        @if($p->quantity_in_stock <= 0)
                                            [RUPTURE]
                                        @else
                                            (stock: {{ $p->quantity_in_stock }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Qté</label>
                            <input type="number" id="newQty" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Prix unit.</label>
                            <input type="number" id="newPrice" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="addItemBtn" class="btn btn-outline-primary w-100">
                                <i class="bi bi-plus-lg"></i> Ajouter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Totaux -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-calculator me-1"></i>Totaux
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Sous-total :</td>
                            <td class="text-end fw-bold" id="displaySubtotal">—</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Remise globale :</td>
                            <td class="text-end">
                                <input type="number" name="discount_amount" id="discountAmount"
                                       class="form-control form-control-sm text-end"
                                       value="{{ (int) $sale->discount_amount }}" min="0"
                                       style="width:110px;display:inline-block">
                                <small class="text-muted">FCFA</small>
                            </td>
                        </tr>
                        <tr class="table-primary">
                            <td class="fw-bold">Nouveau total :</td>
                            <td class="text-end fw-bold fs-5 text-primary" id="displayTotal">—</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Déjà payé :</td>
                            <td class="text-end text-success">{{ number_format($sale->amount_paid, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Différence :</td>
                            <td class="text-end fw-bold" id="displayDiff">—</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Infos client -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-person me-1"></i>Client</div>
                <div class="card-body">
                    <p class="mb-1">
                        @if($sale->client_type === 'walk-in') Client comptoir
                        @elseif($sale->client_type === 'customer') Client enregistré
                        @else Réparateur
                        @endif
                    </p>
                    <strong>{{ $sale->client_name }}</strong>
                    @if($sale->payment_status === 'credit')
                        <span class="badge bg-warning ms-1">Crédit</span>
                    @endif
                </div>
            </div>

            <!-- Notes -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-sticky me-1"></i>Notes</div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="3"
                              placeholder="Notes sur la modification...">{{ $sale->notes }}</textarea>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn btn-warning w-100 btn-lg" id="submitBtn">
                <i class="bi bi-check-lg me-1"></i>Enregistrer les modifications
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
let rowIndex = {{ $sale->items->count() }};

function recalculate() {
    let subtotal = 0;
    document.querySelectorAll('#itemsBody .item-row').forEach(row => {
        const qty      = parseFloat(row.querySelector('.item-qty').value)  || 0;
        const price    = parseFloat(row.querySelector('.item-price').value) || 0;
        const discount = parseFloat(row.querySelector('.item-discount').value) || 0;
        const total    = (qty * price) - discount;
        row.querySelector('.item-total').textContent = total.toLocaleString('fr-FR') + ' FCFA';
        subtotal += total;
    });

    const globalDiscount = parseFloat(document.getElementById('discountAmount').value) || 0;
    const total          = subtotal - globalDiscount;
    const amountPaid     = {{ (int) $sale->amount_paid }};
    const diff           = total - amountPaid;

    document.getElementById('displaySubtotal').textContent = subtotal.toLocaleString('fr-FR') + ' FCFA';
    document.getElementById('displayTotal').textContent    = total.toLocaleString('fr-FR') + ' FCFA';

    const diffEl = document.getElementById('displayDiff');
    if (diff > 0) {
        diffEl.textContent  = '+' + diff.toLocaleString('fr-FR') + ' FCFA';
        diffEl.className    = 'text-end fw-bold text-danger'; // reste à payer
    } else if (diff < 0) {
        diffEl.textContent  = diff.toLocaleString('fr-FR') + ' FCFA';
        diffEl.className    = 'text-end fw-bold text-success'; // trop-perçu
    } else {
        diffEl.textContent  = 'Équilibré';
        diffEl.className    = 'text-end fw-bold text-success';
    }
}

// Recalcul sur changement d'un champ
document.getElementById('itemsBody').addEventListener('input', recalculate);
document.getElementById('discountAmount').addEventListener('input', recalculate);

// Supprimer une ligne
document.getElementById('itemsBody').addEventListener('click', function(e) {
    const btn = e.target.closest('.remove-item');
    if (!btn) return;
    const rows = document.querySelectorAll('#itemsBody .item-row');
    if (rows.length <= 1) {
        alert('La vente doit contenir au moins un article.');
        return;
    }
    btn.closest('tr').remove();
    recalculate();
});

// Ajouter un article
document.getElementById('addItemBtn').addEventListener('click', function() {
    const select = document.getElementById('productSelect');
    const option = select.options[select.selectedIndex];
    if (!option.value) {
        alert('Sélectionnez un produit.');
        return;
    }

    const qty   = parseInt(document.getElementById('newQty').value)   || 1;
    const price = parseInt(document.getElementById('newPrice').value);
    const finalPrice = price > 0 ? price : parseInt(option.dataset.price);

    const idx = rowIndex++;
    const row = document.createElement('tr');
    row.className = 'item-row';
    row.dataset.index = idx;
    row.innerHTML = `
        <td>
            <strong>${option.dataset.name}</strong>
            ${option.dataset.sku ? '<br><small class="text-muted">' + option.dataset.sku + '</small>' : ''}
            <input type="hidden" name="items[${idx}][product_id]" value="${option.value}">
        </td>
        <td>
            <input type="number" name="items[${idx}][quantity]"
                   class="form-control form-control-sm text-center item-qty"
                   value="${qty}" min="1" required>
        </td>
        <td>
            <input type="number" name="items[${idx}][unit_price]"
                   class="form-control form-control-sm text-end item-price"
                   value="${finalPrice}" min="0" required>
        </td>
        <td>
            <input type="number" name="items[${idx}][discount]"
                   class="form-control form-control-sm text-end item-discount"
                   value="0" min="0">
        </td>
        <td class="text-end align-middle fw-bold item-total">0 FCFA</td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Supprimer">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    document.getElementById('itemsBody').appendChild(row);
    select.value = '';
    document.getElementById('newQty').value = 1;
    document.getElementById('newPrice').value = 0;
    recalculate();
});

// Pré-remplir le prix quand on sélectionne un produit
document.getElementById('productSelect').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    if (option.value) {
        document.getElementById('newPrice').value = option.dataset.price;
    }
});

// Confirmation avant soumission si le total a changé
document.getElementById('editForm').addEventListener('submit', function(e) {
    const oldTotal = {{ (int) $sale->total_amount }};
    const subtotal = Array.from(document.querySelectorAll('.item-qty')).reduce((acc, inp, i) => {
        const row = inp.closest('tr');
        return acc + (parseFloat(inp.value)||0) * (parseFloat(row.querySelector('.item-price').value)||0)
               - (parseFloat(row.querySelector('.item-discount').value)||0);
    }, 0);
    const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
    const newTotal = subtotal - discount;

    if (newTotal !== oldTotal) {
        const diff = newTotal - oldTotal;
        const sign = diff > 0 ? '+' : '';
        if (!confirm(`Le total passe de ${oldTotal.toLocaleString('fr-FR')} à ${newTotal.toLocaleString('fr-FR')} FCFA (${sign}${diff.toLocaleString('fr-FR')} FCFA).\n\nConfirmer la modification ?`)) {
            e.preventDefault();
        }
    }
});

// Init
recalculate();
</script>
@endpush
