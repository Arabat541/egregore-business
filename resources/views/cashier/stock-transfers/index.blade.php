@extends('layouts.app')

@section('title', 'Transferts entrants')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="bi bi-arrow-left-right text-primary me-2"></i>Transferts de stock
        @if($pendingCount > 0)
            <span class="badge bg-danger fs-6 ms-1">{{ $pendingCount }} à confirmer</span>
        @endif
    </h2>
    <a href="{{ route('cashier.stock-transfers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Initier un transfert
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('cashier.stock-transfers.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select name="status" class="form-select">
                    <option value="">En transit + Reçus</option>
                    <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>En transit (à confirmer)</option>
                    <option value="received"   {{ request('status') == 'received'   ? 'selected' : '' }}>Reçus</option>
                    <option value="completed"  {{ request('status') == 'completed'  ? 'selected' : '' }}>Validés (ancien)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Du</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Au</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> Filtrer
                </button>
                <a href="{{ route('cashier.stock-transfers.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($transfers->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox display-1"></i>
                <p class="mt-3">Aucun transfert entrant trouvé</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Date</th>
                            <th>En provenance de</th>
                            <th class="text-center">Articles</th>
                            <th class="text-end">Valeur</th>
                            <th>Statut</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transfers as $transfer)
                            <tr class="{{ $transfer->status === 'in_transit' ? 'table-warning' : '' }}">
                                <td><strong>{{ $transfer->reference }}</strong></td>
                                <td>{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <i class="bi bi-shop text-primary"></i>
                                    {{ $transfer->fromShop->name }}
                                </td>
                                <td class="text-center">{{ $transfer->total_items }}</td>
                                <td class="text-end">{{ number_format($transfer->total_value, 0, ',', ' ') }} FCFA</td>
                                <td>
                                    <span class="badge bg-{{ $transfer->status_color }}">
                                        {{ $transfer->status_label }}
                                    </span>
                                    @if($transfer->has_discrepancy)
                                        <span class="badge bg-warning text-dark ms-1">
                                            <i class="bi bi-exclamation-triangle"></i> Écart
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('cashier.stock-transfers.show', $transfer) }}"
                                       class="btn btn-sm {{ $transfer->status === 'in_transit' ? 'btn-success' : 'btn-outline-primary' }}">
                                        @if($transfer->status === 'in_transit')
                                            <i class="bi bi-box-arrow-in-down"></i> Confirmer
                                        @else
                                            <i class="bi bi-eye"></i> Voir
                                        @endif
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>
</div>

{{-- ===== TRANSFERTS SORTANTS (initiés par cette boutique) ===== --}}
<h4 class="mt-5 mb-3">
    <i class="bi bi-box-arrow-right text-warning me-2"></i>Mes demandes sortantes
</h4>
<div class="card">
    <div class="card-body">
        @if($outgoingTransfers->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox display-4"></i>
                <p class="mt-2">Aucune demande sortante.</p>
                <a href="{{ route('cashier.stock-transfers.create') }}" class="btn btn-primary mt-1">
                    <i class="bi bi-plus-circle me-1"></i> Initier un transfert
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Date</th>
                            <th>Vers</th>
                            <th class="text-center">Articles</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($outgoingTransfers as $out)
                            <tr>
                                <td><strong>{{ $out->reference }}</strong></td>
                                <td>{{ $out->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <i class="bi bi-shop text-success"></i>
                                    {{ $out->toShop->name }}
                                </td>
                                <td class="text-center">{{ $out->total_items }}</td>
                                <td>
                                    <span class="badge bg-{{ $out->status_color }}">
                                        {{ $out->status_label }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
