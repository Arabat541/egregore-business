@extends('layouts.app')

@section('title', 'Transferts de stock')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-arrow-left-right"></i> Transferts de stock inter-boutiques</h2>
    <a href="{{ route('admin.stock-transfers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nouveau transfert
    </a>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('admin.stock-transfers.index') }}" method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Boutique source</label>
                <select name="from_shop_id" class="form-select">
                    <option value="">Toutes</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ request('from_shop_id') == $shop->id ? 'selected' : '' }}>
                            {{ $shop->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Boutique destination</label>
                <select name="to_shop_id" class="form-select">
                    <option value="">Toutes</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ request('to_shop_id') == $shop->id ? 'selected' : '' }}>
                            {{ $shop->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Statut</label>
                <select name="status" class="form-select">
                    <option value="">Tous</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Validé</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Du</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Au</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> Filtrer
                </button>
                <a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Liste des transferts -->
<div class="card">
    <div class="card-body">
        @if($transfers->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox display-1"></i>
                <p class="mt-3">Aucun transfert trouvé</p>
                <a href="{{ route('admin.stock-transfers.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Créer un transfert
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Date</th>
                            <th>De</th>
                            <th></th>
                            <th>Vers</th>
                            <th class="text-center">Articles</th>
                            <th class="text-end">Valeur</th>
                            <th>Statut</th>
                            <th>Créé par</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transfers as $transfer)
                            <tr>
                                <td><strong>{{ $transfer->reference }}</strong></td>
                                <td>{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <i class="bi bi-shop text-primary"></i>
                                    {{ $transfer->fromShop->name }}
                                </td>
                                <td class="text-center">
                                    <i class="bi bi-arrow-right text-muted"></i>
                                </td>
                                <td>
                                    <i class="bi bi-shop text-success"></i>
                                    {{ $transfer->toShop->name }}
                                </td>
                                <td class="text-center">{{ $transfer->total_items }}</td>
                                <td class="text-end">{{ number_format($transfer->total_value, 0, ',', ' ') }} FCFA</td>
                                <td>
                                    <span class="badge bg-{{ $transfer->status_color }}">
                                        {{ $transfer->status_label }}
                                    </span>
                                </td>
                                <td>{{ $transfer->user->name ?? '-' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.stock-transfers.show', $transfer) }}" 
                                       class="btn btn-sm btn-outline-primary" title="Voir">
                                        <i class="bi bi-eye"></i>
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
@endsection
