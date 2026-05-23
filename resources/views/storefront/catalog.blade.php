@extends('storefront.layouts.app')

@section('title', 'Catalogue')

@section('content')
<div class="container py-4 py-lg-5">
    {{-- Page header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-2">
        <div>
            <h1 class="sf-section-title mb-1" style="font-size:1.75rem;">Catalogue</h1>
            <p class="sf-section-subtitle mb-0">
                <strong>{{ $products->total() }}</strong> produit{{ $products->total() > 1 ? 's' : '' }}
                @if(request('search')) pour <em>"{{ request('search') }}"</em> @endif
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small d-none d-md-inline">Trier :</span>
            <select class="form-select form-select-sm" style="width:auto; border-radius:var(--sf-radius-sm); border-color:var(--sf-border); font-size:.85rem;" onchange="window.location.href=this.value;">
                @php $qs = request()->except('sort'); @endphp
                <option value="{{ route('storefront.catalog', array_merge($qs, ['sort' => 'newest'])) }}" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Plus récents</option>
                <option value="{{ route('storefront.catalog', array_merge($qs, ['sort' => 'price_asc'])) }}" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Prix croissant</option>
                <option value="{{ route('storefront.catalog', array_merge($qs, ['sort' => 'price_desc'])) }}" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Prix décroissant</option>
                <option value="{{ route('storefront.catalog', array_merge($qs, ['sort' => 'name'])) }}" {{ request('sort') == 'name' ? 'selected' : '' }}>Nom A-Z</option>
            </select>
        </div>
    </div>

    {{-- Active filters --}}
    @if(request('search') || request('category') || request('type') || request('shop') || request('brand'))
        <div class="d-flex flex-wrap gap-2 mb-4">
            @php $types = ['phone' => 'Téléphones', 'accessory' => 'Accessoires', 'spare_part' => 'Pièces détachées']; @endphp
            @if(request('search'))
                <a href="{{ route('storefront.catalog', request()->except('search')) }}" class="sf-filter-tag">
                    <i class="bi bi-search"></i> "{{ request('search') }}" <i class="bi bi-x"></i>
                </a>
            @endif
            @if(request('category'))
                <a href="{{ route('storefront.catalog', request()->except('category')) }}" class="sf-filter-tag">
                    {{ request('category') }} <i class="bi bi-x"></i>
                </a>
            @endif
            @if(request('type'))
                <a href="{{ route('storefront.catalog', request()->except('type')) }}" class="sf-filter-tag">
                    {{ $types[request('type')] ?? request('type') }} <i class="bi bi-x"></i>
                </a>
            @endif
            @if(request('brand'))
                <a href="{{ route('storefront.catalog', request()->except('brand')) }}" class="sf-filter-tag">
                    {{ request('brand') }} <i class="bi bi-x"></i>
                </a>
            @endif
            <a href="{{ route('storefront.catalog') }}" class="sf-filter-tag" style="background:rgba(239,68,68,.08); color:var(--sf-danger);">
                Effacer tout <i class="bi bi-x-circle"></i>
            </a>
        </div>
    @endif

    <div class="row g-4">
        {{-- Sidebar filters --}}
        <div class="col-lg-3">
            <div class="sf-filter-card" style="position:sticky; top:80px;">
                <div class="filter-header">
                    <i class="bi bi-sliders" style="color:var(--sf-primary);"></i> Filtres
                </div>

                {{-- Catégories --}}
                <div class="filter-section">
                    <div class="filter-section-title">Catégorie</div>
                    <a href="{{ route('storefront.catalog', request()->except('category')) }}"
                       class="sf-filter-link {{ !request('category') ? 'active' : '' }}">
                        Toutes les catégories
                    </a>
                    @foreach($categories as $category)
                        <a href="{{ route('storefront.catalog', array_merge(request()->except('category'), ['category' => $category->slug])) }}"
                           class="sf-filter-link {{ request('category') == $category->slug ? 'active' : '' }}">
                            {{ $category->name }}
                            <span class="filter-count">{{ $category->products_count }}</span>
                        </a>
                    @endforeach
                </div>

                {{-- Type --}}
                <div class="filter-section">
                    <div class="filter-section-title">Type</div>
                    @php $types = ['phone' => 'Téléphones', 'accessory' => 'Accessoires', 'spare_part' => 'Pièces détachées']; @endphp
                    <a href="{{ route('storefront.catalog', request()->except('type')) }}"
                       class="sf-filter-link {{ !request('type') ? 'active' : '' }}">
                        Tous les types
                    </a>
                    @foreach($types as $key => $label)
                        <a href="{{ route('storefront.catalog', array_merge(request()->except('type'), ['type' => $key])) }}"
                           class="sf-filter-link {{ request('type') == $key ? 'active' : '' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                {{-- Boutique --}}
                @if($shops->count() > 1)
                <div class="filter-section">
                    <div class="filter-section-title">Boutique</div>
                    <a href="{{ route('storefront.catalog', request()->except('shop')) }}"
                       class="sf-filter-link {{ !request('shop') ? 'active' : '' }}">
                        Toutes les boutiques
                    </a>
                    @foreach($shops as $shop)
                        <a href="{{ route('storefront.catalog', array_merge(request()->except('shop'), ['shop' => $shop->id])) }}"
                           class="sf-filter-link {{ request('shop') == $shop->id ? 'active' : '' }}">
                            {{ $shop->name }}
                        </a>
                    @endforeach
                </div>
                @endif

                {{-- Marque --}}
                @if($brands->isNotEmpty())
                <div class="filter-section" style="padding-bottom:1.25rem;">
                    <div class="filter-section-title">Marque</div>
                    <a href="{{ route('storefront.catalog', request()->except('brand')) }}"
                       class="sf-filter-link {{ !request('brand') ? 'active' : '' }}">
                        Toutes les marques
                    </a>
                    @foreach($brands->take(15) as $brand)
                        <a href="{{ route('storefront.catalog', array_merge(request()->except('brand'), ['brand' => $brand])) }}"
                           class="sf-filter-link {{ request('brand') == $brand ? 'active' : '' }}">
                            {{ $brand }}
                        </a>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Product grid --}}
        <div class="col-lg-9">
            @if($products->isEmpty())
                <div class="text-center py-5">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:80px;height:80px;background:var(--sf-light);">
                        <i class="bi bi-search" style="font-size:2rem; color:var(--sf-gray-light);"></i>
                    </div>
                    <h5 class="fw-semibold text-muted">Aucun produit trouvé</h5>
                    <p class="text-muted small">Essayez avec d'autres critères de recherche.</p>
                    <a href="{{ route('storefront.catalog') }}" class="btn btn-sf-outline">Voir tous les produits</a>
                </div>
            @else
                <div class="row g-3 g-lg-4">
                    @foreach($products as $product)
                        <div class="col-6 col-md-4">
                            @include('storefront.partials.product-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-4 d-flex justify-content-center">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
