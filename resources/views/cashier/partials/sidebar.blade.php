<a href="{{ route('cashier.dashboard') }}" class="nav-link {{ request()->routeIs('cashier.dashboard') ? 'active' : '' }}">
    <i class="bi bi-speedometer2"></i> Tableau de bord
</a>
<a href="{{ route('cashier.cash-register.index') }}" class="nav-link {{ request()->routeIs('cashier.cash-register.*') ? 'active' : '' }}">
    <i class="bi bi-cash-stack"></i> Caisse
</a>
<a href="{{ route('cashier.sales.create') }}" class="nav-link {{ request()->routeIs('cashier.sales.create') ? 'active' : '' }}">
    <i class="bi bi-cart-plus"></i> Nouvelle vente
</a>
<a href="{{ route('cashier.sales.index') }}" class="nav-link {{ request()->routeIs('cashier.sales.index', 'cashier.sales.show') ? 'active' : '' }}">
    <i class="bi bi-receipt"></i> Historique ventes
</a>
<a href="{{ route('cashier.repairs.create') }}" class="nav-link {{ request()->routeIs('cashier.repairs.create') ? 'active' : '' }}">
    <i class="bi bi-wrench"></i> Nouvelle réparation
</a>
<a href="{{ route('cashier.repairs.index') }}" class="nav-link {{ request()->routeIs('cashier.repairs.index', 'cashier.repairs.show', 'cashier.repairs.edit') ? 'active' : '' }}">
    <i class="bi bi-tools"></i> Réparations
</a>
<a href="{{ route('sav.index') }}" class="nav-link {{ request()->routeIs('sav.*') ? 'active' : '' }}">
    <i class="bi bi-headset"></i> S.A.V
</a>
<a href="{{ route('cashier.customers.index') }}" class="nav-link {{ request()->routeIs('cashier.customers.*') ? 'active' : '' }}">
    <i class="bi bi-people"></i> Clients
</a>
<a href="{{ route('cashier.reseller-payments.index') }}" class="nav-link {{ request()->routeIs('cashier.reseller-payments.*') ? 'active' : '' }}">
    <i class="bi bi-credit-card"></i> Créances
</a>
<a href="{{ route('cashier.expenses.index') }}" class="nav-link {{ request()->routeIs('cashier.expenses.*') ? 'active' : '' }}">
    <i class="bi bi-wallet2"></i> Dépenses
</a>
