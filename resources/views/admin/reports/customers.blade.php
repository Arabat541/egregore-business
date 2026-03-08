@extends('layouts.app')

@section('title', 'Rapport Clients')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-people me-2"></i>Rapport Clients & Revendeurs
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Rapports</a></li>
                    <li class="breadcrumb-item active">Clients</li>
                </ol>
            </nav>
        </div>
        <button class="btn btn-outline-primary" onclick="window.print()">
            <i class="bi bi-printer me-2"></i>Imprimer
        </button>
    </div>

    <!-- Filtres de période -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                @if(isset($shops) && $shops->count() > 0)
                <div class="col-md-2">
                    <label class="form-label">Boutique</label>
                    <select name="shop_id" class="form-select">
                        <option value="">Toutes boutiques</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" {{ (isset($shopId) && $shopId == $shop->id) ? 'selected' : '' }}>
                                {{ $shop->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label">Date début</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter me-2"></i>Filtrer
                    </button>
                    <a href="{{ route('admin.reports.customers') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
                <div class="col-md-3 text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="?start_date={{ now()->startOfMonth()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}{{ isset($shopId) ? '&shop_id='.$shopId : '' }}" class="btn btn-outline-danger">Mois</a>
                        <a href="?start_date={{ now()->startOfQuarter()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}{{ isset($shopId) ? '&shop_id='.$shopId : '' }}" class="btn btn-outline-danger">Trimestre</a>
                        <a href="?start_date={{ now()->startOfYear()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}{{ isset($shopId) ? '&shop_id='.$shopId : '' }}" class="btn btn-outline-danger">Année</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs Clients Particuliers -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <h5 class="text-muted mb-3">
                <i class="bi bi-person me-2"></i>Clients Particuliers
            </h5>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total Clients</h6>
                            <h3 class="mb-0 text-primary">{{ number_format($totalCustomers) }}</h3>
                            <small class="text-muted">base clients</small>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle align-self-start">
                            <i class="bi bi-people fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Nouveaux Clients</h6>
                            <h3 class="mb-0 text-success">{{ number_format($newCustomers) }}</h3>
                            <small class="text-muted">sur la période</small>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle align-self-start">
                            <i class="bi bi-person-plus fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Clients Actifs</h6>
                            <h3 class="mb-0 text-info">{{ number_format($activeCustomers) }}</h3>
                            <small class="text-muted">ayant acheté</small>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle align-self-start">
                            <i class="bi bi-cart-check fs-4 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Taux d'Activité</h6>
                            <h3 class="mb-0 text-warning">{{ $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 1) : 0 }}%</h3>
                            <small class="text-muted">clients actifs / total</small>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle align-self-start">
                            <i class="bi bi-activity fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique acquisition clients -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Acquisition de Clients
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 250px; position: relative;">
                        <canvas id="acquisitionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-heart me-2"></i>Clients Fidèles
                    </h5>
                    <span class="badge bg-danger">3+ achats</span>
                </div>
                <div class="card-body">
                    @if($loyalCustomers->count() > 0)
                        <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                            @foreach($loyalCustomers->take(10) as $customer)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <i class="bi bi-star-fill text-warning me-2"></i>
                                        {{ $customer->full_name }}
                                    </div>
                                    <span class="badge bg-primary rounded-pill">{{ $customer->sales_count }} achats</span>
                                </div>
                            @endforeach
                        </div>
                        @if($loyalCustomers->count() > 10)
                            <p class="text-center text-muted mt-3 mb-0">
                                + {{ $loyalCustomers->count() - 10 }} autres clients fidèles
                            </p>
                        @endif
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-emoji-neutral fs-1"></i>
                            <p class="mt-2">Aucun client fidèle sur cette période</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Top Clients par CA -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-trophy me-2"></i>Top 20 Clients par CA
                    </h5>
                    <span class="badge bg-success">Meilleurs clients</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th class="text-center">Achats</th>
                                    <th class="text-end">CA Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topCustomersByRevenue as $index => $customer)
                                    @if($customer->sales_sum_total_amount > 0)
                                        <tr>
                                            <td>
                                                @if($index < 3)
                                                    <span class="badge bg-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'danger') }}">
                                                        {{ $index + 1 }}
                                                    </span>
                                                @else
                                                    {{ $index + 1 }}
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $customer->full_name }}</strong>
                                                <br><small class="text-muted">{{ $customer->phone }}</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $customer->sales_count }}</span>
                                            </td>
                                            <td class="text-end fw-bold text-success">
                                                {{ number_format($customer->sales_sum_total_amount, 0, ',', ' ') }} F
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Aucune vente sur cette période</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clients avec réparations -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-tools me-2"></i>Clients avec Réparations
                    </h5>
                    <span class="badge bg-warning text-dark">Service après-vente</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th class="text-center">Réparations</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customersWithRepairs as $index => $customer)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $customer->full_name }}</strong>
                                            <br><small class="text-muted">{{ $customer->phone }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark">{{ $customer->repairs_count }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Aucune réparation sur cette période</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Revendeurs -->
    <hr class="my-5">

    <div class="row g-4 mb-4">
        <div class="col-12">
            <h5 class="text-muted mb-3">
                <i class="bi bi-shop me-2"></i>Revendeurs (B2B)
            </h5>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-2 opacity-75">Total Revendeurs</h6>
                            <h3 class="mb-0">{{ number_format($totalResellers) }}</h3>
                        </div>
                        <i class="bi bi-shop-window fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-2 text-white-50">Avec Créances</h6>
                            <h3 class="mb-0">{{ number_format($resellersWithDebt) }}</h3>
                            <small>revendeurs endettés</small>
                        </div>
                        <i class="bi bi-exclamation-diamond fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-2 text-white-50">Total Créances</h6>
                            <h3 class="mb-0">{{ number_format($totalResellerDebt, 0, ',', ' ') }} F</h3>
                            <small>à recouvrer</small>
                        </div>
                        <i class="bi bi-cash-coin fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Revendeurs -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-award me-2"></i>Top 10 Revendeurs
                    </h5>
                    <span class="badge bg-primary">Partenaires clés</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Entreprise</th>
                                    <th>Contact</th>
                                    <th class="text-center">Commandes</th>
                                    <th class="text-end">CA Période</th>
                                    <th class="text-end">Crédit Autorisé</th>
                                    <th class="text-end">Dette Actuelle</th>
                                    <th class="text-center">État</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topResellers as $index => $reseller)
                                    @php
                                        $debtPercentage = $reseller->credit_limit > 0 
                                            ? ($reseller->current_debt / $reseller->credit_limit) * 100 
                                            : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            @if($index < 3)
                                                <span class="badge bg-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'danger') }}">
                                                    {{ $index + 1 }}
                                                </span>
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $reseller->company_name }}</strong>
                                            <br><small class="text-muted">{{ $reseller->phone }}</small>
                                        </td>
                                        <td>{{ $reseller->contact_name }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $reseller->sales_count ?? 0 }}</span>
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            {{ number_format($reseller->sales_sum_total_amount ?? 0, 0, ',', ' ') }} F
                                        </td>
                                        <td class="text-end">
                                            @if($reseller->credit_allowed)
                                                {{ number_format($reseller->credit_limit, 0, ',', ' ') }} F
                                            @else
                                                <span class="text-muted">Non autorisé</span>
                                            @endif
                                        </td>
                                        <td class="text-end {{ $reseller->current_debt > 0 ? 'text-danger fw-bold' : '' }}">
                                            {{ number_format($reseller->current_debt, 0, ',', ' ') }} F
                                        </td>
                                        <td class="text-center">
                                            @if($reseller->current_debt == 0)
                                                <span class="badge bg-success">OK</span>
                                            @elseif($debtPercentage < 50)
                                                <span class="badge bg-info">{{ round($debtPercentage) }}%</span>
                                            @elseif($debtPercentage < 80)
                                                <span class="badge bg-warning text-dark">{{ round($debtPercentage) }}%</span>
                                            @else
                                                <span class="badge bg-danger">{{ round($debtPercentage) }}%</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Aucun revendeur actif sur cette période</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerte créances importantes -->
    @if($totalResellerDebt > 0)
        <div class="row g-4 mt-4">
            <div class="col-12">
                <div class="alert alert-danger border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-octagon fs-3 me-3"></i>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-1">⚠️ Créances Revendeurs à Recouvrer</h6>
                            <p class="mb-0">
                                <strong>{{ $resellersWithDebt }}</strong> revendeur(s) avec un total de 
                                <strong>{{ number_format($totalResellerDebt, 0, ',', ' ') }} FCFA</strong> de créances.
                            </p>
                        </div>
                        <a href="{{ route('cashier.reseller-payments.index') }}" class="btn btn-danger">
                            <i class="bi bi-cash me-2"></i>Gérer les paiements
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const acquisitionData = @json($customersByDay);

    new Chart(document.getElementById('acquisitionChart'), {
        type: 'bar',
        data: {
            labels: acquisitionData.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
            }),
            datasets: [{
                label: 'Nouveaux clients',
                data: acquisitionData.map(d => d.count),
                backgroundColor: 'rgba(220, 53, 69, 0.7)',
                borderColor: 'rgb(220, 53, 69)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>
@endpush

<style>
@media print {
    .btn, .form-control, .btn-group, nav, .alert {
        display: none !important;
    }
    .card {
        break-inside: avoid;
    }
}
</style>
@endsection
