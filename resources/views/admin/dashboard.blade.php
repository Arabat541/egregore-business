@extends('layouts.app')

@section('title', 'Tableau de bord Admin')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-speedometer2"></i> Tableau de bord</h2>
    <span class="badge bg-primary">Admin - Lecture seule</span>
</div>

<!-- Statistiques principales -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted">Ventes du jour</h6>
                        <h3 class="mb-0">{{ number_format($stats['today_sales_amount'], 0, ',', ' ') }} <small>FCFA</small></h3>
                        <small class="text-muted">{{ $stats['today_sales_count'] }} ventes</small>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-cart-check" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted">Ventes du mois</h6>
                        <h3 class="mb-0">{{ number_format($stats['month_sales_amount'], 0, ',', ' ') }} <small>FCFA</small></h3>
                        <small class="text-muted">{{ $stats['month_sales_count'] }} ventes</small>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-graph-up-arrow" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted">Réparations en cours</h6>
                        <h3 class="mb-0">{{ $stats['pending_repairs'] }}</h3>
                        <small class="text-muted">{{ $stats['today_repairs'] }} créées aujourd'hui</small>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-tools" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted">Créances revendeurs</h6>
                        <h3 class="mb-0">{{ number_format($stats['total_debt'], 0, ',', ' ') }} <small>FCFA</small></h3>
                        <small class="text-muted">{{ $stats['resellers_with_debt'] }} revendeurs</small>
                    </div>
                    <div class="text-danger">
                        <i class="bi bi-credit-card-2-back" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Stock + S.A.V -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h4 class="mb-0">{{ $stats['total_products'] }}</h4>
                <small class="text-muted">Produits total</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h4 class="mb-0 text-warning">{{ $stats['low_stock_products'] }}</h4>
                <small class="text-muted">Stock faible</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h4 class="mb-0 text-danger">{{ $stats['out_of_stock_products'] }}</h4>
                <small class="text-muted">Rupture de stock</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card" style="border-color: #6f42c1;">
            <div class="card-body text-center">
                <h4 class="mb-0" style="color: #6f42c1;">{{ $stats['sav_open_tickets'] }}</h4>
                <small class="text-muted">Tickets S.A.V ouverts</small>
                @if($stats['sav_urgent_tickets'] > 0)
                    <br><span class="badge bg-danger">{{ $stats['sav_urgent_tickets'] }} urgent(s)</span>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Statistiques Dépenses -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card" style="background: linear-gradient(135deg, #fd7e14, #ffc107);">
            <div class="card-body text-white">
                <h6 class="text-white-50">Dépenses aujourd'hui</h6>
                <h3>{{ number_format($stats['today_expenses'], 0, ',', ' ') }} <small>F</small></h3>
                @if($stats['pending_expenses'] > 0)
                    <span class="badge bg-light text-warning">{{ $stats['pending_expenses'] }} en attente</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card" style="background: linear-gradient(135deg, #dc3545, #fd7e14);">
            <div class="card-body text-white">
                <h6 class="text-white-50">Dépenses du mois</h6>
                <h3>{{ number_format($stats['month_expenses'], 0, ',', ' ') }} <small>F</small></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card" style="background: linear-gradient(135deg, #198754, #20c997);">
            <div class="card-body text-white">
                <h6 class="text-white-50">Bénéfice du jour</h6>
                <h3>{{ number_format($stats['today_profit'], 0, ',', ' ') }} <small>F</small></h3>
                <small class="text-white-50">Ventes - Dépenses</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card" style="background: linear-gradient(135deg, #0dcaf0, #198754);">
            <div class="card-body text-white">
                <h6 class="text-white-50">Bénéfice du mois</h6>
                <h3>{{ number_format($stats['month_profit'], 0, ',', ' ') }} <small>F</small></h3>
                <small class="text-white-50">Ventes - Dépenses</small>
            </div>
        </div>
    </div>
</div>

<!-- S.A.V Alertes -->
@if($urgentSavTickets->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-exclamation-triangle-fill me-2"></i>Tickets S.A.V Urgents</span>
                <a href="{{ route('sav.index') }}?priority=urgent" class="btn btn-sm btn-light">Voir tous</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ticket</th>
                                <th>Type</th>
                                <th>Client</th>
                                <th>Créé</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($urgentSavTickets as $ticket)
                            <tr>
                                <td><span class="badge bg-danger">{{ $ticket->ticket_number }}</span></td>
                                <td>{{ $ticket->type_name }}</td>
                                <td>{{ $ticket->customer->full_name ?? 'N/A' }}</td>
                                <td>{{ $ticket->created_at->diffForHumans() }}</td>
                                <td>
                                    <a href="{{ route('sav.show', $ticket) }}" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- S.A.V Stats du mois -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card" style="background: linear-gradient(135deg, #6f42c1, #9461d6);">
            <div class="card-body text-white">
                <div class="row">
                    <div class="col-6 border-end">
                        <h6 class="text-white-50">Tickets S.A.V ce mois</h6>
                        <h3>{{ $stats['sav_month_tickets'] }}</h3>
                    </div>
                    <div class="col-6">
                        <h6 class="text-white-50">Remboursements</h6>
                        <h3>{{ number_format($stats['sav_month_refunds'], 0, ',', ' ') }} <small>F</small></h3>
                    </div>
                </div>
                <a href="{{ route('admin.reports.sav') }}" class="btn btn-light btn-sm mt-2">
                    <i class="bi bi-graph-up me-1"></i>Voir rapport S.A.V
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="text-muted mb-3"><i class="bi bi-shield-check me-2"></i>Indicateurs Anti-Fraude</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span>Taux de S.A.V / Ventes</span>
                    @php
                        $savRate = $stats['month_sales_count'] > 0 
                            ? round(($stats['sav_month_tickets'] / $stats['month_sales_count']) * 100, 2) 
                            : 0;
                    @endphp
                    <span class="badge bg-{{ $savRate <= 2 ? 'success' : ($savRate <= 5 ? 'warning' : 'danger') }}">
                        {{ $savRate }}%
                    </span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Remboursements / CA</span>
                    @php
                        $refundRate = $stats['month_sales_amount'] > 0 
                            ? round(($stats['sav_month_refunds'] / $stats['month_sales_amount']) * 100, 2) 
                            : 0;
                    @endphp
                    <span class="badge bg-{{ $refundRate <= 1 ? 'success' : ($refundRate <= 3 ? 'warning' : 'danger') }}">
                        {{ $refundRate }}%
                    </span>
                </div>
                @if($savRate > 5 || $refundRate > 3)
                <div class="alert alert-warning border-0 small mt-3 mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Taux anormalement élevé - Vérification recommandée
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Dernières ventes -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cart"></i> Dernières ventes</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>N° Facture</th>
                                <th>Client</th>
                                <th>Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                            <tr>
                                <td><code>{{ $sale->invoice_number }}</code></td>
                                <td>{{ $sale->client_name }}</td>
                                <td>{{ number_format($sale->total_amount, 0, ',', ' ') }}</td>
                                <td>
                                    @if($sale->payment_status === 'paid')
                                        <span class="badge bg-success">Payé</span>
                                    @elseif($sale->payment_status === 'credit')
                                        <span class="badge bg-warning">Crédit</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $sale->payment_status }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Aucune vente</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Dernières réparations -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-tools"></i> Dernières réparations</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>N° Réparation</th>
                                <th>Client</th>
                                <th>Appareil</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRepairs as $repair)
                            <tr>
                                <td><code>{{ $repair->repair_number }}</code></td>
                                <td>{{ $repair->customer->full_name }}</td>
                                <td>{{ $repair->device_full_name }}</td>
                                <td>
                                    <span class="badge bg-{{ $repair->status_color }}">
                                        {{ $repair->status_label }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Aucune réparation</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dépenses récentes et Top catégories -->
<div class="row g-4 mt-2 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #fd7e14, #ffc107); color: white;">
                <span><i class="bi bi-wallet2"></i> Dernières dépenses</span>
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-sm btn-light">Voir tout</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Référence</th>
                                <th>Catégorie</th>
                                <th>Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentExpenses as $expense)
                            <tr>
                                <td><code>{{ $expense->reference }}</code></td>
                                <td>{{ $expense->category->name ?? 'N/A' }}</td>
                                <td>{{ number_format($expense->amount, 0, ',', ' ') }} F</td>
                                <td>
                                    @if($expense->status === 'approved')
                                        <span class="badge bg-success">Approuvé</span>
                                    @elseif($expense->status === 'pending')
                                        <span class="badge bg-warning">En attente</span>
                                    @else
                                        <span class="badge bg-danger">Rejeté</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Aucune dépense</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, #dc3545, #fd7e14); color: white;">
                <i class="bi bi-pie-chart"></i> Top 5 catégories de dépenses (mois)
            </div>
            <div class="card-body">
                @if($topExpenseCategories->count() > 0)
                    @foreach($topExpenseCategories as $expenseCat)
                        @php
                            $percentage = $stats['month_expenses'] > 0 ? ($expenseCat->total / $stats['month_expenses']) * 100 : 0;
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>{{ $expenseCat->category->name ?? 'Non catégorisé' }}</span>
                                <span class="text-muted">{{ number_format($expenseCat->total, 0, ',', ' ') }} F</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $percentage }}%" 
                                     aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted text-center">Aucune dépense ce mois</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Produits en stock faible -->
@if($lowStockProducts->count() > 0)
<div class="card mt-4">
    <div class="card-header bg-warning text-dark">
        <i class="bi bi-exclamation-triangle"></i> Produits en stock faible
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th>Stock actuel</th>
                        <th>Seuil alerte</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowStockProducts as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category->name }}</td>
                        <td>
                            <span class="badge bg-{{ $product->quantity_in_stock <= 0 ? 'danger' : 'warning' }}">
                                {{ $product->quantity_in_stock }}
                            </span>
                        </td>
                        <td>{{ $product->stock_alert_threshold }}</td>
                        <td>
                            <a href="{{ route('admin.products.stock-entry', $product) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-circle"></i> Réapprovisionner
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
