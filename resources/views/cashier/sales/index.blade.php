@extends('layouts.app')

@section('title', 'Historique des ventes')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-receipt"></i> Historique des ventes</h2>
    <a href="{{ route('cashier.sales.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nouvelle vente
    </a>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('cashier.sales.index') }}" method="GET" class="row g-3">
            <div class="col-md-2">
                <input type="date" class="form-control" name="date" value="{{ request('date') }}" placeholder="Date">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="payment_status">
                    <option value="">Tous statuts</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Payé</option>
                    <option value="credit" {{ request('payment_status') === 'credit' ? 'selected' : '' }}>Crédit</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="client_type">
                    <option value="">Tous types</option>
                    <option value="walk-in" {{ request('client_type') === 'walk-in' ? 'selected' : '' }}>Comptoir</option>
                    <option value="customer" {{ request('client_type') === 'customer' ? 'selected' : '' }}>Client</option>
                    <option value="reseller" {{ request('client_type') === 'reseller' ? 'selected' : '' }}>Revendeur</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="N° facture ou client">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('cashier.sales.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Articles</th>
                        <th>Total</th>
                        <th>Payé</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td><code>{{ $sale->invoice_number }}</code></td>
                        <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            {{ $sale->client_name }}
                            @if($sale->client_type === 'reseller')
                                <span class="badge bg-info">Revendeur</span>
                            @endif
                        </td>
                        <td>{{ $sale->items->count() }} articles</td>
                        <td class="fw-bold">{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</td>
                        <td>{{ number_format($sale->amount_paid, 0, ',', ' ') }} FCFA</td>
                        <td>
                            @if($sale->payment_status === 'paid')
                                <span class="badge bg-success">Payé</span>
                            @elseif($sale->payment_status === 'credit')
                                <span class="badge bg-warning">Crédit</span>
                                @if($sale->remaining_amount > 0)
                                    <br><small class="text-danger">Reste: {{ number_format($sale->remaining_amount, 0, ',', ' ') }}</small>
                                @endif
                            @else
                                <span class="badge bg-danger">Annulé</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('cashier.sales.show', $sale) }}" class="btn btn-sm btn-outline-info" title="Détail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('cashier.sales.receipt', $sale) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Ticket">
                                <i class="bi bi-printer"></i>
                            </a>
                            @if($sale->payment_status !== 'cancelled' && $sale->created_at->isToday())
                                <form action="{{ route('cashier.sales.cancel', $sale) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Annuler"
                                            onclick="return confirm('Annuler cette vente ?')">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">Aucune vente trouvée</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $sales->appends(request()->query())->links() }}
    </div>
</div>
@endsection
