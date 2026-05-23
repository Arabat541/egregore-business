@extends('layouts.app')

@section('title', 'Transfert ' . $stockTransfer->reference)

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-box-arrow-in-down text-success me-2"></i>Transfert {{ $stockTransfer->reference }}</h2>
        <span class="badge bg-{{ $stockTransfer->status_color }} fs-6">{{ $stockTransfer->status_label }}</span>
        @if($stockTransfer->has_discrepancy)
            <span class="badge bg-danger fs-6 ms-1"><i class="bi bi-exclamation-triangle"></i> Écart signalé</span>
        @endif
    </div>
    <a href="{{ route('cashier.stock-transfers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Alerte : en transit, en attente de confirmation --}}
@if($stockTransfer->status === 'in_transit')
<div class="alert alert-warning border-warning mb-4">
    <div class="d-flex align-items-center">
        <i class="bi bi-truck fs-3 me-3 text-warning flex-shrink-0"></i>
        <div>
            <strong>Marchandise en transit — en attente de votre confirmation</strong><br>
            Ce transfert a été expédié depuis <strong>{{ $stockTransfer->fromShop->name }}</strong>.
            Vérifiez les articles reçus et confirmez la réception avec les quantités exactes.
            @if($stockTransfer->notes)
                <br><small class="text-muted mt-1 d-block"><i class="bi bi-sticky me-1"></i>{{ $stockTransfer->notes }}</small>
            @endif
        </div>
    </div>
</div>
@endif

{{-- Alerte écart --}}
@if($stockTransfer->status === 'received' && $stockTransfer->has_discrepancy)
<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Réception avec écarts :</strong>
    les quantités reçues ne correspondaient pas aux quantités expédiées. Les stocks ont été ajustés automatiquement.
    @if($stockTransfer->reception_notes)
        <br><strong>Note :</strong> {{ $stockTransfer->reception_notes }}
    @endif
</div>
@endif

