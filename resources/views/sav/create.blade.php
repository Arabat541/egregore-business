@extends('layouts.app')

@section('title', 'Nouveau Ticket SAV')

@section('sidebar')
    @if(auth()->user()->hasRole('admin'))
        @include('admin.partials.sidebar')
    @else
        @include('cashier.partials.sidebar')
    @endif
@endsection

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-plus-circle me-2"></i>Nouveau Ticket SAV
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('sav.index') }}">SAV</a></li>
                    <li class="breadcrumb-item active">Nouveau ticket</li>
                </ol>
            </nav>
        </div>
    </div>

    <form action="{{ route('sav.store') }}" method="POST">
        @csrf
        
        <div class="row g-4">
            <!-- Colonne principale -->
            <div class="col-lg-8">
                <!-- Source du S.A.V. : Vente ou Réparation -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Source du S.A.V.</h5>
                    </div>
                    <div class="card-body">
                        <!-- Sélection du type de source -->
                        <div class="btn-group w-100 mb-3" role="group">
                            <input type="radio" class="btn-check" name="source_type" id="sourceNone" value="none" 
                                   {{ !($sale ?? null) && !($repair ?? null) ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary" for="sourceNone">
                                <i class="bi bi-file-earmark"></i> Aucune
                            </label>
                            
                            <input type="radio" class="btn-check" name="source_type" id="sourceSale" value="sale"
                                   {{ ($sale ?? null) ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary" for="sourceSale">
                                <i class="bi bi-cart"></i> Vente
                            </label>
                            
                            <input type="radio" class="btn-check" name="source_type" id="sourceRepair" value="repair"
                                   {{ ($repair ?? null) ? 'checked' : '' }}>
                            <label class="btn btn-outline-success" for="sourceRepair">
                                <i class="bi bi-tools"></i> Réparation
                            </label>
                        </div>

                        <!-- Recherche de vente -->
                        <div id="saleSearchSection" class="{{ ($sale ?? null) || (!($sale ?? null) && !($repair ?? null)) ? '' : 'd-none' }}">
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="bi bi-receipt"></i></span>
                                <input type="text" id="saleSearch" class="form-control" 
                                       placeholder="N° facture ou téléphone client...">
                                <button type="button" class="btn btn-outline-primary" id="searchSaleBtn">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                            <div id="saleResult" class="d-none"></div>
                            @if($sale ?? null)
                                <div class="alert alert-info mb-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><i class="bi bi-cart-check"></i> Vente sélectionnée</strong><br>
                                            <span class="fw-bold">{{ $sale->invoice_number }}</span><br>
                                            Client : {{ $sale->customer->full_name ?? 'Anonyme' }}<br>
                                            Date : {{ $sale->created_at->format('d/m/Y') }}
                                        </div>
                                        <a href="{{ route('sav.create') }}" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x"></i>
                                        </a>
                                    </div>
                                    <input type="hidden" name="sale_id" value="{{ $sale->id }}">
                                    <input type="hidden" name="customer_id" value="{{ $sale->customer_id }}">
                                </div>
                            @endif
                        </div>

                        <!-- Recherche de réparation -->
                        <div id="repairSearchSection" class="{{ ($repair ?? null) ? '' : 'd-none' }}">
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="bi bi-tools"></i></span>
                                <input type="text" id="repairSearch" class="form-control" 
                                       placeholder="N° ticket réparation ou téléphone client...">
                                <button type="button" class="btn btn-outline-success" id="searchRepairBtn">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                            <div id="repairResult" class="d-none"></div>
                            @if($repair ?? null)
                                <div class="alert alert-success mb-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><i class="bi bi-tools"></i> Réparation sélectionnée</strong><br>
                                            <span class="fw-bold">{{ $repair->repair_number }}</span><br>
                                            Client : {{ $repair->customer->full_name ?? $repair->customer->name ?? 'Anonyme' }}<br>
                                            Appareil : {{ $repair->device_brand }} {{ $repair->device_model }}<br>
                                            Livré le : {{ $repair->delivered_at ? $repair->delivered_at->format('d/m/Y') : 'Non livré' }}
                                        </div>
                                        <a href="{{ route('sav.create') }}" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x"></i>
                                        </a>
                                    </div>
                                    <input type="hidden" name="repair_id" value="{{ $repair->id }}">
                                    <input type="hidden" name="customer_id" value="{{ $repair->customer_id }}">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Informations client -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-person me-2"></i>Client</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Client enregistré</label>
                            <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                                <option value="">-- Sélectionner un client --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" 
                                        {{ old('customer_id', $sale->customer_id ?? '') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->full_name }} - {{ $customer->phone }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Informations produit -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-box me-2"></i>Produit concerné</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Produit en stock</label>
                                <select name="product_id" class="form-select @error('product_id') is-invalid @enderror">
                                    <option value="">-- Sélectionner --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }} ({{ $product->sku }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('product_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ou nom du produit</label>
                                <input type="text" name="product_name" class="form-control @error('product_name') is-invalid @enderror" 
                                       value="{{ old('product_name') }}" placeholder="Si produit non répertorié">
                                @error('product_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">N° Série / IMEI</label>
                                <input type="text" name="product_serial" class="form-control @error('product_serial') is-invalid @enderror" 
                                       value="{{ old('product_serial') }}">
                                @error('product_serial')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date d'achat</label>
                                <input type="date" name="purchase_date" class="form-control @error('purchase_date') is-invalid @enderror" 
                                       value="{{ old('purchase_date', $sale ? $sale->created_at->format('Y-m-d') : '') }}">
                                @error('purchase_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description du problème -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-chat-text me-2"></i>Description du problème</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Description du problème <span class="text-danger">*</span></label>
                            <textarea name="issue_description" class="form-control @error('issue_description') is-invalid @enderror" 
                                      rows="4" required placeholder="Décrivez le problème rencontré par le client...">{{ old('issue_description') }}</textarea>
                            @error('issue_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Demande du client</label>
                            <textarea name="customer_request" class="form-control @error('customer_request') is-invalid @enderror" 
                                      rows="2" placeholder="Que souhaite le client ? (remboursement, échange, réparation...)">{{ old('customer_request') }}</textarea>
                            @error('customer_request')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne latérale -->
            <div class="col-lg-4">
                <!-- Type et priorité -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-tag me-2"></i>Classification</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Type de demande <span class="text-danger">*</span></label>
                            <select name="type" id="ticketType" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="">-- Sélectionner --</option>
                                <optgroup label="Ventes">
                                    <option value="return" {{ old('type') == 'return' ? 'selected' : '' }}>🔄 Retour produit</option>
                                    <option value="exchange" {{ old('type') == 'exchange' ? 'selected' : '' }}>🔁 Échange</option>
                                    <option value="warranty" {{ old('type') == 'warranty' ? 'selected' : '' }}>🛡️ Garantie Produit</option>
                                    <option value="refund" {{ old('type') == 'refund' ? 'selected' : '' }}>💰 Remboursement</option>
                                </optgroup>
                                <optgroup label="Réparations">
                                    <option value="repair_warranty" {{ old('type') == 'repair_warranty' ? 'selected' : '' }}>� Garantie Réparation</option>
                                </optgroup>
                                <optgroup label="Autre">
                                    <option value="complaint" {{ old('type') == 'complaint' ? 'selected' : '' }}>� Réclamation</option>
                                    <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>📝 Autre</option>
                                </optgroup>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priorité <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>🟢 Basse</option>
                                <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>🔵 Moyenne</option>
                                <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>🟠 Haute</option>
                                <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>🔴 Urgente</option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Assignation -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-person-check me-2"></i>Assignation</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Assigner à</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">-- Non assigné --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-2">
                            <i class="bi bi-check-lg me-2"></i>Créer le ticket
                        </button>
                        <a href="{{ route('sav.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-lg me-2"></i>Annuler
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const saleSearchSection = document.getElementById('saleSearchSection');
    const repairSearchSection = document.getElementById('repairSearchSection');
    const sourceRadios = document.querySelectorAll('input[name="source_type"]');
    const ticketType = document.getElementById('ticketType');
    
    // Recherche vente
    const saleSearchInput = document.getElementById('saleSearch');
    const searchSaleBtn = document.getElementById('searchSaleBtn');
    const saleResultDiv = document.getElementById('saleResult');
    
    // Recherche réparation
    const repairSearchInput = document.getElementById('repairSearch');
    const searchRepairBtn = document.getElementById('searchRepairBtn');
    const repairResultDiv = document.getElementById('repairResult');

    // Basculer entre les sections de recherche
    sourceRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            saleSearchSection.classList.add('d-none');
            repairSearchSection.classList.add('d-none');
            
            if (this.value === 'sale') {
                saleSearchSection.classList.remove('d-none');
            } else if (this.value === 'repair') {
                repairSearchSection.classList.remove('d-none');
                // Sélectionner automatiquement le type "Garantie Réparation"
                ticketType.value = 'repair_warranty';
            }
        });
    });

    // Recherche de vente
    if (searchSaleBtn) {
        searchSaleBtn.addEventListener('click', function() {
            const query = saleSearchInput.value.trim();
            if (query.length < 2) return;

            fetch(`{{ route('sav.search-sale') }}?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        saleResultDiv.innerHTML = '<div class="alert alert-warning mb-0">Aucune vente trouvée</div>';
                    } else {
                        let html = '<div class="list-group">';
                        data.forEach(sale => {
                            const warrantyBadge = sale.warranty_valid 
                                ? `<span class="badge bg-success"><i class="bi bi-shield-check"></i> Garantie: ${sale.warranty_days_remaining}j</span>`
                                : `<span class="badge bg-danger"><i class="bi bi-shield-x"></i> Garantie expirée (${sale.warranty_expiry})</span>`;
                            const rowClass = sale.warranty_valid ? '' : 'list-group-item-danger';
                            
                            html += `
                                <div class="list-group-item ${rowClass}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><i class="bi bi-receipt"></i> ${sale.invoice_number}</strong>
                                            <span class="text-muted ms-2">${new Date(sale.created_at).toLocaleDateString('fr-FR')}</span>
                                        </div>
                                        ${warrantyBadge}
                                    </div>
                                    <small class="text-muted">
                                        ${sale.customer ? sale.customer.full_name : 'Client anonyme'}
                                    </small>
                                    ${sale.warranty_valid 
                                        ? `<a href="{{ route('sav.create') }}?sale_id=${sale.id}" class="btn btn-sm btn-primary mt-2">
                                            <i class="bi bi-plus-circle"></i> Créer ticket SAV
                                           </a>`
                                        : `<div class="text-danger small mt-1"><i class="bi bi-exclamation-triangle"></i> Aucun retour possible - garantie expirée</div>`
                                    }
                                </div>
                            `;
                        });
                        html += '</div>';
                        saleResultDiv.innerHTML = html;
                    }
                    saleResultDiv.classList.remove('d-none');
                });
        });

        saleSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchSaleBtn.click();
            }
        });
    }

    // Recherche de réparation
    if (searchRepairBtn) {
        searchRepairBtn.addEventListener('click', function() {
            const query = repairSearchInput.value.trim();
            if (query.length < 2) return;

            fetch(`{{ route('sav.search-repair') }}?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        repairResultDiv.innerHTML = '<div class="alert alert-warning mb-0">Aucune réparation trouvée</div>';
                    } else {
                        let html = '<div class="list-group">';
                        data.forEach(repair => {
                            const warrantyBadge = repair.warranty_valid 
                                ? `<span class="badge bg-success"><i class="bi bi-shield-check"></i> Garantie: ${repair.warranty_days_remaining}j</span>`
                                : `<span class="badge bg-danger"><i class="bi bi-shield-x"></i> Garantie expirée (${repair.warranty_expiry})</span>`;
                            const rowClass = repair.warranty_valid ? '' : 'list-group-item-danger';
                            
                            html += `
                                <div class="list-group-item ${rowClass}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><i class="bi bi-tools"></i> ${repair.repair_number}</strong>
                                            <span class="badge bg-${repair.status === 'delivered' ? 'success' : (repair.status === 'repaired' || repair.status === 'ready_for_pickup' ? 'info' : 'secondary')} ms-2">${repair.status_label || repair.status}</span>
                                        </div>
                                        ${warrantyBadge}
                                    </div>
                                    <div>
                                        <small>${repair.device_brand} ${repair.device_model}</small>
                                    </div>
                                    <small class="text-muted">
                                        ${repair.customer ? repair.customer.name : 'Client anonyme'}
                                        ${repair.delivered_at ? ' - Livré le ' + new Date(repair.delivered_at).toLocaleDateString('fr-FR') : (repair.repaired_at ? ' - Réparé le ' + new Date(repair.repaired_at).toLocaleDateString('fr-FR') : '')}
                                    </small>
                                    ${repair.warranty_valid 
                                        ? `<a href="{{ route('sav.create') }}?repair_id=${repair.id}" class="btn btn-sm btn-primary mt-2">
                                            <i class="bi bi-plus-circle"></i> Créer ticket SAV
                                           </a>`
                                        : `<div class="text-danger small mt-1"><i class="bi bi-exclamation-triangle"></i> Aucune réclamation possible - garantie expirée</div>`
                                    }
                                </div>
                            `;
                        });
                        html += '</div>';
                        repairResultDiv.innerHTML = html;
                    }
                    repairResultDiv.classList.remove('d-none');
                });
        });

        repairSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchRepairBtn.click();
            }
        });
    }
});
</script>
@endpush
