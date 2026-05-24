@extends('layouts.app')

@section('title', 'Paiement créance - ' . $reseller->company_name)

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cash-coin"></i> Paiement créance</h2>
    <a href="{{ route('cashier.reseller-payments.show', $reseller) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<!-- Info réparateur -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ $reseller->company_name }}</h5>
                <p class="mb-0 text-muted">{{ $reseller->contact_name }} - {{ $reseller->phone }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h6>Dette dans votre boutique</h6>
                <h3 id="currentDebt" data-value="{{ $shopDebt }}">{{ number_format($shopDebt, 0, ',', ' ') }} FCFA</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Total paiement</h6>
                <h3 id="totalPayment">0 FCFA</h3>
            </div>
        </div>
    </div>
</div>

<!-- Filtre par période -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <i class="bi bi-funnel"></i> Filtrer les factures par période
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('cashier.reseller-payments.create', $reseller) }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date de début</label>
                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date de fin</label>
                <input type="date" class="form-control" name="date_to" value="{{ $dateTo ?? '' }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrer
                </button>
                <a href="{{ route('cashier.reseller-payments.create', $reseller) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x"></i> Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Liste des factures de la période -->
@if(isset($filteredSales) && $filteredSales->count() > 0)
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <i class="bi bi-receipt"></i> Factures de la période sélectionnée
        @if($dateFrom || $dateTo)
            <span class="badge bg-light text-dark ms-2">
                {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '...' }} 
                - 
                {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '...' }}
            </span>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Facture</th>
                        <th>Date</th>
                        <th class="text-end">Montant total</th>
                        <th class="text-end">Déjà payé</th>
                        <th class="text-end">Reste à payer</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php $periodTotal = 0; @endphp
                    @foreach($filteredSales as $sale)
                        @php $periodTotal += $sale->amount_due; @endphp
                        <tr class="{{ (isset($selectedSale) && $selectedSale->id == $sale->id) ? 'table-primary' : '' }}">
                            <td>
                                <code>{{ $sale->invoice_number }}</code>
                            </td>
                            <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">{{ number_format($sale->total_amount, 0, ',', ' ') }} F</td>
                            <td class="text-end text-success">{{ number_format($sale->amount_paid, 0, ',', ' ') }} F</td>
                            <td class="text-end text-danger fw-bold">{{ number_format($sale->amount_due, 0, ',', ' ') }} F</td>
                            <td class="text-center">
                                <a href="{{ route('cashier.reseller-payments.create', ['reseller' => $reseller, 'sale_id' => $sale->id, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" 
                                   class="btn btn-sm {{ (isset($selectedSale) && $selectedSale->id == $sale->id) ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="bi bi-credit-card"></i> Payer cette facture
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="4" class="text-end">Total de la période:</th>
                        <th class="text-end text-danger">{{ number_format($periodTotal, 0, ',', ' ') }} FCFA</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@elseif($dateFrom || $dateTo)
<div class="alert alert-info mb-4">
    <i class="bi bi-info-circle"></i> Aucune facture impayée pour la période sélectionnée.
</div>
@endif

<!-- Formulaire de paiement pour une facture spécifique -->
@if(isset($selectedSale) && $selectedSale)
<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-credit-card"></i> Paiement de la facture {{ $selectedSale->invoice_number }}
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="bg-light p-3 rounded text-center">
                    <small class="text-muted">Montant total</small>
                    <h5>{{ number_format($selectedSale->total_amount, 0, ',', ' ') }} F</h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-success text-white p-3 rounded text-center">
                    <small>Déjà payé</small>
                    <h5>{{ number_format($selectedSale->amount_paid, 0, ',', ' ') }} F</h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-danger text-white p-3 rounded text-center">
                    <small>Reste à payer</small>
                    <h5 id="saleAmountDue" data-value="{{ $selectedSale->amount_due }}">{{ number_format($selectedSale->amount_due, 0, ',', ' ') }} F</h5>
                </div>
            </div>
        </div>
        
        <form action="{{ route('cashier.reseller-payments.store', $reseller) }}" method="POST" id="invoicePaymentForm">
            @csrf
            <input type="hidden" name="sale_id" value="{{ $selectedSale->id }}">
            <input type="hidden" name="payment_type" value="invoice_partial">
            
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Montant à payer (FCFA)</label>
                    <input type="number" class="form-control form-control-lg" name="cash_amount" id="invoiceCashAmount"
                           value="{{ $selectedSale->amount_due }}" min="1" max="{{ $selectedSale->amount_due }}" step="100" required>
                    <small class="text-muted">Max: {{ number_format($selectedSale->amount_due, 0, ',', ' ') }} FCFA</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mode de paiement</label>
                    <select class="form-select form-select-lg" name="payment_method_id" required>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notes (optionnel)</label>
                    <input type="text" class="form-control form-control-lg" name="notes" placeholder="Référence, commentaire...">
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-check-lg"></i> Enregistrer le paiement
                </button>
                <a href="{{ route('cashier.reseller-payments.create', ['reseller' => $reseller, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-x"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endif

