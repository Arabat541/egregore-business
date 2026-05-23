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
                                <button type="button" class="btn btn-outline-secondary" onclick="openQrScanner('saleSearch', 'searchSaleBtn')" title="Scanner un QR code">
                                    <i class="bi bi-qr-code-scan"></i>
                                </button>
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
                                <button type="button" class="btn btn-outline-secondary" onclick="openQrScanner('repairSearch', 'searchRepairBtn')" title="Scanner un QR code">
                                    <i class="bi bi-qr-code-scan"></i>
                                </button>
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
                                        {{ old('customer_id', $sale->customer_id ?? $repair->customer_id ?? '') == $customer->id ? 'selected' : '' }}>
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

    <!-- Modal scanner QR code -->
    <div class="modal fade" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrScannerLabel">
                        <i class="bi bi-qr-code-scan me-2"></i>Scanner un QR code
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-0">
                    <!-- Vue caméra live -->
                    <div id="qrCameraView" class="d-none">
                        <div style="position:relative;background:#000;">
                            <video id="qrVideo" autoplay playsinline muted style="width:100%;max-height:320px;display:block;"></video>
                            <canvas id="qrCanvas" style="display:none;"></canvas>
                            <!-- Viseur -->
                            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:180px;height:180px;border:3px solid #0d6efd;border-radius:8px;pointer-events:none;">
                                <div style="width:24px;height:3px;background:#0d6efd;position:absolute;top:-3px;left:-3px;"></div>
                                <div style="width:3px;height:24px;background:#0d6efd;position:absolute;top:-3px;left:-3px;"></div>
                                <div style="width:24px;height:3px;background:#0d6efd;position:absolute;top:-3px;right:-3px;"></div>
                                <div style="width:3px;height:24px;background:#0d6efd;position:absolute;top:-3px;right:-3px;"></div>
                                <div style="width:24px;height:3px;background:#0d6efd;position:absolute;bottom:-3px;left:-3px;"></div>
                                <div style="width:3px;height:24px;background:#0d6efd;position:absolute;bottom:-3px;left:-3px;"></div>
                                <div style="width:24px;height:3px;background:#0d6efd;position:absolute;bottom:-3px;right:-3px;"></div>
                                <div style="width:3px;height:24px;background:#0d6efd;position:absolute;bottom:-3px;right:-3px;"></div>
                            </div>
                        </div>
                        <div class="p-3 text-center text-muted small">
                            <i class="bi bi-camera me-1"></i>Pointez la caméra vers le QR code
                        </div>
                    </div>
                    <!-- Fallback : photo depuis galerie/caméra -->
                    <div id="qrFileView" class="p-4 text-center">
                        <div class="mb-3">
                            <i class="bi bi-exclamation-circle text-warning" style="font-size:2rem;"></i>
                            <p class="mt-2 mb-3">La caméra live n'est pas disponible sur ce navigateur.<br>
                            Prenez une photo du QR code :</p>
                        </div>
                        <label class="btn btn-primary btn-lg">
                            <i class="bi bi-camera me-2"></i>Ouvrir la caméra
                            <input type="file" id="qrFileInput" accept="image/*" capture="environment" class="d-none">
                        </label>
                        <p class="text-muted small mt-2">Ou importez une image depuis la galerie</p>
                        <label class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-image me-1"></i>Choisir une image
                            <input type="file" id="qrGalleryInput" accept="image/*" class="d-none">
                        </label>
                    </div>
                    <!-- Message résultat -->
                    <div id="qrResult" class="d-none p-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
// ── Scanner QR code ───────────────────────────────────────────────────────────
let _qrTargetInput = null;
let _qrTriggerBtn  = null;
let _qrStream      = null;
let _qrAnimFrame   = null;

function openQrScanner(inputId, btnId) {
    _qrTargetInput = document.getElementById(inputId);
    _qrTriggerBtn  = document.getElementById(btnId);
    const modal = new bootstrap.Modal(document.getElementById('qrScannerModal'));
    document.getElementById('qrResult').classList.add('d-none');
    document.getElementById('qrResult').innerHTML = '';

    // Essayer la caméra live d'abord
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } } })
            .then(stream => {
                _qrStream = stream;
                const video = document.getElementById('qrVideo');
                video.srcObject = stream;
                document.getElementById('qrCameraView').classList.remove('d-none');
                document.getElementById('qrFileView').classList.add('d-none');
                modal.show();
                _qrAnimFrame = requestAnimationFrame(() => _qrScanFrame(video, modal));
            })
            .catch(() => {
                document.getElementById('qrCameraView').classList.add('d-none');
                document.getElementById('qrFileView').classList.remove('d-none');
                modal.show();
            });
    } else {
        document.getElementById('qrCameraView').classList.add('d-none');
        document.getElementById('qrFileView').classList.remove('d-none');
        modal.show();
    }
}

