@extends('layouts.app')

@section('title', 'Facture ' . $order->reference)

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-file-earmark-text"></i> Facture {{ $order->reference }}</h2>
    <a href="{{ route('admin.suppliers.orders') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <!-- Colonne gauche - Informations -->
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th>Référence:</th>
                        <td>{{ $order->reference }}</td>
                    </tr>
                    <tr>
                        <th>Date:</th>
                        <td>{{ $order->order_date->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        <th>Statut:</th>
                        <td>
                            @switch($order->status)
                                @case('draft')
                                    <span class="badge bg-warning fs-6">Brouillon</span>
                                    @break
                                @case('sent')
                                    <span class="badge bg-info fs-6">Envoyée</span>
                                    @break
                                @case('confirmed')
                                    <span class="badge bg-primary fs-6">Confirmée</span>
                                    @break
                                @case('received')
                                    <span class="badge bg-success fs-6">Réceptionnée</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger fs-6">Annulée</span>
                                    @break
                            @endswitch
                        </td>
                    </tr>
                    @if($order->received_date)
                    <tr>
                        <th>Réceptionné le:</th>
                        <td>{{ $order->received_date->translatedFormat('d F Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th>Créé par:</th>
                        <td>{{ $order->user->name ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-building"></i> Fournisseur
            </div>
            <div class="card-body">
                <h5 class="mb-2">
                    <a href="{{ route('admin.suppliers.show', $order->supplier) }}" class="text-decoration-none">
                        {{ $order->supplier->company_name }}
                    </a>
                </h5>
                @if($order->supplier->contact_name)
                    <p class="mb-1"><i class="bi bi-person"></i> {{ $order->supplier->contact_name }}</p>
                @endif
                @if($order->supplier->phone)
                    <p class="mb-1"><i class="bi bi-telephone"></i> {{ $order->supplier->phone }}</p>
                @endif
                @if($order->supplier->email)
                    <p class="mb-1"><i class="bi bi-envelope"></i> {{ $order->supplier->email }}</p>
                @endif
                @if($order->supplier->whatsapp)
                    <p class="mb-0">
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->supplier->whatsapp) }}" target="_blank" class="btn btn-success btn-sm">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                    </p>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-shop"></i> Boutique
            </div>
            <div class="card-body">
                <h5 class="mb-0">{{ $order->shop->name ?? 'Non spécifiée' }}</h5>
            </div>
        </div>

        @if($order->notes)
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-sticky"></i> Notes
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $order->notes }}</p>
            </div>
        </div>
        @endif

        <!-- Actions -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-lightning"></i> Actions
            </div>
            <div class="card-body d-grid gap-2">
                @if($order->status === 'draft')
                    <form action="{{ route('admin.suppliers.orders.mark-sent', $order) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-info w-100">
                            <i class="bi bi-send"></i> Marquer comme envoyée
                        </button>
                    </form>
                @endif

                @if($order->status !== 'received' && $order->status !== 'cancelled')
                    <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#receiveModal">
                        <i class="bi bi-box-arrow-in-down"></i> Réceptionner
                    </button>
                @endif

                <a href="{{ route('admin.suppliers.orders.pdf', $order) }}" class="btn btn-outline-primary w-100" target="_blank">
                    <i class="bi bi-file-pdf"></i> Télécharger PDF
                </a>

                @if($order->status === 'draft')
                    <form action="{{ route('admin.suppliers.orders.destroy', $order) }}" method="POST" onsubmit="return confirm('Supprimer cette facture ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash"></i> Supprimer
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Colonne droite - Articles -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-ul"></i> Articles ({{ $order->items->count() }})
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th class="text-center">Qté commandée</th>
                                @if($order->status === 'received')
                                <th class="text-center">Qté reçue</th>
                                @endif
                                <th class="text-end">Prix unitaire</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        @if($item->product)
                                            <a href="{{ route('admin.products.show', $item->product) }}" class="text-decoration-none">
                                                <strong>{{ $item->product->name }}</strong>
                                            </a>
                                            @if($item->product->sku)
                                                <br><small class="text-muted">{{ $item->product->sku }}</small>
                                            @endif
                                        @else
                                            <strong>{{ $item->product_name }}</strong>
                                            <br><small class="text-danger">Produit supprimé</small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $item->quantity_ordered }}</td>
                                    @if($order->status === 'received')
                                    <td class="text-center">
                                        <span class="{{ $item->quantity_received < $item->quantity_ordered ? 'text-danger' : 'text-success' }}">
                                            {{ $item->quantity_received }}
                                        </span>
                                    </td>
                                    @endif
                                    <td class="text-end">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
                                    <td class="text-end fw-bold">{{ number_format($item->total_price, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-primary">
                                <th colspan="{{ $order->status === 'received' ? 4 : 3 }}" class="text-end">TOTAL:</th>
                                <th class="text-end fs-5">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de réception -->
@if($order->status !== 'received' && $order->status !== 'cancelled')
<div class="modal fade" id="receiveModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.suppliers.orders.receive', $order) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-down"></i> Réceptionner la commande</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        Vérifiez les quantités reçues et les prix. Les stocks seront automatiquement mis à jour et les prix fournisseurs enregistrés.
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-center">Commandé</th>
                                    <th class="text-center">Reçu</th>
                                    <th>Prix unitaire</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->product->name ?? $item->product_name }}</strong>
                                            <input type="hidden" name="items[{{ $loop->index }}][item_id]" value="{{ $item->id }}">
                                        </td>
                                        <td class="text-center">{{ $item->quantity_ordered }}</td>
                                        <td>
                                            <input type="number" name="items[{{ $loop->index }}][quantity_received]" 
                                                   class="form-control form-control-sm text-center" 
                                                   value="{{ $item->quantity_ordered }}" min="0" max="{{ $item->quantity_ordered * 2 }}" required>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="items[{{ $loop->index }}][unit_price]" 
                                                       class="form-control" value="{{ (int)$item->unit_price }}" min="0" required>
                                                <span class="input-group-text">FCFA</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg"></i> Valider la réception
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
