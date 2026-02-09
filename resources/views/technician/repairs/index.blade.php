@extends('layouts.app')

@section('title', 'Mes réparations')

@section('sidebar')
    @include('technician.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-tools"></i> Mes réparations</h2>
</div>

<!-- Filtres par statut -->
<div class="mb-4">
    <div class="btn-group">
        <a href="{{ route('technician.repairs.index') }}" class="btn btn-outline-secondary {{ !request('status') ? 'active' : '' }}">
            Toutes ({{ $repairs->count() }})
        </a>
        <a href="{{ route('technician.repairs.index', ['status' => 'pending']) }}" class="btn btn-outline-warning {{ request('status') === 'pending' ? 'active' : '' }}">
            En attente
        </a>
        <a href="{{ route('technician.repairs.index', ['status' => 'in_diagnosis']) }}" class="btn btn-outline-info {{ request('status') === 'in_diagnosis' ? 'active' : '' }}">
            Diagnostic
        </a>
        <a href="{{ route('technician.repairs.index', ['status' => 'waiting_parts']) }}" class="btn btn-outline-secondary {{ request('status') === 'waiting_parts' ? 'active' : '' }}">
            Attente pièces
        </a>
        <a href="{{ route('technician.repairs.index', ['status' => 'in_repair']) }}" class="btn btn-outline-primary {{ request('status') === 'in_repair' ? 'active' : '' }}">
            En cours
        </a>
        <a href="{{ route('technician.repairs.index', ['status' => 'repaired']) }}" class="btn btn-outline-success {{ request('status') === 'repaired' ? 'active' : '' }}">
            Terminées
        </a>
    </div>
</div>

<div class="row">
    @forelse($repairs as $repair)
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <code>{{ $repair->repair_number }}</code>
                <span class="badge bg-{{ $repair->status_color }}">{{ $repair->status_label }}</span>
            </div>
            <div class="card-body">
                <h5 class="card-title">{{ $repair->device_brand }} {{ $repair->device_model }}</h5>
                <p class="card-text text-muted">
                    <small>
                        <i class="bi bi-person"></i> {{ $repair->customer->full_name }}<br>
                        <i class="bi bi-telephone"></i> {{ $repair->customer->phone }}
                    </small>
                </p>
                <p class="card-text">
                    <strong>Problème:</strong><br>
                    {{ Str::limit($repair->reported_issue, 100) }}
                </p>
                @if($repair->device_password)
                <p class="card-text">
                    <small class="text-muted"><i class="bi bi-key"></i> Code: {{ $repair->device_password }}</small>
                </p>
                @endif
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        {{ $repair->created_at->format('d/m/Y') }}
                    </small>
                    <a href="{{ route('technician.repairs.show', $repair) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-arrow-right"></i> Gérer
                    </a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> Aucune réparation assignée
        </div>
    </div>
    @endforelse
</div>

{{ $repairs->appends(request()->query())->links() }}
@endsection
