@extends('layouts.app')

@section('title', 'Ventes en attente - Réparateurs')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clock-history"></i> Ventes en attente - Réparateurs</h2>
    <a href="{{ route('cashier.pending-sales.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Ajouter des articles
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Réparateur</label>
                <select name="reseller_id" class="form-select">
                    <option value="">Tous les réparateurs</option>
                    @foreach($resellers as $reseller)
                        <option value="{{ $reseller->id }}" {{ request('reseller_id') == $reseller->id ? 'selected' : '' }}>
                            {{ $reseller->company_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="{{ request('date', date('Y-m-d')) }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary w-100">
                    <i class="bi bi-search"></i> Filtrer
                </button>
            </div>
        </form>
    </div>
</div>

@if($pendingSales->isEmpty())
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Aucune vente en attente pour cette date.
    </div>
@else
    <div class="row">
        @foreach($pendingSales as $pendingSale)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>{{ $pendingSale->reseller->company_name }}</strong>
                            <span class="badge bg-dark">{{ $pendingSale->items->count() }} article(s)</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <i class="bi bi-calendar"></i> {{ $pendingSale->sale_date->format('d/m/Y') }}
                        </p>
                        <p class="mb-2">
                            <i class="bi bi-person"></i> {{ $pendingSale->user->name }}
                        </p>
                        
                        <!-- Liste des articles -->
                        <div class="small mb-3" style="max-height: 150px; overflow-y: auto;">
                            <table class="table table-sm table-borderless mb-0">
                                @foreach($pendingSale->items as $item)
                                    <tr>
                                        <td>{{ $item->product->name }}</td>
                                        <td class="text-end">x{{ $item->quantity }}</td>
                                        <td class="text-end">{{ number_format($item->total_price, 0, ',', ' ') }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                        
                        <div class="border-top pt-2">
                            <h5 class="text-end mb-0">
                                Total: <strong>{{ number_format($pendingSale->total_amount, 0, ',', ' ') }} FCFA</strong>
                            </h5>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group w-100">
                            <a href="{{ route('cashier.pending-sales.create', ['reseller_id' => $pendingSale->reseller_id]) }}" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-plus"></i> Ajouter
                            </a>
                            <a href="{{ route('cashier.pending-sales.show', $pendingSale) }}" 
                               class="btn btn-outline-success btn-sm">
                                <i class="bi bi-check-lg"></i> Valider
                            </a>
                            <form action="{{ route('cashier.pending-sales.cancel', $pendingSale) }}" method="POST" 
                                  onsubmit="return confirm('Annuler cette vente en attente ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
