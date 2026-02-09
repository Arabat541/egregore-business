{{-- Styles pour les menus dépliables --}}
<style>
    .sidebar-menu-group {
        margin-bottom: 2px;
    }
    .sidebar-menu-header {
        color: #94a3b8;
        padding: 12px 20px;
        border-radius: 8px;
        margin: 2px 10px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        background: transparent;
        border: none;
        width: calc(100% - 20px);
        text-align: left;
    }
    .sidebar-menu-header:hover {
        color: #fff;
        background-color: rgba(255,255,255,0.1);
    }
    .sidebar-menu-header.active {
        color: #fff;
        background-color: rgba(59, 130, 246, 0.3);
    }
    .sidebar-menu-header i.menu-icon {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }
    .sidebar-menu-header .chevron {
        transition: transform 0.3s ease;
        font-size: 0.8rem;
    }
    .sidebar-menu-header[aria-expanded="true"] .chevron {
        transform: rotate(90deg);
    }
    .sidebar-submenu {
        padding-left: 15px;
    }
    .sidebar-submenu .nav-link {
        padding: 8px 15px 8px 35px !important;
        font-size: 0.9rem;
        margin: 1px 10px !important;
    }
    .sidebar-submenu .nav-link i {
        font-size: 0.85rem;
    }
    .sidebar-divider {
        border-top: 1px solid rgba(148, 163, 184, 0.2);
        margin: 10px 20px;
    }
</style>

{{-- Tableau de bord --}}
<a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
    <i class="bi bi-speedometer2"></i> Tableau de bord
</a>

<div class="sidebar-divider"></div>

{{-- Boutiques --}}
@php
    $shopsActive = request()->routeIs('admin.shops.*');
    $shopsCount = \App\Models\Shop::active()->count();
@endphp
<div class="sidebar-menu-group">
    <button class="sidebar-menu-header {{ $shopsActive ? 'active' : '' }}" 
            data-bs-toggle="collapse" 
            data-bs-target="#menuBoutiques" 
            aria-expanded="{{ $shopsActive ? 'true' : 'false' }}">
        <span><i class="bi bi-shop menu-icon"></i> Boutiques</span>
        <span>
            @if($shopsCount > 0)
                <span class="badge bg-primary me-1">{{ $shopsCount }}</span>
            @endif
            <i class="bi bi-chevron-right chevron"></i>
        </span>
    </button>
    <div class="collapse {{ $shopsActive ? 'show' : '' }}" id="menuBoutiques">
        <div class="sidebar-submenu">
            <a href="{{ route('admin.shops.dashboard') }}" class="nav-link {{ request()->routeIs('admin.shops.dashboard') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i> Vue d'ensemble
            </a>
            <a href="{{ route('admin.shops.index') }}" class="nav-link {{ request()->routeIs('admin.shops.index', 'admin.shops.show', 'admin.shops.create', 'admin.shops.edit') ? 'active' : '' }}">
                <i class="bi bi-list-ul"></i> Gérer les boutiques
            </a>
        </div>
    </div>
</div>

{{-- Gestion Stock --}}
@php
    $stockActive = request()->routeIs('admin.categories.*') || request()->routeIs('admin.products.*') || request()->routeIs('admin.suppliers.*');
    $lowStockCount = \App\Models\Product::whereColumn('quantity_in_stock', '<=', 'stock_alert_threshold')->count();
@endphp
<div class="sidebar-menu-group">
    <button class="sidebar-menu-header {{ $stockActive ? 'active' : '' }}" 
            data-bs-toggle="collapse" 
            data-bs-target="#menuStock" 
            aria-expanded="{{ $stockActive ? 'true' : 'false' }}">
        <span><i class="bi bi-box-seam menu-icon"></i> Stock</span>
        <span>
            @if($lowStockCount > 0)
                <span class="badge bg-warning me-1">{{ $lowStockCount }}</span>
            @endif
            <i class="bi bi-chevron-right chevron"></i>
        </span>
    </button>
    <div class="collapse {{ $stockActive ? 'show' : '' }}" id="menuStock">
        <div class="sidebar-submenu">
            <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                <i class="bi bi-folder"></i> Catégories
            </a>
            <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.index', 'admin.products.create', 'admin.products.edit', 'admin.products.show') ? 'active' : '' }}">
                <i class="bi bi-box"></i> Produits
            </a>
            <a href="{{ route('admin.products.low-stock') }}" class="nav-link {{ request()->routeIs('admin.products.low-stock') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle"></i> Stock faible
                @if($lowStockCount > 0)
                    <span class="badge bg-warning ms-1">{{ $lowStockCount }}</span>
                @endif
            </a>
            <a href="{{ route('admin.suppliers.index') }}" class="nav-link {{ request()->routeIs('admin.suppliers.index', 'admin.suppliers.create', 'admin.suppliers.edit', 'admin.suppliers.show') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Fournisseurs
            </a>
            <a href="{{ route('admin.suppliers.low-stock') }}" class="nav-link {{ request()->routeIs('admin.suppliers.low-stock', 'admin.suppliers.orders*') ? 'active' : '' }}">
                <i class="bi bi-cart-plus"></i> Commandes
            </a>
            <a href="{{ route('admin.suppliers.price-comparison') }}" class="nav-link {{ request()->routeIs('admin.suppliers.price-comparison', 'admin.products.supplier-prices') ? 'active' : '' }}">
                <i class="bi bi-bar-chart"></i> Comparatif prix
            </a>
        </div>
    </div>
