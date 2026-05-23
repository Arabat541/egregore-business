@extends('layouts.app')

@section('title', 'Commande ' . $onlineOrder->order_number)

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.online-orders.index') }}" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i>Retour aux commandes
            </a>
            <h4 class="fw-bold mb-0 mt-1">
                <i class="bi bi-globe me-2"></i>{{ $onlineOrder->order_number }}
                <span class="badge bg-{{ $onlineOrder->status_badge }} ms-2">{{ $onlineOrder->status_label }}</span>
            </h4>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Column --}}
        <div class="col-lg-8">
            {{-- Articles --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold"><i class="bi bi-box-seam me-1"></i>Articles commandés</div>
                <div class="card-body p-0">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">Produit</th>
                                <th class="text-center">Qté</th>
                                <th class="text-end">Prix unitaire</th>
                                <th class="text-end pe-3">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($onlineOrder->items as $item)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold">{{ $item->product_name }}</div>
                                        @if($item->product)
                                            <small class="text-muted">SKU: {{ $item->product->sku ?? '-' }}</small>
                                        @else
                                            <small class="text-danger">Produit supprimé</small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ number_format($item->unit_price, 0, ',', ' ') }} F</td>
                                    <td class="text-end pe-3 fw-bold">{{ number_format($item->total_price, 0, ',', ' ') }} F</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="3" class="text-end fw-bold pe-3">Sous-total</td>
                                <td class="text-end pe-3 fw-bold">{{ number_format($onlineOrder->subtotal, 0, ',', ' ') }} F</td>
                            </tr>
                            @if($onlineOrder->shipping_cost > 0)
                            <tr>
                                <td colspan="3" class="text-end pe-3">Livraison</td>
                                <td class="text-end pe-3">{{ number_format($onlineOrder->shipping_cost, 0, ',', ' ') }} F</td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="3" class="text-end fw-bold pe-3 fs-5">Total</td>
                                <td class="text-end pe-3 fw-bold fs-5 text-primary">{{ number_format($onlineOrder->total_amount, 0, ',', ' ') }} F</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Client info --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold"><i class="bi bi-person me-1"></i>Informations client</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Nom :</strong> {{ $onlineOrder->customer_name }}</p>
                            <p class="mb-1"><strong>Téléphone :</strong> {{ $onlineOrder->customer_phone }}</p>
                            @if($onlineOrder->customer_email)
                                <p class="mb-1"><strong>Email :</strong> {{ $onlineOrder->customer_email }}</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            @if($onlineOrder->customer_city)
                                <p class="mb-1"><strong>Ville :</strong> {{ $onlineOrder->customer_city }}</p>
                            @endif
                            @if($onlineOrder->customer_address)
                                <p class="mb-1"><strong>Adresse :</strong> {{ $onlineOrder->customer_address }}</p>
                            @endif
                        </div>
                    </div>
                    @if($onlineOrder->notes)
                        <div class="mt-2 p-2 bg-light rounded">
                            <small><strong>Notes du client :</strong> {{ $onlineOrder->notes }}</small>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Admin notes --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-sticky me-1"></i>Notes internes</div>
                <div class="card-body">
                    <form action="{{ route('admin.online-orders.add-note', $onlineOrder) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <textarea name="admin_notes" class="form-control mb-2" rows="3" placeholder="Ajouter une note interne...">{{ $onlineOrder->admin_notes }}</textarea>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-save me-1"></i>Sauvegarder</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="col-lg-4">
            {{-- Status update --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold"><i class="bi bi-arrow-repeat me-1"></i>Changer le statut</div>
                <div class="card-body">
                    <form action="{{ route('admin.online-orders.update-status', $onlineOrder) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="form-select mb-2">
                            @foreach(\App\Models\OnlineOrder::getStatusLabels() as $key => $label)
                                <option value="{{ $key }}" {{ $onlineOrder->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle me-1"></i>Mettre à jour
                        </button>
                    </form>
                    @if($onlineOrder->status === 'cancelled')
                        <div class="alert alert-danger mt-2 mb-0 small">
                            <i class="bi bi-exclamation-triangle me-1"></i>Les stocks ont été restaurés lors de l'annulation.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Payment update --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold"><i class="bi bi-wallet2 me-1"></i>Paiement</div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Méthode :</strong> {{ $onlineOrder->payment_label }}<br>
                        <strong>Statut :</strong>
                        <span class="badge bg-{{ $onlineOrder->payment_status === 'paid' ? 'success' : ($onlineOrder->payment_status === 'refunded' ? 'danger' : 'warning') }}">
                            {{ $onlineOrder->payment_status === 'paid' ? 'Payé' : ($onlineOrder->payment_status === 'refunded' ? 'Remboursé' : 'En attente') }}
                        </span>
                    </p>
                    <form action="{{ route('admin.online-orders.update-payment', $onlineOrder) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <select name="payment_status" class="form-select mb-2">
                            <option value="pending" {{ $onlineOrder->payment_status === 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="paid" {{ $onlineOrder->payment_status === 'paid' ? 'selected' : '' }}>Payé</option>
                            <option value="refunded" {{ $onlineOrder->payment_status === 'refunded' ? 'selected' : '' }}>Remboursé</option>
                        </select>
                        <button type="submit" class="btn btn-outline-primary w-100 btn-sm">Mettre à jour le paiement</button>
                    </form>
                </div>
            </div>

            {{-- Order info --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold"><i class="bi bi-info-circle me-1"></i>Détails</div>
                <div class="card-body small">
                    <p class="mb-1"><strong>Boutique :</strong> {{ $onlineOrder->shop->name ?? '-' }}</p>
                    <p class="mb-1"><strong>Livraison :</strong> {{ $onlineOrder->delivery_label }}</p>
                    <p class="mb-1"><strong>Créée le :</strong> {{ $onlineOrder->created_at->format('d/m/Y à H:i') }}</p>
                    @if($onlineOrder->confirmed_at)
                        <p class="mb-1"><strong>Confirmée le :</strong> {{ $onlineOrder->confirmed_at->format('d/m/Y à H:i') }}</p>
                    @endif
                    @if($onlineOrder->shipped_at)
                        <p class="mb-1"><strong>Expédiée le :</strong> {{ $onlineOrder->shipped_at->format('d/m/Y à H:i') }}</p>
                    @endif
                    @if($onlineOrder->delivered_at)
                        <p class="mb-1"><strong>Livrée le :</strong> {{ $onlineOrder->delivered_at->format('d/m/Y à H:i') }}</p>
                    @endif
                    @if($onlineOrder->processedBy)
                        <p class="mb-0"><strong>Traitée par :</strong> {{ $onlineOrder->processedBy->name }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
