@extends('layouts.app')

@section('title', 'Valider vente en attente')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-check-circle"></i> Valider la vente</h2>
    <a href="{{ route('cashier.pending-sales.create', ['reseller_id' => $pendingSale->reseller_id]) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <!-- Colonne gauche - Détails de la vente -->
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-person-badge"></i> Réparateur
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>{{ $pendingSale->reseller->company_name }}</strong>
                        <br>{{ $pendingSale->reseller->contact_name }}
                        <br><i class="bi bi-phone"></i> {{ $pendingSale->reseller->phone }}
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">Crédit disponible</small>
                        <h4 class="text-success mb-0">{{ number_format($pendingSale->reseller->available_credit, 0, ',', ' ') }} FCFA</h4>
                        <small class="text-muted">Dette actuelle: {{ number_format($pendingSale->reseller->current_debt, 0, ',', ' ') }} FCFA</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des articles -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-cart"></i> Articles ({{ $pendingSale->items->count() }})
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-center">Qté</th>
                            <th class="text-end">Prix unit.</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingSale->items as $item)
                            <tr>
                                <td>{{ $item->product->name }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">{{ number_format($item->unit_price, 0, ',', ' ') }}</td>
                                <td class="text-end"><strong>{{ number_format($item->total_price, 0, ',', ' ') }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <th colspan="3" class="text-end">TOTAL</th>
                            <th class="text-end">{{ number_format($pendingSale->total_amount, 0, ',', ' ') }} FCFA</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Colonne droite - Paiement -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-cash-stack"></i> Paiement & Validation
            </div>
            <div class="card-body">
                <form action="{{ route('cashier.pending-sales.validate', $pendingSale) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Montant total</label>
                        <div class="form-control bg-light fw-bold fs-4 text-end">
                            {{ number_format($pendingSale->total_amount, 0, ',', ' ') }} FCFA
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mode de paiement</label>
                        <select name="payment_method_id" class="form-select" required>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}" {{ $method->type == 'cash' ? 'selected' : '' }}>
                                    {{ $method->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Somme reçue du réparateur</label>
                        <input type="number" name="amount_given" class="form-control form-control-lg" 
                               value="{{ $pendingSale->total_amount }}" min="0" step="any" required id="amountGiven">
                    </div>

                    <div class="mb-3" id="changeSection" style="display: none;">
                        <label class="form-label">Monnaie à rendre</label>
                        <div class="form-control bg-info text-white fw-bold fs-5 text-end" id="changeAmount">0 FCFA</div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_credit" id="isCredit" value="1">
                            <label class="form-check-label" for="isCredit">
                                Vente à crédit (reste à payer plus tard)
                            </label>
                        </div>
                        <small class="text-muted">
                            Crédit disponible: {{ number_format($pendingSale->reseller->available_credit, 0, ',', ' ') }} FCFA
                        </small>
                    </div>

                    <div class="mb-3" id="amountDueSection" style="display: none;">
                        <label class="form-label">Reste à payer</label>
                        <div class="form-control bg-warning fw-bold fs-5 text-end" id="amountDue">0 FCFA</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (optionnel)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Notes sur cette vente...">{{ $pendingSale->notes }}</textarea>
                    </div>

                    <hr>

                    <button type="submit" class="btn btn-success btn-lg w-100">
                        <i class="bi bi-check-circle"></i> Valider la vente
                    </button>
                </form>

                <hr>

                <form action="{{ route('cashier.pending-sales.cancel', $pendingSale) }}" method="POST"
                      onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette vente en attente ? Les articles ne seront pas vendus.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-x-circle"></i> Annuler la vente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const totalAmount = {{ $pendingSale->total_amount }};
    const amountGivenInput = document.getElementById('amountGiven');
    const isCreditCheckbox = document.getElementById('isCredit');
    const amountDueSection = document.getElementById('amountDueSection');
    const amountDueDisplay = document.getElementById('amountDue');
    const changeSection = document.getElementById('changeSection');
    const changeAmountDisplay = document.getElementById('changeAmount');

    function updateCalculations() {
        const amountGiven = parseFloat(amountGivenInput.value) || 0;
        const change = amountGiven - totalAmount;
        const amountDue = Math.max(0, totalAmount - amountGiven);
        
        // Afficher la monnaie à rendre si positive
        if (change > 0) {
            changeSection.style.display = 'block';
            changeAmountDisplay.textContent = new Intl.NumberFormat('fr-FR').format(change) + ' FCFA';
        } else {
            changeSection.style.display = 'none';
        }
        
        // Afficher le reste à payer si crédit coché et montant insuffisant
        if (isCreditCheckbox.checked && amountDue > 0) {
            amountDueSection.style.display = 'block';
            amountDueDisplay.textContent = new Intl.NumberFormat('fr-FR').format(amountDue) + ' FCFA';
        } else {
            amountDueSection.style.display = 'none';
        }
    }

    amountGivenInput.addEventListener('input', updateCalculations);
    isCreditCheckbox.addEventListener('change', updateCalculations);
    
    // Calcul initial
    updateCalculations();
});
</script>
@endpush
@endsection
