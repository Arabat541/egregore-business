@extends('storefront.layouts.app')

@section('title', $product->name)

@section('content')
<div class="container py-4 py-lg-5">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small mb-0" style="font-size:.85rem;">
            <li class="breadcrumb-item"><a href="{{ route('storefront.home') }}" class="text-decoration-none" style="color:var(--sf-gray);">Accueil</a></li>
            <li class="breadcrumb-item"><a href="{{ route('storefront.catalog') }}" class="text-decoration-none" style="color:var(--sf-gray);">Catalogue</a></li>
            @if($product->category)
                <li class="breadcrumb-item"><a href="{{ route('storefront.catalog', ['category' => $product->category->slug]) }}" class="text-decoration-none" style="color:var(--sf-gray);">{{ $product->category->name }}</a></li>
            @endif
            <li class="breadcrumb-item active fw-semibold">{{ Str::limit($product->name, 40) }}</li>
        </ol>
    </nav>

    <div class="row g-4 g-lg-5">
        {{-- Image placeholder --}}
        <div class="col-lg-5">
            <div class="sf-card" style="overflow:hidden; position:sticky; top:80px;">
                @php
                    $typeIcons = ['phone' => 'bi-phone', 'accessory' => 'bi-headphones', 'spare_part' => 'bi-tools'];
                    $typeGrad = ['phone' => 'rgba(99,102,241,.06)', 'accessory' => 'rgba(16,185,129,.06)', 'spare_part' => 'rgba(245,158,11,.06)'];
                @endphp
                @if($product->image)
                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" style="width:100%; height:420px; object-fit:contain; padding:15px; background:#f8fafc;">
                @else
                    <div class="d-flex align-items-center justify-content-center" style="height:420px; background:linear-gradient(145deg, #f1f5f9, {{ $typeGrad[$product->type] ?? '#e2e8f0' }});">
                        <i class="bi {{ $typeIcons[$product->type] ?? 'bi-box' }}" style="font-size:7rem; color:var(--sf-gray-light); opacity:.5;"></i>
                    </div>
                @endif
            </div>
        </div>

        {{-- Product info --}}
        <div class="col-lg-7">
            @php
                $typeLabels = ['phone' => 'Téléphone', 'accessory' => 'Accessoire', 'spare_part' => 'Pièce détachée'];
                $typeColors = ['phone' => '#6366f1', 'accessory' => '#10b981', 'spare_part' => '#f59e0b'];
                $typeBg = ['phone' => 'rgba(99,102,241,.1)', 'accessory' => 'rgba(16,185,129,.1)', 'spare_part' => 'rgba(245,158,11,.1)'];
            @endphp
            <span class="d-inline-block mb-3 px-3 py-1" style="background:{{ $typeBg[$product->type] ?? 'rgba(0,0,0,.06)' }}; color:{{ $typeColors[$product->type] ?? '#64748b' }}; border-radius:6px; font-size:.8rem; font-weight:600; text-transform:uppercase; letter-spacing:.3px;">
                {{ $typeLabels[$product->type] ?? $product->type }}
            </span>

            <h1 style="font-size:1.75rem; font-weight:800; letter-spacing:-.5px; line-height:1.25; color:var(--sf-dark);">{{ $product->name }}</h1>

            @if($product->brand || $product->model)
                <p class="mb-2" style="color:var(--sf-gray); font-size:.95rem;">
                    @if($product->brand) <span class="fw-semibold">{{ $product->brand }}</span> @endif
                    @if($product->model) {{ $product->model }} @endif
                </p>
            @endif

            @if($product->sku)
                <p class="mb-3" style="color:var(--sf-gray-light); font-size:.82rem;">Réf : {{ $product->sku }}</p>
            @endif

            {{-- Price --}}
            <div class="mb-4 pb-4" style="border-bottom:1px solid var(--sf-border);">
                <span style="font-size:2.25rem; font-weight:900; color:var(--sf-dark); letter-spacing:-1px;">{{ number_format($product->normal_price, 0, ',', ' ') }}</span>
                <span style="font-size:1rem; color:var(--sf-gray); font-weight:500;"> FCFA</span>
            </div>

            {{-- Stock --}}
            <div class="mb-4">
                @if($product->quantity_in_stock > 10)
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-2" style="background:rgba(16,185,129,.08); border-radius:var(--sf-radius-sm); color:#065f46;">
                        <i class="bi bi-check-circle-fill" style="color:var(--sf-success);"></i>
                        <span class="fw-semibold" style="font-size:.9rem;">En stock</span>
                    </div>
                @elseif($product->quantity_in_stock > 0)
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-2" style="background:rgba(245,158,11,.08); border-radius:var(--sf-radius-sm); color:#92400e;">
                        <i class="bi bi-exclamation-triangle-fill" style="color:var(--sf-warning);"></i>
                        <span class="fw-semibold" style="font-size:.9rem;">Plus que {{ $product->quantity_in_stock }} en stock</span>
                    </div>
                @else
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-2" style="background:rgba(239,68,68,.08); border-radius:var(--sf-radius-sm); color:#991b1b;">
                        <i class="bi bi-x-circle-fill" style="color:var(--sf-danger);"></i>
                        <span class="fw-semibold" style="font-size:.9rem;">Rupture de stock</span>
                    </div>
                @endif
            </div>

            {{-- Shop --}}
            <div class="d-flex align-items-center gap-3 mb-4 p-3" style="background:var(--sf-light); border-radius:var(--sf-radius-sm);">
                <div style="width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,var(--sf-primary),var(--sf-primary-light));display:flex;align-items:center;justify-content:center;color:#fff;">
                    <i class="bi bi-shop"></i>
                </div>
                <div>
                    <strong style="font-size:.9rem;">{{ $product->shop->name ?? 'Boutique' }}</strong>
                    @if($product->shop && $product->shop->address)
                        <div style="font-size:.82rem; color:var(--sf-gray);">{{ $product->shop->address }}</div>
                    @endif
                </div>
            </div>

            @if($product->description)
                <div class="mb-4">
                    <h6 class="fw-bold mb-2" style="font-size:.9rem; text-transform:uppercase; letter-spacing:.3px; color:var(--sf-gray);">Description</h6>
                    <ul class="mb-0 ps-3" style="color:var(--sf-dark-2); line-height:1.8; font-size:.95rem; list-style-type:disc;">
                        @foreach(array_filter(preg_split('/\r?\n/', $product->description)) as $line)
                            <li>{{ trim($line) }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Caractéristiques --}}
            @if($product->characteristics && count($product->characteristics) > 0)
                <div class="mb-4">
                    <h6 class="fw-bold mb-2" style="font-size:.9rem; text-transform:uppercase; letter-spacing:.3px; color:var(--sf-gray);">Caractéristiques</h6>
                    <div style="border:1px solid var(--sf-border); border-radius:var(--sf-radius-sm); overflow:hidden;">
                        @foreach($product->characteristics as $key => $value)
                            <div class="d-flex justify-content-between align-items-center px-3 py-2" style="{{ !$loop->last ? 'border-bottom:1px solid var(--sf-border);' : '' }} {{ $loop->even ? 'background:var(--sf-light);' : '' }}">
                                <span class="fw-semibold" style="font-size:.9rem; color:var(--sf-gray);">{{ $key }}</span>
                                <span class="fw-semibold" style="font-size:.9rem; color:var(--sf-dark);">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Category --}}
            @if($product->category)
                <div class="mb-4">
                    <a href="{{ route('storefront.catalog', ['category' => $product->category->slug]) }}" class="sf-filter-tag">
                        <i class="bi bi-tag"></i> {{ $product->category->name }}
                    </a>
                </div>
            @endif

            {{-- Add to cart --}}
            @if($product->quantity_in_stock > 0)
                <form action="{{ route('storefront.cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-inline-flex align-items-center" style="background:#fff; border:1px solid var(--sf-border); border-radius:var(--sf-radius-sm); overflow:hidden;">
                            <button class="btn border-0 px-3 py-2" type="button" onclick="changeQty(-1)" style="font-size:1.1rem; color:var(--sf-gray);">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" name="quantity" id="qty" value="1" min="1" max="{{ $product->quantity_in_stock }}" class="form-control border-0 text-center fw-bold" style="width:50px; box-shadow:none;">
                            <button class="btn border-0 px-3 py-2" type="button" onclick="changeQty(1)" style="font-size:1.1rem; color:var(--sf-gray);">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        <button type="submit" class="btn btn-sf-primary btn-lg flex-grow-1" style="font-size:.95rem;">
                            <i class="bi bi-bag-plus me-2"></i>Ajouter au panier
                        </button>
                    </div>
                </form>
            @else
                <div class="p-3 text-center" style="background:rgba(239,68,68,.06); border-radius:var(--sf-radius-sm); color:#991b1b;">
                    <i class="bi bi-info-circle me-1"></i>Ce produit est actuellement en rupture de stock.
                </div>
            @endif
        </div>
    </div>

    {{-- Related products --}}
    @if($relatedProducts->isNotEmpty())
    <section class="mt-5 pt-5" style="border-top:1px solid var(--sf-border);">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="sf-section-title mb-1">Produits <span class="title-accent">similaires</span></h2>
                <p class="sf-section-subtitle mb-0">Vous aimerez peut-être aussi</p>
            </div>
        </div>
        <div class="row g-3 g-lg-4">
            @foreach($relatedProducts as $relProduct)
                <div class="col-6 col-md-3">
                    @include('storefront.partials.product-card', ['product' => $relProduct])
                </div>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection

@section('scripts')
<script>
function changeQty(delta) {
    const input = document.getElementById('qty');
    let val = parseInt(input.value) + delta;
    val = Math.max(1, Math.min(val, parseInt(input.max)));
    input.value = val;
}
</script>
@endsection
