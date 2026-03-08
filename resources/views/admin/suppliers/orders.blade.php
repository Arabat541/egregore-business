@extends('layouts.app')

@section('title', 'Factures Fournisseurs')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-file-earmark-text"></i> Factures Fournisseurs</h2>
    <a href="{{ route('admin.suppliers.orders.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nouvelle facture
    </a>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Fournisseur</label>
                <select name="supplier_id" class="form-select">
                    <option value="">Tous les fournisseurs</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->company_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select name="status" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                    <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Envoyée</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmée</option>
                    <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Réceptionnée</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulée</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-search"></i> Filtrer
                </button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('admin.suppliers.orders') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-lg"></i> Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Statistiques rapides -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $orders->where('status', 'draft')->count() }}</h3>
                <small>Brouillons</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $orders->where('status', 'sent')->count() }}</h3>
                <small>En attente</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $orders->where('status', 'received')->count() }}</h3>
                <small>Réceptionnées</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ number_format($orders->where('status', 'received')->sum('total_amount'), 0, ',', ' ') }}</h3>
                <small>Total FCFA</small>
            </div>
        </div>
    </div>
</div>

<!-- Liste des factures -->
<div class="card">
    <div class="card-body">
        @if($orders->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-file-earmark-x display-1 text-muted"></i>
                <p class="mt-3 text-muted">Aucune facture fournisseur trouvée</p>
                <a href="{{ route('admin.suppliers.orders.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Créer une facture
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Date</th>
                            <th>Fournisseur</th>
                            <th>Articles</th>
                            <th class="text-end">Montant</th>
                            <th>Statut</th>
                            <th>Créé par</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.suppliers.orders.show', $order) }}" class="fw-bold text-decoration-none">
                                        {{ $order->reference }}
                                    </a>
                                </td>
                                <td>{{ $order->order_date->format('d/m/Y') }}</td>
                                <td>
                                    <a href="{{ route('admin.suppliers.show', $order->supplier) }}" class="text-decoration-none">
                                        {{ $order->supplier->company_name }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $order->items->count() }} article(s)</span>
                                </td>
                                <td class="text-end fw-bold">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</td>
                                <td>
                                    @switch($order->status)
                                        @case('draft')
                                            <span class="badge bg-warning">Brouillon</span>
                                            @break
                                        @case('sent')
                                            <span class="badge bg-info">Envoyée</span>
                                            @break
                                        @case('confirmed')
                                            <span class="badge bg-primary">Confirmée</span>
                                            @break
                                        @case('received')
                                            <span class="badge bg-success">Réceptionnée</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-danger">Annulée</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>{{ $order->user->name ?? '-' }}</td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.suppliers.orders.show', $order) }}" class="btn btn-outline-primary" title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($order->status !== 'received' && $order->status !== 'cancelled')
                                            <a href="{{ route('admin.suppliers.orders.show', $order) }}#receive" class="btn btn-outline-success" title="Réceptionner">
                                                <i class="bi bi-box-arrow-in-down"></i>
                                            </a>
                                        @endif
                                        @if($order->status === 'draft')
                                            <form action="{{ route('admin.suppliers.orders.destroy', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette facture ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
