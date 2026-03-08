@extends('layouts.app')

@section('title', 'Nouvelle réparation')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@push('styles')
<style>
    .part-result:hover {
        background-color: #f8f9fa;
    }
    #partSearchResults {
        top: 100%;
        left: 0;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-wrench"></i> Nouvelle réparation</h2>
    <a href="{{ route('cashier.repairs.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<form action="{{ route('cashier.repairs.store') }}" method="POST" id="repairForm">
    @csrf
    
    <div class="row">
        <div class="col-md-8">
            <!-- Informations client -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-person"></i> Client
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Client <span class="text-danger">*</span></label>
                                <select class="form-select @error('customer_id') is-invalid @enderror" name="customer_id" id="customerSelect" required>
                                    <option value="">-- Sélectionner un client --</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->full_name }} - {{ $customer->phone }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#newCustomerModal">
                                <i class="bi bi-plus"></i> Nouveau client
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations appareil -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-phone"></i> Appareil
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('device_type') is-invalid @enderror" name="device_type" required>
                                    <option value="phone" {{ old('device_type', 'phone') === 'phone' ? 'selected' : '' }}>Téléphone</option>
                                    <option value="tablet" {{ old('device_type') === 'tablet' ? 'selected' : '' }}>Tablette</option>
                                    <option value="laptop" {{ old('device_type') === 'laptop' ? 'selected' : '' }}>Laptop</option>
                                    <option value="other" {{ old('device_type') === 'other' ? 'selected' : '' }}>Autre</option>
                                </select>
                                @error('device_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Marque <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('device_brand') is-invalid @enderror" 
                                       name="device_brand" value="{{ old('device_brand') }}" required placeholder="Samsung, Apple...">
                                @error('device_brand')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Modèle <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('device_model') is-invalid @enderror" 
                                       name="device_model" value="{{ old('device_model') }}" required placeholder="Galaxy S21...">
                                @error('device_model')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">IMEI</label>
                                <input type="text" class="form-control" name="device_imei" value="{{ old('device_imei') }}" placeholder="Optionnel">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Problème signalé <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('reported_issue') is-invalid @enderror" 
                                  name="reported_issue" rows="2" required placeholder="Décrivez le problème...">{{ old('reported_issue') }}</textarea>
                        @error('reported_issue')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">État de l'appareil</label>
                                <input type="text" class="form-control" name="device_condition" 
                                       value="{{ old('device_condition') }}" placeholder="Rayures, fissures, etc.">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Accessoires reçus</label>
                                <input type="text" class="form-control" name="accessories_received" 
                                       value="{{ old('accessories_received') }}" placeholder="Chargeur, coque, etc.">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Code du téléphone</label>
                                <input type="text" class="form-control" name="device_password" 
                                       value="{{ old('device_password') }}" placeholder="Code PIN, schéma, mot de passe...">
                                <small class="text-muted">Nécessaire pour tester l'appareil</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date de livraison prévue <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('estimated_completion_date') is-invalid @enderror" 
                                       name="estimated_completion_date" value="{{ old('estimated_completion_date', date('Y-m-d')) }}" required>
                                @error('estimated_completion_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Diagnostic immédiat -->
            <div class="card mb-3">
                <div class="card-header bg-warning text-dark">
                    <i class="bi bi-search"></i> Diagnostic immédiat
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Diagnostic du technicien <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('diagnosis') is-invalid @enderror" 
                                  name="diagnosis" rows="3" required placeholder="Résultat du diagnostic...">{{ old('diagnosis') }}</textarea>
                        @error('diagnosis')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes de réparation</label>
                        <textarea class="form-control" name="repair_notes" rows="2" placeholder="Détails...">{{ old('repair_notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Pièces de rechange -->
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-box-seam"></i> Pièces utilisées
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Rechercher une pièce</label>
                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="partSearch" placeholder="Tapez pour rechercher une pièce..." autocomplete="off">
                                <input type="number" class="form-control" id="partQty" value="1" min="1" style="max-width: 80px;">
                                <button type="button" class="btn btn-outline-secondary" id="addPartBtn" disabled>
                                    <i class="bi bi-plus"></i> Ajouter
                                </button>
                            </div>
                            <div id="partSearchResults" class="position-absolute w-100 bg-white border rounded-bottom shadow-sm" style="z-index: 1000; max-height: 300px; overflow-y: auto; display: none;">
                                <!-- Résultats de recherche -->
                            </div>
                        </div>
                        <input type="hidden" id="selectedPartId">
                        <div id="selectedPartInfo" class="mt-2 p-2 bg-light rounded d-none">
                            <small class="text-success"><i class="bi bi-check-circle"></i> <span id="selectedPartName"></span></small>
                        </div>
                    </div>

                    <!-- Liste des pièces pour référence (caché) -->
                    <div id="spareParts" class="d-none">
                        @foreach($spareParts as $part)
                            <div class="spare-part-data" 
                                 data-id="{{ $part->id }}"
                                 data-name="{{ $part->name }}" 
                                 data-price="{{ $part->normal_price }}"
                                 data-stock="{{ $part->quantity_in_stock }}"
                                 data-category="{{ $part->category->name ?? 'Non catégorisé' }}"
                                 data-display="[{{ $part->category->name ?? 'N/A' }}] {{ $part->name }} - {{ number_format($part->normal_price, 0, ',', ' ') }} FCFA (Stock: {{ $part->quantity_in_stock }})">
                            </div>
                        @endforeach
                    </div>

                    <table class="table table-sm" id="partsTable">
                        <thead>
                            <tr>
                                <th>Pièce</th>
                                <th>Catégorie</th>
                                <th class="text-center" style="width: 80px;">Qté</th>
                                <th class="text-end" style="width: 120px;">Prix unit.</th>
                                <th class="text-end" style="width: 120px;">Total</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="partsBody">
                            <!-- Pièces ajoutées dynamiquement -->
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="4" class="text-end fw-bold">Total pièces :</td>
                                <td class="text-end fw-bold" id="partsTotal">0 FCFA</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <small class="text-muted">Le coût des pièces sera ajouté au coût de réparation.</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Coût et Paiement -->
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-cash"></i> Coût & Paiement
                </div>
                <div class="card-body">
                    <!-- Main d'œuvre -->
                    <div class="mb-3">
                        <label class="form-label">Main d'œuvre (FCFA)</label>
                        <input type="number" class="form-control text-center @error('labor_cost') is-invalid @enderror" 
                               name="labor_cost" id="laborCost" value="{{ old('labor_cost', 0) }}" min="0">
                        @error('labor_cost')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Récapitulatif des coûts -->
                    <div class="card bg-light mb-3">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between">
                                <span>Pièces :</span>
                                <span id="partsCostDisplay">0 FCFA</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Main d'œuvre :</span>
                                <span id="laborCostDisplay">0 FCFA</span>
                            </div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total réparation :</span>
                                <span id="totalCostDisplay" class="text-success">0 FCFA</span>
                            </div>
                        </div>
                    </div>

                    <!-- Avance et reste à payer -->
                    <div class="card bg-info bg-opacity-10 border-info mb-3">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-wallet2"></i> Avance (pièces) :</span>
                                <span id="advanceDisplay" class="fw-bold text-primary">0 FCFA</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted">
                                <span><i class="bi bi-clock"></i> Reste à la livraison :</span>
                                <span id="remainingDisplay">0 FCFA</span>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="advance_amount" id="advanceAmount" value="0">

                    <!-- Champ caché pour le coût total -->
                    <input type="hidden" name="final_cost" id="finalCost" value="{{ old('final_cost', 0) }}">

                    <div class="mb-3">
                        <label class="form-label">Avance à payer maintenant (FCFA)</label>
                        <div class="form-control form-control-lg text-center bg-primary text-white fw-bold" id="amountToPay">0 FCFA</div>
                        <small class="text-muted">= Coût des pièces de rechange</small>
                        <input type="hidden" name="amount_paid" id="amountPaid" value="{{ old('amount_paid', 0) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Montant donné par le client (FCFA) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg text-center @error('amount_given') is-invalid @enderror" 
                               name="amount_given" id="amountGiven" value="{{ old('amount_given') }}" min="0" required placeholder="Saisir le montant...">
                        @error('amount_given')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Monnaie à rendre</label>
                        <div class="form-control form-control-lg text-center bg-warning fw-bold" id="changeAmount">0 FCFA</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mode de paiement <span class="text-danger">*</span></label>
                        <select class="form-select @error('payment_method_id') is-invalid @enderror" name="payment_method_id" required>
                            <option value="">-- Sélectionner --</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}" {{ old('payment_method_id') == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                            @endforeach
                        </select>
                        @error('payment_method_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label">Technicien <span class="text-danger">*</span></label>
                        <select class="form-select" name="technician_id" required>
                            <option value="">-- Sélectionner un technicien --</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}" {{ old('technician_id') == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="print_ticket" id="printTicket" value="1" checked>
                        <label class="form-check-label" for="printTicket">
                            <i class="bi bi-printer"></i> Imprimer le ticket
                        </label>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-lg"></i> Valider
                        </button>
                        <a href="{{ route('cashier.repairs.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal Nouveau client -->
<div class="modal fade" id="newCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="quickCustomerForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Nouveau client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" name="phone" required>
                    </div>
                    <div id="modalErrors" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="modalSubmitBtn"><i class="bi bi-check"></i> Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const finalCost = document.getElementById('finalCost');
    const laborCost = document.getElementById('laborCost');
    const amountPaid = document.getElementById('amountPaid');
    const amountGiven = document.getElementById('amountGiven');
    const amountToPay = document.getElementById('amountToPay');
    const changeAmount = document.getElementById('changeAmount');
    const partsCostDisplay = document.getElementById('partsCostDisplay');
    const laborCostDisplay = document.getElementById('laborCostDisplay');
    const totalCostDisplay = document.getElementById('totalCostDisplay');
    const advanceDisplay = document.getElementById('advanceDisplay');
    const remainingDisplay = document.getElementById('remainingDisplay');
    const advanceAmount = document.getElementById('advanceAmount');
    
    // Gestion des pièces avec recherche
    const partSearch = document.getElementById('partSearch');
    const partSearchResults = document.getElementById('partSearchResults');
    const selectedPartId = document.getElementById('selectedPartId');
    const selectedPartInfo = document.getElementById('selectedPartInfo');
    const selectedPartName = document.getElementById('selectedPartName');
    const partQty = document.getElementById('partQty');
    const addPartBtn = document.getElementById('addPartBtn');
    const partsBody = document.getElementById('partsBody');
    const partsTotal = document.getElementById('partsTotal');
    
    // Charger toutes les pièces disponibles
    const sparePartElements = document.querySelectorAll('.spare-part-data');
    const allSpareParts = Array.from(sparePartElements).map(el => ({
        id: el.dataset.id,
        name: el.dataset.name,
        price: parseFloat(el.dataset.price),
        stock: parseInt(el.dataset.stock),
        category: el.dataset.category,
        display: el.dataset.display
    }));
    
    let addedParts = [];
    let currentPartsTotal = 0;
    let selectedPart = null;

    function formatNumber(num) {
        return num.toLocaleString('fr-FR');
    }

    function updateTotals() {
        // Calculer le total des pièces
        currentPartsTotal = 0;
        addedParts.forEach(p => currentPartsTotal += p.price * p.qty);
        
        // Récupérer la main d'œuvre
        const labor = parseInt(laborCost.value) || 0;
        
        // Calculer le total
        const total = currentPartsTotal + labor;
        
        // Avance = coût des pièces uniquement
        // Reste à payer à la livraison = main d'œuvre
        const advance = currentPartsTotal;
        const remaining = labor;
        
        // Mettre à jour l'affichage
        partsCostDisplay.textContent = formatNumber(currentPartsTotal) + ' FCFA';
        laborCostDisplay.textContent = formatNumber(labor) + ' FCFA';
        totalCostDisplay.textContent = formatNumber(total) + ' FCFA';
        partsTotal.textContent = formatNumber(currentPartsTotal) + ' FCFA';
        
        // Avance et reste
        advanceDisplay.textContent = formatNumber(advance) + ' FCFA';
        remainingDisplay.textContent = formatNumber(remaining) + ' FCFA';
        amountToPay.textContent = formatNumber(advance) + ' FCFA';
        
        // Mettre à jour les champs cachés
        finalCost.value = total;
        advanceAmount.value = advance;
        amountPaid.value = advance; // L'avance = coût des pièces
        
        // Mettre à jour la monnaie rendue
        updateChange();
    }

    function updateChange() {
        // La monnaie est calculée sur l'avance, pas le total
        const advance = parseInt(advanceAmount.value) || 0;
        const given = parseInt(amountGiven.value) || 0;
        const change = Math.max(0, given - advance);
        changeAmount.textContent = formatNumber(change) + ' FCFA';
        
        if (advance === 0) {
            // Pas de pièces = pas d'avance requise
            changeAmount.classList.remove('bg-danger', 'text-white');
            changeAmount.classList.add('bg-success', 'text-white');
            changeAmount.textContent = 'Aucune avance requise';
        } else if (given > 0 && given >= advance) {
            changeAmount.classList.remove('bg-warning', 'bg-danger');
            changeAmount.classList.add('bg-success', 'text-white');
        } else if (given > 0 && given < advance) {
            changeAmount.classList.remove('bg-success', 'bg-warning');
            changeAmount.classList.add('bg-danger', 'text-white');
            changeAmount.textContent = 'Insuffisant: ' + formatNumber(advance - given) + ' FCFA manquants';
        } else {
            changeAmount.classList.remove('bg-success', 'bg-danger', 'text-white');
            changeAmount.classList.add('bg-warning');
        }
    }

    function renderParts() {
        partsBody.innerHTML = '';
        addedParts.forEach((part, idx) => {
            const total = part.price * part.qty;
            partsBody.innerHTML += `
                <tr data-index="${idx}">
                    <td>
                        ${part.name}
                        <input type="hidden" name="parts[${idx}][product_id]" value="${part.id}">
                        <input type="hidden" name="parts[${idx}][quantity]" value="${part.qty}">
                        <input type="hidden" name="parts[${idx}][unit_price]" value="${part.price}">
                    </td>
                    <td><span class="badge bg-secondary">${part.category}</span></td>
                    <td class="text-center">${part.qty}</td>
                    <td class="text-end">${formatNumber(part.price)}</td>
                    <td class="text-end">${formatNumber(total)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-part" data-index="${idx}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        updateTotals();
    }

    // Écouter les changements sur la main d'œuvre
    laborCost.addEventListener('input', updateTotals);
    amountGiven.addEventListener('input', updateChange);

    // Mettre à jour la monnaie quand le coût change
    laborCost.addEventListener('change', function() {
        updateChange();
    });

    // Recherche de pièces
    partSearch.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        
        if (query.length < 1) {
            partSearchResults.style.display = 'none';
            return;
        }
        
        const filtered = allSpareParts.filter(part => 
            part.name.toLowerCase().includes(query) || 
            part.category.toLowerCase().includes(query)
        ).slice(0, 15); // Limiter à 15 résultats
        
        if (filtered.length === 0) {
            partSearchResults.innerHTML = '<div class="p-2 text-muted text-center">Aucun produit trouvé</div>';
        } else {
            partSearchResults.innerHTML = filtered.map(part => `
                <div class="p-2 border-bottom part-result" style="cursor: pointer;" 
                     data-id="${part.id}" 
                     data-name="${part.name}" 
                     data-price="${part.price}" 
                     data-stock="${part.stock}"
                     data-category="${part.category}">
                    <div class="fw-bold">${part.name}</div>
                    <small class="text-muted">
                        <span class="badge bg-secondary">${part.category}</span>
                        ${formatNumber(part.price)} FCFA - Stock: ${part.stock}
                    </small>
                </div>
            `).join('');
        }
        
        partSearchResults.style.display = 'block';
    });
    
    // Sélectionner une pièce
    partSearchResults.addEventListener('click', function(e) {
        const result = e.target.closest('.part-result');
        if (!result) return;
        
        selectedPart = {
            id: result.dataset.id,
            name: result.dataset.name,
            price: parseFloat(result.dataset.price),
            stock: parseInt(result.dataset.stock),
            category: result.dataset.category
        };
        
        selectedPartId.value = selectedPart.id;
        selectedPartName.textContent = `${selectedPart.name} - ${formatNumber(selectedPart.price)} FCFA`;
        selectedPartInfo.classList.remove('d-none');
        partSearch.value = selectedPart.name;
        partSearchResults.style.display = 'none';
        addPartBtn.disabled = false;
    });
    
    // Fermer les résultats si clic en dehors
    document.addEventListener('click', function(e) {
        if (!partSearch.contains(e.target) && !partSearchResults.contains(e.target)) {
            partSearchResults.style.display = 'none';
        }
    });

    addPartBtn.addEventListener('click', function() {
        if (!selectedPart) return;
        
        const qty = parseInt(partQty.value) || 1;
        const stock = selectedPart.stock;
        
        if (qty > stock) {
            alert('Stock insuffisant ! Disponible: ' + stock);
            return;
        }
        
        // Vérifier si déjà ajouté
        const existing = addedParts.find(p => p.id == selectedPart.id);
        if (existing) {
            if (existing.qty + qty > stock) {
                alert('Stock insuffisant ! Disponible: ' + stock);
                return;
            }
            existing.qty += qty;
        } else {
            addedParts.push({
                id: selectedPart.id,
                name: selectedPart.name,
                category: selectedPart.category,
                price: selectedPart.price,
                qty: qty,
                stock: stock
            });
        }
        
        renderParts();
        partSearch.value = '';
        partQty.value = 1;
        selectedPart = null;
        selectedPartInfo.classList.add('d-none');
        addPartBtn.disabled = true;
        updateChange();
    });

    partsBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-part')) {
            const idx = parseInt(e.target.closest('.remove-part').dataset.index);
            addedParts.splice(idx, 1);
            renderParts();
        }
    });

    // Initialiser
    updateTotals();

    // Modal nouveau client
    const quickCustomerForm = document.getElementById('quickCustomerForm');
    if (quickCustomerForm) {
        quickCustomerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('modalSubmitBtn');
            const errorsDiv = document.getElementById('modalErrors');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Création...';
            errorsDiv.classList.add('d-none');
            
            fetch('{{ route("cashier.customers.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(this)
            })
            .then(response => {
                if (!response.ok) return response.json().then(err => { throw err; });
                return response.json();
            })
            .then(data => {
                if (data.success || data.customer) {
                    const customer = data.customer;
                    const customerSelect = document.getElementById('customerSelect');
                    const option = new Option(customer.full_name + ' - ' + customer.phone, customer.id, true, true);
                    customerSelect.add(option);
                    bootstrap.Modal.getInstance(document.getElementById('newCustomerModal')).hide();
                    quickCustomerForm.reset();
                }
            })
            .catch(error => {
                let message = 'Erreur';
                if (error.errors) message = Object.values(error.errors).flat().join('<br>');
                else if (error.message) message = error.message;
                errorsDiv.innerHTML = message;
                errorsDiv.classList.remove('d-none');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});
</script>
@endsection
