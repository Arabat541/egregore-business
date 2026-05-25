@extends('storefront.layouts.app')

@section('title', 'Accueil')

@section('content')
    {{-- Hero --}}
    <section class="sf-hero">
        <div class="container position-relative" style="z-index:2;">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="d-inline-block px-3 py-1 mb-3 rounded-pill" style="background:rgba(99,102,241,.15); border:1px solid rgba(99,102,241,.25);">
                        <small class="text-white fw-semibold" style="font-size:.8rem;">
                            <i class="bi bi-stars me-1" style="color:var(--sf-accent);"></i>Boutique en ligne officielle
                        </small>
                    </div>
                    <h1>Trouvez le<br><span class="text-gradient">téléphone idéal</span></h1>
                    <p class="lead mb-4" style="max-width:480px;">Téléphones, accessoires et pièces détachées disponibles dans nos boutiques physiques, livrés chez vous.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('storefront.catalog') }}" class="btn btn-sf-primary btn-lg px-4">
                            <i class="bi bi-grid-3x3-gap me-2"></i>Explorer le catalogue
                        </a>
                        <a href="{{ route('storefront.track') }}" class="btn btn-sf-ghost btn-lg text-white px-4" style="border:1px solid rgba(255,255,255,.15);">
                            <i class="bi bi-search me-2"></i>Suivre ma commande
                        </a>
                    </div>
                    <div class="d-flex gap-4 mt-4 pt-2">
                        <div>
                            <span class="text-white fw-bold fs-5">100%</span>
                            <span class="d-block text-white text-opacity-50" style="font-size:.8rem;">Authentique</span>
                        </div>
                        <div style="width:1px; background:rgba(255,255,255,.15);"></div>
                        <div>
                            <span class="text-white fw-bold fs-5">Rapide</span>
                            <span class="d-block text-white text-opacity-50" style="font-size:.8rem;">Livraison</span>
                        </div>
                        <div style="width:1px; background:rgba(255,255,255,.15);"></div>
                        <div>
                            <span class="text-white fw-bold fs-5">Garanti</span>
                            <span class="d-block text-white text-opacity-50" style="font-size:.8rem;">Service SAV</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 text-center d-none d-lg-block">
                    <div class="hero-decoration">
                        <div class="hero-glow"></div>
                        <i class="bi bi-phone hero-phone-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Categories --}}
    @if($categories->isNotEmpty())
    <section class="py-4 bg-white" style="border-bottom:1px solid var(--sf-border);">
        <div class="container">
            <div class="d-flex flex-wrap gap-2 justify-content-center py-1">
                <a href="{{ route('storefront.catalog') }}" class="sf-category-pill {{ !request('category') ? 'active' : '' }}">
                    <i class="bi bi-grid-3x3-gap"></i> Tous
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('storefront.catalog', ['category' => $category->slug]) }}" class="sf-category-pill">
                        {{ $category->name }} <span class="opacity-50">({{ $category->products_count }})</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Type shortcuts --}}
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="sf-section-title">Nos <span class="title-accent">catégories</span></h2>
                <p class="sf-section-subtitle mt-1">Trouvez exactement ce que vous cherchez</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <a href="{{ route('storefront.catalog', ['type' => 'phone']) }}" class="sf-type-card type-phone">
                        <div class="type-icon" style="background:rgba(99,102,241,.1); color:var(--sf-primary);">
                            <i class="bi bi-phone"></i>
                        </div>
                        <div class="type-label">Téléphones</div>
                        <div class="type-count">Smartphones & feature phones</div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('storefront.catalog', ['type' => 'accessory']) }}" class="sf-type-card type-accessory">
                        <div class="type-icon" style="background:rgba(16,185,129,.1); color:var(--sf-success);">
                            <i class="bi bi-headphones"></i>
                        </div>
                        <div class="type-label">Accessoires</div>
                        <div class="type-count">Coques, chargeurs, écouteurs...</div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('storefront.catalog', ['type' => 'spare_part']) }}" class="sf-type-card type-spare">
                        <div class="type-icon" style="background:rgba(245,158,11,.1); color:var(--sf-accent);">
                            <i class="bi bi-tools"></i>
                        </div>
                        <div class="type-label">Pièces détachées</div>
                        <div class="type-count">Écrans, batteries, connecteurs...</div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Services : suivi réparation + espace réparateur --}}
    <section id="services" class="py-5" style="background:linear-gradient(135deg,#f0f4ff 0%,#fafafa 100%);">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="sf-section-title">Nos <span class="title-accent">services</span></h2>
                <p class="sf-section-subtitle mt-1">Suivi en temps réel et espace dédié à nos réparateurs partenaires</p>
            </div>
            <div class="row g-4 justify-content-center">
                {{-- Suivi de réparation --}}
                <div class="col-md-5">
                    <div class="sf-service-card h-100">
                        <div class="service-icon-wrap" style="background:rgba(245,158,11,.12);color:#d97706;">
                            <i class="bi bi-tools"></i>
                        </div>
                        <h5 class="fw-bold mt-3 mb-1">Suivi de réparation</h5>
                        <p class="text-muted small mb-4">Entrez votre numéro de réparation pour suivre l'avancement de votre appareil en temps réel.</p>
                        <form onsubmit="trackRepair(event)">
                            <div class="input-group">
                                <input type="text" id="repairTicketInput" class="form-control"
                                       placeholder="Ex : REP-20260525-0001"
                                       style="border-radius:10px 0 0 10px;font-size:.9rem;">
                                <button class="btn fw-semibold" type="submit"
                                        style="background:#d97706;color:#fff;border-radius:0 10px 10px 0;">
                                    <i class="bi bi-search me-1"></i>Suivre
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Espace réparateur --}}
                <div class="col-md-5">
                    <div class="sf-service-card h-100 d-flex flex-column">
                        <div class="service-icon-wrap" style="background:rgba(99,102,241,.12);color:var(--sf-primary);">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <h5 class="fw-bold mt-3 mb-1">Espace Réparateur</h5>
                        <p class="text-muted small mb-4 flex-grow-1">Vous êtes réparateur partenaire ? Accédez à votre relevé de compte, vos achats et vos avantages fidélité.</p>
                        <a href="{{ route('reseller-portal.index') }}" class="btn btn-sf-primary">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Accéder à mon espace
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Nouveautés --}}
    <section class="py-5 bg-white">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h2 class="sf-section-title mb-1">Derniers <span class="title-accent">arrivages</span></h2>
                    <p class="sf-section-subtitle mb-0">Nos produits les plus récents</p>
                </div>
                <a href="{{ route('storefront.catalog') }}" class="btn btn-sf-outline btn-sm d-none d-md-inline-flex">
                    Voir tout <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            @if($featuredProducts->isEmpty())
                <div class="text-center py-5">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:80px;height:80px;background:var(--sf-light);">
                        <i class="bi bi-inbox" style="font-size:2rem; color:var(--sf-gray-light);"></i>
                    </div>
                    <p class="text-muted">Aucun produit disponible pour le moment.</p>
                </div>
            @else
                <div class="row g-3 g-lg-4">
                    @foreach($featuredProducts as $product)
                        <div class="col-6 col-md-4 col-lg-3">
                            @include('storefront.partials.product-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>
                <div class="text-center mt-4 d-md-none">
                    <a href="{{ route('storefront.catalog') }}" class="btn btn-sf-outline">
                        Voir tout <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            @endif
        </div>
    </section>

    {{-- Nos boutiques --}}
    @if($shops->isNotEmpty())
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="sf-section-title">Nos <span class="title-accent">boutiques</span></h2>
                <p class="sf-section-subtitle mt-1">Retrouvez-nous dans nos points de vente</p>
            </div>
            <div class="row g-4 justify-content-center">
                @foreach($shops as $shop)
                    <div class="col-md-4">
                        <div class="sf-shop-card h-100">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div class="shop-icon flex-shrink-0">
                                    <i class="bi bi-shop"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">{{ $shop->name }}</h6>
                                    @if($shop->address)
                                        <p class="text-muted small mb-1"><i class="bi bi-geo-alt me-1"></i>{{ $shop->address }}</p>
                                    @endif
                                    @if($shop->phone)
                                        <p class="text-muted small mb-0"><i class="bi bi-telephone me-1"></i>{{ $shop->phone }}</p>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('storefront.catalog', ['shop' => $shop->id]) }}" class="btn btn-sf-outline btn-sm w-100">
                                Voir les produits <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif
@endsection

@section('scripts')
<script>
function trackRepair(e) {
    e.preventDefault();
    var ticket = document.getElementById('repairTicketInput').value.trim();
    if (ticket) {
        window.location.href = '/repair/track/' + encodeURIComponent(ticket);
    } else {
        document.getElementById('repairTicketInput').focus();
    }
}
</script>
@endsection
