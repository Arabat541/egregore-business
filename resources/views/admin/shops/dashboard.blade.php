@extends('layouts.app')

@section('title', 'Dashboard Multi-Boutiques')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-graph-up me-2"></i>Dashboard Multi-Boutiques</h2>
    <a href="{{ route('admin.shops.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Gestion des boutiques
    </a>
</div>

<!-- Totaux globaux -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $totals['shops_count'] }}</h3>
                <small>Boutiques actives</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ number_format($totals['revenue_today'], 0, ',', ' ') }} F</h3>
                <small>CA Total du jour</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $totals['sales_today'] }}</h3>
                <small>Ventes totales</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $totals['pending_repairs'] }}</h3>
                <small>Réparations en cours</small>
            </div>
        </div>
    </div>
</div>

<!-- Performance par boutique -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Performance par boutique (Aujourd'hui)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Boutique</th>
                        <th class="text-end">CA du jour</th>
                        <th class="text-center">Ventes</th>
                        <th class="text-center">Réparations</th>
                        <th class="text-center">En attente</th>
                        <th class="text-center">% CA Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shops as $shop)
                        @php
                            $percentage = $totals['revenue_today'] > 0 
                                ? ($shop->today_revenue / $totals['revenue_today']) * 100 
                                : 0;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.shops.show', $shop) }}" class="text-decoration-none">
                                    <strong>{{ $shop->name }}</strong>
                                </a>
                                <span class="badge bg-dark ms-2">{{ $shop->code }}</span>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-success">{{ number_format($shop->today_revenue, 0, ',', ' ') }} F</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ $shop->sales_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $shop->repairs_count }}</span>
                            </td>
                            <td class="text-center">
                                @if($shop->pending_repairs > 0)
                                    <span class="badge bg-warning text-dark">{{ $shop->pending_repairs }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="progress" style="height: 20px; min-width: 100px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: {{ $percentage }}%">
                                        {{ number_format($percentage, 1) }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-secondary">
                    <tr>
                        <th>TOTAL</th>
                        <th class="text-end text-success">{{ number_format($totals['revenue_today'], 0, ',', ' ') }} F</th>
                        <th class="text-center">{{ $totals['sales_today'] }}</th>
                        <th class="text-center">-</th>
                        <th class="text-center">{{ $totals['pending_repairs'] }}</th>
                        <th class="text-center">100%</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Graphique de répartition -->
<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Répartition du CA</h5>
            </div>
            <div class="card-body">
                @foreach($shops as $shop)
                    @php
                        $percentage = $totals['revenue_today'] > 0 
                            ? ($shop->today_revenue / $totals['revenue_today']) * 100 
                            : 0;
                        $colors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
                        $color = $colors[$loop->index % count($colors)];
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ $shop->name }}</span>
                            <span>{{ number_format($shop->today_revenue, 0, ',', ' ') }} F</span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-{{ $color }}" role="progressbar" 
                                 style="width: {{ max($percentage, 2) }}%">
                                {{ number_format($percentage, 1) }}%
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Activité récente</h5>
            </div>
            <div class="card-body">
                <p class="text-muted text-center">
                    <i class="bi bi-info-circle me-2"></i>
                    Vue consolidée de toutes les boutiques.<br>
                    Cliquez sur une boutique pour voir ses détails.
                </p>
                <div class="list-group">
                    @foreach($shops->take(5) as $shop)
                        <a href="{{ route('admin.shops.show', $shop) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-shop me-2"></i>{{ $shop->name }}
                            </div>
                            <span class="badge bg-success">{{ number_format($shop->today_revenue, 0, ',', ' ') }} F</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
