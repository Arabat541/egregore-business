@extends('layouts.app')

@section('title', 'Ajustement manuel de stock')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-sliders"></i> Ajustement manuel de stock</h2>
    <a href="{{ route('admin.stock-movements.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.stock-movements.adjustment.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">Boutique <span class="text-danger">*</span></label>
                        <select name="shop_id" id="shop_id" class="form-select @error('shop_id') is-invalid @enderror" required>
                            <option value="">Sélectionner une boutique</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" {{ old('shop_id') == $shop->id ? 'selected' : '' }}>
                                    {{ $shop->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('shop_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Produit <span class="text-danger">*</span></label>
                        <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                            <option value="">Sélectionner un produit</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" 
                                        data-shop="{{ $product->shop_id }}"
                                        data-stock="{{ $product->quantity }}"
                                        {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} ({{ $product->category->name ?? '-' }}) - Stock: {{ $product->quantity }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Stock actuel</label>
                        <input type="text" id="current_stock" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Type d'ajustement <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="adjustment_type" id="type_add" value="add" {{ old('adjustment_type', 'add') == 'add' ? 'checked' : '' }}>
                            <label class="btn btn-outline-success" for="type_add">
                                <i class="bi bi-plus-circle"></i> Ajouter
                            </label>

                            <input type="radio" class="btn-check" name="adjustment_type" id="type_remove" value="remove" {{ old('adjustment_type') == 'remove' ? 'checked' : '' }}>
                            <label class="btn btn-outline-danger" for="type_remove">
                                <i class="bi bi-dash-circle"></i> Retirer
                            </label>

                            <input type="radio" class="btn-check" name="adjustment_type" id="type_set" value="set" {{ old('adjustment_type') == 'set' ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary" for="type_set">
                                <i class="bi bi-arrow-repeat"></i> Définir à
                            </label>
                        </div>
                        @error('adjustment_type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantité <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" 
                               value="{{ old('quantity', 1) }}" min="1" required>
                        @error('quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Motif <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" 
                                  rows="3" required placeholder="Indiquez le motif de l'ajustement...">{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Ex: Casse, Perte, Erreur de saisie, Retour, etc.</small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-lg"></i> Enregistrer l'ajustement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-body">
                <h5><i class="bi bi-info-circle"></i> Aide</h5>
                <hr>
                <p><strong>Ajouter :</strong> Augmente le stock du nombre indiqué.</p>
                <p><strong>Retirer :</strong> Diminue le stock du nombre indiqué.</p>
                <p><strong>Définir à :</strong> Remplace le stock par le nombre indiqué.</p>
                <hr>
                <p class="text-muted small">
                    <i class="bi bi-exclamation-triangle"></i> 
                    Tous les ajustements sont enregistrés dans le journal des mouvements de stock.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const shopSelect = document.getElementById('shop_id');
    const productSelect = document.getElementById('product_id');
    const currentStockInput = document.getElementById('current_stock');
    const productOptions = productSelect.querySelectorAll('option[data-shop]');
    
    function filterProducts() {
        const selectedShop = shopSelect.value;
        
        productOptions.forEach(option => {
            if (!selectedShop || option.dataset.shop === selectedShop) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
        
        // Reset product selection if not matching
        const selectedOption = productSelect.selectedOptions[0];
        if (selectedOption && selectedOption.dataset.shop && selectedOption.dataset.shop !== selectedShop) {
            productSelect.value = '';
            currentStockInput.value = '';
        }
    }
    
    function updateStock() {
        const selectedOption = productSelect.selectedOptions[0];
        if (selectedOption && selectedOption.dataset.stock !== undefined) {
            currentStockInput.value = selectedOption.dataset.stock + ' unités';
        } else {
            currentStockInput.value = '';
        }
    }
    
    shopSelect.addEventListener('change', filterProducts);
    productSelect.addEventListener('change', updateStock);
    
    // Initialize
    filterProducts();
    updateStock();
});
</script>
@endpush
@endsection
