@extends('layouts.app')

@section('title', 'Rapport S.A.V - Service Après-Vente')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-shield-exclamation me-2"></i>Rapport S.A.V
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Rapports</a></li>
                    <li class="breadcrumb-item active">Service Après-Vente</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('sav.index') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-list me-2"></i>Voir tickets
            </a>
            <button class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-2"></i>Imprimer
            </button>
        </div>
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
                    <a href="{{ route('admin.reports.sav') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
                <div class="col-md-3 text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="?start_date={{ now()->startOfDay()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}{{ isset($shopId) ? '&shop_id='.$shopId : '' }}" class="btn btn-outline-primary">Aujourd'hui</a>
                        <a href="?start_date={{ now()->startOfWeek()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}{{ isset($shopId) ? '&shop_id='.$shopId : '' }}" class="btn btn-outline-primary">Semaine</a>
                        <a href="?start_date={{ now()->startOfMonth()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}{{ isset($shopId) ? '&shop_id='.$shopId : '' }}" class="btn btn-outline-primary">Mois</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Synthèse S.A.V -->
    <div class="row g-4 mb-4">
        <!-- Total Tickets -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #6f42c1, #9461d6);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-white-50 mb-2">TICKETS S.A.V</h6>
                            <h2 class="mb-1">{{ $totalTickets }}</h2>
                            <small class="text-white-50">
                                @if($ticketGrowth != 0)
                                    <i class="bi bi-{{ $ticketGrowth > 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                                    {{ abs($ticketGrowth) }}% vs période préc.
                                @else
                                    Stable
                                @endif
                            </small>
                        </div>
                        <i class="bi bi-ticket-perforated fs-1 opacity-50"></i>
                    </div>
                    <hr class="my-3 opacity-25">
                    <div class="d-flex justify-content-between small">
                        <span>Ouverts: <strong>{{ $openTickets }}</strong></span>
                        <span>Fermés: <strong>{{ $closedTickets }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remboursements -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-white-50 mb-2">REMBOURSEMENTS</h6>
                            <h2 class="mb-1">{{ number_format($totalRefunds, 0, ',', ' ') }} <small>F</small></h2>
                            <small class="text-white-50">
                                @if($refundGrowth != 0)
                                    <i class="bi bi-{{ $refundGrowth > 0 ? 'arrow-up text-warning' : 'arrow-down text-success' }}"></i>
                                    {{ abs($refundGrowth) }}% vs période préc.
                                @else
                                    Stable
                                @endif
                            </small>
                        </div>
                        <i class="bi bi-cash-coin fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pertes échanges -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="opacity-75 mb-2">PERTES ÉCHANGES</h6>
                            <h2 class="mb-1">{{ number_format($totalExchangeLosses, 0, ',', ' ') }} <small>F</small></h2>
                            <small class="opacity-75">Différences négatives</small>
                        </div>
                        <i class="bi bi-arrow-left-right fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gains échanges -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-white-50 mb-2">GAINS ÉCHANGES</h6>
                            <h2 class="mb-1">{{ number_format($totalExchangeGains, 0, ',', ' ') }} <small>F</small></h2>
                            <small class="text-white-50">Suppléments clients</small>
                        </div>
                        <i class="bi bi-plus-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Impact Financier Net -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm {{ ($totalRefunds + $totalExchangeLosses - $totalExchangeGains) > 0 ? 'border-start border-danger border-4' : 'border-start border-success border-4' }}">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-1">
                                <i class="bi bi-calculator me-2"></i>Impact Financier Net S.A.V
                            </h5>
                            <small class="text-muted">Pertes totales liées au service après-vente</small>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex justify-content-around text-center">
                                <div>
                                    <small class="text-muted d-block">Remboursements</small>
                                    <span class="text-danger fw-bold">- {{ number_format($totalRefunds, 0, ',', ' ') }} F</span>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Pertes échanges</small>
                                    <span class="text-danger fw-bold">- {{ number_format($totalExchangeLosses, 0, ',', ' ') }} F</span>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Gains échanges</small>
                                    <span class="text-success fw-bold">+ {{ number_format($totalExchangeGains, 0, ',', ' ') }} F</span>
                                </div>
                                <div class="border-start ps-4">
                                    <small class="text-muted d-block">= PERTE NETTE</small>
                                    @php $netLoss = $totalRefunds + $totalExchangeLosses - $totalExchangeGains; @endphp
                                    <span class="fs-4 fw-bold {{ $netLoss > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $netLoss > 0 ? '-' : '+' }} {{ number_format(abs($netLoss), 0, ',', ' ') }} F
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ SECTION ALERTES ANTI-MALVERSATION ============ -->
    @if($suspiciousRefunds->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm border-danger border-top border-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        ALERTES - Remboursements Suspects ({{ $suspiciousRefunds->count() }})
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning border-0 mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Ces tickets présentent des anomalies qui nécessitent une vérification manuelle.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>N° Ticket</th>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th class="text-end">Montant</th>
                                    <th>Créé par</th>
                                    <th>Vente liée</th>
                                    <th>Alertes</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($suspiciousRefunds as $ticket)
                                <tr>
                                    <td>
                                        <span class="badge bg-danger">{{ $ticket->ticket_number }}</span>
                                    </td>
                                    <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $ticket->customer->full_name ?? 'N/A' }}</td>
                                    <td class="text-end fw-bold text-danger">
                                        {{ number_format($ticket->refund_amount, 0, ',', ' ') }} F
                                    </td>
                                    <td>{{ $ticket->creator->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($ticket->sale)
                                            {{ $ticket->sale->invoice_number }}
                                            <br><small class="text-muted">par {{ $ticket->sale->user->name ?? '?' }}</small>
                                        @else
                                            <span class="text-danger">Aucune</span>
                                        @endif
                                    </td>
                                    <td>
                                        @foreach($ticket->alerts as $alert)
                                            @switch($alert)
                                                @case('montant_eleve')
                                                    <span class="badge bg-danger" title="Montant élevé > 50 000 F">
                                                        <i class="bi bi-cash"></i> Élevé
                                                    </span>
                                                    @break
                                                @case('meme_employe')
                                                    <span class="badge bg-warning text-dark" title="Même employé a fait la vente et le SAV">
                                                        <i class="bi bi-person-exclamation"></i> Même emp.
                                                    </span>
                                                    @break
                                                @case('rapide')
                                                    <span class="badge bg-info" title="Créé moins de 24h après la vente">
                                                        <i class="bi bi-clock-history"></i> Rapide
                                                    </span>
                                                    @break
                                                @case('sans_vente')
                                                    <span class="badge bg-dark" title="Remboursement sans vente liée">
                                                        <i class="bi bi-question-circle"></i> Sans vente
                                                    </span>
                                                    @break
                                            @endswitch
                                        @endforeach
                                    </td>
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

    <!-- Graphiques -->
    <div class="row g-4 mb-4">
        <!-- Évolution des tickets -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Évolution des Tickets S.A.V
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 300px; position: relative;">
                        <canvas id="ticketsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Par Type -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-pie-chart me-2"></i>Par Type
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 200px; position: relative;">
                        <canvas id="typeChart"></canvas>
                    </div>
                    <div class="mt-3">
                        @foreach($ticketsByType as $type)
                            <div class="d-flex justify-content-between mb-2">
                                <span>
                                    @switch($type->type)
                                        @case('return') <i class="bi bi-arrow-return-left text-warning"></i> Retour @break
                                        @case('exchange') <i class="bi bi-arrow-left-right text-info"></i> Échange @break
                                        @case('warranty') <i class="bi bi-shield-check text-success"></i> Garantie @break
                                        @case('complaint') <i class="bi bi-chat-left-dots text-danger"></i> Réclamation @break
                                        @case('refund') <i class="bi bi-cash-coin text-danger"></i> Remboursement @break
                                        @default <i class="bi bi-question-circle text-secondary"></i> Autre
                                    @endswitch
                                </span>
                                <strong>{{ $type->count }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ ANALYSE PAR EMPLOYÉ ============ -->
    <div class="row g-4 mb-4">
        <!-- S.A.V par créateur -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-person-badge me-2"></i>Tickets par Employé (créateur)
                    </h5>
                    <span class="badge bg-secondary">Suivi anti-fraude</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Employé</th>
                                    <th class="text-center">Tickets</th>
                                    <th class="text-center">Remb.</th>
                                    <th class="text-end">Total Remb.</th>
                                    <th class="text-end">Pertes Éch.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ticketsByCreator as $creator)
                                <tr class="{{ $creator->total_refunds > 100000 ? 'table-warning' : '' }}">
                                    <td>
                                        <strong>{{ $creator->creator->name ?? 'N/A' }}</strong>
                                        @if($creator->total_refunds > 100000)
                                            <i class="bi bi-exclamation-triangle text-warning" title="Remboursements élevés"></i>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $creator->total_tickets }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-danger">{{ $creator->refund_count }}</span>
                                    </td>
                                    <td class="text-end text-danger fw-bold">
                                        {{ number_format($creator->total_refunds, 0, ',', ' ') }} F
                                    </td>
                                    <td class="text-end text-warning">
                                        {{ number_format($creator->exchange_losses, 0, ',', ' ') }} F
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Aucun ticket</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Taux de S.A.V par vendeur -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-percent me-2"></i>Taux de S.A.V par Vendeur
                    </h5>
                    <span class="badge bg-info">Qualité ventes</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-0 small mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Un taux élevé peut indiquer des ventes forcées ou un mauvais conseil client.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Vendeur</th>
                                    <th class="text-center">Ventes</th>
                                    <th class="text-center">S.A.V</th>
                                    <th class="text-end">Taux</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($savByVendor as $vendor)
                                @if($vendor->sale && $vendor->sale->user)
                                <tr class="{{ $vendor->sav_rate > 10 ? 'table-danger' : ($vendor->sav_rate > 5 ? 'table-warning' : '') }}">
                                    <td>
                                        <strong>{{ $vendor->sale->user->name ?? 'N/A' }}</strong>
                                        @if($vendor->sav_rate > 10)
                                            <i class="bi bi-exclamation-triangle-fill text-danger" title="Taux S.A.V critique"></i>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $vendor->total_sales }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark">{{ $vendor->sav_count }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-{{ $vendor->sav_rate > 10 ? 'danger' : ($vendor->sav_rate > 5 ? 'warning' : 'success') }}">
                                            {{ $vendor->sav_rate }}%
                                        </span>
                                    </td>
                                </tr>
                                @endif
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Aucune donnée</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ PRODUITS ET CLIENTS À RISQUE ============ -->
    <div class="row g-4 mb-4">
        <!-- Produits problématiques -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-box-seam me-2 text-warning"></i>Produits les Plus Retournés
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Retours</th>
                                    <th class="text-center">Échanges</th>
                                    <th class="text-center">Garantie</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($problematicProducts as $product)
                                <tr>
                                    <td>
                                        @if($product->product)
                                            <strong>{{ Str::limit($product->product->name, 25) }}</strong>
                                            <br><small class="text-muted">{{ $product->product->sku }}</small>
                                        @else
                                            <span class="text-muted">Produit supprimé</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $product->sav_count }}</span>
                                    </td>
                                    <td class="text-center text-warning">{{ $product->return_count }}</td>
                                    <td class="text-center text-info">{{ $product->exchange_count }}</td>
                                    <td class="text-center text-success">{{ $product->warranty_count }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Aucun produit</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clients avec beaucoup de S.A.V -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-person-exclamation me-2 text-danger"></i>Clients à Surveiller
                    </h5>
                    <span class="badge bg-danger">≥ 2 tickets</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning border-0 small mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Clients avec plusieurs réclamations - possible abus ou besoin d'attention particulière.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th class="text-center">Tickets</th>
                                    <th class="text-end">Remboursés</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customersWithMostSav as $customer)
                                <tr class="{{ $customer->sav_count >= 5 ? 'table-danger' : '' }}">
                                    <td>
                                        @if($customer->customer)
                                            <strong>{{ $customer->customer->full_name }}</strong>
                                            <br><small class="text-muted">{{ $customer->customer->phone }}</small>
                                        @else
                                            <span class="text-muted">Client supprimé</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $customer->sav_count >= 5 ? 'danger' : 'warning' }}">
                                            {{ $customer->sav_count }}
                                        </span>
                                    </td>
                                    <td class="text-end text-danger fw-bold">
                                        {{ number_format($customer->total_refunds, 0, ',', ' ') }} F
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Aucun client avec ≥ 2 tickets</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ TICKETS ANCIENS ET DERNIERS REMBOURSEMENTS ============ -->
    <div class="row g-4 mb-4">
        <!-- Tickets en attente depuis longtemps -->
        @if($oldOpenTickets->count() > 0)
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 border-warning border-top border-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>Tickets en Attente > 7 jours ({{ $oldOpenTickets->count() }})
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Ticket</th>
                                    <th>Client</th>
                                    <th>Assigné à</th>
                                    <th>Âge</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($oldOpenTickets as $ticket)
                                <tr>
                                    <td>
                                        <span class="badge bg-warning text-dark">{{ $ticket->ticket_number }}</span>
                                    </td>
                                    <td>{{ $ticket->customer->full_name ?? 'N/A' }}</td>
                                    <td>{{ $ticket->assignedUser->name ?? '-' }}</td>
                                    <td>
                                        <span class="text-danger fw-bold">
                                            {{ $ticket->created_at->diffForHumans() }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('sav.show', $ticket) }}" class="btn btn-sm btn-outline-warning">
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
        @endif

        <!-- Derniers remboursements -->
        <div class="col-lg-{{ $oldOpenTickets->count() > 0 ? '6' : '12' }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-journal-text me-2"></i>Journal des Remboursements
                    </h5>
                    <span class="badge bg-secondary">Audit</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Date</th>
                                    <th>Ticket</th>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th class="text-end">Montant</th>
                                    <th>Par</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentRefunds as $refund)
                                <tr>
                                    <td>{{ $refund->resolved_at ? $refund->resolved_at->format('d/m H:i') : $refund->created_at->format('d/m') }}</td>
                                    <td>
                                        <a href="{{ route('sav.show', $refund) }}" class="text-decoration-none">
                                            {{ $refund->ticket_number }}
                                        </a>
                                    </td>
                                    <td>{{ Str::limit($refund->customer->full_name ?? 'N/A', 15) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $refund->type == 'refund' ? 'danger' : 'warning' }}">
                                            {{ $refund->type_name }}
                                        </span>
                                    </td>
                                    <td class="text-end text-danger fw-bold">
                                        @if($refund->refund_amount > 0)
                                            {{ number_format($refund->refund_amount, 0, ',', ' ') }} F
                                        @elseif($refund->exchange_difference < 0)
                                            {{ number_format(abs($refund->exchange_difference), 0, ',', ' ') }} F
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $refund->creator->name ?? '?' }}</small>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Aucun remboursement</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicateurs de performance -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 text-center">
                <div class="card-body">
                    <i class="bi bi-clock fs-1 text-info"></i>
                    <h3 class="my-2">{{ $avgResolutionTime ? number_format($avgResolutionTime, 0) : 'N/A' }} h</h3>
                    <p class="text-muted mb-0">Temps moyen de résolution</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 text-center">
                <div class="card-body">
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    @php
                        $resolutionRate = $totalTickets > 0 ? round(($closedTickets / $totalTickets) * 100, 1) : 0;
                    @endphp
                    <h3 class="my-2">{{ $resolutionRate }}%</h3>
                    <p class="text-muted mb-0">Taux de résolution</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 text-center">
                <div class="card-body">
                    <i class="bi bi-currency-exchange fs-1 text-danger"></i>
                    @php
                        $avgRefund = $totalTickets > 0 ? ($totalRefunds / $totalTickets) : 0;
                    @endphp
                    <h3 class="my-2">{{ number_format($avgRefund, 0, ',', ' ') }} F</h3>
                    <p class="text-muted mb-0">Remboursement moyen / ticket</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique évolution tickets
    const ticketsCtx = document.getElementById('ticketsChart');
    if (ticketsCtx) {
        new Chart(ticketsCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($ticketsByDay->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))) !!},
                datasets: [{
                    label: 'Tickets',
                    data: {!! json_encode($ticketsByDay->pluck('count')) !!},
                    borderColor: '#6f42c1',
                    backgroundColor: 'rgba(111, 66, 193, 0.1)',
                    fill: true,
                    tension: 0.3
                }, {
                    label: 'Remboursements (F)',
                    data: {!! json_encode($ticketsByDay->pluck('refunds')) !!},
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: { display: true, text: 'Nombre de tickets' }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: 'Remboursements (F)' }
                    }
                }
            }
        });
    }

    // Graphique par type
    const typeCtx = document.getElementById('typeChart');
    if (typeCtx) {
        const typeLabels = {
            'return': 'Retour',
            'exchange': 'Échange',
            'warranty': 'Garantie',
            'complaint': 'Réclamation',
            'refund': 'Remboursement',
            'other': 'Autre'
        };
        new Chart(typeCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($ticketsByType->pluck('type')->map(fn($t) => ['return'=>'Retour','exchange'=>'Échange','warranty'=>'Garantie','complaint'=>'Réclamation','refund'=>'Remboursement','other'=>'Autre'][$t] ?? $t)) !!},
                datasets: [{
                    data: {!! json_encode($ticketsByType->pluck('count')) !!},
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545', '#c82333', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>
@endpush

<style>
@media print {
    .btn, form, .breadcrumb, nav, .alert {
        display: none !important;
    }
    .card {
        break-inside: avoid;
        border: 1px solid #ddd !important;
    }
}
</style>
@endsection
