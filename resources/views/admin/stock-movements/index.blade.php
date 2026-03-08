@extends('layouts.app')

@section('title', 'Journal des mouvements de stock')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-journal-text"></i> Journal des mouvements de stock</h2>
    <div>
        <a href="{{ route('admin.stock-movements.adjustment') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Ajustement manuel
        </a>
        <a href="{{ route('admin.stock-movements.export', request()->all()) }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel"></i> Exporter CSV
        </a>
    </div>
</div>

<!-- Statistiques période -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body text-center">
                <h4 class="text-success">+{{ number_format($stats['total_in'], 0, ',', ' ') }}</h4>
                <small class="text-muted">Entrées</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h4 class="text-danger">{{ number_format($stats['total_out'], 0, ',', ' ') }}</h4>
                <small class="text-muted">Sorties</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>{{ number_format($stats['total_movements'], 0, ',', ' ') }}</h4>
                <small class="text-muted">Mouvements</small>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('admin.stock-movements.index') }}" method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Boutique</label>
                <select name="shop_id" class="form-select">
                    <option value="">Toutes</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                            {{ $shop->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="">Tous</option>
                    @foreach($movementTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
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
            <div class="col-md-2">
                <label class="form-label">Recherche</label>
                <input type="text" name="search" class="form-control" placeholder="Produit, code-barres..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> Filtrer
                </button>
                <a href="{{ route('admin.stock-movements.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Liste des mouvements -->
<div class="card">
    <div class="card-body">
        @if($movements->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox display-1"></i>
                <p class="mt-3">Aucun mouvement de stock trouvé</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date/Heure</th>
                            <th>Boutique</th>
                            <th>Produit</th>
                            <th>Type</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-center">Avant</th>
                            <th class="text-center">Après</th>
                            <th>Référence</th>
                            <th>Utilisateur</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $movement)
                            <tr>
                                <td>
                                    <small>{{ $movement->created_at->format('d/m/Y') }}</small><br>
                                    <small class="text-muted">{{ $movement->created_at->format('H:i') }}</small>
                                </td>
                                <td>{{ $movement->shop->name ?? '-' }}</td>
                                <td>
                                    <strong>{{ $movement->product->name ?? '-' }}</strong>
                                    @if($movement->product?->sku)
                                        <br><small class="text-muted">{{ $movement->product->sku }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $movement->type_color }}">
                                        {{ $movement->type_label }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="{{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $movement->quantity_before ?? '-' }}</td>
                                <td class="text-center">{{ $movement->quantity_after ?? '-' }}</td>
                                <td>
                                    <small>{{ $movement->reference ?? '-' }}</small>
                                    @if($movement->reason)
                                        <br><small class="text-muted">{{ Str::limit($movement->reason, 30) }}</small>
                                    @endif
                                </td>
                                <td>{{ $movement->user->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
