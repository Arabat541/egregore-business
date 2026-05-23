@extends('layouts.app')

@section('title', 'Produits en stock faible')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-exclamation-triangle text-warning"></i> Produits en stock faible</h2>
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour aux produits
    </a>
</div>

@if($productsByShop->count() > 0)
    @foreach($productsByShop as $shopName => $products)
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-shop"></i> {{ $shopName }}
                <span class="badge bg-light text-dark ms-2">{{ $products->count() }} produit(s)</span>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Catégorie</th>
                            <th>Stock actuel</th>
                            <th>Seuil alerte</th>
                            <th>Statut</th>
                            <th>Prix d'achat</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                        <tr>
                            <td>
                                <strong>{{ $product->name }}</strong>
                                @if($product->sku)
                                    <br><small class="text-muted">{{ $product->sku }}</small>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary">{{ $product->category->name ?? 'N/A' }}</span></td>
                            <td>
                                <span class="badge bg-{{ $product->quantity_in_stock <= 0 ? 'danger' : 'warning' }} fs-6">
                                    {{ $product->quantity_in_stock }}
                                </span>
                            </td>
                            <td>{{ $product->stock_alert_threshold }}</td>
                            <td>
                                @if($product->quantity_in_stock <= 0)
                                    <span class="badge bg-danger">Rupture</span>
                                @else
                                    <span class="badge bg-warning">Stock faible</span>
                                @endif
                            </td>
                            <td>{{ number_format($product->purchase_price, 0, ',', ' ') }} FCFA</td>
                            <td>
                                <a href="{{ route('admin.products.stock-entry', $product) }}" class="btn btn-sm btn-success">
                                    <i class="bi bi-plus-circle"></i> Réapprovisionner
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach
@else
<div class="alert alert-success">
    <i class="bi bi-check-circle"></i> Aucun produit en stock faible !
</div>
@endif
@endsection
