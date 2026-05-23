@extends('layouts.app')

@section('title', 'Rapports & Analyses')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-graph-up me-2"></i>Rapports & Analyses
        </h1>
    </div>

    <div class="row g-4">
        <!-- Rapport Ventes -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <span class="bg-primary bg-opacity-10 p-4 rounded-circle d-inline-flex">
                            <i class="bi bi-cart-check fs-1 text-primary"></i>
                        </span>
                    </div>
                    <h5 class="card-title">Rapport des Ventes</h5>
                    <p class="card-text text-muted">
                        Analyse détaillée des ventes, produits les plus vendus, performance par période et par vendeur.
                    </p>
                    <a href="{{ route('admin.reports.sales') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-right-circle me-2"></i>Consulter
                    </a>
                </div>
            </div>
        </div>

        <!-- Rapport Réparations -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <span class="bg-warning bg-opacity-10 p-4 rounded-circle d-inline-flex">
                            <i class="bi bi-tools fs-1 text-warning"></i>
                        </span>
                    </div>
                    <h5 class="card-title">Rapport des Réparations</h5>
                    <p class="card-text text-muted">
                        Suivi des réparations, temps moyen, performance des techniciens, problèmes fréquents.
                    </p>
                    <a href="{{ route('admin.reports.repairs') }}" class="btn btn-warning">
                        <i class="bi bi-arrow-right-circle me-2"></i>Consulter
                    </a>
                </div>
            </div>
        </div>

        <!-- Rapport S.A.V -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <span class="bg-purple bg-opacity-10 p-4 rounded-circle d-inline-flex" style="background-color: rgba(111, 66, 193, 0.1);">
                            <i class="bi bi-shield-exclamation fs-1" style="color: #6f42c1;"></i>
                        </span>
                    </div>
                    <h5 class="card-title">Rapport S.A.V</h5>
                    <p class="card-text text-muted">
                        Retours, échanges, remboursements, réclamations et indicateurs anti-malversation.
                    </p>
                    <a href="{{ route('admin.reports.sav') }}" class="btn" style="background-color: #6f42c1; color: white;">
                        <i class="bi bi-arrow-right-circle me-2"></i>Consulter
                    </a>
                </div>
            </div>
        </div>

        <!-- Rapport Stock -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <span class="bg-info bg-opacity-10 p-4 rounded-circle d-inline-flex">
                            <i class="bi bi-boxes fs-1 text-info"></i>
                        </span>
                    </div>
                    <h5 class="card-title">Rapport du Stock</h5>
                    <p class="card-text text-muted">
                        État du stock, valeur inventaire, produits à commander, rotation des produits.
                    </p>
                    <a href="{{ route('admin.reports.stock') }}" class="btn btn-info">
                        <i class="bi bi-arrow-right-circle me-2"></i>Consulter
                    </a>
                </div>
            </div>
        </div>

        <!-- Rapport Financier -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <span class="bg-success bg-opacity-10 p-4 rounded-circle d-inline-flex">
                            <i class="bi bi-cash-stack fs-1 text-success"></i>
                        </span>
                    </div>
                    <h5 class="card-title">Rapport Financier</h5>
                    <p class="card-text text-muted">
                        Revenus, marges bénéficiaires, flux de trésorerie, créances clients et revendeurs.
                    </p>
                    <a href="{{ route('admin.reports.financial') }}" class="btn btn-success">
                        <i class="bi bi-arrow-right-circle me-2"></i>Consulter
                    </a>
                </div>
            </div>
        </div>

        <!-- Rapport Clients -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <span class="bg-danger bg-opacity-10 p-4 rounded-circle d-inline-flex">
                            <i class="bi bi-people fs-1 text-danger"></i>
                        </span>
                    </div>
                    <h5 class="card-title">Rapport Clients</h5>
                    <p class="card-text text-muted">
                        Analyse clientèle, clients fidèles, top clients, acquisition et revendeurs.
                    </p>
                    <a href="{{ route('admin.reports.customers') }}" class="btn btn-danger">
                        <i class="bi bi-arrow-right-circle me-2"></i>Consulter
                    </a>
                </div>
            </div>
        </div>

        <!-- Export Données -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <span class="bg-secondary bg-opacity-10 p-4 rounded-circle d-inline-flex">
                            <i class="bi bi-download fs-1 text-secondary"></i>
                        </span>
                    </div>
                    <h5 class="card-title">Export de Données</h5>
                    <p class="card-text text-muted">
                        Exportez vos données en CSV pour analyse externe ou archivage.
                    </p>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>Exporter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicateurs clés rapides -->
    <div class="row mt-5 g-4">
        <div class="col-12">
            <h5 class="text-muted mb-3">
                <i class="bi bi-lightning-charge me-2"></i>Indicateurs Clés du Mois
            </h5>
        </div>
        
        @php
            $thisMonth = \Carbon\Carbon::now()->startOfMonth();
            $salesThisMonth = \App\Models\Sale::where('created_at', '>=', $thisMonth)->sum('total_amount');
            $repairsThisMonth = \App\Models\Repair::where('created_at', '>=', $thisMonth)->count();
            $newCustomers = \App\Models\Customer::where('created_at', '>=', $thisMonth)->count();
            $totalDebt = \App\Models\Reseller::sum('current_debt');
            
            // S.A.V Stats
            $savTicketsMonth = \App\Models\SavTicket::where('created_at', '>=', $thisMonth)->count();
            $savRefundsMonth = \App\Models\SavTicket::where('created_at', '>=', $thisMonth)
                ->whereIn('status', ['resolved', 'closed'])
                ->sum('refund_amount');
        @endphp

        <div class="col-md-3">
            <div class="card border-0 bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-0 text-white-50">CA Ventes</h6>
                            <h3 class="mb-0">{{ number_format($salesThisMonth, 0, ',', ' ') }} F</h3>
                        </div>
                        <i class="bi bi-graph-up-arrow fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-0 opacity-75">Réparations</h6>
                            <h3 class="mb-0">{{ $repairsThisMonth }}</h3>
                        </div>
                        <i class="bi bi-wrench fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 text-white" style="background-color: #6f42c1;">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-0 text-white-50">Tickets S.A.V</h6>
                            <h3 class="mb-0">{{ $savTicketsMonth }}</h3>
                            @if($savRefundsMonth > 0)
                                <small class="text-white-50">{{ number_format($savRefundsMonth, 0, ',', ' ') }} F remboursés</small>
                            @endif
                        </div>
                        <i class="bi bi-shield-exclamation fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-0 text-white-50">Créances</h6>
                            <h3 class="mb-0">{{ number_format($totalDebt, 0, ',', ' ') }} F</h3>
                        </div>
                        <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Export -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.reports.export') }}" method="GET">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-download me-2"></i>Exporter les données
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type de données</label>
                        <select name="type" class="form-select" required>
                            <option value="sales">Ventes</option>
                            <option value="repairs">Réparations</option>
                            <option value="stock">Stock actuel</option>
                            <option value="sav">S.A.V (Audit détaillé)</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date début</label>
                            <input type="date" name="start_date" class="form-control" 
                                value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date fin</label>
                            <input type="date" name="end_date" class="form-control" 
                                value="{{ now()->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="alert alert-info border-0 small mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        L'export S.A.V inclut des colonnes d'audit anti-fraude (même employé vente/SAV, délai de résolution, etc.)
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-download me-2"></i>Télécharger CSV
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>
@endsection
