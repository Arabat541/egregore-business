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
                @php
                    $grossSubtotal = $pendingSale->items->sum(fn($i) => $i->unit_price * $i->quantity);
                    $lineDiscounts = $pendingSale->items->sum('discount');
                @endphp
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-center">Qté</th>
                            <th class="text-end">Prix unit.</th>
                            <th class="text-end">Remise</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingSale->items as $item)
                            <tr>
                                <td>
                                    {{ $item->product->name }}
                                    @if($item->product->category)
                                        <br><span class="badge bg-secondary" style="font-size:.65rem;">{{ $item->product->category->name }}</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">{{ number_format($item->unit_price, 0, ',', ' ') }}</td>
                                <td class="text-end text-success">
                                    {{ $item->discount > 0 ? '- ' . number_format($item->discount, 0, ',', ' ') : '—' }}
                                </td>
                                <td class="text-end"><strong>{{ number_format($item->total_price, 0, ',', ' ') }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @if($lineDiscounts > 0)
                        <tr class="table-light">
                            <td colspan="3" class="text-end text-muted">Sous-total brut</td>
                            <td colspan="2" class="text-end text-muted">{{ number_format($grossSubtotal, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        <tr class="table-light">
                            <td colspan="3" class="text-end text-success">Remises lignes</td>
                            <td colspan="2" class="text-end text-success">- {{ number_format($lineDiscounts, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        @endif
                        <tr class="table-dark">
                            <th colspan="4" class="text-end">TOTAL ARTICLES</th>
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

                    <!-- Remise globale -->
                    <div class="mb-2">
                        <label class="form-label form-label-sm mb-1">Remise globale (FCFA)</label>
                        <input type="number" name="discount_amount" class="form-control form-control-sm"
                               id="discountAmount" value="{{ old('discount_amount', 0) }}" min="0" step="100"
                               max="{{ $pendingSale->total_amount }}"
                               oninput="updateCalculations()">
                    </div>

                    <div class="d-flex justify-content-between mb-1 text-success small" id="discountRow" style="display:none">
                        <span>Remise globale:</span>
                        <span id="discountDisplay">- 0 FCFA</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Montant à payer</label>
                        <div class="form-control bg-light fw-bold fs-4 text-end" id="netTotal">
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
                        <label class="form-label">Montant reçu</label>
                        <input type="number" name="amount_given" class="form-control form-control-lg"
                               value="0" min="0" step="any" required id="amountGiven"
                               placeholder="Saisir le montant reçu...">
                    </div>

                    <div class="mb-3" id="changeSection" style="display: none;">
                        <label class="form-label">Monnaie à rendre</label>
                        <div class="form-control bg-info text-white fw-bold fs-5 text-end" id="changeAmount">0 FCFA</div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_credit" id="isCredit" value="1">
                            <label class="form-check-label" for="isCredit">
                                <i class="bi bi-credit-card"></i> Vente à crédit (reste à payer plus tard)
                            </label>
                        </div>
                        <small class="text-muted">
                            Crédit disponible: <strong class="text-success">{{ number_format($pendingSale->reseller->available_credit, 0, ',', ' ') }} FCFA</strong>
                        </small>
                    </div>

                    <div class="mb-3" id="amountDueSection" style="display: none;">
                        <label class="form-label">Reste à payer (crédit)</label>
                        <div class="form-control bg-danger text-white fw-bold fs-5 text-end" id="amountDue">0 FCFA</div>
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
    const baseTotal = {{ $pendingSale->total_amount }};
    const fmt = v => new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' FCFA';

    const discountAmountInput = document.getElementById('discountAmount');
    const discountRow         = document.getElementById('discountRow');
    const discountDisplay     = document.getElementById('discountDisplay');
    const netTotalDisplay     = document.getElementById('netTotal');
    const amountGivenInput    = document.getElementById('amountGiven');
    const isCreditCheckbox    = document.getElementById('isCredit');
    const amountDueSection    = document.getElementById('amountDueSection');
    const amountDueDisplay    = document.getElementById('amountDue');
    const changeSection       = document.getElementById('changeSection');
    const changeAmountDisplay = document.getElementById('changeAmount');

    window.updateCalculations = function() {
        const discount   = Math.min(parseFloat(discountAmountInput.value) || 0, baseTotal);
        const netTotal   = Math.max(0, baseTotal - discount);
        const amountGiven = parseFloat(amountGivenInput.value) || 0;
        const change     = amountGiven - netTotal;
        const amountDue  = Math.max(0, netTotal - amountGiven);

        // Remise globale
        if (discount > 0) {
            discountRow.style.removeProperty('display');
            discountDisplay.textContent = '- ' + fmt(discount);
        } else {
            discountRow.style.display = 'none';
        }
        netTotalDisplay.textContent = fmt(netTotal);

        // Monnaie à rendre
        if (change > 0) {
            changeSection.style.display = 'block';
            changeAmountDisplay.textContent = fmt(change);
        } else {
            changeSection.style.display = 'none';
        }

        // Auto-cocher crédit
        if (amountDue > 0 && amountGiven > 0) {
            isCreditCheckbox.checked = true;
        } else if (amountGiven >= netTotal) {
            isCreditCheckbox.checked = false;
        }

        // Reste à payer
        if (amountDue > 0) {
            amountDueSection.style.display = 'block';
            amountDueDisplay.textContent = fmt(amountDue);
            amountDueDisplay.className = isCreditCheckbox.checked
                ? 'form-control bg-danger text-white fw-bold fs-5 text-end'
                : 'form-control bg-warning fw-bold fs-5 text-end';
        } else {
            amountDueSection.style.display = 'none';
        }
    };

    discountAmountInput.addEventListener('input', updateCalculations);
    amountGivenInput.addEventListener('input', updateCalculations);
    isCreditCheckbox.addEventListener('change', updateCalculations);

    updateCalculations();
});
</script>
@endpush
@endsection
