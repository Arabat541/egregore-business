@extends('layouts.app')

@section('title', 'Tableau de bord Dépenses')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Tableau de bord Dépenses</h1>
            <p class="text-muted mb-0">Analyse et suivi des dépenses</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-list me-1"></i> Liste
            </a>
            <a href="{{ route('admin.expenses.export', request()->all()) }}" class="btn btn-success">
                <i class="fas fa-file-excel me-1"></i> Exporter
            </a>
        </div>
    </div>

    <!-- Filtres période -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('admin.expenses.dashboard') }}" method="GET" class="row g-3 align-items-end">
                @if($shops->isNotEmpty())
                    <div class="col-md-3">
                        <label class="form-label">Boutique</label>
                        <select name="shop_id" class="form-select">
                            <option value="">Toutes les boutiques</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" {{ $shopId == $shop->id ? 'selected' : '' }}>
                                    {{ $shop->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label">Date début</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Appliquer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistiques principales -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Dépenses</h6>
                            <h2 class="mb-0">{{ number_format($totalExpenses, 0, ',', ' ') }} F</h2>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Mois précédent</h6>
                            <h2 class="mb-0">{{ number_format($previousMonthTotal, 0, ',', ' ') }} F</h2>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-history"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm {{ $monthVariation >= 0 ? 'bg-warning' : 'bg-success' }} text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Variation</h6>
                            <h2 class="mb-0">
                                {{ $monthVariation >= 0 ? '+' : '' }}{{ number_format($monthVariation, 1) }}%
                            </h2>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-{{ $monthVariation >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Dépenses par catégorie -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Répartition par catégorie</h5>
                </div>
                <div class="card-body">
                    @if($expensesByCategory->isEmpty())
                        <p class="text-muted text-center">Aucune donnée</p>
                    @else
                        <canvas id="categoryChart" height="250"></canvas>
                        <div class="mt-3">
                            @foreach($expensesByCategory as $cat)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <span class="badge me-2" style="background-color: {{ $cat['color'] }}">
                                            <i class="fas {{ $cat['icon'] }}"></i>
                                        </span>
                                        {{ $cat['name'] }}
                                    </div>
                                    <span class="fw-bold">{{ number_format($cat['total'], 0, ',', ' ') }} F</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Suivi des budgets -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Suivi des budgets mensuels</h5>
                </div>
                <div class="card-body">
                    @if($categoriesWithBudget->isEmpty())
                        <p class="text-muted text-center">Aucun budget défini</p>
                    @else
                        @foreach($categoriesWithBudget as $cat)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ $cat['name'] }}</span>
                                    <span class="{{ $cat['exceeded'] ? 'text-danger fw-bold' : '' }}">
                                        {{ number_format($cat['spent'], 0, ',', ' ') }} / {{ number_format($cat['budget'], 0, ',', ' ') }} F
                                    </span>
                                </div>
                                <div class="progress" style="height: 20px">
                                    @php
                                        $color = $cat['exceeded'] ? 'danger' : ($cat['percentage'] >= 80 ? 'warning' : 'success');
                                    @endphp
                                    <div class="progress-bar bg-{{ $color }}" 
                                         style="width: {{ min(100, $cat['percentage']) }}%">
                                        {{ number_format($cat['percentage'], 0) }}%
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Évolution journalière -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Évolution des dépenses</h5>
                </div>
                <div class="card-body">
                    @if($dailyExpenses->isEmpty())
                        <p class="text-muted text-center">Aucune donnée</p>
                    @else
                        <canvas id="dailyChart" height="120"></canvas>
                    @endif
                </div>
            </div>
        </div>

        <!-- Mode de paiement -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Par mode de paiement</h5>
                </div>
                <div class="card-body">
                    @if($expensesByPaymentMethod->isEmpty())
                        <p class="text-muted text-center">Aucune donnée</p>
                    @else
                        @foreach($expensesByPaymentMethod as $method)
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                                <div>
                                    @switch($method->payment_method)
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
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">{{ number_format($method->total, 0, ',', ' ') }} F</div>
                                    <small class="text-muted">{{ $method->count }} opérations</small>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Top 10 des dépenses -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-sort-amount-down me-2"></i>Top 10 des plus grosses dépenses</h5>
        </div>
        <div class="card-body p-0">
            @if($topExpenses->isEmpty())
                <p class="text-muted text-center py-4">Aucune dépense</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Catégorie</th>
                                <th>Description</th>
                                <th>Par</th>
                                <th class="text-end">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topExpenses as $index => $expense)
                                <tr>
                                    <td><span class="badge bg-secondary">{{ $index + 1 }}</span></td>
                                    <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $expense->category->color ?? '#6c757d' }}">
                                            {{ $expense->category->name ?? '-' }}
                                        </span>
                                    </td>
                                    <td>{{ Str::limit($expense->description, 40) }}</td>
                                    <td><small>{{ $expense->user->name ?? '-' }}</small></td>
                                    <td class="text-end fw-bold text-danger">
                                        {{ number_format($expense->amount, 0, ',', ' ') }} F
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique par catégorie
    @if($expensesByCategory->isNotEmpty())
        const categoryCtx = document.getElementById('categoryChart');
        if (categoryCtx) {
            new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($expensesByCategory->pluck('name')) !!},
                    datasets: [{
                        data: {!! json_encode($expensesByCategory->pluck('total')) !!},
                        backgroundColor: {!! json_encode($expensesByCategory->pluck('color')) !!},
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    @endif

    // Graphique évolution journalière
    @if($dailyExpenses->isNotEmpty())
        const dailyCtx = document.getElementById('dailyChart');
        if (dailyCtx) {
            new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($dailyExpenses->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))) !!},
                    datasets: [{
                        label: 'Dépenses',
                        data: {!! json_encode($dailyExpenses->pluck('total')) !!},
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('fr-FR').format(value) + ' F';
                                }
                            }
                        }
                    }
                }
            });
        }
    @endif
});
</script>
@endpush
