@extends('layouts.app')

@section('title', 'Dashboard SAV')

@section('sidebar')
    @if(auth()->user()->hasRole('admin'))
        @include('admin.partials.sidebar')
    @else
        @include('cashier.partials.sidebar')
    @endif
@endsection

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard S.A.V
            </h1>
            <small class="text-muted">Vue d'ensemble du Service Après-Vente</small>
        </div>
        <div>
            <a href="{{ route('sav.index') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-list me-2"></i>Liste des tickets
            </a>
            <a href="{{ route('sav.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Nouveau Ticket
            </a>
        </div>
    </div>

    <!-- Statistiques principales -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-white-50 mb-1">Tickets ouverts</h6>
                            <h2 class="mb-0">{{ $stats['total_open'] }}</h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded p-2">
                            <i class="bi bi-folder2-open fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-white-50 mb-1">Urgents</h6>
                            <h2 class="mb-0">{{ $stats['urgent'] }}</h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded p-2">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="opacity-75 mb-1">Priorité haute</h6>
                            <h2 class="mb-0">{{ $stats['high'] }}</h2>
                        </div>
                        <div class="bg-dark bg-opacity-25 rounded p-2">
                            <i class="bi bi-arrow-up-circle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-white-50 mb-1">En attente client</h6>
                            <h2 class="mb-0">{{ $stats['waiting_customer'] }}</h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded p-2">
                            <i class="bi bi-hourglass-split fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Métriques de performance -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle text-success fs-1 mb-3"></i>
                    <h4>{{ $stats['resolved_this_month'] }}</h4>
                    <p class="text-muted mb-0">Tickets résolus ce mois</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-clock text-primary fs-1 mb-3"></i>
                    <h4>{{ $stats['avg_resolution_time'] ? round($stats['avg_resolution_time']) : '-' }}h</h4>
                    <p class="text-muted mb-0">Temps moyen de résolution</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-person-x text-warning fs-1 mb-3"></i>
                    <h4>{{ $unassigned }}</h4>
                    <p class="text-muted mb-0">Tickets non assignés</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Tickets par type -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Répartition par type</h5>
                </div>
                <div class="card-body">
                    @php
                        $typeColors = [
                            'return' => 'primary',
                            'exchange' => 'info',
                            'warranty' => 'success',
                            'complaint' => 'warning',
                            'refund' => 'danger',
                            'other' => 'secondary',
                        ];
                        $typeNames = [
                            'return' => 'Retour',
                            'exchange' => 'Échange',
                            'warranty' => 'Garantie',
                            'complaint' => 'Réclamation',
                            'refund' => 'Remboursement',
                            'other' => 'Autre',
                        ];
                        $total = $byType->sum('count') ?: 1;
                    @endphp
                    @forelse($byType as $type)
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="badge bg-{{ $typeColors[$type->type] ?? 'secondary' }}">
                                    {{ $typeNames[$type->type] ?? $type->type }}
                                </span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-2" style="width: 100px; height: 8px;">
                                    <div class="progress-bar bg-{{ $typeColors[$type->type] ?? 'secondary' }}" 
                                         style="width: {{ ($type->count / $total) * 100 }}%"></div>
                                </div>
                                <strong>{{ $type->count }}</strong>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center">Aucune donnée</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Tickets récents urgents/prioritaires -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Tickets à traiter en priorité</h5>
                    <a href="{{ route('sav.index') }}?status=open" class="btn btn-sm btn-outline-primary">
                        Voir tout
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ticket</th>
                                    <th>Type</th>
                                    <th>Client</th>
                                    <th>Priorité</th>
                                    <th>Depuis</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOpen as $ticket)
                                    <tr class="{{ $ticket->priority == 'urgent' ? 'table-danger' : ($ticket->priority == 'high' ? 'table-warning' : '') }}">
                                        <td>
                                            <a href="{{ route('sav.show', $ticket) }}" class="fw-bold text-decoration-none">
                                                {{ $ticket->ticket_number }}
                                            </a>
                                        </td>
                                        <td><span class="badge bg-secondary">{{ $ticket->type_name }}</span></td>
                                        <td>{{ $ticket->customer->full_name ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $ticket->priority_color }}">
                                                {{ $ticket->priority_name }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>{{ $ticket->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            @if(!$ticket->assignedUser)
                                                <span class="badge bg-secondary">Non assigné</span>
                                            @else
                                                <small>{{ $ticket->assignedUser->name }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>
                                            Aucun ticket en attente
                                        </td>
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
@endsection
