@extends('layouts.app')

@section('title', 'Inventaires')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clipboard-check"></i> Inventaires</h2>
    <a href="{{ route('admin.inventory.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nouvel inventaire
    </a>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
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
            <div class="col-md-2">
                <label class="form-label">Statut</label>
                <select name="status" class="form-select">
                    <option value="">Tous</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>En cours</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Terminé</option>
                    <option value="validated" {{ request('status') == 'validated' ? 'selected' : '' }}>Validé</option>
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
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary me-2">
                    <i class="bi bi-search"></i> Filtrer
                </button>
                <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Liste des inventaires -->
<div class="card">
    <div class="card-body">
        @if($inventories->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-clipboard-x display-1 text-muted"></i>
                <p class="mt-3 text-muted">Aucun inventaire trouvé</p>
                <a href="{{ route('admin.inventory.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Créer un inventaire
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Boutique</th>
                            <th>Créé par</th>
                            <th>Date</th>
                            <th>Produits</th>
                            <th>Écarts</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inventories as $inventory)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.inventory.show', $inventory) }}" class="fw-bold text-decoration-none">
                                        {{ $inventory->reference }}
                                    </a>
                                </td>
                                <td>{{ $inventory->shop->name }}</td>
                                <td>{{ $inventory->user->name }}</td>
                                <td>
                                    {{ $inventory->created_at->format('d/m/Y H:i') }}
                                    @if($inventory->validated_at)
                                        <small class="d-block text-success">
                                            Validé le {{ $inventory->validated_at->format('d/m/Y') }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $inventory->total_products }}</span>
                                </td>
                                <td>
                                    @if($inventory->status != 'in_progress')
                                        @if($inventory->products_with_difference > 0)
                                            <span class="badge bg-warning">{{ $inventory->products_with_difference }} écarts</span>
                                            <small class="d-block {{ $inventory->total_difference_value < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format($inventory->total_difference_value, 0, ',', ' ') }} FCFA
                                            </small>
                                        @else
                                            <span class="badge bg-success">Aucun écart</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $inventory->status_color }}">
                                        {{ $inventory->status_label }}
                                    </span>
                                    @if($inventory->status == 'in_progress')
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div class="progress-bar" style="width: {{ $inventory->progress }}%"></div>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.inventory.show', $inventory) }}" class="btn btn-outline-primary" title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($inventory->status != 'in_progress')
                                            <a href="{{ route('admin.inventory.report', $inventory) }}" class="btn btn-outline-info" title="Rapport">
                                                <i class="bi bi-file-text"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{ $inventories->links() }}
        @endif
    </div>
</div>
@endsection
