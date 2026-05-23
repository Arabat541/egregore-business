@extends('layouts.app')

@section('title', 'Historique stock - ' . $product->name)

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-clock-history"></i> Historique du produit</h2>
        <p class="text-muted mb-0">{{ $product->name }} - Stock actuel: <strong>{{ $product->quantity_in_stock }}</strong></p>
    </div>
    <a href="{{ route('admin.stock-movements.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour au journal
    </a>
</div>

<!-- Infos produit -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted">Code-barres</small>
                <p class="mb-0"><strong>{{ $product->sku ?? '-' }}</strong></p>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Catégorie</small>
                <p class="mb-0"><strong>{{ $product->category->name ?? '-' }}</strong></p>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Prix d'achat</small>
                <p class="mb-0"><strong>{{ number_format($product->purchase_price, 0, ',', ' ') }} FCFA</strong></p>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Prix normal</small>
                <p class="mb-0"><strong>{{ number_format($product->normal_price, 0, ',', ' ') }} FCFA</strong></p>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.stock-movements.product-history', $product) }}">
            <div class="row g-3">
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <label class="form-label">Du</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Au</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filtrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Historique -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-list"></i> Historique des mouvements ({{ $movements->total() }} au total)
    </div>
    <div class="card-body">
        @if($movements->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Date/Heure</th>
                            <th>Boutique</th>
                            <th>Type</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-center">Avant</th>
                            <th class="text-center">Après</th>
                            <th>Référence</th>
                            <th>Par</th>
                            <th>Raison</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $movement)
                            <tr>
                                <td>
                                    {{ $movement->created_at->format('d/m/Y') }}<br>
                                    <small class="text-muted">{{ $movement->created_at->format('H:i:s') }}</small>
                                </td>
                                <td>{{ $movement->shop->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $movement->type_color }}">
                                        {{ $movement->type_label }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold {{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $movement->quantity_before }}</td>
                                <td class="text-center"><strong>{{ $movement->quantity_after }}</strong></td>
                                <td><small>{{ $movement->reference ?? '-' }}</small></td>
                                <td>{{ $movement->user->name ?? '-' }}</td>
                                <td><small>{{ Str::limit($movement->reason, 30) ?? '-' }}</small></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-4">
                {{ $movements->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h4 class="mt-3 text-muted">Aucun mouvement pour ce produit</h4>
            </div>
        @endif
    </div>
</div>
@endsection
