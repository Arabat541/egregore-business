@php
    $typeLabels = ['phone' => 'Téléphone', 'accessory' => 'Accessoire', 'spare_part' => 'Pièce détachée'];
    $typeColors = ['phone' => '#6366f1', 'accessory' => '#10b981', 'spare_part' => '#f59e0b'];
    $typeBg = ['phone' => 'rgba(99,102,241,.1)', 'accessory' => 'rgba(16,185,129,.1)', 'spare_part' => 'rgba(245,158,11,.1)'];
    $typeIcons = ['phone' => 'bi-phone', 'accessory' => 'bi-headphones', 'spare_part' => 'bi-tools'];
@endphp

<div class="sf-product-card">
    @if($product->quantity_in_stock <= 3 && $product->quantity_in_stock > 0)
        <span class="stock-badge bg-danger bg-opacity-10 text-danger">
            <i class="bi bi-exclamation-circle"></i> Plus que {{ $product->quantity_in_stock }}
        </span>
    @endif
    <a href="{{ route('storefront.product', $product->id) }}" class="text-decoration-none">
        <div class="card-img-wrap">
            @if($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}">
            @else
                <i class="bi {{ $typeIcons[$product->type] ?? 'bi-box' }}"></i>
            @endif
        </div>
    </a>
    <div class="card-body d-flex flex-column">
        <div class="mb-2">
            <span class="badge-type" style="background:{{ $typeBg[$product->type] ?? 'rgba(0,0,0,.06)' }}; color:{{ $typeColors[$product->type] ?? '#64748b' }}; display:inline-block; padding:.25rem .6rem; border-radius:6px;">
                {{ $typeLabels[$product->type] ?? $product->type }}
            </span>
        </div>
        <a href="{{ route('storefront.product', $product->id) }}" class="product-name mb-1">{{ $product->name }}</a>
        @if($product->brand)
            <small class="text-muted mb-1" style="font-size:.78rem;">{{ $product->brand }} {{ $product->model }}</small>
        @endif
        <div class="product-shop mb-2">
            <i class="bi bi-geo-alt-fill" style="font-size:.7rem;"></i> {{ $product->shop->name ?? 'Boutique' }}
        </div>
        <div class="mt-auto d-flex justify-content-between align-items-center pt-2" style="border-top:1px solid var(--sf-border);">
            <div>
                <span class="product-price">{{ number_format($product->normal_price, 0, ',', ' ') }}</span>
                <small> FCFA</small>
            </div>
            <form action="{{ route('storefront.cart.add') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn-add-cart" title="Ajouter au panier">
                    <i class="bi bi-bag-plus"></i>
                </button>
            </form>
        </div>
    </div>
</div>
