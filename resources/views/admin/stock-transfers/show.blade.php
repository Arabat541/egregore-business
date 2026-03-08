@extends('layouts.app')

@section('title', 'Transfert ' . $stockTransfer->reference)

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-arrow-left-right"></i> Transfert {{ $stockTransfer->reference }}</h2>
        <span class="badge bg-{{ $stockTransfer->status_color }} fs-6">{{ $stockTransfer->status_label }}</span>
    </div>
    <a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Informations du transfert -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Référence</th>
                                <td><strong>{{ $stockTransfer->reference }}</strong></td>
                            </tr>
                            <tr>
                                <th>Boutique source</th>
                                <td>
                                    <i class="bi bi-shop text-primary"></i>
                                    {{ $stockTransfer->fromShop->name }}
                                </td>
                            </tr>
                            <tr>
                                <th>Boutique destination</th>
                                <td>
                                    <i class="bi bi-shop text-success"></i>
                                    {{ $stockTransfer->toShop->name }}
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Créé par</th>
                                <td>{{ $stockTransfer->user->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Date création</th>
                                <td>{{ $stockTransfer->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Validé par</th>
                                <td>{{ $stockTransfer->validatedBy->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Date validation</th>
                                <td>{{ $stockTransfer->validated_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                @if($stockTransfer->notes)
                    <div class="mt-2">
                        <strong>Notes :</strong> {{ $stockTransfer->notes }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Articles du transfert -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-box-seam"></i> Articles ({{ $stockTransfer->items->count() }})
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Catégorie</th>
                                <th class="text-center">Quantité</th>
                                <th class="text-end">Prix unitaire</th>
                                <th class="text-end">Valeur</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockTransfer->items as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->product->name ?? '-' }}</strong>
                                        @if($item->product?->sku)
                                            <br><small class="text-muted">{{ $item->product->sku }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $item->product->category->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary fs-6">{{ $item->quantity }}</span>
                                    </td>
                                    <td class="text-end">{{ number_format($item->purchase_price, 0, ',', ' ') }} FCFA</td>
                                    <td class="text-end">{{ number_format($item->total_value, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <th colspan="2">TOTAL</th>
                                <th class="text-center">{{ $stockTransfer->total_items }}</th>
                                <th></th>
                                <th class="text-end">{{ number_format($stockTransfer->total_value, 0, ',', ' ') }} FCFA</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Actions -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-lightning"></i> Actions
            </div>
            <div class="card-body">
                @if($stockTransfer->status === 'pending')
                    <form action="{{ route('admin.stock-transfers.validate', $stockTransfer) }}" method="POST" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success w-100" 
                                onclick="return confirm('Êtes-vous sûr de vouloir valider ce transfert ?\nLe stock sera automatiquement mis à jour.')">
                            <i class="bi bi-check-lg"></i> Valider le transfert
                        </button>
                    </form>
                    
                    <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelModal">
                        <i class="bi bi-x-lg"></i> Annuler le transfert
                    </button>
                @elseif($stockTransfer->status === 'completed')
                    <div class="text-center text-success py-3">
                        <i class="bi bi-check-circle display-4"></i>
                        <p class="mt-2 mb-0">Transfert validé</p>
                        <small class="text-muted">Le stock a été mis à jour</small>
                    </div>
                @else
                    <div class="text-center text-secondary py-3">
                        <i class="bi bi-x-circle display-4"></i>
                        <p class="mt-2 mb-0">Transfert annulé</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Résumé -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calculator"></i> Résumé
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Nombre de produits:</span>
                    <strong>{{ $stockTransfer->items->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Quantité totale:</span>
                    <strong>{{ $stockTransfer->total_items }}</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span>Valeur totale:</span>
                    <strong class="text-primary">{{ number_format($stockTransfer->total_value, 0, ',', ' ') }} FCFA</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Annulation -->
@if($stockTransfer->status === 'pending')
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.stock-transfers.cancel', $stockTransfer) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Annuler le transfert</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Motif de l'annulation <span class="text-danger">*</span></label>
                        <textarea name="cancel_reason" class="form-control" rows="3" required 
                                  placeholder="Indiquez le motif de l'annulation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-danger">Annuler le transfert</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
