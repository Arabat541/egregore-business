@extends('layouts.app')

@section('title', 'Nouvelle réparation')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

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
                        <label class="form-label">Ajouter une pièce</label>
                        <div class="input-group">
                            <select class="form-select" id="partSelect">
                                <option value="">-- Sélectionner une pièce --</option>
                                @foreach($spareParts as $part)
                                    <option value="{{ $part->id }}" 
                                            data-name="{{ $part->name }}" 
                                            data-price="{{ $part->selling_price }}"
                                            data-stock="{{ $part->quantity_in_stock }}">
                                        {{ $part->name }} - {{ number_format($part->selling_price, 0, ',', ' ') }} FCFA (Stock: {{ $part->quantity_in_stock }})
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" class="form-control" id="partQty" value="1" min="1" style="max-width: 80px;">
                            <button type="button" class="btn btn-outline-secondary" id="addPartBtn">
                                <i class="bi bi-plus"></i> Ajouter
                            </button>
                        </div>
                    </div>

                    <table class="table table-sm" id="partsTable">
                        <thead>
                            <tr>
                                <th>Pièce</th>
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
                                <td colspan="3" class="text-end fw-bold">Total pièces :</td>
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
                                <span>Total :</span>
                                <span id="totalCostDisplay" class="text-success">0 FCFA</span>
                            </div>
                        </div>
                    </div>

                    <!-- Champ caché pour le coût total -->
                    <input type="hidden" name="final_cost" id="finalCost" value="{{ old('final_cost', 0) }}">

                    <div class="mb-3">
                        <label class="form-label">Montant à payer (FCFA)</label>
                        <div class="form-control form-control-lg text-center bg-light fw-bold" id="amountToPay">0 FCFA</div>
                        <input type="hidden" name="amount_paid" id="amountPaid" value="{{ old('amount_paid', 0) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Montant donné par le client (FCFA) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg text-center @error('amount_given') is-invalid @enderror" 
                               name="amount_given" id="amountGiven" value="{{ old('amount_given', 0) }}" min="0" required>
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
                        <label class="form-label">Statut final</label>
                        <select class="form-select" name="final_status">
                            <option value="delivered" {{ old('final_status', 'delivered') === 'delivered' ? 'selected' : '' }}>Livré (terminé)</option>
                            <option value="ready_for_pickup" {{ old('final_status') === 'ready_for_pickup' ? 'selected' : '' }}>Prêt à retirer</option>
                            <option value="in_repair" {{ old('final_status') === 'in_repair' ? 'selected' : '' }}>En cours</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Technicien</label>
                        <select class="form-select" name="technician_id">
                            <option value="">Non assigné</option>
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
    
    // Gestion des pièces
    const partSelect = document.getElementById('partSelect');
    const partQty = document.getElementById('partQty');
    const addPartBtn = document.getElementById('addPartBtn');
    const partsBody = document.getElementById('partsBody');
    const partsTotal = document.getElementById('partsTotal');
    
    let addedParts = [];
    let currentPartsTotal = 0;

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
        
        // Mettre à jour l'affichage
        partsCostDisplay.textContent = formatNumber(currentPartsTotal) + ' FCFA';
        laborCostDisplay.textContent = formatNumber(labor) + ' FCFA';
        totalCostDisplay.textContent = formatNumber(total) + ' FCFA';
        partsTotal.textContent = formatNumber(currentPartsTotal) + ' FCFA';
        amountToPay.textContent = formatNumber(total) + ' FCFA';
        
        // Mettre à jour le champ caché
        finalCost.value = total;
        amountPaid.value = total;
        
        // Mettre à jour la monnaie rendue
        updateChange();
    }

    function updateChange() {
        const cost = parseInt(finalCost.value) || 0;
        const given = parseInt(amountGiven.value) || 0;
        const change = Math.max(0, given - cost);
        changeAmount.textContent = formatNumber(change) + ' FCFA';
        
        if (given > 0 && given >= cost) {
            changeAmount.classList.remove('bg-warning');
            changeAmount.classList.add('bg-success', 'text-white');
        } else if (given > 0 && given < cost) {
            changeAmount.classList.remove('bg-success', 'text-white');
            changeAmount.classList.add('bg-danger', 'text-white');
            changeAmount.textContent = 'Insuffisant: ' + formatNumber(cost - given) + ' FCFA manquants';
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

    // Auto-remplir le montant donné quand le total change
    laborCost.addEventListener('change', function() {
        const total = parseInt(finalCost.value) || 0;
        if (parseInt(amountGiven.value) === 0 && total > 0) {
            amountGiven.value = total;
            updateChange();
        }
    });

    addPartBtn.addEventListener('click', function() {
        const selected = partSelect.options[partSelect.selectedIndex];
        if (!selected.value) return;
        
        const qty = parseInt(partQty.value) || 1;
        const stock = parseInt(selected.dataset.stock);
        
        if (qty > stock) {
            alert('Stock insuffisant ! Disponible: ' + stock);
            return;
        }
        
        // Vérifier si déjà ajouté
        const existing = addedParts.find(p => p.id == selected.value);
        if (existing) {
            if (existing.qty + qty > stock) {
                alert('Stock insuffisant ! Disponible: ' + stock);
                return;
            }
            existing.qty += qty;
        } else {
            addedParts.push({
                id: selected.value,
                name: selected.dataset.name,
                price: parseFloat(selected.dataset.price),
                qty: qty,
                stock: stock
            });
        }
        
        renderParts();
        partSelect.value = '';
        partQty.value = 1;
        
        // Auto-remplir le montant donné
        const total = parseInt(finalCost.value) || 0;
        if (parseInt(amountGiven.value) === 0 && total > 0) {
            amountGiven.value = total;
            updateChange();
        }
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
