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
                        <div class="position-relative">
                            <input type="text" id="product_search" class="form-control @error('product_id') is-invalid @enderror"
                                   placeholder="Tapez pour rechercher un produit..." autocomplete="off">
                            <div id="product_results" class="position-absolute w-100 bg-white border rounded shadow-sm"
                                 style="z-index:1000; max-height:260px; overflow-y:auto; display:none;"></div>
                        </div>
                        <input type="hidden" name="product_id" id="product_id" value="{{ old('product_id') }}" required>
                        <div id="product_selected" class="mt-2 p-2 bg-light rounded d-none">
                            <span id="product_selected_label"></span>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" id="clear_product">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                        @error('product_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
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
    const shopSelect      = document.getElementById('shop_id');
    const searchInput     = document.getElementById('product_search');
    const resultsBox      = document.getElementById('product_results');
    const hiddenInput     = document.getElementById('product_id');
    const selectedBox     = document.getElementById('product_selected');
    const selectedLabel   = document.getElementById('product_selected_label');
    const clearBtn        = document.getElementById('clear_product');
    const currentStockInput = document.getElementById('current_stock');

    // Données produits embarquées
    const allProducts = @json($products->map(fn($p) => [
        'id'       => $p->id,
        'name'     => $p->name,
        'category' => $p->category->name ?? '-',
        'stock'    => $p->quantity_in_stock,
        'shop_id'  => $p->shop_id,
    ]));

    function getFilteredProducts(query) {
        const shopId = shopSelect.value;
        const q = query.toLowerCase().trim();
        return allProducts.filter(p => {
            const matchShop = !shopId || String(p.shop_id) === shopId;
            const matchQ    = !q || p.name.toLowerCase().includes(q)
                                 || p.category.toLowerCase().includes(q);
            return matchShop && matchQ;
        });
    }

    function showResults(products) {
        resultsBox.innerHTML = '';
        if (!products.length) {
            resultsBox.innerHTML = '<div class="p-2 text-muted">Aucun produit trouvé</div>';
            resultsBox.style.display = 'block';
            return;
        }
        products.slice(0, 50).forEach(p => {
            const div = document.createElement('div');
            div.className = 'p-2 border-bottom product-result-item';
            div.style.cursor = 'pointer';
            div.innerHTML = `<strong>${p.name}</strong> <span class="text-muted small">(${p.category})</span> — Stock&nbsp;: <strong>${p.stock}</strong>`;
            div.addEventListener('mouseenter', () => div.classList.add('bg-light'));
            div.addEventListener('mouseleave', () => div.classList.remove('bg-light'));
            div.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selectProduct(p);
            });
            resultsBox.appendChild(div);
        });
        resultsBox.style.display = 'block';
    }

    function selectProduct(p) {
        hiddenInput.value = p.id;
        currentStockInput.value = p.stock + ' unités';
        selectedLabel.textContent = p.name + ' (' + p.category + ') — Stock : ' + p.stock;
        selectedBox.classList.remove('d-none');
        searchInput.value = '';
        resultsBox.style.display = 'none';
    }

    function clearProduct() {
        hiddenInput.value = '';
        currentStockInput.value = '';
        searchInput.value = '';
        selectedBox.classList.add('d-none');
        resultsBox.style.display = 'none';
    }

    searchInput.addEventListener('input', function() {
        if (this.value.length === 0 && !shopSelect.value) {
            resultsBox.style.display = 'none';
            return;
        }
        showResults(getFilteredProducts(this.value));
    });

    searchInput.addEventListener('focus', function() {
        if (getFilteredProducts(this.value).length) {
            showResults(getFilteredProducts(this.value));
        }
    });

    searchInput.addEventListener('blur', function() {
        setTimeout(() => { resultsBox.style.display = 'none'; }, 150);
    });

    shopSelect.addEventListener('change', function() {
        clearProduct();
        if (searchInput.value) showResults(getFilteredProducts(searchInput.value));
    });

    clearBtn.addEventListener('click', clearProduct);
});
</script>
@endpush
@endsection