<hr class="my-4">

<h4 class="mb-3"><i class="bi bi-wallet2"></i> Paiement global (toutes factures)</h4>

<form action="{{ route('cashier.reseller-payments.store', $reseller) }}" method="POST" id="paymentForm">
    @csrf
    
    <div class="row g-4">
        <!-- Paiement en espèces -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-cash"></i> Paiement en espèces
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Montant (FCFA)</label>
                        <input type="number" class="form-control form-control-lg" name="cash_amount" id="cashAmount"
                               value="0" min="0" max="{{ $shopDebt }}" step="100">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mode de paiement</label>
                        <select class="form-select" name="payment_method_id" id="paymentMethod">
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Optionnel..."></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Retour de produits -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning">
                    <i class="bi bi-box-arrow-in-left"></i> Retour de produits
                    <small class="float-end">(Optionnel)</small>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        <i class="bi bi-info-circle"></i> 
                        Le réparateur peut rendre des produits non vendus. La valeur sera déduite de sa dette.
                    </p>
                    
                    <div id="productReturns">
                        <!-- Les lignes de retour seront ajoutées ici -->
                    </div>
                    
                    <button type="button" class="btn btn-outline-warning w-100" id="addReturnBtn">
                        <i class="bi bi-plus-lg"></i> Ajouter un produit à retourner
                    </button>
                </div>
            </div>
            
            <!-- Récapitulatif des retours -->
            <div class="card mt-3 d-none" id="returnSummary">
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td>Valeur des retours:</td>
                            <td class="text-end fw-bold" id="returnTotal">0 FCFA</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Récapitulatif final -->
    <div class="card mt-4 border-primary">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-calculator"></i> Récapitulatif
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center">
                    <small class="text-muted">Espèces</small>
                    <h4 id="summaryCash">0 FCFA</h4>
                </div>
                <div class="col-md-1 text-center d-flex align-items-center justify-content-center">
                    <h3>+</h3>
                </div>
                <div class="col-md-3 text-center">
                    <small class="text-muted">Retour produits</small>
                    <h4 id="summaryReturn">0 FCFA</h4>
                </div>
                <div class="col-md-1 text-center d-flex align-items-center justify-content-center">
                    <h3>=</h3>
                </div>
                <div class="col-md-4 text-center">
                    <small class="text-muted">Total paiement</small>
                    <h3 class="text-success" id="summaryTotal">0 FCFA</h3>
                </div>
            </div>
            
            <div class="alert alert-info mt-3 mb-0" id="newDebtInfo">
                <strong>Nouvelle dette après paiement:</strong> 
                <span id="newDebt">{{ number_format($shopDebt, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-success btn-lg w-100" id="submitBtn" disabled>
                <i class="bi bi-check-lg"></i> Enregistrer le paiement
            </button>
        </div>
    </div>
</form>

<!-- Modal pour sélectionner un produit -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-search"></i> Sélectionner un produit à retourner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="productSearchInput" class="form-control"
                           placeholder="Rechercher un produit par nom…" autocomplete="off">
                </div>
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle"></i>
                    <strong>Produits retournables :</strong> tous les articles non encore retournés, y compris ceux des factures déjà soldées.
                    La valeur des retours est imputée sur les factures encore dues (de la plus ancienne à la plus récente).
                </div>
                
                <!-- Produits retournables : toutes les factures de la période (crédit ET payées) -->
                @php $hasAnyReturnableItem = false; @endphp
                @if($returnableSales->count() > 0)
                    @foreach($returnableSales as $sale)
                        @php
                            $returnableItems = [];
                            foreach ($sale->items as $item) {
                                $unitPrice = (float) $item->unit_price;
                                if ($unitPrice <= 0 || !$item->product) continue;

                                $alreadyReturned = \App\Models\ProductReturn::where('sale_item_id', $item->id)->sum('quantity');
                                $remainingQty    = max(0, $item->quantity - $alreadyReturned);
                                if ($remainingQty <= 0) continue;

                                $returnableItems[] = [
                                    'item'          => $item,
                                    'remaining_qty' => $remainingQty,
                                    'value'         => $remainingQty * $unitPrice,
                                ];
                            }
                        @endphp

                        @if(count($returnableItems) > 0)
                        @php $hasAnyReturnableItem = true; @endphp
                        <div class="card mb-3">
                            <div class="card-header bg-light py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="bi bi-receipt"></i>
                                        <code>{{ $sale->invoice_number }}</code>
                                        <small class="text-muted ms-1">{{ $sale->created_at->format('d/m/Y') }}</small>
                                    </span>
                                    @if($sale->amount_due > 0)
                                        <span class="badge bg-danger">Reste dû: {{ number_format($sale->amount_due, 0, ',', ' ') }} F</span>
                                    @else
                                        <span class="badge bg-success">Facture soldée</span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produit</th>
                                            <th>Prix unit.</th>
                                            <th>Qté disponible</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($returnableItems as $ri)
                                            @php $item = $ri['item']; @endphp
                                            <tr>
                                                <td>
                                                    <strong>{{ $item->product->name }}</strong>
                                                    @if($item->product->sku)
                                                        <br><small class="text-muted">{{ $item->product->sku }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ number_format($item->unit_price, 0, ',', ' ') }} F</td>
                                                <td>
                                                    <span class="badge bg-info">{{ $ri['remaining_qty'] }}</span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary select-product-btn"
                                                            data-product-id="{{ $item->product_id }}"
                                                            data-product-name="{{ $item->product->name }}"
                                                            data-unit-price="{{ $item->unit_price }}"
                                                            data-max-qty="{{ $ri['remaining_qty'] }}"
                                                            data-sale-id="{{ $sale->id }}"
                                                            data-sale-item-id="{{ $item->id }}"
                                                            data-sale-amount-due="{{ $sale->amount_due }}">
                                                        <i class="bi bi-plus"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    @endforeach

                    @if(!$hasAnyReturnableItem)
                        <p class="text-muted text-center">Aucun produit à retourner sur la période sélectionnée.</p>
                    @endif
                @else
                    <p class="text-muted text-center">Aucune facture trouvée pour cette période.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Template pour une ligne de retour -->
<template id="returnRowTemplate">
    <div class="return-row card mb-2">
        <div class="card-body p-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="hidden" name="returns[INDEX][product_id]" class="return-product-id">
                    <input type="hidden" name="returns[INDEX][sale_id]" class="return-sale-id">
                    <input type="hidden" name="returns[INDEX][sale_item_id]" class="return-sale-item-id">
                    <input type="hidden" name="returns[INDEX][unit_price]" class="return-unit-price">
                    <strong class="return-product-name">Produit</strong>
                    <br><small class="text-muted return-price-info">0 FCFA/unité</small>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm return-quantity" 
                           name="returns[INDEX][quantity]" min="1" value="1" placeholder="Qté">
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="returns[INDEX][condition]">
                        <option value="new">Neuf</option>
                        <option value="good" selected>Bon état</option>
                        <option value="damaged">Endommagé</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <span class="return-line-total fw-bold">0 FCFA</span>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-return-btn">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentDebt = parseFloat(document.getElementById('currentDebt').dataset.value);
    const cashAmountInput = document.getElementById('cashAmount');
    const productReturnsContainer = document.getElementById('productReturns');
    const addReturnBtn = document.getElementById('addReturnBtn');
    const productModal = new bootstrap.Modal(document.getElementById('productModal'));
    const returnRowTemplate = document.getElementById('returnRowTemplate');
    
    let returnIndex = 0;

    // Formatter les nombres
    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR').format(Math.round(amount)) + ' FCFA';
    }

    // Mettre à jour les totaux
    function updateTotals() {
        const cashAmount = parseFloat(cashAmountInput.value) || 0;
        let returnTotal = 0;

        // Calculer le total des retours
        document.querySelectorAll('.return-row').forEach(row => {
            const qty = parseInt(row.querySelector('.return-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.return-unit-price').value) || 0;
            const lineTotal = qty * price;
            row.querySelector('.return-line-total').textContent = formatMoney(lineTotal);
            returnTotal += lineTotal;
        });

        const totalPayment = cashAmount + returnTotal;
        const newDebt = currentDebt - totalPayment;

        // Mettre à jour l'affichage
        document.getElementById('summaryCash').textContent = formatMoney(cashAmount);
        document.getElementById('summaryReturn').textContent = formatMoney(returnTotal);
        document.getElementById('summaryTotal').textContent = formatMoney(totalPayment);
        document.getElementById('totalPayment').textContent = formatMoney(totalPayment);
        document.getElementById('returnTotal').textContent = formatMoney(returnTotal);
        document.getElementById('newDebt').textContent = formatMoney(Math.max(0, newDebt));

        // Afficher/masquer le récap retours
        document.getElementById('returnSummary').classList.toggle('d-none', returnTotal === 0);

        // Activer/désactiver le bouton
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = totalPayment <= 0 || totalPayment > currentDebt;

        // Alerte si dépassement
        if (totalPayment > currentDebt) {
            document.getElementById('newDebtInfo').classList.remove('alert-info');
            document.getElementById('newDebtInfo').classList.add('alert-danger');
            document.getElementById('newDebt').textContent = 'DÉPASSEMENT! Réduisez le montant.';
        } else {
            document.getElementById('newDebtInfo').classList.remove('alert-danger');
            document.getElementById('newDebtInfo').classList.add('alert-info');
        }
    }

    // Événement sur le montant espèces
    cashAmountInput.addEventListener('input', updateTotals);

    // Ouvrir la modal pour ajouter un produit
    addReturnBtn.addEventListener('click', function() {
        productModal.show();
    });

    // Sélectionner un produit depuis la modal
    document.querySelectorAll('.select-product-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            const unitPrice = parseFloat(this.dataset.unitPrice);
            const maxQty = parseInt(this.dataset.maxQty);
            const saleId = this.dataset.saleId;
            const saleItemId = this.dataset.saleItemId;

            addReturnRow(productId, productName, unitPrice, maxQty, saleId, saleItemId);
            productModal.hide();
        });
    });

    // Ajouter une ligne de retour
    function addReturnRow(productId, productName, unitPrice, maxQty, saleId, saleItemId) {
        const template = returnRowTemplate.content.cloneNode(true);
        const row = template.querySelector('.return-row');

        // Mettre à jour les attributs name avec l'index correct
        row.querySelectorAll('[name*="INDEX"]').forEach(input => {
            input.name = input.name.replace('INDEX', returnIndex);
        });

        // Remplir les valeurs des champs cachés
        const productIdField = row.querySelector('.return-product-id');
        const saleIdField = row.querySelector('.return-sale-id');
        const saleItemIdField = row.querySelector('.return-sale-item-id');
        const unitPriceField = row.querySelector('.return-unit-price');
        
        productIdField.value = productId;
        saleIdField.value = saleId || '';
        saleItemIdField.value = saleItemId || '';
        unitPriceField.value = unitPrice;
        
        row.querySelector('.return-product-name').textContent = productName;
        row.querySelector('.return-price-info').textContent = formatMoney(unitPrice) + '/unité';
        
        const qtyInput = row.querySelector('.return-quantity');
        qtyInput.max = maxQty;
        qtyInput.value = 1;
        qtyInput.addEventListener('input', updateTotals);

        // Bouton supprimer
        row.querySelector('.remove-return-btn').addEventListener('click', function() {
            row.remove();
            updateTotals();
        });

        // État du produit
        row.querySelector('select').addEventListener('change', updateTotals);

        productReturnsContainer.appendChild(row);
        returnIndex++;
        updateTotals();
    }

    // Initialisation
    updateTotals();

    // Recherche dans la liste des produits retournables
    document.getElementById('productSearchInput').addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        document.querySelectorAll('#productModal .card.mb-3').forEach(function (invoiceCard) {
            let invoiceHasMatch = false;
            invoiceCard.querySelectorAll('tbody tr').forEach(function (row) {
                const productName = row.querySelector('td strong')?.textContent.toLowerCase() ?? '';
                const match = !term || productName.includes(term);
                row.style.display = match ? '' : 'none';
                if (match) invoiceHasMatch = true;
            });
            invoiceCard.style.display = invoiceHasMatch ? '' : 'none';
        });
    });

    // Vider la recherche à chaque ouverture du modal
    document.getElementById('productModal').addEventListener('show.bs.modal', function () {
        const input = document.getElementById('productSearchInput');
        input.value = '';
        input.dispatchEvent(new Event('input'));
    });
});
</script>
@endsection
