@extends('layouts.app')

@section('title', 'Détail mouvement de stock')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-arrow-left-right"></i> Détail du mouvement</h2>
        <p class="text-muted mb-0">{{ $stockMovement->created_at->format('d/m/Y H:i:s') }}</p>
    </div>
    <a href="{{ route('admin.stock-movements.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="row">
    <div class="col-md-6">
        <!-- Informations du mouvement -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Date/Heure</th>
                        <td>{{ $stockMovement->created_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    <tr>
                        <th>Boutique</th>
                        <td>{{ $stockMovement->shop->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Type</th>
                        <td><span class="badge bg-{{ $stockMovement->type_color }} fs-6">{{ $stockMovement->type_label }}</span></td>
                    </tr>
                    <tr>
                        <th>Quantité</th>
                        <td>
                            <span class="fs-4 {{ $stockMovement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                {{ $stockMovement->quantity > 0 ? '+' : '' }}{{ $stockMovement->quantity }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Stock avant</th>
                        <td>{{ $stockMovement->quantity_before }}</td>
                    </tr>
                    <tr>
                        <th>Stock après</th>
                        <td><strong>{{ $stockMovement->quantity_after }}</strong></td>
                    </tr>
                    <tr>
                        <th>Référence</th>
                        <td>{{ $stockMovement->reference ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Effectué par</th>
                        <td>{{ $stockMovement->user->name ?? '-' }}</td>
                    </tr>
                    @if($stockMovement->reason)
                    <tr>
                        <th>Raison/Notes</th>
                        <td>{{ $stockMovement->reason }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <!-- Informations du produit -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-box"></i> Produit concerné
            </div>
            <div class="card-body">
                @if($stockMovement->product)
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Nom</th>
                            <td><strong>{{ $stockMovement->product->name }}</strong></td>
                        </tr>
                        <tr>
                            <th>Code-barres</th>
                            <td>{{ $stockMovement->product->sku ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Catégorie</th>
                            <td>{{ $stockMovement->product->category->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Stock actuel</th>
                            <td>
                                <span class="badge bg-{{ $stockMovement->product->quantity_in_stock > 0 ? 'success' : 'danger' }} fs-6">
                                    {{ $stockMovement->product->quantity_in_stock }}
                                </span>
                            </td>
                        </tr>
                    </table>
                    <a href="{{ route('admin.stock-movements.product-history', $stockMovement->product) }}" class="btn btn-outline-primary w-100">
                        <i class="bi bi-clock-history"></i> Voir historique du produit
                    </a>
                @else
                    <p class="text-muted">Produit non disponible</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Contexte des mouvements -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-clock-history"></i> Contexte (mouvements proches)
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th class="text-center">Qté</th>
                        <th class="text-center">Stock</th>
                        <th>Référence</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($previousMovements->reverse() as $mov)
                        <tr class="text-muted">
                            <td>{{ $mov->created_at->format('d/m H:i') }}</td>
                            <td><span class="badge bg-{{ $mov->type_color }}">{{ $mov->type_label }}</span></td>
                            <td class="text-center">{{ $mov->quantity > 0 ? '+' : '' }}{{ $mov->quantity }}</td>
                            <td class="text-center">{{ $mov->quantity_after }}</td>
                            <td>{{ $mov->reference ?? '-' }}</td>
                        </tr>
                    @endforeach
                    <tr class="table-warning">
                        <td><strong>{{ $stockMovement->created_at->format('d/m H:i') }}</strong></td>
                        <td><span class="badge bg-{{ $stockMovement->type_color }}">{{ $stockMovement->type_label }}</span></td>
                        <td class="text-center"><strong>{{ $stockMovement->quantity > 0 ? '+' : '' }}{{ $stockMovement->quantity }}</strong></td>
                        <td class="text-center"><strong>{{ $stockMovement->quantity_after }}</strong></td>
                        <td><strong>{{ $stockMovement->reference ?? '-' }}</strong></td>
                    </tr>
                    @foreach($nextMovements as $mov)
                        <tr class="text-muted">
                            <td>{{ $mov->created_at->format('d/m H:i') }}</td>
                            <td><span class="badge bg-{{ $mov->type_color }}">{{ $mov->type_label }}</span></td>
                            <td class="text-center">{{ $mov->quantity > 0 ? '+' : '' }}{{ $mov->quantity }}</td>
                            <td class="text-center">{{ $mov->quantity_after }}</td>
                            <td>{{ $mov->reference ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
