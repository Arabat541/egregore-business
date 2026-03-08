@extends('layouts.app')

@section('title', 'Rapport Financier')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-cash-stack me-2"></i>Rapport Financier
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Rapports</a></li>
                    <li class="breadcrumb-item active">Financier</li>
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
                    <a href="{{ route('admin.reports.financial') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
                <div class="col-md-3 text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="?start_date={{ now()->startOfDay()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}{{ isset($shopId) ? '&shop_id='.$shopId : '' }}" class="btn btn-outline-success">Aujourd'hui</a>
                        <a href="?start_date={{ now()->startOfWeek()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}{{ isset($shopId) ? '&shop_id='.$shopId : '' }}" class="btn btn-outline-success">Semaine</a>
                        <a href="?start_date={{ now()->startOfMonth()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}{{ isset($shopId) ? '&shop_id='.$shopId : '' }}" class="btn btn-outline-success">Mois</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Synthèse Financière -->
    <div class="row g-4 mb-4">
        <!-- Revenus Totaux -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm h-100 bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-3">REVENUS BRUTS</h6>
                    <h2 class="mb-4">{{ number_format($totalRevenue, 0, ',', ' ') }} <small>F</small></h2>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-cart-check me-2"></i>Ventes</span>
                        <strong>{{ number_format($salesRevenue, 0, ',', ' ') }} F</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-tools me-2"></i>Réparations</span>
                        <strong>{{ number_format($repairsRevenue, 0, ',', ' ') }} F</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- S.A.V Impact -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #6f42c1, #9461d6);">
                <div class="card-body text-white">
                    <h6 class="text-white-50 mb-3">IMPACT S.A.V</h6>
                    <h2 class="mb-4">-{{ number_format($savTotalImpact, 0, ',', ' ') }} <small>F</small></h2>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-cash-coin me-2"></i>Remboursements</span>
                        <strong>{{ number_format($savStats['total_refunds'], 0, ',', ' ') }} F</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-arrow-left-right me-2"></i>Pertes échanges</span>
                        <strong>{{ number_format($savStats['exchange_losses'], 0, ',', ' ') }} F</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-ticket me-2"></i>Tickets: {{ $savStats['total_tickets'] }}</span>
                        <a href="{{ route('admin.reports.sav') }}" class="text-white small">
                            Détails <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Marge Brute -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm h-100 bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-3">MARGE BRUTE</h6>
                    <h2 class="mb-4">{{ number_format($grossProfit, 0, ',', ' ') }} <small>F</small></h2>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-bag me-2"></i>Coût ventes</span>
                        <strong>{{ number_format($costOfGoodsSold, 0, ',', ' ') }} F</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-percent me-2"></i>Taux marge</span>
                        <strong>{{ $profitMargin }}%</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Créances (Dettes des revendeurs) -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm h-100 bg-danger text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-3">CRÉANCES REVENDEURS</h6>
                    <h2 class="mb-4">{{ number_format($resellerDebt, 0, ',', ' ') }} <small>F</small></h2>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-shop me-2"></i>Dette totale</span>
                        <strong>{{ number_format($resellerDebt, 0, ',', ' ') }} F</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-info-circle me-2"></i>Ventes à crédit</span>
                        <strong>incluses</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenu Net (après S.A.V) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex justify-content-around text-center">
                                <div>
                                    <small class="text-muted d-block">Revenus bruts</small>
                                    <span class="text-success fw-bold fs-5">{{ number_format($totalRevenue, 0, ',', ' ') }} F</span>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-dash fs-3 text-muted"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Pertes S.A.V</small>
                                    <span class="fw-bold fs-5" style="color: #6f42c1;">{{ number_format($savTotalImpact, 0, ',', ' ') }} F</span>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-dash fs-3 text-muted"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Dépenses</small>
                                    <span class="fw-bold fs-5 text-danger">{{ number_format($totalExpenses ?? 0, 0, ',', ' ') }} F</span>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-equal fs-3 text-muted"></i>
                                </div>
                                <div class="border-start ps-4">
                                    <small class="text-muted d-block">= REVENU NET</small>
                                    <span class="fw-bold fs-4 {{ ($netRevenue - ($totalExpenses ?? 0)) >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($netRevenue - ($totalExpenses ?? 0), 0, ',', ' ') }} F
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end border-start">
                            <small class="text-muted d-block">BÉNÉFICE NET FINAL</small>
                            <span class="fw-bold fs-3 {{ ($finalNetProfit ?? $netProfit) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($finalNetProfit ?? $netProfit, 0, ',', ' ') }} F
                            </span>
                            <small class="text-muted d-block">(Marge + Réparations - S.A.V - Dépenses)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dépenses -->
    @if(isset($totalExpenses) && $totalExpenses > 0)
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="bi bi-wallet2 me-2"></i>Dépenses par catégorie</h6>
                </div>
                <div class="card-body">
                    @if(isset($expensesByCategory) && $expensesByCategory->count() > 0)
                        @foreach($expensesByCategory as $exp)
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                <span>
                                    <span class="badge me-2" style="background-color: {{ $exp->category->color ?? '#6c757d' }}">
                                        <i class="fas {{ $exp->category->icon ?? 'fa-tag' }}"></i>
                                    </span>
                                    {{ $exp->category->name ?? 'Sans catégorie' }}
                                </span>
                                <strong class="text-danger">{{ number_format($exp->total, 0, ',', ' ') }} F</strong>
                            </div>
                        @endforeach
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total Dépenses</span>
                            <span class="text-danger">{{ number_format($totalExpenses, 0, ',', ' ') }} F</span>
                        </div>
                    @else
                        <p class="text-muted text-center mb-0">Aucune dépense</p>
                    @endif
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('admin.expenses.dashboard') }}" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-bar-chart me-1"></i>Voir le détail des dépenses
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="bi bi-credit-card me-2"></i>Dépenses par mode de paiement</h6>
                </div>
                <div class="card-body">
                    @if(isset($expensesByPaymentMethod) && $expensesByPaymentMethod->count() > 0)
                        @foreach($expensesByPaymentMethod as $exp)
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                <span>
                                    @switch($exp->payment_method)
                                        @case('cash')
                                            <span class="badge bg-success me-2"><i class="fas fa-money-bill"></i></span> Espèces
                                            @break
                                        @case('bank_transfer')
                                            <span class="badge bg-info me-2"><i class="fas fa-university"></i></span> Virement
                                            @break
                                        @case('mobile_money')
                                            <span class="badge bg-warning text-dark me-2"><i class="fas fa-mobile-alt"></i></span> Mobile Money
                                            @break
                                        @case('check')
                                            <span class="badge bg-secondary me-2"><i class="fas fa-money-check"></i></span> Chèque
                                            @break
                                    @endswitch
                                </span>
                                <strong>{{ number_format($exp->total, 0, ',', ' ') }} F</strong>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center mb-0">Aucune donnée</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Indicateurs secondaires -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-arrow-down-circle fs-1 text-success"></i>
                    <h3 class="my-2 text-success">{{ number_format($cashIn, 0, ',', ' ') }} F</h3>
                    <p class="text-muted mb-0">Entrées de caisse</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-arrow-up-circle fs-1 text-danger"></i>
                    <h3 class="my-2 text-danger">{{ number_format(abs($cashOut), 0, ',', ' ') }} F</h3>
                    <p class="text-muted mb-0">Sorties de caisse</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-wallet2 fs-1 text-info"></i>
                    <h3 class="my-2 text-info">{{ number_format($cashIn + $cashOut, 0, ',', ' ') }} F</h3>
                    <p class="text-muted mb-0">Solde net caisse</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-piggy-bank fs-1 text-warning"></i>
                    <h3 class="my-2">{{ $profitMargin }}%</h3>
                    <p class="text-muted mb-0">Marge bénéficiaire</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique d'évolution -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Évolution des Revenus
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 300px; position: relative;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-credit-card me-2"></i>Par Mode de Paiement
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 200px; position: relative;">
                        <canvas id="paymentChart"></canvas>
                    </div>
                    <div class="mt-3">
                        @foreach($revenueByPayment as $payment)
                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ $payment->payment_method }}</span>
                                <strong>{{ number_format($payment->total, 0, ',', ' ') }} F</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compte de résultat simplifié -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-calculator me-2"></i>Compte de Résultat Simplifié
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tbody>
                            <tr class="border-bottom">
                                <td><strong>Chiffre d'affaires Ventes</strong></td>
                                <td class="text-end text-success fw-bold">{{ number_format($salesRevenue, 0, ',', ' ') }} F</td>
                            </tr>
                            <tr>
                                <td class="ps-4 text-muted">- Coût des marchandises vendues</td>
                                <td class="text-end text-danger">- {{ number_format($costOfGoodsSold, 0, ',', ' ') }} F</td>
                            </tr>
                            <tr class="border-bottom bg-light">
                                <td><strong>= Marge brute Ventes</strong></td>
                                <td class="text-end text-primary fw-bold">{{ number_format($grossProfit, 0, ',', ' ') }} F</td>
                            </tr>
                            <tr>
                                <td><strong>+ Revenus Réparations</strong></td>
                                <td class="text-end text-success">+ {{ number_format($repairsRevenue, 0, ',', ' ') }} F</td>
                            </tr>
                            <tr class="border-bottom">
                                <td><strong>= Sous-total avant S.A.V</strong></td>
                                <td class="text-end fw-bold">{{ number_format($grossProfit + $repairsRevenue, 0, ',', ' ') }} F</td>
                            </tr>
                            <tr style="background-color: rgba(111, 66, 193, 0.1);">
                                <td>
                                    <strong class="text-purple">- Pertes S.A.V</strong>
                                    <br><small class="text-muted ps-3">Remboursements: {{ number_format($savStats['total_refunds'], 0, ',', ' ') }} F</small>
                                    <br><small class="text-muted ps-3">Pertes échanges: {{ number_format($savStats['exchange_losses'], 0, ',', ' ') }} F</small>
                                    <br><small class="text-success ps-3">Gains échanges: +{{ number_format($savStats['exchange_gains'], 0, ',', ' ') }} F</small>
                                </td>
                                <td class="text-end align-middle" style="color: #6f42c1;">
                                    <strong>- {{ number_format($savTotalImpact, 0, ',', ' ') }} F</strong>
                                </td>
                            </tr>
                            <tr class="table-success">
                                <td><strong>= RÉSULTAT NET ESTIMÉ</strong></td>
                                <td class="text-end fw-bold fs-5">{{ number_format($netProfit, 0, ',', ' ') }} F</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="alert alert-info border-0 mb-0">
                        <small>
                            <i class="bi bi-info-circle me-2"></i>
                            Ce résultat n'inclut pas les charges d'exploitation (loyer, salaires, électricité, etc.)
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Santé financière -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-heart-pulse me-2"></i>Indicateurs de Santé Financière
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Taux de marge -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Taux de marge brute</span>
                            <strong>{{ $profitMargin }}%</strong>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-{{ $profitMargin >= 30 ? 'success' : ($profitMargin >= 20 ? 'warning' : 'danger') }}" 
                                 style="width: {{ min($profitMargin, 100) }}%">
                                {{ $profitMargin }}%
                            </div>
                        </div>
                        <small class="text-muted">
                            @if($profitMargin >= 30)
                                <i class="bi bi-check-circle text-success"></i> Excellent
                            @elseif($profitMargin >= 20)
                                <i class="bi bi-exclamation-circle text-warning"></i> Acceptable
                            @else
                                <i class="bi bi-x-circle text-danger"></i> À améliorer
                            @endif
                        </small>
                    </div>

                    <!-- Taux de recouvrement -->
                    @php
                        $recoverRate = $totalRevenue > 0 ? round((($salesRevenue + $repairsRevenue) / ($totalRevenue + $resellerDebt)) * 100, 1) : 100;
                    @endphp
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Taux de recouvrement</span>
                            <strong>{{ $recoverRate }}%</strong>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-{{ $recoverRate >= 90 ? 'success' : ($recoverRate >= 70 ? 'warning' : 'danger') }}" 
                                 style="width: {{ $recoverRate }}%">
                                {{ $recoverRate }}%
                            </div>
                        </div>
                        <small class="text-muted">
                            @if($recoverRate >= 90)
                                <i class="bi bi-check-circle text-success"></i> Très bon recouvrement
                            @elseif($recoverRate >= 70)
                                <i class="bi bi-exclamation-circle text-warning"></i> Créances à surveiller
                            @else
                                <i class="bi bi-x-circle text-danger"></i> Créances trop élevées
                            @endif
                        </small>
                    </div>

                    <!-- Taux de S.A.V (pertes / revenus) -->
                    @php
                        $savRate = $totalRevenue > 0 ? round(($savTotalImpact / $totalRevenue) * 100, 2) : 0;
                    @endphp
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Taux de pertes S.A.V</span>
                            <strong>{{ $savRate }}%</strong>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar" style="width: {{ min($savRate * 10, 100) }}%; background-color: #6f42c1;">
                                {{ $savRate }}%
                            </div>
                        </div>
                        <small class="text-muted">
                            @if($savRate <= 2)
                                <i class="bi bi-check-circle text-success"></i> Très faible - Excellent
                            @elseif($savRate <= 5)
                                <i class="bi bi-exclamation-circle text-warning"></i> Normal - À surveiller
                            @else
                                <i class="bi bi-x-circle text-danger"></i> Élevé - Enquêter
                            @endif
                            <a href="{{ route('admin.reports.sav') }}" class="ms-2 text-decoration-none">
                                <i class="bi bi-arrow-right-circle"></i> Détails
                            </a>
                        </small>
                    </div>

                    <!-- Part réparations -->
                    @php
                        $repairShare = $totalRevenue > 0 ? round(($repairsRevenue / $totalRevenue) * 100, 1) : 0;
                    @endphp
                    <div class="mb-0">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Part réparations dans le CA</span>
                            <strong>{{ $repairShare }}%</strong>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-warning" style="width: {{ $repairShare }}%">{{ $repairShare }}%</div>
                            <div class="progress-bar bg-primary" style="width: {{ 100 - $repairShare }}%">{{ 100 - $repairShare }}%</div>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Diversification des revenus
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historique des caisses -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-journal-text me-2"></i>Historique des Caisses
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date Ouverture</th>
                                    <th>Caissier</th>
                                    <th class="text-end">Fond Initial</th>
                                    <th class="text-end">Ventes</th>
                                    <th class="text-end">Dépenses</th>
                                    <th class="text-end">Théorique</th>
                                    <th class="text-end">Réel</th>
                                    <th class="text-center">Écart</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cashRegisters as $register)
                                    @php
                                        $theoretical = $register->opening_balance + $register->total_income - $register->total_expense;
                                        $actual = $register->closing_balance ?? 0;
                                        $difference = $actual - $theoretical;
                                    @endphp
                                    <tr>
                                        <td>{{ $register->opened_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $register->user->name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($register->opening_balance, 0, ',', ' ') }} F</td>
                                        <td class="text-end text-success">{{ number_format($register->total_income, 0, ',', ' ') }} F</td>
                                        <td class="text-end text-danger">{{ number_format($register->total_expense, 0, ',', ' ') }} F</td>
                                        <td class="text-end">{{ number_format($theoretical, 0, ',', ' ') }} F</td>
                                        <td class="text-end fw-bold">{{ number_format($actual, 0, ',', ' ') }} F</td>
                                        <td class="text-center">
                                            @if($register->status === 'closed')
                                                <span class="badge bg-{{ $difference == 0 ? 'success' : ($difference > 0 ? 'info' : 'danger') }}">
                                                    {{ $difference > 0 ? '+' : '' }}{{ number_format($difference, 0, ',', ' ') }} F
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($register->status === 'open')
                                                <span class="badge bg-success">Ouverte</span>
                                            @else
                                                <span class="badge bg-secondary">Fermée</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">Aucune caisse sur cette période</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dates = @json($dates);
    const paymentData = @json($revenueByPayment);

    // Graphique évolution revenus
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: dates.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
            }),
            datasets: [{
                label: 'Ventes (FCFA)',
                data: dates.map(d => d.sales),
                backgroundColor: 'rgba(25, 135, 84, 0.7)',
                borderColor: 'rgb(25, 135, 84)',
                borderWidth: 1
            }, {
                label: 'Réparations (FCFA)',
                data: dates.map(d => d.repairs),
                backgroundColor: 'rgba(255, 193, 7, 0.7)',
                borderColor: 'rgb(255, 193, 7)',
                borderWidth: 1
            }, {
                label: 'Pertes S.A.V (FCFA)',
                data: dates.map(d => -d.sav_losses),
                backgroundColor: 'rgba(111, 66, 193, 0.7)',
                borderColor: 'rgb(111, 66, 193)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: { callback: value => value.toLocaleString('fr-FR') + ' F' }
                }
            }
        }
    });

    // Graphique modes de paiement
    const paymentColors = {
        'Espèces': '#198754',
        'cash': '#198754',
        'Orange Money': '#ff7900',
        'orange_money': '#ff7900',
        'Wave': '#1dc3e3',
        'wave': '#1dc3e3',
        'MTN Money': '#ffcc00',
        'mtn_money': '#ffcc00',
        'Moov Money': '#0066b3',
        'moov_money': '#0066b3',
        'Carte Bancaire': '#6f42c1',
        'card': '#6f42c1',
        'Virement Bancaire': '#0d6efd',
        'bank_transfer': '#0d6efd'
    };

    new Chart(document.getElementById('paymentChart'), {
        type: 'doughnut',
        data: {
            labels: paymentData.map(d => d.payment_method),
            datasets: [{
                data: paymentData.map(d => d.total),
                backgroundColor: paymentData.map(d => paymentColors[d.payment_method] || '#6c757d')
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.label + ': ' + ctx.parsed.toLocaleString('fr-FR') + ' FCFA'
                    }
                }
            }
        }
    });
});
</script>
@endpush

<style>
@media print {
    .btn, .form-control, .btn-group, nav {
        display: none !important;
    }
    .card {
        break-inside: avoid;
    }
}
.text-purple {
    color: #6f42c1;
}
</style>
@endsection
