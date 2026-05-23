<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $companyName ?? 'EGREGORE BUSINESS') - Boutique en ligne</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --sf-primary: #6366f1;
            --sf-primary-dark: #4f46e5;
            --sf-primary-light: #818cf8;
            --sf-accent: #f59e0b;
            --sf-accent-dark: #d97706;
            --sf-dark: #0f172a;
            --sf-dark-2: #1e293b;
            --sf-gray: #64748b;
            --sf-gray-light: #94a3b8;
            --sf-light: #f8fafc;
            --sf-border: #e2e8f0;
            --sf-success: #10b981;
            --sf-danger: #ef4444;
            --sf-warning: #f59e0b;
            --sf-radius: 16px;
            --sf-radius-sm: 10px;
            --sf-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 14px rgba(0,0,0,.06);
            --sf-shadow-lg: 0 4px 6px rgba(0,0,0,.04), 0 10px 40px rgba(0,0,0,.1);
            --sf-shadow-hover: 0 8px 30px rgba(99,102,241,.15), 0 4px 14px rgba(0,0,0,.08);
            --sf-transition: all .3s cubic-bezier(.4,0,.2,1);
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--sf-light);
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
        }

        /* ===== Navbar ===== */
        .sf-navbar {
            background: #fff;
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            padding: 0;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 8px rgba(0,0,0,.06);
            z-index: 1050;
        }
        .sf-navbar .navbar-brand {
            font-weight: 800;
            font-size: 1.3rem;
            color: #1a3c7a !important;
            letter-spacing: -.5px;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .sf-navbar .navbar-brand .brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #1a3c7a, #c0392b);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .sf-navbar .navbar-brand span { color: #c0392b; }
        .sf-navbar .nav-link {
            color: #475569 !important;
            font-weight: 500;
            font-size: .9rem;
            padding: 1rem 1rem !important;
            transition: var(--sf-transition);
            position: relative;
        }
        .sf-navbar .nav-link:hover { color: #1a3c7a !important; }
        .sf-navbar .nav-link.active {
            color: #1a3c7a !important;
            font-weight: 600;
        }
        .sf-navbar .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 1rem;
            right: 1rem;
            height: 2px;
            background: linear-gradient(90deg, #1a3c7a, #c0392b);
            border-radius: 2px;
        }
        .sf-cart-btn {
            position: relative;
            background: rgba(26,60,122,.06);
            border: 1px solid rgba(26,60,122,.12);
            border-radius: var(--sf-radius-sm);
            padding: .5rem .75rem;
            color: #1a3c7a;
            text-decoration: none;
            transition: var(--sf-transition);
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            font-weight: 500;
        }
        .sf-cart-btn:hover {
            background: rgba(26,60,122,.12);
            color: #1a3c7a;
        }
        .sf-cart-btn .cart-count {
            background: #c0392b;
            color: #fff;
            font-size: .7rem;
            font-weight: 700;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Search bar */
        .sf-search-form {
            max-width: 360px;
        }
        .sf-search-form .form-control {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #1e293b;
            border-radius: var(--sf-radius-sm);
            padding: .55rem 1rem;
            font-size: .875rem;
            transition: var(--sf-transition);
        }
        .sf-search-form .form-control::placeholder { color: #94a3b8; }
        .sf-search-form .form-control:focus {
            background: #fff;
            box-shadow: 0 0 0 3px rgba(26,60,122,.15);
            border-color: #1a3c7a;
            color: #1e293b;
        }
        .sf-search-form .btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            margin-left: -42px;
            z-index: 5;
            position: relative;
        }

        /* ===== Product cards ===== */
        .sf-product-card {
            border: 1px solid var(--sf-border);
            border-radius: var(--sf-radius);
            overflow: hidden;
            transition: var(--sf-transition);
            background: #fff;
            height: 100%;
            position: relative;
        }
        .sf-product-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--sf-shadow-hover);
            border-color: rgba(99,102,241,.2);
        }
        .sf-product-card .card-img-wrap {
            height: 210px;
            background: linear-gradient(145deg, #f1f5f9, #e8ecf3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--sf-gray-light);
            font-size: 3.5rem;
            position: relative;
            overflow: hidden;
        }
        .sf-product-card .card-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
            position: relative;
            z-index: 1;
        }
        .sf-product-card .card-img-wrap::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 70%, rgba(255,255,255,.6) 100%);
            z-index: 2;
            pointer-events: none;
        }
        .sf-product-card .card-body { padding: 1rem 1.1rem; }
        .sf-product-card .product-name {
            font-weight: 600;
            font-size: .9rem;
            color: var(--sf-dark);
            text-decoration: none;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
            transition: color .2s;
        }
        .sf-product-card .product-name:hover { color: var(--sf-primary); }
        .sf-product-card .product-price {
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--sf-dark);
        }
        .sf-product-card .product-price small {
            font-weight: 500;
            font-size: .75rem;
            color: var(--sf-gray);
        }
        .sf-product-card .product-shop {
            font-size: .78rem;
            color: var(--sf-gray);
        }
        .sf-product-card .badge-type {
            font-size: .68rem;
            font-weight: 600;
            letter-spacing: .3px;
            text-transform: uppercase;
            padding: .3rem .6rem;
            border-radius: 6px;
        }
        .sf-product-card .btn-add-cart {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--sf-primary);
            color: #fff;
            border: none;
            transition: var(--sf-transition);
            font-size: 1rem;
        }
        .sf-product-card .btn-add-cart:hover {
            background: var(--sf-primary-dark);
            transform: scale(1.08);
        }
        .sf-product-card .stock-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            font-size: .68rem;
            font-weight: 600;
            z-index: 2;
            padding: .3rem .6rem;
            border-radius: 6px;
            backdrop-filter: blur(8px);
        }

        /* ===== Buttons ===== */
        .btn-sf-primary {
            background: linear-gradient(135deg, var(--sf-primary), var(--sf-primary-dark));
            color: #fff;
            border: none;
            border-radius: var(--sf-radius-sm);
            font-weight: 600;
            padding: .6rem 1.5rem;
            transition: var(--sf-transition);
            box-shadow: 0 2px 8px rgba(99,102,241,.3);
        }
        .btn-sf-primary:hover {
            background: linear-gradient(135deg, var(--sf-primary-dark), #4338ca);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(99,102,241,.4);
        }
        .btn-sf-outline {
            border: 2px solid var(--sf-primary);
            color: var(--sf-primary);
            border-radius: var(--sf-radius-sm);
            font-weight: 600;
            padding: .55rem 1.4rem;
            background: transparent;
            transition: var(--sf-transition);
        }
        .btn-sf-outline:hover {
            background: var(--sf-primary);
            color: #fff;
            transform: translateY(-1px);
        }
        .btn-sf-ghost {
            border: none;
            color: var(--sf-gray);
            border-radius: var(--sf-radius-sm);
            font-weight: 500;
            padding: .5rem 1rem;
            background: transparent;
            transition: var(--sf-transition);
        }
        .btn-sf-ghost:hover {
            background: rgba(99,102,241,.08);
            color: var(--sf-primary);
        }

        /* ===== Hero ===== */
        .sf-hero {
            background: var(--sf-dark);
            color: #fff;
            padding: 5rem 0;
            position: relative;
            overflow: hidden;
        }
        .sf-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,.25) 0%, transparent 70%);
            pointer-events: none;
        }
        .sf-hero::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: -10%;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(245,158,11,.12) 0%, transparent 70%);
            pointer-events: none;
        }
        .sf-hero h1 {
            font-size: 3rem;
            font-weight: 900;
            letter-spacing: -1.5px;
            line-height: 1.1;
        }
        .sf-hero .lead {
            color: var(--sf-gray-light);
            font-size: 1.1rem;
            line-height: 1.7;
        }
        .sf-hero .hero-decoration {
            position: relative;
        }
        .sf-hero .hero-decoration .hero-glow {
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--sf-primary), rgba(99,102,241,.2));
            filter: blur(60px);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .sf-hero .hero-phone-icon {
            font-size: 10rem;
            opacity: .12;
            position: relative;
            z-index: 1;
        }

        /* ===== Section titles ===== */
        .sf-section-title {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--sf-dark);
            letter-spacing: -.5px;
        }
        .sf-section-title .title-accent {
            color: var(--sf-primary);
        }
        .sf-section-subtitle {
            color: var(--sf-gray);
            font-size: .95rem;
        }

        /* ===== Category pills ===== */
        .sf-category-pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem 1.2rem;
            border: 1px solid var(--sf-border);
            border-radius: 50px;
            text-decoration: none;
            color: var(--sf-dark-2);
            font-weight: 500;
            font-size: .85rem;
            transition: var(--sf-transition);
            background: #fff;
        }
        .sf-category-pill:hover, .sf-category-pill.active {
            background: var(--sf-primary);
            color: #fff;
            border-color: var(--sf-primary);
            box-shadow: 0 2px 10px rgba(99,102,241,.25);
        }

        /* ===== Cards global ===== */
        .sf-card {
            background: #fff;
            border: 1px solid var(--sf-border);
            border-radius: var(--sf-radius);
            box-shadow: var(--sf-shadow);
            transition: var(--sf-transition);
        }
        .sf-card:hover { box-shadow: var(--sf-shadow-lg); }
        .sf-card .card-body { padding: 1.5rem; }

        /* ===== Type cards ===== */
        .sf-type-card {
            background: #fff;
            border: 1px solid var(--sf-border);
            border-radius: var(--sf-radius);
            padding: 2rem 1.5rem;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: var(--sf-transition);
            position: relative;
            overflow: hidden;
        }
        .sf-type-card::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity .3s;
            border-radius: var(--sf-radius);
        }
        .sf-type-card.type-phone::before { background: linear-gradient(135deg, rgba(99,102,241,.06), rgba(99,102,241,.12)); }
        .sf-type-card.type-accessory::before { background: linear-gradient(135deg, rgba(16,185,129,.06), rgba(16,185,129,.12)); }
        .sf-type-card.type-spare::before { background: linear-gradient(135deg, rgba(245,158,11,.06), rgba(245,158,11,.12)); }
        .sf-type-card:hover::before { opacity: 1; }
        .sf-type-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--sf-shadow-lg);
            border-color: transparent;
        }
        .sf-type-card .type-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: .75rem;
            position: relative;
            z-index: 1;
        }
        .sf-type-card .type-label {
            font-weight: 700;
            color: var(--sf-dark);
            font-size: 1rem;
            position: relative;
            z-index: 1;
        }
        .sf-type-card .type-count {
            font-size: .8rem;
            color: var(--sf-gray);
            position: relative;
            z-index: 1;
        }

        /* ===== Shop cards ===== */
        .sf-shop-card {
            background: #fff;
            border: 1px solid var(--sf-border);
            border-radius: var(--sf-radius);
            padding: 1.5rem;
            transition: var(--sf-transition);
        }
        .sf-shop-card:hover {
            box-shadow: var(--sf-shadow-lg);
            transform: translateY(-2px);
        }
        .sf-shop-card .shop-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--sf-primary), var(--sf-primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.2rem;
        }

        /* ===== Filter sidebar ===== */
        .sf-filter-card {
            background: #fff;
            border: 1px solid var(--sf-border);
            border-radius: var(--sf-radius);
            box-shadow: var(--sf-shadow);
            overflow: hidden;
        }
        .sf-filter-card .filter-header {
            font-weight: 700;
            font-size: .95rem;
            padding: 1.25rem 1.25rem .75rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .sf-filter-card .filter-section {
            padding: .75rem 1.25rem;
            border-top: 1px solid var(--sf-border);
        }
        .sf-filter-card .filter-section-title {
            font-weight: 600;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--sf-gray);
            margin-bottom: .5rem;
        }
        .sf-filter-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .35rem .5rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: .85rem;
            color: var(--sf-dark-2);
            transition: var(--sf-transition);
            margin: 0 -.5rem;
        }
        .sf-filter-link:hover { background: rgba(99,102,241,.06); color: var(--sf-primary); }
        .sf-filter-link.active { background: rgba(99,102,241,.08); color: var(--sf-primary); font-weight: 600; }
        .sf-filter-link .filter-count {
            font-size: .75rem;
            color: var(--sf-gray-light);
            background: var(--sf-light);
            padding: .15rem .45rem;
            border-radius: 4px;
        }

        /* ===== Active filter tags ===== */
        .sf-filter-tag {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .7rem;
            background: rgba(99,102,241,.08);
            color: var(--sf-primary);
            border-radius: 50px;
            font-size: .8rem;
            font-weight: 500;
            text-decoration: none;
            transition: var(--sf-transition);
        }
        .sf-filter-tag:hover {
            background: rgba(99,102,241,.15);
            color: var(--sf-primary-dark);
        }
        .sf-filter-tag .bi-x { font-size: .9rem; }

        /* ===== Footer ===== */
        .sf-footer {
            background: #0f172a;
            color: rgba(255,255,255,.5);
            padding: 3.5rem 0 1.5rem;
            margin-top: 4rem;
            position: relative;
        }
        .sf-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #1a3c7a, #c0392b);
        }
        .sf-footer a {
            color: rgba(255,255,255,.6);
            text-decoration: none;
            transition: color .2s;
        }
        .sf-footer a:hover { color: #fff; }
        .sf-footer .footer-heading {
            color: #fff;
            font-weight: 700;
            font-size: .85rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 1rem;
        }
        .sf-footer .footer-links li {
            margin-bottom: .5rem;
        }
        .sf-footer .footer-links li a {
            font-size: .9rem;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
        }
        .sf-footer .footer-contact p {
            font-size: .9rem;
            line-height: 2;
        }

        /* ===== Toast / Flash ===== */
        .sf-flash {
            border: none;
            border-radius: var(--sf-radius-sm);
            padding: .85rem 1.25rem;
            font-size: .9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: .5rem;
            box-shadow: var(--sf-shadow);
            animation: sfSlideDown .4s cubic-bezier(.4,0,.2,1);
        }
        .sf-flash.alert-success {
            background: rgba(16,185,129,.08);
            color: #065f46;
            border-left: 4px solid var(--sf-success);
        }
        .sf-flash.alert-danger {
            background: rgba(239,68,68,.08);
            color: #991b1b;
            border-left: 4px solid var(--sf-danger);
        }

        @keyframes sfSlideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== Utilities ===== */
        .sf-badge-stock {
            font-size: .7rem;
            font-weight: 600;
        }
        .sf-glass {
            background: rgba(255,255,255,.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,.3);
        }
        .text-gradient {
            background: linear-gradient(135deg, var(--sf-primary), var(--sf-primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ===== Responsive ===== */
        @media (max-width: 768px) {
            .sf-hero h1 { font-size: 2rem; }
            .sf-hero { padding: 3rem 0; }
            .sf-product-card .card-img-wrap { height: 170px; }
            .sf-navbar .navbar-brand { font-size: 1.1rem; }
            .sf-navbar .navbar-brand .brand-icon { width: 30px; height: 30px; font-size: .9rem; }
            .sf-search-form { max-width: 100%; margin: .5rem 0; }
        }

        @yield('styles')
    </style>
</head>
<body>
    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg sf-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('storefront.home') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height:40px; width:auto; margin-right:6px; vertical-align:middle;">
                {{ $companyName ?? 'EGREGORE' }} <span>SHOP</span>
            </a>

            <div class="d-flex align-items-center gap-2 d-lg-none">
                @php $cartCount = collect(session('cart', []))->sum('quantity'); @endphp
                <a href="{{ route('storefront.cart') }}" class="sf-cart-btn">
                    <i class="bi bi-bag"></i>
                    @if($cartCount > 0)
                        <span class="cart-count">{{ $cartCount }}</span>
                    @endif
                </a>
                <button class="navbar-toggler border-0 p-1" type="button" data-bs-toggle="collapse" data-bs-target="#sfNavbar" style="color:#1a3c7a;">
                    <i class="bi bi-list fs-4"></i>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="sfNavbar">
                <ul class="navbar-nav me-auto ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('storefront.home') ? 'active' : '' }}" href="{{ route('storefront.home') }}">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('storefront.catalog') ? 'active' : '' }}" href="{{ route('storefront.catalog') }}">Catalogue</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('storefront.track*') ? 'active' : '' }}" href="{{ route('storefront.track') }}">Suivi commande</a>
                    </li>
                    <li class="nav-item d-lg-none">
                        @auth
                            <a class="nav-link" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-1"></i>Tableau de bord</a>
                        @else
                            <a class="nav-link" href="{{ route('login') }}"><i class="bi bi-person-circle me-1"></i>Connexion</a>
                        @endauth
                    </li>
                </ul>

                {{-- Search --}}
                <form class="sf-search-form d-flex me-3 position-relative" action="{{ route('storefront.catalog') }}" method="GET">
                    <input class="form-control pe-5" type="search" name="search" placeholder="Rechercher un produit..." value="{{ request('search') }}">
                    <button class="btn position-absolute end-0" type="submit"><i class="bi bi-search"></i></button>
                </form>

                {{-- Cart (desktop) --}}
                @php if(!isset($cartCount)) $cartCount = collect(session('cart', []))->sum('quantity'); @endphp
                <a href="{{ route('storefront.cart') }}" class="sf-cart-btn d-none d-lg-inline-flex">
                    <i class="bi bi-bag"></i>
                    Panier
                    @if($cartCount > 0)
                        <span class="cart-count">{{ $cartCount }}</span>
                    @endif
                </a>

                {{-- Login / Dashboard --}}
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-sm ms-2 d-none d-lg-inline-flex align-items-center gap-1" style="background:#1a3c7a; color:#fff; border:none; border-radius:8px; padding:.4rem .8rem; font-size:.85rem; transition:var(--sf-transition);">
                        <i class="bi bi-speedometer2"></i> Tableau de bord
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm ms-2 d-none d-lg-inline-flex align-items-center gap-1" style="background:#1a3c7a; color:#fff; border:none; border-radius:8px; padding:.4rem .8rem; font-size:.85rem; transition:var(--sf-transition);">
                        <i class="bi bi-person-circle"></i> Connexion
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Flash messages --}}
    <div class="container mt-3">
        @if(session('success'))
            <div class="sf-flash alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="sf-flash alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    </div>

    @yield('content')

    {{-- Footer --}}
    <footer class="sf-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 mb-3">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height:36px; width:auto; border-radius:8px;">
                        <span class="text-white fw-bold fs-5">{{ $companyName ?? 'EGREGORE' }} <span style="color:var(--sf-primary-light);">SHOP</span></span>
                    </div>
                    <p class="small mb-0" style="line-height:1.7;">Votre boutique en ligne de téléphones, accessoires et pièces détachées. Qualité garantie.</p>
                </div>
                <div class="col-lg-2 col-6 mb-3">
                    <h6 class="footer-heading">Navigation</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="{{ route('storefront.home') }}"><i class="bi bi-chevron-right" style="font-size:.7rem;"></i> Accueil</a></li>
                        <li><a href="{{ route('storefront.catalog') }}"><i class="bi bi-chevron-right" style="font-size:.7rem;"></i> Catalogue</a></li>
                        <li><a href="{{ route('storefront.track') }}"><i class="bi bi-chevron-right" style="font-size:.7rem;"></i> Suivi</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6 mb-3">
                    <h6 class="footer-heading">Catégories</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="{{ route('storefront.catalog', ['type' => 'phone']) }}"><i class="bi bi-chevron-right" style="font-size:.7rem;"></i> Téléphones</a></li>
                        <li><a href="{{ route('storefront.catalog', ['type' => 'accessory']) }}"><i class="bi bi-chevron-right" style="font-size:.7rem;"></i> Accessoires</a></li>
                        <li><a href="{{ route('storefront.catalog', ['type' => 'spare_part']) }}"><i class="bi bi-chevron-right" style="font-size:.7rem;"></i> Pièces</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-3">
                    <h6 class="footer-heading">Contact</h6>
                    @php
                        $footerPhone    = \App\Models\Setting::get('company_phone');
                        $footerWhatsapp = \App\Models\Setting::get('company_whatsapp');
                        $footerEmail    = \App\Models\Setting::get('company_email');
                        $footerAddress  = \App\Models\Setting::get('company_address');
                        $footerFacebook = \App\Models\Setting::get('company_facebook');
                    @endphp
                    @if($footerPhone || $footerWhatsapp || $footerEmail || $footerAddress || $footerFacebook)
                        <div class="footer-contact">
                            <p class="small mb-0">
                                @if($footerPhone)
                                    <i class="bi bi-telephone-fill me-2" style="color:var(--sf-primary-light);"></i>{{ $footerPhone }}<br>
                                @endif
                                @if($footerWhatsapp)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $footerWhatsapp) }}" target="_blank" style="color:rgba(255,255,255,.6);">
                                        <i class="bi bi-whatsapp me-2" style="color:#25d366;"></i>{{ $footerWhatsapp }}
                                    </a><br>
                                @endif
                                @if($footerEmail)
                                    <i class="bi bi-envelope-fill me-2" style="color:var(--sf-primary-light);"></i>{{ $footerEmail }}<br>
                                @endif
                                @if($footerFacebook)
                                    <a href="{{ $footerFacebook }}" target="_blank" style="color:rgba(255,255,255,.6);">
                                        <i class="bi bi-facebook me-2" style="color:#1877f2;"></i>{{ $footerFacebook }}
                                    </a><br>
                                @endif
                                @if($footerAddress)
                                    <i class="bi bi-geo-alt-fill me-2" style="color:var(--sf-primary-light);"></i>{{ $footerAddress }}
                                @endif
                            </p>
                        </div>
                    @else
                        <p class="small mb-0 fst-italic">Contactez-nous via nos boutiques physiques.</p>
                    @endif
                </div>
            </div>
            <hr style="border-color:rgba(255,255,255,.08);">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <p class="small mb-0">&copy; {{ date('Y') }} {{ $companyName ?? 'EGREGORE BUSINESS' }}. Tous droits réservés.</p>
                <div class="d-flex gap-3">
                    @if($footerWhatsapp ?? false)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $footerWhatsapp) }}" target="_blank"
                           style="font-size:1.2rem; color:#25d366;" title="WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                    @endif
                    @if($footerFacebook ?? false)
                        <a href="{{ $footerFacebook }}" target="_blank"
                           style="font-size:1.2rem; color:#1877f2;" title="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
