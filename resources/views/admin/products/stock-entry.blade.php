@extends('layouts.app')

@section('title', 'Entrée de stock')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-plus-circle"></i> Entrée de stock</h2>
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-box-seam"></i> {{ $product->name }}
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th>Catégorie</th>
                        <td>{{ $product->category->name }}</td>
                    </tr>
                    <tr>
                        <th>Code-barres</th>
                        <td><code>{{ $product->barcode ?: '-' }}</code></td>
                    </tr>
                    <tr>
                        <th>Stock actuel</th>
                        <td>
                            <span class="badge bg-{{ $product->quantity_in_stock <= 0 ? 'danger' : ($product->quantity_in_stock <= $product->stock_alert_threshold ? 'warning' : 'success') }} fs-5">
                                {{ $product->quantity_in_stock }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Prix d'achat</th>
                        <td>{{ number_format($product->purchase_price, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-lg"></i> Ajouter au stock
            </div>
            <div class="card-body">
                <form action="{{ route('admin.products.store-stock-entry', $product) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Quantité à ajouter <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg @error('quantity') is-invalid @enderror" 
                               name="quantity" value="{{ old('quantity', 1) }}" min="1" required autofocus>
                        @error('quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Prix d'achat unitaire (FCFA)</label>
                        <input type="number" class="form-control @error('unit_price') is-invalid @enderror" 
                               name="unit_price" value="{{ old('unit_price', $product->purchase_price) }}" min="0">
                        @error('unit_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Laisser vide pour conserver le prix d'achat actuel</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fournisseur</label>
                        @php
                            $currentSupplier = $product->supplierPrices()->with('supplier')->orderBy('unit_price', 'asc')->first();
                            $suppliers = \App\Models\Supplier::active()->orderBy('company_name')->get();
                        @endphp
                        <select class="form-select @error('supplier_id') is-invalid @enderror" name="supplier_id">
                            <option value="">-- Aucun fournisseur --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" 
                                        {{ old('supplier_id', $currentSupplier?->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->company_name }}
                                    @if($currentSupplier && $currentSupplier->supplier_id == $supplier->id)
                                        ⭐ (actuel - {{ number_format($currentSupplier->unit_price, 0, ',', ' ') }} F)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Le prix sera enregistré dans l'historique de ce fournisseur</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Référence / N° facture fournisseur</label>
                        <input type="text" class="form-control @error('reference') is-invalid @enderror" 
                               name="reference" value="{{ old('reference') }}" placeholder="Ex: FAC-2025-001">
                        @error('reference')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  name="notes" rows="2" placeholder="Notes optionnelles...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-plus-circle"></i> Valider l'entrée
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