</div>

{{-- Ventes & Finance --}}
@php
    $financeActive = request()->routeIs('admin.cash-registers.*') || request()->routeIs('admin.reports.*') || request()->routeIs('admin.resellers.*');
    $openCashRegisters = \App\Models\CashRegister::open()->count();
@endphp
<div class="sidebar-menu-group">
    <button class="sidebar-menu-header {{ $financeActive ? 'active' : '' }}" 
            data-bs-toggle="collapse" 
            data-bs-target="#menuFinance" 
            aria-expanded="{{ $financeActive ? 'true' : 'false' }}">
        <span><i class="bi bi-cash-coin menu-icon"></i> Finance</span>
        <span>
            @if($openCashRegisters > 0)
                <span class="badge bg-success me-1">{{ $openCashRegisters }}</span>
            @endif
            <i class="bi bi-chevron-right chevron"></i>
        </span>
    </button>
    <div class="collapse {{ $financeActive ? 'show' : '' }}" id="menuFinance">
        <div class="sidebar-submenu">
            <a href="{{ route('admin.cash-registers.index') }}" class="nav-link {{ request()->routeIs('admin.cash-registers.*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i> Caisses
                @if($openCashRegisters > 0)
                    <span class="badge bg-success ms-1">{{ $openCashRegisters }}</span>
                @endif
            </a>
            <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                <i class="bi bi-graph-up"></i> Rapports
            </a>
            <a href="{{ route('admin.resellers.index') }}" class="nav-link {{ request()->routeIs('admin.resellers.index', 'admin.resellers.show', 'admin.resellers.create', 'admin.resellers.edit', 'admin.resellers.statement') ? 'active' : '' }}">
                <i class="bi bi-building"></i> Revendeurs
            </a>
            <a href="{{ route('admin.resellers.loyalty') }}" class="nav-link {{ request()->routeIs('admin.resellers.loyalty*') ? 'active' : '' }}">
                <i class="bi bi-award"></i> Fidélité
            </a>
            <a href="{{ route('admin.expenses.index') }}" class="nav-link {{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Dépenses
            </a>
        </div>
    </div>
</div>

{{-- S.A.V --}}
<a href="{{ route('sav.index') }}" class="nav-link {{ request()->routeIs('sav.*') ? 'active' : '' }}">
    <i class="bi bi-headset"></i> S.A.V
</a>

<div class="sidebar-divider"></div>

{{-- Système --}}
@php
    $systemActive = request()->routeIs('admin.users.*') || request()->routeIs('admin.security.*') || request()->routeIs('admin.maintenance.*') || request()->routeIs('admin.settings.*');
    $unresolvedAlerts = \App\Models\SecurityAlert::unresolved()->count();
@endphp
<div class="sidebar-menu-group">
    <button class="sidebar-menu-header {{ $systemActive ? 'active' : '' }}" 
            data-bs-toggle="collapse" 
            data-bs-target="#menuSystem" 
            aria-expanded="{{ $systemActive ? 'true' : 'false' }}">
        <span><i class="bi bi-gear-wide-connected menu-icon"></i> Système</span>
        <span>
            @if($unresolvedAlerts > 0)
                <span class="badge bg-danger me-1">{{ $unresolvedAlerts }}</span>
            @endif
            <i class="bi bi-chevron-right chevron"></i>
        </span>
    </button>
    <div class="collapse {{ $systemActive ? 'show' : '' }}" id="menuSystem">
        <div class="sidebar-submenu">
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Utilisateurs
            </a>
            <a href="{{ route('admin.security.index') }}" class="nav-link {{ request()->routeIs('admin.security.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i> Sécurité
                @if($unresolvedAlerts > 0)
                    <span class="badge bg-danger ms-1">{{ $unresolvedAlerts }}</span>
                @endif
            </a>
            <a href="{{ route('admin.maintenance.index') }}" class="nav-link {{ request()->routeIs('admin.maintenance.*') ? 'active' : '' }}">
                <i class="bi bi-tools"></i> Maintenance
            </a>
            <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <i class="bi bi-sliders"></i> Paramètres
            </a>
        </div>
    </div>
</div>
