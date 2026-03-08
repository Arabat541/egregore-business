@extends('layouts.app')

@section('title', 'Créance ' . $reseller->company_name)

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-building"></i> {{ $reseller->company_name }}</h2>
    <div>
        <a href="{{ route('cashier.reseller-payments.create', $reseller) }}" class="btn btn-success">
            <i class="bi bi-cash-coin"></i> Paiement (espèces + retours)
        </a>
        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
            <i class="bi bi-cash"></i> Paiement rapide
        </button>
        <a href="{{ route('cashier.reseller-payments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Contact</h6>
                <p class="mb-0">{{ $reseller->contact_name }}</p>
                <p class="mb-0">{{ $reseller->phone }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Limite de crédit</h6>
                <h4>{{ number_format($reseller->credit_limit, 0, ',', ' ') }} FCFA</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card {{ $reseller->current_debt > 0 ? 'bg-danger text-white' : 'bg-success text-white' }}">
            <div class="card-body text-center">
                <h6>Dette actuelle</h6>
                <h3>{{ number_format($reseller->current_debt, 0, ',', ' ') }} FCFA</h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <!-- Ventes à crédit non soldées -->
        <div class="card mb-3">
            <div class="card-header bg-warning">
                <i class="bi bi-receipt"></i> Ventes à crédit en cours
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Facture</th>
                                <th>Total</th>
                                <th>Payé</th>
                                <th>Reste</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reseller->sales()->where('amount_due', '>', 0)->oldest()->get() as $sale)
                            <tr>
                                <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                                <td><code>{{ $sale->invoice_number }}</code></td>
                                <td>{{ number_format($sale->total_amount, 0, ',', ' ') }}</td>
                                <td class="text-success">{{ number_format($sale->amount_paid, 0, ',', ' ') }}</td>
                                <td class="text-danger fw-bold">{{ number_format($sale->amount_due, 0, ',', ' ') }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#paymentModal"
                                            data-sale-id="{{ $sale->id }}"
                                            data-sale-number="{{ $sale->invoice_number }}"
                                            data-sale-due="{{ $sale->amount_due }}">
                                        <i class="bi bi-cash"></i> Payer
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-muted text-center">Aucune vente à crédit en cours</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Historique des paiements -->
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <i class="bi bi-cash"></i> Historique des paiements
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Référence</th>
                                <th>Montant</th>
                                <th>Détail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reseller->payments()->with('productReturns.product')->latest()->take(10)->get() as $payment)
                            <tr>
                                <td>{{ $payment->created_at->format('d/m/Y') }}</td>
                                <td><code>PAY-{{ str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}</code></td>
                                <td class="text-success fw-bold">{{ number_format($payment->amount, 0, ',', ' ') }}</td>
                                <td>
                                    @if($payment->has_product_return)
                                        <span class="badge bg-info" data-bs-toggle="tooltip" 
                                              title="Espèces: {{ number_format($payment->cash_amount, 0, ',', ' ') }} + Retours: {{ number_format($payment->return_amount, 0, ',', ' ') }}">
                                            <i class="bi bi-box-arrow-in-left"></i> Mixte
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">{{ $payment->payment_method_label }}</span>
                                    @endif
                                </td>
                            </tr>
                            @if($payment->has_product_return && $payment->productReturns->count() > 0)
                            <tr class="table-light">
                                <td colspan="4" class="small ps-4">
                                    <i class="bi bi-arrow-return-left text-muted"></i>
                                    <strong>Retours:</strong>
                                    @foreach($payment->productReturns as $return)
                                        {{ $return->product->name ?? 'Produit' }} (x{{ $return->quantity }})@if(!$loop->last), @endif
                                    @endforeach
                                </td>
                            </tr>
                            @endif
                            @empty
                            <tr>
                                <td colspan="4" class="text-muted text-center">Aucun paiement</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Historique des retours de produits -->
        @php
            $productReturns = \App\Models\ProductReturn::where('reseller_id', $reseller->id)
                ->with(['product', 'user'])
                ->latest()
                ->take(10)
                ->get();
        @endphp
        @if($productReturns->count() > 0)
        <div class="card mb-3">
            <div class="card-header bg-warning">
                <i class="bi bi-box-arrow-in-left"></i> Retours de produits
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Produit</th>
                                <th>Qté</th>
                                <th>Valeur</th>
                                <th>État</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productReturns as $return)
                            <tr>
                                <td>{{ $return->created_at->format('d/m/Y') }}</td>
                                <td>{{ $return->product->name ?? 'Produit supprimé' }}</td>
                                <td>{{ $return->quantity }}</td>
                                <td>{{ number_format($return->total_value, 0, ',', ' ') }}</td>
                                <td>
                                    <span class="badge {{ $return->condition_badge_class }}">{{ $return->condition_label }}</span>
                                    @if($return->restock)
                                        <i class="bi bi-arrow-repeat text-success" title="Remis en stock"></i>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal Paiement -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('cashier.reseller-payments.store', $reseller) }}" method="POST">
                @csrf
                <input type="hidden" name="sale_id" id="modalSaleId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash"></i> Enregistrer un paiement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info" id="modalDebtInfo">
                        <strong>Dette actuelle:</strong> {{ number_format($reseller->current_debt, 0, ',', ' ') }} FCFA
                    </div>
                    
                    <div class="alert alert-warning d-none" id="modalSaleInfo">
                        <strong>Paiement pour la facture:</strong> <span id="modalSaleNumber"></span><br>
                        <strong>Reste à payer:</strong> <span id="modalSaleDue"></span> FCFA
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Montant du paiement (FCFA)</label>
                        <input type="number" class="form-control form-control-lg" name="cash_amount" id="modalAmount"
                               min="1" max="{{ $reseller->current_debt }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mode de paiement</label>
                        <select class="form-select" name="payment_method_id" required>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" id="modalNotes"></textarea>
                    </div>
                    
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-info-circle"></i> Pour un paiement avec retour de produits, utilisez 
                        <a href="{{ route('cashier.reseller-payments.create', $reseller) }}">le formulaire complet</a>.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var paymentModal = document.getElementById('paymentModal');
    
    paymentModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var saleId = button ? button.getAttribute('data-sale-id') : null;
        var saleNumber = button ? button.getAttribute('data-sale-number') : null;
        var saleDue = button ? button.getAttribute('data-sale-due') : null;
        
        var modalSaleId = document.getElementById('modalSaleId');
        var modalSaleInfo = document.getElementById('modalSaleInfo');
        var modalDebtInfo = document.getElementById('modalDebtInfo');
        var modalSaleNumber = document.getElementById('modalSaleNumber');
        var modalSaleDue = document.getElementById('modalSaleDue');
        var modalAmount = document.getElementById('modalAmount');
        var modalNotes = document.getElementById('modalNotes');
        
        if (saleId) {
            // Paiement pour une vente spécifique
            modalSaleId.value = saleId;
            modalSaleInfo.classList.remove('d-none');
            modalDebtInfo.classList.add('d-none');
            modalSaleNumber.textContent = saleNumber;
            modalSaleDue.textContent = new Intl.NumberFormat('fr-FR').format(saleDue);
            modalAmount.max = saleDue;
            modalAmount.value = saleDue;
            modalNotes.value = 'Paiement facture ' + saleNumber;
        } else {
            // Paiement global
            modalSaleId.value = '';
            modalSaleInfo.classList.add('d-none');
            modalDebtInfo.classList.remove('d-none');
            modalAmount.max = {{ $reseller->current_debt }};
            modalAmount.value = '';
            modalNotes.value = '';
        }
    });
});
</script>
@endsection
