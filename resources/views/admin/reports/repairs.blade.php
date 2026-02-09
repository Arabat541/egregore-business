@extends('layouts.app')

@section('title', 'Rapport des Réparations')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-tools me-2"></i>Rapport des Réparations
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Rapports</a></li>
                    <li class="breadcrumb-item active">Réparations</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.reports.export', ['type' => 'repairs', 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
               class="btn btn-outline-success">
                <i class="bi bi-download me-2"></i>Exporter CSV
            </a>
            <button class="btn btn-outline-primary" onclick="window.print()">
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
                        <option value="">Toutes</option>
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
                    <a href="{{ route('admin.reports.repairs') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
                <div class="col-md-3 text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="?start_date={{ now()->startOfDay()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}" class="btn btn-outline-warning">Aujourd'hui</a>
                        <a href="?start_date={{ now()->startOfWeek()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}" class="btn btn-outline-warning">Semaine</a>
                        <a href="?start_date={{ now()->startOfMonth()->format('Y-m-d') }}&end_date={{ now()->format('Y-m-d') }}" class="btn btn-outline-warning">Mois</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs Principaux -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total Réparations</h6>
                            <h3 class="mb-0 text-warning">{{ $totalRepairs }}</h3>
                            <small class="text-muted">appareils reçus</small>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle align-self-start">
                            <i class="bi bi-phone fs-4 text-warning"></i>
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
                            <h6 class="text-muted mb-2">Revenus Réparations</h6>
                            <h3 class="mb-0 text-success">{{ number_format($totalRevenue, 0, ',', ' ') }} F</h3>
                            <small class="text-muted">CA généré</small>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle align-self-start">
                            <i class="bi bi-cash-coin fs-4 text-success"></i>
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
                            <h6 class="text-muted mb-2">Taux de Réussite</h6>
                            <h3 class="mb-0 text-info">{{ $successRate }}%</h3>
                            <small class="text-muted">{{ $deliveredCount }} livrées sur {{ $totalRepairs }}</small>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle align-self-start">
                            <i class="bi bi-check-circle fs-4 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Temps Moyen</h6>
                            <h3 class="mb-0 text-primary">{{ $averageRepairTime ? round($averageRepairTime) : 'N/A' }}</h3>
                            <small class="text-muted">heures de réparation</small>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle align-self-start">
                            <i class="bi bi-clock-history fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="row g-4 mb-4">
        <!-- Évolution des réparations -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Évolution des Réparations
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 300px; position: relative;">
                        <canvas id="repairsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Répartition par statut -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-pie-chart me-2"></i>Par Statut
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 200px; position: relative;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="mt-3">
                        @php
                            $statusLabels = [
                                'pending' => ['Réception', 'secondary'],
                                'diagnosing' => ['Diagnostic', 'info'],
                                'diagnosed' => ['Diagnostiqué', 'primary'],
                                'repairing' => ['En réparation', 'warning'],
                                'repaired' => ['Réparé', 'success'],
                                'delivered' => ['Livré', 'dark'],
                                'cancelled' => ['Annulé', 'danger'],
                            ];
                        @endphp
                        @foreach($repairsByStatus as $status)
                            <div class="d-flex justify-content-between mb-2">
                                <span>
                                    <span class="badge bg-{{ $statusLabels[$status->status][1] ?? 'secondary' }} me-2">
                                        {{ $status->count }}
                                    </span>
                                    {{ $statusLabels[$status->status][0] ?? $status->status }}
                                </span>
                                <small class="text-muted">{{ $totalRepairs > 0 ? round(($status->count / $totalRepairs) * 100, 1) : 0 }}%</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Réparations par type d'appareil -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-phone me-2"></i>Par Type d'Appareil
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 200px; position: relative;">
                        <canvas id="deviceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Réparations par marque -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-tag me-2"></i>Top 10 Marques
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 200px; position: relative;">
                        <canvas id="brandChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Performance des techniciens -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-person-gear me-2"></i>Performance des Techniciens
                    </h5>
                    <span class="badge bg-primary">KPI</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Technicien</th>
                                    <th class="text-center">Assignées</th>
                                    <th class="text-center">Terminées</th>
                                    <th class="text-center">Taux</th>
                                    <th class="text-end">Temps Moy.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($technicianPerformance as $tech)
                                    @php
                                        $rate = $tech->total_repairs > 0 ? round(($tech->completed / $tech->total_repairs) * 100, 1) : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <i class="bi bi-person-circle me-2 text-primary"></i>
                                            {{ $tech->technician->name ?? 'N/A' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ $tech->total_repairs }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success">{{ $tech->completed }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-{{ $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger') }}" 
                                                     style="width: {{ $rate }}%">
                                                    {{ $rate }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            @if($tech->avg_repair_hours)
                                                <strong>{{ round($tech->avg_repair_hours) }}h</strong>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Aucune donnée de performance</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Problèmes les plus fréquents -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>Problèmes Fréquents
                    </h5>
                    <span class="badge bg-danger">Insight</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Problème Signalé</th>
                                    <th class="text-center">Occurrences</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($commonIssues as $index => $issue)
                                    <tr>
                                        <td>
                                            @if($index < 3)
                                                <span class="badge bg-danger">{{ $index + 1 }}</span>
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </td>
                                        <td>{{ Str::limit($issue->reported_issue, 40) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark">{{ $issue->count }}</span>
                                        </td>
                                        <td class="text-end">
                                            {{ $totalRepairs > 0 ? round(($issue->count / $totalRepairs) * 100, 1) : 0 }}%
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Aucune donnée</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerte réparations en attente -->
    @php
        $pendingRepairs = \App\Models\Repair::whereIn('status', ['pending_payment', 'paid_pending_diagnosis', 'in_diagnosis', 'waiting_parts', 'in_repair'])->count();
        $oldRepairs = \App\Models\Repair::whereIn('status', ['pending_payment', 'paid_pending_diagnosis', 'in_diagnosis', 'waiting_parts', 'in_repair'])
            ->where('created_at', '<', now()->subDays(7))
            ->count();
    @endphp
    @if($pendingRepairs > 0 || $oldRepairs > 0)
        <div class="row g-4">
            <div class="col-12">
                <div class="alert alert-warning border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle fs-3 me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Attention requise</h6>
                            <p class="mb-0">
                                <strong>{{ $pendingRepairs }}</strong> réparation(s) en cours
                                @if($oldRepairs > 0)
                                    dont <strong class="text-danger">{{ $oldRepairs }}</strong> depuis plus de 7 jours
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('cashier.repairs.index') }}" class="btn btn-warning ms-auto">
                            <i class="bi bi-arrow-right me-2"></i>Voir les réparations
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
    // Données
    const repairsData = @json($repairsByDay);
    const statusData = @json($repairsByStatus);
    const deviceData = @json($repairsByDevice);
    const brandData = @json($repairsByBrand);

    // Graphique évolution
    new Chart(document.getElementById('repairsChart'), {
        type: 'bar',
        data: {
            labels: repairsData.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
            }),
            datasets: [{
                label: 'Nombre de réparations',
                data: repairsData.map(d => d.count),
                backgroundColor: 'rgba(255, 193, 7, 0.7)',
                borderColor: 'rgb(255, 193, 7)',
                borderWidth: 1
            }, {
                label: 'Revenus (FCFA)',
                data: repairsData.map(d => d.total),
                type: 'line',
                borderColor: 'rgb(25, 135, 84)',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.3,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, position: 'left' },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { callback: value => value.toLocaleString('fr-FR') + ' F' }
                }
            }
        }
    });

    // Graphique statuts
    const statusColors = {
        'pending': '#6c757d',
        'diagnosing': '#0dcaf0',
        'diagnosed': '#0d6efd',
        'repairing': '#ffc107',
        'repaired': '#198754',
        'delivered': '#212529',
        'cancelled': '#dc3545'
    };
    
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusData.map(d => {
                const labels = {
                    'pending': 'Réception',
                    'diagnosing': 'Diagnostic',
                    'diagnosed': 'Diagnostiqué',
                    'repairing': 'En réparation',
                    'repaired': 'Réparé',
                    'delivered': 'Livré',
                    'cancelled': 'Annulé'
                };
                return labels[d.status] || d.status;
            }),
            datasets: [{
                data: statusData.map(d => d.count),
                backgroundColor: statusData.map(d => statusColors[d.status] || '#6c757d')
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    // Graphique types d'appareils
    new Chart(document.getElementById('deviceChart'), {
        type: 'bar',
        data: {
            labels: deviceData.map(d => d.device_type || 'Non spécifié'),
            datasets: [{
                label: 'Nombre',
                data: deviceData.map(d => d.count),
                backgroundColor: 'rgba(13, 110, 253, 0.7)'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    // Graphique marques
    new Chart(document.getElementById('brandChart'), {
        type: 'bar',
        data: {
            labels: brandData.map(d => d.device_brand),
            datasets: [{
                label: 'Nombre',
                data: brandData.map(d => d.count),
                backgroundColor: 'rgba(255, 193, 7, 0.7)'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
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
