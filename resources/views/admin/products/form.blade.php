@extends('layouts.app')

@section('title', isset($product) ? 'Modifier produit' : 'Nouveau produit')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="bi bi-{{ isset($product) ? 'pencil' : 'plus-circle' }}"></i> 
        {{ isset($product) ? 'Modifier le produit' : 'Nouveau produit' }}
    </h2>
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($product))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nom du produit <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               name="name" value="{{ old('name', $product->name ?? '') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Boutique <span class="text-danger">*</span></label>
                        <select class="form-select @error('shop_id') is-invalid @enderror" name="shop_id" required>
                            <option value="">Sélectionner une boutique</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" 
                                        {{ old('shop_id', $product->shop_id ?? '') == $shop->id ? 'selected' : '' }}>
                                    {{ $shop->name }} ({{ $shop->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('shop_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                        <select class="form-select @error('category_id') is-invalid @enderror" name="category_id" required>
                            <option value="">Sélectionner une catégorie</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                        {{ old('category_id', $product->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">SKU / Référence</label>
                        <input type="text" class="form-control @error('sku') is-invalid @enderror" 
                               name="sku" value="{{ old('sku', $product->sku ?? '') }}">
                        @error('sku')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Marque</label>
                        <input type="text" class="form-control @error('brand') is-invalid @enderror" 
                               name="brand" value="{{ old('brand', $product->brand ?? '') }}" 
                               placeholder="Ex: Samsung, Apple...">
                        @error('brand')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          name="description" rows="2">{{ old('description', $product->description ?? '') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Image du produit --}}
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-image me-1"></i>Image du produit</label>
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex gap-2 mb-2">
                            <label class="btn btn-outline-primary flex-fill" for="imageFile" style="cursor:pointer;">
                                <i class="bi bi-folder2-open me-1"></i> Parcourir fichiers
                            </label>
                            <button type="button" class="btn btn-outline-success flex-fill" id="btnCamera">
                                <i class="bi bi-camera me-1"></i> Prendre une photo
                            </button>
                        </div>
                        <input type="file" class="form-control d-none @error('image') is-invalid @enderror" 
                               name="image" id="imageFile" accept="image/*">
                        <input type="file" class="d-none" id="cameraInput" accept="image/*" capture="environment">
                        @error('image')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">JPG, PNG ou WebP. Max 2 Mo.</small>
                    </div>
                    <div class="col-md-4 text-center">
                        <div id="imagePreviewContainer" style="width:120px; height:120px; border:2px dashed #dee2e6; border-radius:12px; display:flex; align-items:center; justify-content:center; margin:auto; overflow:hidden; background:#f8f9fa;">
                            @if(isset($product) && $product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" id="imagePreview" style="width:100%; height:100%; object-fit:cover;">
                            @else
                                <div id="imagePreviewPlaceholder">
                                    <i class="bi bi-image text-muted" style="font-size:2rem;"></i>
                                </div>
                                <img src="" id="imagePreview" style="width:100%; height:100%; object-fit:cover; display:none;">
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <hr>
            <h5 class="mb-3"><i class="bi bi-currency-dollar"></i> Prix</h5>
            
            <div class="alert alert-info" id="priceStructureInfo">
                <i class="bi bi-info-circle"></i> <strong>Structure des prix :</strong>
                <ul class="mb-0 mt-2" id="priceStructureList">
                    <li><strong>Prix normal</strong> : Client régulier</li>
                    <li><strong>Prix réparateur</strong> : Réparateur (1–<span id="lblSemiMin">{{ old('qty_semi_wholesale_min', $product->qty_semi_wholesale_min ?? 3) - 1 }}</span> pcs)</li>
                    <li><strong>Prix demi-gros</strong> : Réparateur (<span id="lblSemiMin2">{{ old('qty_semi_wholesale_min', $product->qty_semi_wholesale_min ?? 3) }}</span>–<span id="lblWholesaleMin">{{ old('qty_wholesale_min', $product->qty_wholesale_min ?? 10) - 1 }}</span> pcs)</li>
                    <li><strong>Prix de gros</strong> : Réparateur (<span id="lblWholesaleMin2">{{ old('qty_wholesale_min', $product->qty_wholesale_min ?? 10) }}</span>+ pcs)</li>
                </ul>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Prix d'achat (FCFA)</label>
                        <input type="number" class="form-control @error('purchase_price') is-invalid @enderror" 
                               name="purchase_price" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" min="0">
                        @error('purchase_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Prix normal (FCFA) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('normal_price') is-invalid @enderror" 
                               name="normal_price" value="{{ old('normal_price', $product->normal_price ?? 0) }}" min="0" required>
                        <small class="text-muted">Client boutique (1-2 pcs)</small>
                        @error('normal_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Prix réparateur (FCFA)</label>
                        <input type="number" class="form-control @error('reseller_price') is-invalid @enderror" 
                               name="reseller_price" value="{{ old('reseller_price', $product->reseller_price ?? '') }}" min="0">
                        <small class="text-muted">Réparateur (1-2 pcs)</small>
                        @error('reseller_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Prix demi-gros (FCFA)</label>
                        <input type="number" class="form-control @error('semi_wholesale_price') is-invalid @enderror" 
                               name="semi_wholesale_price" value="{{ old('semi_wholesale_price', $product->semi_wholesale_price ?? '') }}" min="0">
                        <small class="text-muted">Réparateur (≥<span id="lblSemiMinPrice">{{ old('qty_semi_wholesale_min', $product->qty_semi_wholesale_min ?? 3) }}</span> pcs)</small>
                        @error('semi_wholesale_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Prix de gros (FCFA)</label>
                        <input type="number" class="form-control @error('wholesale_price') is-invalid @enderror" 
                               name="wholesale_price" value="{{ old('wholesale_price', $product->wholesale_price ?? '') }}" min="0">
                        <small class="text-muted">Réparateur (≥<span id="lblWholesaleMinPrice">{{ old('qty_wholesale_min', $product->qty_wholesale_min ?? 10) }}</span> pcs)</small>
                        @error('wholesale_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Seuils de quantité configurables --}}
            <div class="row">
                <div class="col-12"><h6 class="text-muted"><i class="bi bi-sliders"></i> Seuils de quantité pour les tarifs réparateur</h6></div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Qté min pour prix demi-gros <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('qty_semi_wholesale_min') is-invalid @enderror"
                               name="qty_semi_wholesale_min" id="qtySemiMin"
                               value="{{ old('qty_semi_wholesale_min', $product->qty_semi_wholesale_min ?? 3) }}"
                               min="2" required>
                        <small class="text-muted">Défaut : 3. À partir de cette quantité, le prix demi-gros s’applique.</small>
                        @error('qty_semi_wholesale_min')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Qté min pour prix de gros <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('qty_wholesale_min') is-invalid @enderror"
                               name="qty_wholesale_min" id="qtyWholesaleMin"
                               value="{{ old('qty_wholesale_min', $product->qty_wholesale_min ?? 10) }}"
                               min="2" required>
                        <small class="text-muted">Défaut : 10. À partir de cette quantité, le prix de gros s’applique.</small>
                        @error('qty_wholesale_min')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <hr>
            <h5 class="mb-3"><i class="bi bi-box-seam"></i> Stock</h5>

            <div class="row">
                @if(!isset($product))
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Quantité initiale</label>
                        <input type="number" class="form-control @error('quantity_in_stock') is-invalid @enderror" 
                               name="quantity_in_stock" value="{{ old('quantity_in_stock', 0) }}" min="0">
                        @error('quantity_in_stock')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                @else
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Stock actuel</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-light" value="{{ $product->quantity_in_stock }}" readonly>
                            <a href="{{ route('admin.products.stock-entry', $product) }}" class="btn btn-outline-success">
                                <i class="bi bi-plus-lg"></i> Entrée stock
                            </a>
                        </div>
                        <small class="text-muted">Le stock est géré via les entrées/sorties pour traçabilité</small>
                    </div>
                </div>
                @endif
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Seuil d'alerte stock</label>
                        <input type="number" class="form-control @error('stock_alert_threshold') is-invalid @enderror" 
                               name="stock_alert_threshold" value="{{ old('stock_alert_threshold', $product->stock_alert_threshold ?? 5) }}" min="0">
                        @error('stock_alert_threshold')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Emplacement</label>
                        <input type="text" class="form-control @error('location') is-invalid @enderror" 
                               name="location" value="{{ old('location', $product->location ?? '') }}" 
                               placeholder="Ex: Rayon A, Étagère 3">
                        @error('location')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                                   {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label">Produit actif</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="allow_negative_stock" value="1" 
                                   {{ old('allow_negative_stock', $product->allow_negative_stock ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label">Autoriser stock négatif</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section Fournisseur (uniquement à la création) --}}
            @if(!isset($product))
            <hr>
            <h5 class="mb-3"><i class="bi bi-truck"></i> Fournisseur <small class="text-muted fw-normal">(optionnel)</small></h5>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Associer ce produit à un fournisseur permet de suivre les prix d'achat et de faciliter les réapprovisionnements.
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Fournisseur</label>
                        <select class="form-select @error('supplier_id') is-invalid @enderror" name="supplier_id" id="supplierSelect">
                            <option value="">Aucun fournisseur</option>
                            @php
                                $suppliers = \App\Models\Supplier::active()->orderBy('company_name')->get();
                            @endphp
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->company_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Prix d'achat fournisseur (FCFA)</label>
                        <input type="number" class="form-control @error('supplier_price') is-invalid @enderror" 
                               name="supplier_price" id="supplierPriceInput" value="{{ old('supplier_price') }}" min="0"
                               placeholder="Identique au prix d'achat si vide">
                        @error('supplier_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Si différent du prix d'achat général</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Délai de livraison (jours)</label>
                        <input type="number" class="form-control @error('supplier_lead_time') is-invalid @enderror" 
                               name="supplier_lead_time" value="{{ old('supplier_lead_time') }}" min="0"
                               placeholder="Ex: 3">
                        @error('supplier_lead_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <a href="{{ route('admin.suppliers.create') }}" class="btn btn-outline-secondary btn-sm" target="_blank">
                            <i class="bi bi-plus-lg me-1"></i>Créer un nouveau fournisseur
                        </a>
                    </div>
                </div>
            </div>
            @endif

            {{-- Caractéristiques du produit --}}
            <hr>
            <h5 class="mb-3"><i class="bi bi-list-check"></i> Caractéristiques <small class="text-muted fw-normal">(optionnel)</small></h5>
            
            <div id="characteristicsContainer">
                @php
                    $chars = old('char_keys', isset($product) && $product->characteristics ? array_keys($product->characteristics) : []);
                    $vals = old('char_values', isset($product) && $product->characteristics ? array_values($product->characteristics) : []);
                @endphp
                @if(count($chars) > 0)
                    @foreach($chars as $i => $key)
                        <div class="row mb-2 char-row">
                            <div class="col-5">
                                <input type="text" class="form-control" name="char_keys[]" value="{{ $key }}" placeholder="Ex: RAM, Stockage, Écran...">
                            </div>
                            <div class="col-5">
                                <input type="text" class="form-control" name="char_values[]" value="{{ $vals[$i] ?? '' }}" placeholder="Ex: 8 Go, 128 Go, 6.5 pouces...">
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.char-row').remove()">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="addCharBtn">
                <i class="bi bi-plus-lg me-1"></i>Ajouter une caractéristique
            </button>

            <hr>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> {{ isset($product) ? 'Mettre à jour' : 'Créer' }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Image preview for file and camera inputs
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('imagePreviewPlaceholder');
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.getElementById('imageFile').addEventListener('change', function() { previewImage(this); });
document.getElementById('cameraInput').addEventListener('change', function() {
    // Copy camera file to the main file input via DataTransfer
    const mainInput = document.getElementById('imageFile');
    const dt = new DataTransfer();
    dt.items.add(this.files[0]);
    mainInput.files = dt.files;
    previewImage(this);
});

document.getElementById('btnCamera').addEventListener('click', function() {
    document.getElementById('cameraInput').click();
});

// Characteristics dynamic rows
document.getElementById('addCharBtn').addEventListener('click', function() {
    const container = document.getElementById('characteristicsContainer');
    const row = document.createElement('div');
    row.className = 'row mb-2 char-row';
    row.innerHTML = `
        <div class="col-5">
            <input type="text" class="form-control" name="char_keys[]" placeholder="Ex: RAM, Stockage, Écran...">
        </div>
        <div class="col-5">
            <input type="text" class="form-control" name="char_values[]" placeholder="Ex: 8 Go, 128 Go, 6.5 pouces...">
        </div>
        <div class="col-2">
            <button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.char-row').remove()">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
    row.querySelector('input').focus();
});

// Mise à jour en temps réel des libellés de seuils dans l'alerte et les sous-titres
function updateThresholdLabels() {
    const semi = parseInt(document.getElementById('qtySemiMin')?.value) || 3;
    const whole = parseInt(document.getElementById('qtyWholesaleMin')?.value) || 10;
    const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    setTxt('lblSemiMin',       semi - 1);
    setTxt('lblSemiMin2',      semi);
    setTxt('lblSemiMinPrice',  semi);
    setTxt('lblWholesaleMin',  whole - 1);
    setTxt('lblWholesaleMin2', whole);
    setTxt('lblWholesaleMinPrice', whole);
}
document.getElementById('qtySemiMin')?.addEventListener('input', updateThresholdLabels);
document.getElementById('qtyWholesaleMin')?.addEventListener('input', updateThresholdLabels);
</script>
@endpush
@endsection
