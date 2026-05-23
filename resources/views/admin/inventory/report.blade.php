@extends('layouts.app')

@section('title', 'Rapport Inventaire ' . $inventory->reference)

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-file-text"></i> Rapport d'inventaire</h2>
        <p class="text-muted mb-0">{{ $inventory->reference }} - {{ $inventory->shop->name }}</p>
    </div>
    <div>
        <a href="{{ route('admin.inventory.show', $inventory) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Imprimer
        </button>
    </div>
</div>

<!-- Résumé -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h4 class="text-primary">{{ $summary['total_products'] }}</h4>
                <small class="text-muted">Produits inventoriés</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h4 class="text-success">{{ $summary['total_products'] - $summary['products_with_difference'] }}</h4>
                <small class="text-muted">Conformes</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h4 class="text-danger">{{ $summary['shortage_count'] }}</h4>
                <small class="text-muted">Manquants</small>
                <div class="text-danger fw-bold">{{ number_format($summary['shortage_value'], 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h4 class="text-info">{{ $summary['surplus_count'] }}</h4>
                <small class="text-muted">Surplus</small>
                <div class="text-info fw-bold">+{{ number_format($summary['surplus_value'], 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
    </div>
</div>

<!-- Informations générales -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-info-circle"></i> Informations générales
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="40%">Référence</th>
                        <td>{{ $inventory->reference }}</td>
                    </tr>
                    <tr>
                        <th>Boutique</th>
                        <td>{{ $inventory->shop->name }}</td>
                    </tr>
                    <tr>
                        <th>Créé par</th>
                        <td>{{ $inventory->user->name }}</td>
                    </tr>
                    <tr>
                        <th>Date de création</th>
                        <td>{{ $inventory->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="40%">Statut</th>
                        <td><span class="badge bg-{{ $inventory->status_color }}">{{ $inventory->status_label }}</span></td>
                    </tr>
                    <tr>
                        <th>Terminé le</th>
                        <td>{{ $inventory->completed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Validé par</th>
                        <td>{{ $inventory->validatedBy?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Validé le</th>
                        <td>{{ $inventory->validated_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        @if($inventory->notes)
            <div class="mt-2">
                <strong>Notes :</strong> {{ $inventory->notes }}
            </div>
        @endif
    </div>
</div>

<!-- Bilan financier -->
<div class="card mb-4 {{ $summary['total_difference_value'] < 0 ? 'border-danger' : 'border-success' }}">
    <div class="card-header {{ $summary['total_difference_value'] < 0 ? 'bg-danger text-white' : 'bg-success text-white' }}">
        <i class="bi bi-currency-dollar"></i> Bilan financier des écarts
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4">
                <h5>Manques (pertes)</h5>
                <h3 class="text-danger">{{ number_format(abs($summary['shortage_value']), 0, ',', ' ') }} FCFA</h3>
            </div>
            <div class="col-md-4">
                <h5>Surplus (gains)</h5>
                <h3 class="text-success">{{ number_format($summary['surplus_value'], 0, ',', ' ') }} FCFA</h3>
            </div>
            <div class="col-md-4">
                <h5>Bilan net</h5>
                <h3 class="{{ $summary['total_difference_value'] < 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($summary['total_difference_value'], 0, ',', ' ') }} FCFA
                </h3>
            </div>
        </div>
    </div>
</div>

<!-- Détail des écarts -->
@if($items->count() > 0)
<div class="card">
    <div class="card-header bg-warning text-dark">
        <i class="bi bi-exclamation-triangle"></i> Détail des écarts ({{ $items->count() }} produits)
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th class="text-center">Théorique</th>
                        <th class="text-center">Physique</th>
                        <th class="text-center">Écart</th>
                        <th class="text-end">Valeur écart</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr class="{{ $item->difference < 0 ? 'table-danger' : 'table-info' }}">
                            <td><strong>{{ $item->product->name }}</strong></td>
                            <td>{{ $item->product->category->name ?? '-' }}</td>
                            <td class="text-center">{{ $item->theoretical_quantity }}</td>
                            <td class="text-center">{{ $item->physical_quantity }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $item->difference < 0 ? 'danger' : 'info' }} fs-6">
                                    {{ $item->difference > 0 ? '+' : '' }}{{ $item->difference }}
                                </span>
                            </td>
                            <td class="text-end {{ $item->difference_value < 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($item->difference_value, 0, ',', ' ') }} FCFA
                            </td>
                            <td>{{ $item->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-dark">
                    <tr>
                        <th colspan="6" class="text-end">TOTAL</th>
                        <th class="text-end">{{ number_format($summary['total_difference_value'], 0, ',', ' ') }} FCFA</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@else
<div class="card border-success">
    <div class="card-body text-center py-5">
        <i class="bi bi-check-circle text-success display-1"></i>
        <h4 class="mt-3 text-success">Aucun écart détecté</h4>
        <p class="text-muted">Tous les produits sont conformes au stock théorique.</p>
    </div>
</div>
@endif

<!-- CSS pour impression -->
<style>
@media print {
    .btn, .sidebar, nav, header {
        display: none !important;
    }
    .card {
        border: 1px solid #dee2e6 !important;
        break-inside: avoid;
    }
}
</style>
@endsection
