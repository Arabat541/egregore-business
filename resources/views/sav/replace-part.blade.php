@extends('layouts.app')

@section('title', 'Remplacement pièce - ' . $ticket->ticket_number)

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
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="bi bi-arrow-repeat text-warning me-2"></i>
                Remplacement de Pièce Défectueuse
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('sav.index') }}">S.A.V.</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sav.show', $ticket) }}">{{ $ticket->ticket_number }}</a></li>
                    <li class="breadcrumb-item active">Remplacement pièce</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('sav.show', $ticket) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Retour au ticket
        </a>
    </div>

    <div class="row">
        <!-- Informations du ticket et de la réparation -->
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
                            <td><span class="badge bg-warning">{{ $ticket->type_name }}</span></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Client:</th>
                            <td>{{ $ticket->repair->customer->full_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Réparation:</th>
                            <td>
                                <strong>{{ $ticket->repair->repair_number }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Appareil:</th>
                            <td>{{ $ticket->repair->device_type }} {{ $ticket->repair->device_brand }} {{ $ticket->repair->device_model }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Technicien:</th>
                            <td>
                                @if($ticket->repair->technician)
                                    <span class="badge bg-primary">{{ $ticket->repair->technician->name }}</span>
                                @else
                                    <span class="text-muted">Non assigné</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Pièces utilisées dans la réparation -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-box-seam me-2"></i>
                        Pièces utilisées
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($ticket->repair->parts->count() > 0)
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Pièce</th>
                                    <th>Qté</th>
                                    <th>Prix</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ticket->repair->parts as $part)
                                <tr>
                                    <td>
                                        {{ $part->product->name ?? $part->description }}
                                        @if($part->product->category)
                                            <br><small class="badge bg-secondary">{{ $part->product->category->name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $part->quantity }}</td>
                                    <td>{{ number_format($part->unit_cost, 0, ',', ' ') }} F</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted text-center p-3 mb-0">Aucune pièce utilisée</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Formulaire de remplacement -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-arrow-repeat me-2"></i>
                        Remplacer une pièce défectueuse
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Important:</strong> Le coût de la pièce défectueuse sera déduit du chiffre d'affaires du technicien qui a effectué la réparation initiale.
                    </div>

                    <form action="{{ route('sav.process-replace-part', $ticket) }}" method="POST" id="replacePartForm">
                        @csrf

                        <!-- Sélection de la pièce défectueuse -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">1. Pièce défectueuse à remplacer <span class="text-danger">*</span></label>
                            @if($ticket->repair->parts->count() > 0)
                                <div class="list-group">
                                    @foreach($ticket->repair->parts as $part)
                                    <label class="list-group-item list-group-item-action">
                                        <div class="d-flex align-items-center">
                                            <input type="radio" name="original_repair_part_id" value="{{ $part->id }}" 
                                                   class="form-check-input me-3" required
                                                   data-max-qty="{{ $part->quantity }}"
                                                   data-cost="{{ $part->unit_cost }}">
                                            <div class="flex-grow-1">
                                                <strong>{{ $part->product->name ?? $part->description }}</strong>
                                                @if($part->product->category)
                                                    <span class="badge bg-secondary ms-2">{{ $part->product->category->name }}</span>
                                                @endif
                                                <br>
                                                <small class="text-muted">
                                                    Quantité: {{ $part->quantity }} | 
                                                    Prix unitaire: {{ number_format($part->unit_cost, 0, ',', ' ') }} F
                                                </small>
                                            </div>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    Aucune pièce n'a été utilisée dans cette réparation.
                                </div>
                            @endif
                            @error('original_repair_part_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Quantité à remplacer -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">2. Quantité à remplacer <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantity" class="form-control" 
                                   value="1" min="1" required style="max-width: 150px;">
                            <small class="text-muted">Nombre de pièces défectueuses à remplacer</small>
                            @error('quantity')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Sélection de la pièce de remplacement -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">3. Pièce de remplacement <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="partSearch" 
                                           placeholder="Rechercher une pièce de remplacement..." autocomplete="off">
                                </div>
                                <div id="partSearchResults" class="position-absolute w-100 bg-white border rounded-bottom shadow-sm" 
                                     style="z-index: 1000; max-height: 300px; overflow-y: auto; display: none;">
                                </div>
                            </div>
                            <input type="hidden" name="replacement_product_id" id="replacementProductId" required>
                            <div id="selectedPartInfo" class="mt-2 p-3 bg-success bg-opacity-10 border border-success rounded d-none">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span id="selectedPartName" class="fw-bold"></span>
                            </div>
                            @error('replacement_product_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Raison du remplacement -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">4. Raison du remplacement</label>
                            <textarea name="reason" class="form-control" rows="3" 
                                      placeholder="Décrivez le problème avec la pièce défectueuse...">{{ old('reason') }}</textarea>
                            @error('reason')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Récapitulatif -->
                        <div class="card bg-light mb-4" id="summary" style="display: none;">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-calculator me-2"></i>Récapitulatif</h6>
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td>Coût pièce défectueuse:</td>
                                        <td class="text-end text-danger fw-bold" id="defectiveCost">0 F</td>
                                    </tr>
                                    <tr class="table-warning">
                                        <td><strong>Montant déduit du CA technicien:</strong></td>
                                        <td class="text-end text-danger fw-bold" id="deductionAmount">0 F</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning" id="submitBtn" disabled>
                                <i class="bi bi-arrow-repeat me-2"></i>
                                Confirmer le remplacement
                            </button>
                            <a href="{{ route('sav.show', $ticket) }}" class="btn btn-outline-secondary">
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Données des pièces de rechange -->
<div id="spareParts" class="d-none">
    @foreach($spareParts as $part)
        <div class="spare-part-data" 
             data-id="{{ $part->id }}"
             data-name="{{ $part->name }}" 
             data-price="{{ $part->normal_price }}"
             data-stock="{{ $part->quantity_in_stock }}"
             data-category="{{ $part->category->name ?? 'Non catégorisé' }}">
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const partSearch = document.getElementById('partSearch');
    const partSearchResults = document.getElementById('partSearchResults');
    const replacementProductId = document.getElementById('replacementProductId');
    const selectedPartInfo = document.getElementById('selectedPartInfo');
    const selectedPartName = document.getElementById('selectedPartName');
    const quantityInput = document.getElementById('quantity');
    const summary = document.getElementById('summary');
    const defectiveCost = document.getElementById('defectiveCost');
    const deductionAmount = document.getElementById('deductionAmount');
    const submitBtn = document.getElementById('submitBtn');
    
    // Charger toutes les pièces disponibles
    const sparePartElements = document.querySelectorAll('.spare-part-data');
    const allSpareParts = Array.from(sparePartElements).map(el => ({
        id: el.dataset.id,
        name: el.dataset.name,
        price: parseFloat(el.dataset.price),
        stock: parseInt(el.dataset.stock),
        category: el.dataset.category
    }));
    
    let selectedOriginalPart = null;
    let selectedReplacementPart = null;

    function formatNumber(num) {
        return num.toLocaleString('fr-FR');
    }

    function updateSummary() {
        if (selectedOriginalPart && selectedReplacementPart) {
            const qty = parseInt(quantityInput.value) || 1;
            const cost = parseFloat(selectedOriginalPart.dataset.cost) * qty;
            
            defectiveCost.textContent = formatNumber(cost) + ' F';
            deductionAmount.textContent = formatNumber(cost) + ' F';
            summary.style.display = 'block';
            submitBtn.disabled = false;
        } else {
            summary.style.display = 'none';
            submitBtn.disabled = true;
        }
    }

    // Écouter la sélection de la pièce défectueuse
    document.querySelectorAll('input[name="original_repair_part_id"]').forEach(radio => {
        radio.addEventListener('change', function() {
            selectedOriginalPart = this;
            const maxQty = parseInt(this.dataset.maxQty);
            quantityInput.max = maxQty;
            if (parseInt(quantityInput.value) > maxQty) {
                quantityInput.value = maxQty;
            }
            updateSummary();
        });
    });

    // Écouter le changement de quantité
    quantityInput.addEventListener('input', updateSummary);

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
        ).slice(0, 15);
        
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
                        ${formatNumber(part.price)} F - Stock: ${part.stock}
                    </small>
                </div>
            `).join('');
        }
        
        partSearchResults.style.display = 'block';
    });
    
    // Sélectionner une pièce de remplacement
    partSearchResults.addEventListener('click', function(e) {
        const result = e.target.closest('.part-result');
        if (!result) return;
        
        selectedReplacementPart = {
            id: result.dataset.id,
            name: result.dataset.name,
            price: parseFloat(result.dataset.price),
            stock: parseInt(result.dataset.stock),
            category: result.dataset.category
        };
        
        replacementProductId.value = selectedReplacementPart.id;
        selectedPartName.textContent = `${selectedReplacementPart.name} - ${formatNumber(selectedReplacementPart.price)} F (Stock: ${selectedReplacementPart.stock})`;
        selectedPartInfo.classList.remove('d-none');
        partSearch.value = selectedReplacementPart.name;
        partSearchResults.style.display = 'none';
        
        updateSummary();
    });
    
    // Fermer les résultats si clic en dehors
    document.addEventListener('click', function(e) {
        if (!partSearch.contains(e.target) && !partSearchResults.contains(e.target)) {
            partSearchResults.style.display = 'none';
        }
    });
});
</script>
@endpush
