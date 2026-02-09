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
        <form action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}" method="POST">
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
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Code-barres</label>
                        <input type="text" class="form-control @error('barcode') is-invalid @enderror" 
                               name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}" 
                               placeholder="Scanner ou saisir">
                        @error('barcode')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">SKU / Référence</label>
                        <input type="text" class="form-control @error('sku') is-invalid @enderror" 
                               name="sku" value="{{ old('sku', $product->sku ?? '') }}">
                        @error('sku')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
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

            <hr>
            <h5 class="mb-3"><i class="bi bi-currency-dollar"></i> Prix</h5>

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
                        <label class="form-label">Prix de vente (FCFA) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('selling_price') is-invalid @enderror" 
                               name="selling_price" value="{{ old('selling_price', $product->selling_price ?? 0) }}" min="0" required>
                        @error('selling_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Prix revendeur (FCFA)</label>
                        <input type="number" class="form-control @error('reseller_price') is-invalid @enderror" 
                               name="reseller_price" value="{{ old('reseller_price', $product->reseller_price ?? '') }}" min="0">
                        @error('reseller_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Laisser vide pour utiliser le prix de vente</small>
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
@endsection
