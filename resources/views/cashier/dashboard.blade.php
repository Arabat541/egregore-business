@extends('layouts.app')

@section('title', 'Tableau de bord Caissière')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('navbar-actions')
    @if($cashRegister)
        <span class="badge bg-success me-2">
            <i class="bi bi-unlock"></i> Caisse ouverte
        </span>
    @else
        <span class="badge bg-danger me-2">
            <i class="bi bi-lock"></i> Caisse fermée
        </span>
    @endif
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-speedometer2"></i> Tableau de bord</h2>
    <div>
        @if(!$cashRegister)
            <a href="{{ route('cashier.cash-register.open-form') }}" class="btn btn-success">
                <i class="bi bi-unlock"></i> Ouvrir la caisse
            </a>
        @else
            <a href="{{ route('cashier.sales.create') }}" class="btn btn-primary me-2">
                <i class="bi bi-cart-plus"></i> Nouvelle vente
            </a>
            <a href="{{ route('cashier.repairs.create') }}" class="btn btn-warning">
                <i class="bi bi-wrench"></i> Nouvelle réparation
            </a>
        @endif
    </div>
</div>

<!-- Statistiques du jour -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted">Ventes du jour</h6>
                        <h3 class="mb-0">{{ number_format($stats['today_sales_amount'], 0, ',', ' ') }}</h3>
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
                        <h6 class="text-muted">Espèces</h6>
                        <h3 class="mb-0">{{ number_format($stats['today_cash_sales'], 0, ',', ' ') }}</h3>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-cash" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted">Mobile Money</h6>
                        <h3 class="mb-0">{{ number_format($stats['today_mobile_money_sales'], 0, ',', ' ') }}</h3>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-phone" style="font-size: 2rem;"></i>
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
                        <h6 class="text-muted">Réparations</h6>
                        <h3 class="mb-0">{{ number_format($stats['today_repairs_amount'], 0, ',', ' ') }}</h3>
                        <small class="text-muted">{{ $stats['today_repairs_count'] }} fiches</small>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-tools" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistiques Dépenses -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card" style="background: linear-gradient(135deg, #fd7e14, #ffc107);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Dépenses du jour</h6>
                        <h3 class="mb-0">{{ number_format($stats['today_expenses_amount'], 0, ',', ' ') }} <small>F</small></h3>
                        <small>{{ $stats['today_expenses_count'] }} dépense(s)</small>
                    </div>
                    <div>
                        <i class="bi bi-wallet2" style="font-size: 2rem;"></i>
                    </div>
                </div>
                @if($stats['today_pending_expenses'] > 0)
                    <span class="badge bg-light text-warning mt-2">{{ $stats['today_pending_expenses'] }} en attente</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card" style="background: linear-gradient(135deg, #198754, #20c997);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Bénéfice net du jour</h6>
                        <h3 class="mb-0">{{ number_format($stats['today_net_profit'], 0, ',', ' ') }} <small>F</small></h3>
                        <small>CA - Dépenses</small>
                    </div>
                    <div>
                        <i class="bi bi-graph-up-arrow" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="d-grid gap-2">
            <a href="{{ route('cashier.expenses.create') }}" class="btn btn-lg btn-warning">
                <i class="bi bi-plus-circle me-2"></i>Nouvelle dépense
            </a>
            <a href="{{ route('cashier.expenses.index') }}" class="btn btn-lg btn-outline-warning">
                <i class="bi bi-list-ul me-2"></i>Voir toutes les dépenses
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Réparations prêtes pour retrait -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-check-circle"></i> Réparations prêtes pour retrait
            </div>
            <div class="card-body">
                @if($readyRepairs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Client</th>
                                    <th>Téléphone</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($readyRepairs as $repair)
                                <tr>
                                    <td><code>{{ $repair->repair_number }}</code></td>
                                    <td>{{ $repair->customer->full_name }}</td>
                                    <td>{{ $repair->customer->phone }}</td>
                                    <td>
                                        <a href="{{ route('cashier.repairs.show', $repair) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center mb-0">Aucune réparation prête</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Revendeurs avec créances -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-credit-card"></i> Créances revendeurs
            </div>
            <div class="card-body">
                @if($resellersWithDebt->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Revendeur</th>
                                    <th>Dette</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resellersWithDebt as $reseller)
                                <tr>
                                    <td>{{ $reseller->company_name }}</td>
                                    <td class="text-danger fw-bold">{{ number_format($reseller->current_debt, 0, ',', ' ') }} FCFA</td>
                                    <td>
                                        <a href="{{ route('cashier.reseller-payments.show', $reseller) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center mb-0">Aucune créance</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Dernières ventes -->
<div class="card mt-4">
    <div class="card-header">
        <i class="bi bi-receipt"></i> Dernières ventes
    </div>
    <div class="card-body">
        @if($recentSales->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Articles</th>
                            <th>Total</th>
                            <th>Paiement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSales as $sale)
                        <tr>
                            <td><code>{{ $sale->invoice_number }}</code></td>
                            <td>{{ $sale->created_at->format('H:i') }}</td>
                            <td>
                                {{ $sale->client_name }}
                                @if($sale->client_type === 'reseller')
                                    <span class="badge bg-info">Revendeur</span>
                                @endif
                            </td>
                            <td>{{ $sale->item_count }} articles</td>
                            <td class="fw-bold">{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</td>
                            <td>
                                @if($sale->payment_status === 'paid')
                                    <span class="badge bg-success">Payé</span>
                                @elseif($sale->payment_status === 'credit')
                                    <span class="badge bg-warning">Crédit</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('cashier.sales.show', $sale) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('cashier.sales.receipt', $sale) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted text-center mb-0">Aucune vente aujourd'hui</p>
        @endif
    </div>
</div>

<!-- Dernières dépenses -->
<div class="card mt-4">
    <div class="card-header" style="background: linear-gradient(135deg, #fd7e14, #ffc107); color: white;">
        <i class="bi bi-wallet2"></i> Dernières dépenses
    </div>
    <div class="card-body">
        @if($recentExpenses->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Date</th>
                            <th>Catégorie</th>
                            <th>Description</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentExpenses as $expense)
                        <tr>
                            <td><code>{{ $expense->reference }}</code></td>
                            <td>{{ $expense->expense_date->format('d/m H:i') }}</td>
                            <td>
                                @if($expense->category)
                                    <span class="badge" style="background-color: {{ $expense->category->color ?? '#6c757d' }}">
                                        {{ $expense->category->icon ?? '📋' }} {{ $expense->category->name }}
                                    </span>
                                @else
                                    <span class="text-muted">Non catégorisé</span>
                                @endif
                            </td>
                            <td>{{ Str::limit($expense->description, 30) }}</td>
                            <td class="fw-bold">{{ number_format($expense->amount, 0, ',', ' ') }} F</td>
                            <td>
                                @if($expense->status === 'approved')
                                    <span class="badge bg-success">Approuvé</span>
                                @elseif($expense->status === 'pending')
                                    <span class="badge bg-warning">En attente</span>
                                @else
                                    <span class="badge bg-danger">Rejeté</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('cashier.expenses.show', $expense) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted text-center mb-0">Aucune dépense récente</p>
        @endif
    </div>
</div>
@endsection