<div class="row">
    <div class="col-md-8">
        <!-- Articles -->
        <div class="card mb-4">
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
                                <th class="text-center">Qté expédiée</th>
                                @if(in_array($stockTransfer->status, ['received', 'completed']))
                                <th class="text-center">Qté reçue</th>
                                @endif
                                <th class="text-end">Prix unitaire</th>
                                <th class="text-end">Valeur</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockTransfer->items as $item)
                                <tr class="{{ $item->has_discrepancy ? 'table-warning' : '' }}">
                                    <td>
                                        <strong>{{ $item->product->name ?? '-' }}</strong>
                                        @if($item->product?->sku)
                                            <br><small class="text-muted">{{ $item->product->sku }}</small>
                                        @endif
                                        @if($item->has_discrepancy)
                                            <br><span class="badge bg-warning text-dark">
                                                <i class="bi bi-exclamation-triangle"></i> Écart
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $item->product->category->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary fs-6">{{ $item->quantity }}</span>
                                    </td>
                                    @if(in_array($stockTransfer->status, ['received', 'completed']))
                                    <td class="text-center">
                                        @if($item->quantity_received !== null)
                                            @if($item->quantity_received === $item->quantity)
                                                <span class="badge bg-success fs-6">
                                                    <i class="bi bi-check-circle"></i> {{ $item->quantity_received }}
                                                </span>
                                            @elseif($item->quantity_received < $item->quantity)
                                                <span class="badge bg-danger fs-6">{{ $item->quantity_received }}</span>
                                                <small class="text-danger d-block">-{{ $item->quantity - $item->quantity_received }} manquant(s)</small>
                                            @else
                                                <span class="badge bg-warning text-dark fs-6">{{ $item->quantity_received }}</span>
                                                <small class="text-success d-block">+{{ $item->quantity_received - $item->quantity }} en plus</small>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    @endif
                                    <td class="text-end">{{ number_format($item->purchase_price, 0, ',', ' ') }} FCFA</td>
                                    <td class="text-end">{{ number_format($item->total_value, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <th colspan="2">TOTAL</th>
                                <th class="text-center">{{ $stockTransfer->total_items }}</th>
                                @if(in_array($stockTransfer->status, ['received', 'completed']))
                                <th class="text-center">
                                    @if($stockTransfer->status === 'received')
                                        {{ $stockTransfer->items->sum('quantity_received') }}
                                    @endif
                                </th>
                                @endif
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
        <!-- Action principale -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-lightning"></i> Action
            </div>
            <div class="card-body">
                @if($stockTransfer->status === 'in_transit')
                    <button type="button" class="btn btn-success w-100 btn-lg"
                            data-bs-toggle="modal" data-bs-target="#receiveModal">
                        <i class="bi bi-box-arrow-in-down me-2"></i>Confirmer la réception
                    </button>
                    <p class="text-muted small mt-2 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Comptez les articles et saisissez les quantités réellement reçues.
                    </p>

                @elseif($stockTransfer->status === 'received')
                    @if($stockTransfer->has_discrepancy)
                        <div class="text-center py-3">
                            <i class="bi bi-exclamation-triangle-fill display-4 text-warning"></i>
                            <p class="mt-2 mb-1 fw-bold">Reçu avec écarts</p>
                            <small class="text-muted">Stocks ajustés automatiquement</small>
                        </div>
                    @else
                        <div class="text-center py-3 text-success">
                            <i class="bi bi-check-circle-fill display-4"></i>
                            <p class="mt-2 mb-1 fw-bold">Réception confirmée</p>
                            <small class="text-muted">Tous les articles correspondent</small>
                        </div>
                    @endif
                    <small class="text-muted d-block text-center mt-1">
                        Par {{ $stockTransfer->receivedBy->name ?? '—' }}<br>
                        le {{ $stockTransfer->received_at?->format('d/m/Y à H:i') }}
                    </small>

                @elseif($stockTransfer->status === 'completed')
                    <div class="text-center text-success py-3">
                        <i class="bi bi-check-circle display-4"></i>
                        <p class="mt-2 mb-0">Transfert validé</p>
                    </div>

                @else
                    <div class="text-center text-secondary py-3">
                        <i class="bi bi-x-circle display-4"></i>
                        <p class="mt-2 mb-0">Transfert annulé</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Informations -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th>Référence</th>
                        <td><strong>{{ $stockTransfer->reference }}</strong></td>
                    </tr>
                    <tr>
                        <th>En provenance de</th>
                        <td><i class="bi bi-shop text-primary"></i> {{ $stockTransfer->fromShop->name }}</td>
                    </tr>
                    <tr>
                        <th>Créé par</th>
                        <td>{{ $stockTransfer->user->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Créé le</th>
                        <td>{{ $stockTransfer->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @if($stockTransfer->in_transit_at)
                    <tr>
                        <th>Expédié le</th>
                        <td>{{ $stockTransfer->in_transit_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endif
                    @if($stockTransfer->received_at)
                    <tr>
                        <th>Réceptionné le</th>
                        <td>{{ $stockTransfer->received_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endif
                </table>
                @if($stockTransfer->reception_notes)
                    <div class="mt-2 p-2 bg-light rounded">
                        <strong><i class="bi bi-chat-text me-1"></i>Notes :</strong>
                        {{ $stockTransfer->reception_notes }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal de confirmation de réception --}}
@if($stockTransfer->status === 'in_transit')
<div class="modal fade" id="receiveModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('cashier.stock-transfers.receive', $stockTransfer) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-box-arrow-in-down me-2"></i>Confirmer la réception
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-1"></i>
                        Comptez chaque article et saisissez les quantités <strong>réellement reçues</strong>.
                        Si une quantité diffère, l'écart sera tracé automatiquement.
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-center">Expédié</th>
                                    <th class="text-center" style="width:140px">Reçu</th>
                                    <th class="text-center">Écart</th>
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
                                        <input type="hidden" name="items[{{ $loop->index }}][item_id]" value="{{ $item->id }}">
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $item->quantity }}</span>
                                    </td>
                                    <td>
                                        <input type="number"
                                               name="items[{{ $loop->index }}][quantity_received]"
                                               class="form-control form-control-sm text-center qty-received"
                                               value="{{ $item->quantity }}"
                                               min="0"
                                               data-expected="{{ $item->quantity }}"
                                               required>
                                    </td>
                                    <td class="text-center ecart-cell" id="ecart-{{ $loop->index }}">—</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold">
                            <i class="bi bi-chat-text me-1"></i>Notes de réception
                            <small class="text-muted fw-normal">(optionnel — écarts, problèmes, remarques)</small>
                        </label>
                        <textarea name="reception_notes" class="form-control" rows="2"
                                  placeholder="Ex: 2 articles manquants, emballage abîmé, article supplémentaire non prévu..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-check-lg me-1"></i>Valider la réception
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.querySelectorAll('.qty-received').forEach(function(input, idx) {
    input.addEventListener('input', function() {
        const expected = parseInt(this.dataset.expected);
        const received = parseInt(this.value) || 0;
        const diff     = received - expected;
        const cell     = document.getElementById('ecart-' + idx);
        if (!cell) return;
        if (diff === 0) {
            cell.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> OK</span>';
        } else if (diff < 0) {
            cell.innerHTML = '<span class="text-danger fw-bold">' + diff + ' manquant(s)</span>';
        } else {
            cell.innerHTML = '<span class="text-warning fw-bold">+' + diff + ' en plus</span>';
        }
    });
    input.dispatchEvent(new Event('input'));
});
</script>
@endpush
@endsection