function _qrScanFrame(video, modal) {
    if (video.readyState !== video.HAVE_ENOUGH_DATA) {
        _qrAnimFrame = requestAnimationFrame(() => _qrScanFrame(video, modal));
        return;
    }
    const canvas  = document.getElementById('qrCanvas');
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    // Utiliser jsQR
    if (typeof jsQR !== 'undefined') {
        const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });
        if (code) {
            _qrFound(code.data, modal);
            return;
        }
    }
    _qrAnimFrame = requestAnimationFrame(() => _qrScanFrame(video, modal));
}

function _qrFound(rawValue, modal) {
    cancelAnimationFrame(_qrAnimFrame);
    _qrStopCamera();
    _qrFillAndSearch(rawValue, modal);
}

function _qrStopCamera() {
    if (_qrStream) {
        _qrStream.getTracks().forEach(t => t.stop());
        _qrStream = null;
    }
}

function _qrFillAndSearch(rawValue, modal) {
    if (_qrTargetInput) _qrTargetInput.value = rawValue;
    const resultDiv = document.getElementById('qrResult');
    resultDiv.innerHTML = `<div class="alert alert-success mb-0"><i class="bi bi-check-circle me-2"></i>QR détecté : <strong>${rawValue}</strong></div>`;
    resultDiv.classList.remove('d-none');
    setTimeout(() => {
        if (modal) modal.hide();
        if (_qrTriggerBtn) _qrTriggerBtn.click();
    }, 600);
}

