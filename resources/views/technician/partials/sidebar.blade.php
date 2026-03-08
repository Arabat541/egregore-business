<a href="{{ route('technician.dashboard') }}" class="nav-link {{ request()->routeIs('technician.dashboard') ? 'active' : '' }}">
    <i class="bi bi-speedometer2"></i> Tableau de bord
</a>
<a href="{{ route('technician.repairs.index') }}" class="nav-link {{ request()->routeIs('technician.repairs.*') ? 'active' : '' }}">
    <i class="bi bi-tools"></i> Mes réparations
</a>
<a href="{{ route('technician.sav-deductions') }}" class="nav-link {{ request()->routeIs('technician.sav-deductions') ? 'active' : '' }}">
    <i class="bi bi-exclamation-triangle text-danger"></i> Mes déductions SAV
</a>
