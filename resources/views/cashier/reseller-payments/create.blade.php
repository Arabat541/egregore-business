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

<!-- Info revendeur -->
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
                <h6>Dette actuelle</h6>
                <h3 id="currentDebt" data-value="{{ $reseller->current_debt }}">{{ number_format($reseller->current_debt, 0, ',', ' ') }} FCFA</h3>
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
                               value="0" min="0" max="{{ $reseller->current_debt }}" step="100">
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
                        Le revendeur peut rendre des produits non vendus. La valeur sera déduite de sa dette.
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
                <span id="newDebt">{{ number_format($reseller->current_debt, 0, ',', ' ') }} FCFA</span>
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
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Important:</strong> Seuls les produits dont la valeur ne dépasse pas la dette restante de la facture peuvent être retournés.
                    Si le revendeur a déjà payé une partie, la quantité retournable est réduite proportionnellement.
                </div>
                
                <!-- Produits des ventes à crédit -->
                @if($reseller->sales->count() > 0)
                    @foreach($reseller->sales as $sale)
                        @php
                            // Calculer la valeur restante retournable pour cette vente
                            $amountDue = (float) $sale->amount_due;
                            $totalSaleValue = (float) $sale->total_amount;
                            
                            // Calculer les items retournables
                            $returnableItems = [];
                            $remainingReturnValue = $amountDue;
                            
                            // Trier les items par prix unitaire croissant (retourner les moins chers d'abord)
                            $sortedItems = $sale->items->sortBy('unit_price');
                            
                            foreach ($sortedItems as $item) {
                                if ($remainingReturnValue <= 0) break;
                                
                                $unitPrice = (float) $item->unit_price;
                                if ($unitPrice <= 0) continue;
                                
                                // Combien d'unités de ce produit peuvent être retournées?
                                $maxReturnableQty = min(
                                    $item->quantity,
                                    floor($remainingReturnValue / $unitPrice)
                                );
                                
                                if ($maxReturnableQty > 0) {
                                    $returnableItems[] = [
                                        'item' => $item,
                                        'max_qty' => $maxReturnableQty,
                                        'value' => $maxReturnableQty * $unitPrice,
                                    ];
                                    $remainingReturnValue -= ($maxReturnableQty * $unitPrice);
                                }
                            }
                        @endphp
                        
                        @if(count($returnableItems) > 0)
                        <div class="card mb-3">
                            <div class="card-header bg-light py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="bi bi-receipt"></i> 
                                        <code>{{ $sale->invoice_number }}</code>
                                    </span>
                                    <span class="badge bg-danger">
                                        Reste: {{ number_format($amountDue, 0, ',', ' ') }} FCFA
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produit</th>
                                            <th>Prix unit.</th>
                                            <th>Qté retournable</th>
                                            <th>Valeur max</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($returnableItems as $returnableItem)
                                            @php $item = $returnableItem['item']; @endphp
                                            <tr>
                                                <td>
                                                    <strong>{{ $item->product->name ?? 'Produit supprimé' }}</strong>
                                                    @if($item->product)
                                                        <br><small class="text-muted">{{ $item->product->sku }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ number_format($item->unit_price, 0, ',', ' ') }}</td>
                                                <td>
                                                    <span class="badge bg-info">Max: {{ $returnableItem['max_qty'] }}</span>
                                                    <small class="text-muted">/ {{ $item->quantity }} achetés</small>
                                                </td>
                                                <td>{{ number_format($returnableItem['value'], 0, ',', ' ') }}</td>
                                                <td>
                                                    @if($item->product)
                                                        <button type="button" class="btn btn-sm btn-outline-primary select-product-btn"
                                                                data-product-id="{{ $item->product_id }}"
                                                                data-product-name="{{ $item->product->name }}"
                                                                data-unit-price="{{ $item->unit_price }}"
                                                                data-max-qty="{{ $returnableItem['max_qty'] }}"
                                                                data-sale-id="{{ $sale->id }}"
                                                                data-sale-item-id="{{ $item->id }}"
                                                                data-sale-amount-due="{{ $amountDue }}">
                                                            <i class="bi bi-plus"></i>
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    @endforeach
                    
                    @php
                        $hasReturnableItems = $reseller->sales->sum(function($sale) {
                            return $sale->amount_due > 0 ? 1 : 0;
                        }) > 0;
                    @endphp
                    
                    @if(!$hasReturnableItems)
                        <p class="text-muted text-center">Aucun produit retournable disponible.</p>
                    @endif
                @else
                    <p class="text-muted text-center">Aucune facture à crédit trouvée.</p>
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
});
</script>
@endsection
