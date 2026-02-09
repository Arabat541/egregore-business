@extends('layouts.app')

@section('title', 'Tableau de bord Technicien')

@section('sidebar')
    <a href="{{ route('technician.dashboard') }}" class="nav-link {{ request()->routeIs('technician.dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i> Tableau de bord
    </a>
    <a href="{{ route('technician.repairs.index') }}" class="nav-link {{ request()->routeIs('technician.repairs.*') ? 'active' : '' }}">
        <i class="bi bi-tools"></i> Mes réparations
    </a>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-speedometer2"></i> Tableau de bord Technicien</h2>
</div>

<!-- Statistiques -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $stats['assigned_count'] }}</h3>
                <small class="text-muted">Réparations assignées</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $stats['in_diagnosis'] }}</h3>
                <small class="text-muted">En diagnostic</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $stats['in_repair'] }}</h3>
                <small class="text-muted">En réparation</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $stats['repaired_today'] }}</h3>
                <small class="text-muted">Réparées aujourd'hui</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Réparations non assignées -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-inbox"></i> Réparations disponibles
            </div>
            <div class="card-body">
                @if($unassignedRepairs->count() > 0)
                    <div class="list-group">
                        @foreach($unassignedRepairs as $repair)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $repair->repair_number }}</strong><br>
                                <small class="text-muted">
                                    {{ $repair->device_full_name }}<br>
                                    {{ $repair->reported_issue }}
                                </small>
                            </div>
                            <form action="{{ route('technician.repairs.take-over', $repair) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="bi bi-hand-index"></i> Prendre
                                </button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center mb-0">Aucune réparation disponible</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Mes réparations en cours -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-tools"></i> Mes réparations en cours
            </div>
            <div class="card-body">
                @if($myRepairs->count() > 0)
                    <div class="list-group">
                        @foreach($myRepairs->take(10) as $repair)
                        <a href="{{ route('technician.repairs.show', $repair) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $repair->repair_number }}</strong>
                                    <span class="badge bg-{{ $repair->status_color }} ms-2">{{ $repair->status_label }}</span><br>
                                    <small class="text-muted">{{ $repair->device_full_name }}</small>
                                </div>
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    @if($myRepairs->count() > 10)
                        <div class="text-center mt-3">
                            <a href="{{ route('technician.repairs.index') }}" class="btn btn-outline-primary">
                                Voir toutes ({{ $myRepairs->count() }})
                            </a>
                        </div>
                    @endif
                @else
                    <p class="text-muted text-center mb-0">Aucune réparation assignée</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Réparations par statut -->
@if($repairsByStatus->count() > 0)
<div class="card mt-4">
    <div class="card-header">
        <i class="bi bi-list-check"></i> Réparations par statut
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($repairsByStatus as $status => $repairs)
            <div class="col-md-4 mb-3">
                <h6>
                    <span class="badge bg-{{ $repairs->first()->status_color }}">{{ $repairs->first()->status_label }}</span>
                    <span class="text-muted">({{ $repairs->count() }})</span>
                </h6>
                <ul class="list-unstyled">
                    @foreach($repairs->take(3) as $repair)
                    <li>
                        <a href="{{ route('technician.repairs.show', $repair) }}">
                            {{ $repair->repair_number }} - {{ $repair->device_brand }} {{ $repair->device_model }}
                        </a>
                    </li>
                    @endforeach
                    @if($repairs->count() > 3)
                    <li class="text-muted">+ {{ $repairs->count() - 3 }} autres...</li>
                    @endif
                </ul>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endsection
