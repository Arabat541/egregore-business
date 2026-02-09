@extends('layouts.app')

@section('title', 'Retour en stock - ' . $ticket->ticket_number)

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="bi bi-box-arrow-in-down text-success me-2"></i>
                Retour en Stock
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('sav.index') }}">S.A.V.</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sav.show', $ticket) }}">{{ $ticket->ticket_number }}</a></li>
                    <li class="breadcrumb-item active">Retour en stock</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('sav.show', $ticket) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Retour au ticket
        </a>
    </div>

    <div class="row">
        <!-- Informations du ticket -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Ticket {{ $ticket->ticket_number }}
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th class="text-muted">Type:</th>
                            <td>{{ $ticket->type_name }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Statut:</th>
                            <td>
                                <span class="badge bg-{{ $ticket->status_color }}">
                                    {{ $ticket->status_name }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Client:</th>
                            <td>{{ $ticket->customer->full_name ?? 'N/A' }}</td>
                        </tr>
                        @if($ticket->sale)
                        <tr>
                            <th class="text-muted">Vente:</th>
                            <td>
                                <a href="{{ route('cashier.sales.show', $ticket->sale) }}">
                                    {{ $ticket->sale->invoice_number }}
                                </a>
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th class="text-muted">Produit:</th>
                            <td>{{ $ticket->product->name ?? $ticket->product_name ?? 'N/A' }}</td>
                        </tr>
                    </table>

                    @if($ticket->stock_returned)
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Retour déjà effectué</strong><br>
                        <small>
                            Le {{ $ticket->stock_returned_at->format('d/m/Y à H:i') }}<br>
                            Par {{ $ticket->stockReturnedByUser->name ?? 'N/A' }}<br>
                            Quantité: {{ $ticket->quantity_returned }}
                        </small>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Aide -->
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-lightbulb me-2"></i> Guide
                </div>
                <div class="card-body">
                    <p class="small mb-2"><strong>État des produits:</strong></p>
                    <ul class="small mb-0">
                        <li><strong>Neuf:</strong> Emballage intact, jamais utilisé</li>
                        <li><strong>Bon état:</strong> Utilisé mais fonctionnel</li>
                        <li><strong>Endommagé:</strong> Dégâts physiques (non remis en stock)</li>
                        <li><strong>Défectueux:</strong> Ne fonctionne pas (non remis en stock)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Formulaire de retour -->
        <div class="col-lg-8">
            @if($ticket->stock_returned)
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">Retour en stock déjà effectué</h4>
                    <p class="text-muted">
                        {{ $ticket->quantity_returned }} article(s) ont été remis en stock le 
                        {{ $ticket->stock_returned_at->format('d/m/Y à H:i') }}
                    </p>
                    @if($ticket->return_notes)
                    <div class="alert alert-secondary">
                        <strong>Notes:</strong> {{ $ticket->return_notes }}
                    </div>
                    @endif
                    <form action="{{ route('sav.cancel-stock-return', $ticket) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger" 
                                onclick="return confirm('Êtes-vous sûr de vouloir annuler ce retour en stock?')">
                            <i class="bi bi-x-circle me-1"></i> Annuler le retour
                        </button>
                    </form>
                </div>
            </div>
            @else
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-box-arrow-in-down me-2"></i>
                        Produits à retourner en stock
                    </h5>
                </div>
                <div class="card-body">
                    @if($products->isEmpty())
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Aucun produit associé à ce ticket SAV.
                    </div>
                    @else
                    <form action="{{ route('sav.process-stock-return', $ticket) }}" method="POST" id="stockReturnForm">
                        @csrf
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="selectAll" checked>
                                            </div>
                                        </th>
                                        <th>Produit</th>
                                        <th width="100">Quantité</th>
                                        <th width="180">État</th>
                                        <th class="text-end">Stock actuel</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $index => $product)
                                    <tr class="product-row">
                                        <td>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input product-checkbox" 
                                                       data-index="{{ $index }}" checked>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-light rounded p-2 me-3">
                                                    <i class="bi bi-box text-primary"></i>
                                                </div>
                                                <div>
                                                    <strong>{{ $product->name }}</strong>
                                                    @if($product->sku)
                                                    <br><small class="text-muted">SKU: {{ $product->sku }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                            <input type="hidden" name="products[{{ $index }}][product_id]" 
                                                   value="{{ $product->id }}" class="product-id-input">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm quantity-input" 
                                                   name="products[{{ $index }}][quantity]" 
                                                   value="1" min="1" max="100">
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm condition-select" 
                                                    name="products[{{ $index }}][condition]">
                                                <option value="new">✨ Neuf</option>
                                                <option value="good" selected>✅ Bon état</option>
                                                <option value="damaged">⚠️ Endommagé</option>
                                                <option value="defective">❌ Défectueux</option>
                                            </select>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-{{ $product->quantity_in_stock > 5 ? 'success' : ($product->quantity_in_stock > 0 ? 'warning' : 'danger') }}">
                                                {{ $product->quantity_in_stock }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Options de remboursement -->
                        <div class="card bg-light mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="bi bi-cash-coin me-2"></i>
                                    Options de remboursement
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="refund_method" class="form-label">Mode de remboursement</label>
                                        <select class="form-select" id="refund_method" name="refund_method">
                                            <option value="cash">💵 Espèces</option>
                                            <option value="wave">📱 Wave</option>
                                            <option value="orange_money">📱 Orange Money</option>
                                            <option value="card">💳 Carte bancaire</option>
                                            <option value="credit">📝 Avoir client</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mt-4 pt-2">
                                            <input class="form-check-input" type="checkbox" id="refund_damaged" name="refund_damaged" value="1">
                                            <label class="form-check-label" for="refund_damaged">
                                                Rembourser aussi les produits endommagés/défectueux
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                @if($ticket->sale)
                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Vente associée:</strong> {{ $ticket->sale->invoice_number }}<br>
                                    <small class="text-muted">Le montant du remboursement sera calculé à partir du prix de vente original et déduit du chiffre d'affaires.</small>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="return_notes" class="form-label">Notes du retour (optionnel)</label>
                            <textarea class="form-control" id="return_notes" name="return_notes" rows="3"
                                      placeholder="Observations sur l'état des produits, raison du retour, etc."></textarea>
                        </div>

                        <!-- Résumé -->
                        <div class="alert alert-info">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Résumé:</strong>
                                    <span id="summaryText">
                                        {{ $products->count() }} produit(s) sélectionné(s)
                                    </span>
                                </div>
                                <div class="text-muted small">
                                    Les produits endommagés/défectueux ne seront pas remis en stock (sauf option cochée)
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('sav.show', $ticket) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x me-1"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-box-arrow-in-down me-2"></i>
                                Confirmer le retour en stock
                            </button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const form = document.getElementById('stockReturnForm');

    // Sélectionner/Désélectionner tout
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            productCheckboxes.forEach(cb => {
                cb.checked = this.checked;
                toggleProductRow(cb);
            });
            updateSummary();
        });
    }

    // Gestion individuelle des checkboxes
    productCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            toggleProductRow(this);
            updateSummary();
        });
    });

    function toggleProductRow(checkbox) {
        const row = checkbox.closest('.product-row');
        const inputs = row.querySelectorAll('input:not(.product-checkbox), select');
        
        inputs.forEach(input => {
            input.disabled = !checkbox.checked;
        });
        
        row.style.opacity = checkbox.checked ? '1' : '0.5';
    }

    function updateSummary() {
        const checked = document.querySelectorAll('.product-checkbox:checked').length;
        const total = productCheckboxes.length;
        document.getElementById('summaryText').textContent = 
            `${checked} produit(s) sélectionné(s) sur ${total}`;
    }

    // Validation du formulaire
    if (form) {
        form.addEventListener('submit', function(e) {
            const checkedProducts = document.querySelectorAll('.product-checkbox:checked');
            if (checkedProducts.length === 0) {
                e.preventDefault();
                alert('Veuillez sélectionner au moins un produit à retourner.');
                return false;
            }
            
            // Désactiver les produits non cochés avant l'envoi
            document.querySelectorAll('.product-checkbox:not(:checked)').forEach(cb => {
                const row = cb.closest('.product-row');
                row.querySelectorAll('input, select').forEach(input => {
                    input.name = '';
                });
            });
        });
    }
});
</script>
@endpush
@endsection
