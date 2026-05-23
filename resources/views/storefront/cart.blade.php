@extends('storefront.layouts.app')

@section('title', 'Mon panier')

@section('content')
<div class="container py-4 py-lg-5">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div style="width:48px;height:48px;border-radius:14px;background:rgba(99,102,241,.08);display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-bag" style="font-size:1.3rem; color:var(--sf-primary);"></i>
        </div>
        <div>
            <h1 class="sf-section-title mb-0" style="font-size:1.5rem;">Mon panier</h1>
            @if(!empty($cartItems))
                <p class="sf-section-subtitle mb-0" style="font-size:.85rem;">{{ count($cartItems) }} article{{ count($cartItems) > 1 ? 's' : '' }}</p>
            @endif
        </div>
    </div>

    @if(empty($cartItems))
        <div class="text-center py-5">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:100px;height:100px;background:rgba(99,102,241,.06);">
                <i class="bi bi-bag-x" style="font-size:2.5rem; color:var(--sf-gray-light);"></i>
            </div>
            <h4 class="fw-bold mt-2 mb-1">Votre panier est vide</h4>
            <p class="text-muted mb-4">Parcourez notre catalogue pour trouver ce qu'il vous faut.</p>
            <a href="{{ route('storefront.catalog') }}" class="btn btn-sf-primary px-4">
                <i class="bi bi-grid-3x3-gap me-2"></i>Découvrir nos produits
            </a>
        </div>
    @else
        <div class="row g-4">
            <div class="col-lg-8">
                {{-- Cart items --}}
                <div class="d-flex flex-column gap-3">
                    @foreach($cartItems as $item)
                        <div class="sf-card">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-3">
                                    {{-- Product image --}}
                                    <a href="{{ route('storefront.product', $item['product']->id) }}" class="flex-shrink-0">
                                        <div class="d-flex align-items-center justify-content-center" style="width:80px;height:80px;background:linear-gradient(145deg,#f1f5f9,#e8ecf3);border-radius:var(--sf-radius-sm);">
                                            <i class="bi bi-phone" style="font-size:1.5rem; color:var(--sf-gray-light);"></i>
                                        </div>
                                    </a>
                                    {{-- Product info --}}
                                    <div class="flex-grow-1 min-width-0">
                                        <a href="{{ route('storefront.product', $item['product']->id) }}" class="text-decoration-none fw-semibold" style="color:var(--sf-dark); font-size:.95rem;">
                                            {{ $item['product']->name }}
                                        </a>
                                        <div class="text-muted small mt-1">
                                            <i class="bi bi-geo-alt-fill" style="font-size:.7rem;"></i> {{ $item['product']->shop->name ?? '' }}
                                        </div>
                                        <div class="fw-bold mt-1" style="color:var(--sf-dark);">
                                            {{ number_format($item['product']->normal_price, 0, ',', ' ') }} <small class="fw-normal" style="color:var(--sf-gray);">FCFA</small>
                                        </div>
                                    </div>
                                    {{-- Quantity controls --}}
                                    <div class="d-flex flex-column align-items-end gap-2">
                                        <form action="{{ route('storefront.cart.update') }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                                            <div class="d-inline-flex align-items-center" style="border:1px solid var(--sf-border);border-radius:var(--sf-radius-sm);overflow:hidden;background:#fff;">
                                                <button type="submit" name="quantity" value="{{ $item['quantity'] - 1 }}" class="btn border-0 px-2 py-1" style="color:var(--sf-gray);font-size:.9rem;">
                                                    <i class="bi bi-dash"></i>
                                                </button>
                                                <span class="px-2 fw-bold" style="font-size:.9rem;min-width:28px;text-align:center;">{{ $item['quantity'] }}</span>
                                                <button type="submit" name="quantity" value="{{ $item['quantity'] + 1 }}" class="btn border-0 px-2 py-1" style="color:var(--sf-gray);font-size:.9rem;">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                            </div>
                                        </form>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold" style="color:var(--sf-primary);">{{ number_format($item['line_total'], 0, ',', ' ') }} F</span>
                                            <form action="{{ route('storefront.cart.remove') }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                                                <button type="submit" class="btn border-0 p-1" style="color:var(--sf-danger);font-size:.9rem;" title="Retirer">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <a href="{{ route('storefront.catalog') }}" class="btn btn-sf-ghost">
                        <i class="bi bi-arrow-left me-1"></i>Continuer mes achats
                    </a>
                    <form action="{{ route('storefront.cart.clear') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sf-ghost" style="color:var(--sf-danger);">
                            <i class="bi bi-trash3 me-1"></i>Vider le panier
                        </button>
                    </form>
                </div>
            </div>

            {{-- Order summary --}}
            <div class="col-lg-4">
                <div class="sf-card" style="position:sticky; top:80px;">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3" style="font-size:1.05rem;">Récapitulatif</h5>
                        <div class="d-flex justify-content-between mb-2" style="font-size:.9rem;">
                            <span style="color:var(--sf-gray);">Sous-total</span>
                            <span class="fw-semibold">{{ number_format($total, 0, ',', ' ') }} F</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3" style="font-size:.9rem;">
                            <span style="color:var(--sf-gray);">Livraison</span>
                            <span style="color:var(--sf-gray);">À calculer</span>
                        </div>
                        <div style="height:1px; background:var(--sf-border); margin:0 -.5rem;"></div>
                        <div class="d-flex justify-content-between my-3">
                            <span class="fw-bold" style="font-size:1.1rem;">Total</span>
                            <span class="fw-bold" style="font-size:1.1rem; color:var(--sf-primary);">{{ number_format($total, 0, ',', ' ') }} FCFA</span>
                        </div>
                        <a href="{{ route('storefront.checkout') }}" class="btn btn-sf-primary w-100" style="padding:.7rem;">
                            <i class="bi bi-lock me-1"></i>Passer la commande
                        </a>
                        <div class="text-center mt-3">
                            <small style="color:var(--sf-gray-light); font-size:.78rem;">
                                <i class="bi bi-shield-check me-1"></i>Paiement sécurisé
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