// Nettoyage à la fermeture du modal
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('qrScannerModal').addEventListener('hidden.bs.modal', function() {
        cancelAnimationFrame(_qrAnimFrame);
        _qrStopCamera();
    });

    // Fallback : fichier image → décodage via canvas + jsQR
    ['qrFileInput', 'qrGalleryInput'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.getElementById('qrCanvas');
                    canvas.width  = img.width;
                    canvas.height = img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);
                    const imageData = ctx.getImageData(0, 0, img.width, img.height);
                    const code = typeof jsQR !== 'undefined'
                        ? jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' })
                        : null;
                    if (code) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('qrScannerModal'));
                        _qrFillAndSearch(code.data, modal);
                    } else {
                        const resultDiv = document.getElementById('qrResult');
                        resultDiv.innerHTML = '<div class="alert alert-danger mb-0"><i class="bi bi-x-circle me-2"></i>QR code non reconnu dans cette image.</div>';
                        resultDiv.classList.remove('d-none');
                    }
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    });
});
// ── Fin scanner QR ────────────────────────────────────────────────────────────

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

    // Éléments du formulaire pour l'auto-remplissage
    const customerSelect = document.querySelector('select[name="customer_id"]');
    const productSelect = document.querySelector('select[name="product_id"]');
    const productNameInput = document.querySelector('input[name="product_name"]');
    const purchaseDateInput = document.querySelector('input[name="purchase_date"]');

    // Variable globale pour la sélection multiple
    let selectedProductIndexes = [];

    // Sélectionner un produit dans le formulaire
    function selectProduct(item) {
        if (productSelect && item.product_id) {
            const option = productSelect.querySelector(`option[value="${item.product_id}"]`);
            if (option) {
                productSelect.value = item.product_id;
                if (productNameInput) productNameInput.value = '';
            } else if (productNameInput) {
                productSelect.value = '';
                productNameInput.value = item.product_name;
            }
        } else if (productNameInput) {
            productNameInput.value = item.product_name;
        }
        selectedProductIndexes = [];
    }

    // Appliquer la sélection multiple de produits au formulaire
    function applyMultiProductSelection(items) {
        const selectedItems = selectedProductIndexes.map(i => items[i]);

        if (selectedItems.length === 0) {
            if (productSelect) productSelect.value = '';
            if (productNameInput) productNameInput.value = '';
        } else if (selectedItems.length === 1) {
            // Un seul produit : sélectionner (garder la liste visible)
            selectProduct(selectedItems[0]);
        } else {
            // Plusieurs produits : garder la liste visible
            if (productSelect) productSelect.value = '';
            if (productNameInput) {
                productNameInput.value = selectedItems.map(item => item.product_name).join(', ');
            }
        }
    }

    // Afficher une liste de produits à choisir (plusieurs articles)
    function showProductSelection(items) {
        // Supprimer une éventuelle liste précédente
        const existing = document.getElementById('productSelectionList');
        if (existing) existing.remove();

        const productCard = productSelect ? productSelect.closest('.card-body') : null;
        if (!productCard) return;

        let html = '<div id="productSelectionList" class="alert alert-primary mt-3 mb-0">';
        html += '<strong><i class="bi bi-list-ul me-1"></i>Articles de la facture — cochez le(s) produit(s) concerné(s) :</strong>';
        html += '<div class="list-group mt-2">';
        items.forEach((item, index) => {
            const checked = selectedProductIndexes.includes(index) ? 'checked' : '';
            html += `
                <label class="list-group-item list-group-item-action d-flex align-items-center" style="cursor:pointer">
                    <input type="checkbox" class="form-check-input me-2 chk-pick-product" data-index="${index}" value="${index}" ${checked}>
                    <span><i class="bi bi-box me-1"></i>${item.product_name} <span class="text-muted">(x${item.quantity})</span></span>
                </label>
            `;
        });
        html += '</div></div>';

        productCard.insertAdjacentHTML('beforeend', html);

        productCard.querySelectorAll('.chk-pick-product').forEach(chk => {
            chk.addEventListener('change', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                if (this.checked) {
                    if (!selectedProductIndexes.includes(idx)) selectedProductIndexes.push(idx);
                } else {
                    selectedProductIndexes = selectedProductIndexes.filter(i => i !== idx);
                }
                applyMultiProductSelection(items);
            });
        });
        applyMultiProductSelection(items);
    }

    // Fonction pour auto-remplir le formulaire avec les données d'une vente
    function fillFormFromSale(sale) {
        // Ajouter ou mettre à jour le champ hidden sale_id
        let saleIdInput = document.querySelector('input[name="sale_id"]');
        if (!saleIdInput) {
            saleIdInput = document.createElement('input');
            saleIdInput.type = 'hidden';
            saleIdInput.name = 'sale_id';
            saleSearchSection.appendChild(saleIdInput);
        }
        saleIdInput.value = sale.id;

        // Auto-remplir le client
        if (sale.customer && customerSelect) {
            customerSelect.value = sale.customer.id;
        }

        // Auto-remplir le produit
        if (sale.items && sale.items.length === 1) {
            // Un seul article : sélectionner directement
            selectProduct(sale.items[0]);
        } else if (sale.items && sale.items.length > 1) {
            // Plusieurs articles : afficher une liste pour choisir
            showProductSelection(sale.items);
        }

        // Auto-remplir la date d'achat
        if (purchaseDateInput) {
            const saleDate = sale.completed_at || sale.created_at;
            if (saleDate) {
                purchaseDateInput.value = new Date(saleDate).toISOString().split('T')[0];
            }
        }

        // Afficher le résumé de la vente sélectionnée
        const itemsList = sale.items.map(item => item.product_name + ' (x' + item.quantity + ')').join(', ');
        saleResultDiv.innerHTML = `
            <div class="alert alert-info mb-0">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong><i class="bi bi-cart-check"></i> Vente sélectionnée</strong><br>
                        <span class="fw-bold">${sale.invoice_number}</span><br>
                        Client : ${sale.customer ? sale.customer.full_name : 'Anonyme'}<br>
                        Articles : ${itemsList}<br>
                        Date : ${new Date(sale.created_at).toLocaleDateString('fr-FR')}
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="clearSaleSelection">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
        `;
        saleResultDiv.classList.remove('d-none');

        // Bouton pour annuler la sélection
        document.getElementById('clearSaleSelection').addEventListener('click', function() {
            const hiddenInput = document.querySelector('input[name="sale_id"]');
            if (hiddenInput) hiddenInput.remove();
            customerSelect.value = '';
            productSelect.value = '';
            if (productNameInput) productNameInput.value = '';
            if (purchaseDateInput) purchaseDateInput.value = '';
            const selectionList = document.getElementById('productSelectionList');
            if (selectionList) selectionList.remove();
            saleResultDiv.innerHTML = '';
            saleResultDiv.classList.add('d-none');
            saleSearchInput.value = '';
        });
    }

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
                    } else if (data.length === 1) {
                        // Une seule vente trouvée : auto-remplir directement
                        fillFormFromSale(data[0]);
                        return;
                    } else {
                        let html = '<div class="list-group">';
                        data.forEach(sale => {
                            const warrantyBadge = sale.warranty_valid 
                                ? `<span class="badge bg-success"><i class="bi bi-shield-check"></i> Garantie: ${sale.warranty_days_remaining}j</span>`
                                : `<span class="badge bg-danger"><i class="bi bi-shield-x"></i> Garantie expirée (${sale.warranty_expiry})</span>`;
                            const rowClass = sale.warranty_valid ? '' : 'list-group-item-danger';
                            const itemsList = sale.items.map(item => item.product_name).join(', ');
                            
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
                                        ${itemsList ? ' — ' + itemsList : ''}
                                    </small>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-primary btn-select-sale" data-sale='${JSON.stringify(sale).replace(/'/g, "&#39;")}'>
                                            <i class="bi bi-check-circle"></i> Sélectionner
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        saleResultDiv.innerHTML = html;

                        // Attacher les événements aux boutons "Sélectionner"
                        saleResultDiv.querySelectorAll('.btn-select-sale').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const saleData = JSON.parse(this.getAttribute('data-sale'));
                                fillFormFromSale(saleData);
                            });
                        });
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
