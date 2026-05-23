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
        @if($stockTransfer->has_discrepancy)
            <span class="badge bg-danger fs-6 ms-1"><i class="bi bi-exclamation-triangle"></i> Écart signalé</span>
        @endif
    </div>
    <a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-outline-secondary">
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

{{-- Alerte écart de réception --}}
@if($stockTransfer->status === 'received' && $stockTransfer->has_discrepancy)
<div class="alert alert-warning border-warning">
    <div class="d-flex align-items-start">
        <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning mt-1"></i>
        <div>
            <strong>Écart de réception détecté</strong><br>
            Les quantités reçues ne correspondent pas à celles expédiées. Les stocks ont été ajustés automatiquement.
            Consultez le détail ci-dessous et les mouvements de stock pour les régularisations effectuées.
            @if($stockTransfer->reception_notes)
                <br><strong>Note :</strong> {{ $stockTransfer->reception_notes }}
            @endif
        </div>
    </div>
</div>
@endif

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
                                <td><i class="bi bi-shop text-primary"></i> {{ $stockTransfer->fromShop->name }}</td>
                            </tr>
                            <tr>
                                <th>Boutique destination</th>
                                <td><i class="bi bi-shop text-success"></i> {{ $stockTransfer->toShop->name }}</td>
                            </tr>
                            @if($stockTransfer->notes)
                            <tr>
                                <th>Notes</th>
                                <td class="text-muted">{{ $stockTransfer->notes }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th width="45%">Créé par</th>
                                <td>{{ $stockTransfer->user->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Date création</th>
                                <td>{{ $stockTransfer->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @if($stockTransfer->in_transit_at)
                            <tr>
                                <th>Expédié par</th>
                                <td>{{ $stockTransfer->sentBy->name ?? $stockTransfer->validatedBy->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Date expédition</th>
                                <td>{{ $stockTransfer->in_transit_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endif
                            @if($stockTransfer->received_at)
                            <tr>
                                <th>Réceptionné par</th>
                                <td>{{ $stockTransfer->receivedBy->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Date réception</th>
                                <td>{{ $stockTransfer->received_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endif
                            @if($stockTransfer->status === 'completed')
                            <tr>
                                <th>Validé par</th>
                                <td>{{ $stockTransfer->validatedBy->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Date validation</th>
                                <td>{{ $stockTransfer->validated_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
                @if($stockTransfer->reception_notes)
                    <div class="mt-2 p-2 bg-light rounded">
                        <strong><i class="bi bi-chat-text me-1"></i>Notes de réception :</strong>
                        {{ $stockTransfer->reception_notes }}
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
                                            <br><span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Écart</span>
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
                                                <span class="badge bg-success fs-6">{{ $item->quantity_received }}</span>
                                            @elseif($item->quantity_received < $item->quantity)
                                                <span class="badge bg-danger fs-6">{{ $item->quantity_received }}</span>
                                                <small class="text-danger d-block">-{{ $item->quantity - $item->quantity_received }}</small>
                                            @else
                                                <span class="badge bg-warning text-dark fs-6">{{ $item->quantity_received }}</span>
                                                <small class="text-success d-block">+{{ $item->quantity_received - $item->quantity }}</small>
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
        <!-- Actions -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-lightning"></i> Actions
            </div>
            <div class="card-body">
                @if($stockTransfer->status === 'pending')
                    <div class="alert alert-warning p-2 mb-3">
                        <small><i class="bi bi-info-circle me-1"></i>
                        Expédier déduira le stock de <strong>{{ $stockTransfer->fromShop->name }}</strong>.
                        La boutique destination devra confirmer la réception.</small>
                    </div>
                    <form action="{{ route('admin.stock-transfers.validate', $stockTransfer) }}" method="POST" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success w-100"
                                onclick="return confirm('Expédier ce transfert ?\nLe stock de {{ $stockTransfer->fromShop->name }} sera déduit immédiatement.')">
                            <i class="bi bi-send"></i> Expédier le transfert
                        </button>
                    </form>
                    <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelModal">
                        <i class="bi bi-x-lg"></i> Annuler le transfert
                    </button>

                @elseif($stockTransfer->status === 'in_transit')
                    <div class="text-center py-2 mb-3">
                        <i class="bi bi-truck display-4 text-info"></i>
                        <p class="mt-2 mb-1 fw-bold">En transit</p>
                        <small class="text-muted">
                            En attente de confirmation par la caissière de<br>
                            <strong>{{ $stockTransfer->toShop->name }}</strong>
                        </small>
                    </div>
                    <hr>
                    <p class="text-muted small text-center mb-2">
                        <i class="bi bi-info-circle me-1"></i>
                        La caissière doit confirmer la réception depuis son interface.
                    </p>
                    <button type="button" class="btn btn-outline-secondary w-100 btn-sm"
                            data-bs-toggle="modal" data-bs-target="#receiveModal">
                        <i class="bi bi-shield-lock me-1"></i>Forcer la réception (admin)
                    </button>

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
                    <small class="text-muted d-block text-center">
                        Par {{ $stockTransfer->receivedBy->name ?? '—' }}<br>
                        le {{ $stockTransfer->received_at?->format('d/m/Y à H:i') }}
                    </small>

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
                    <span>Nb produits :</span>
                    <strong>{{ $stockTransfer->items->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Qté expédiée :</span>
                    <strong>{{ $stockTransfer->total_items }}</strong>
                </div>
                @if($stockTransfer->status === 'received')
                <div class="d-flex justify-content-between mb-2">
                    <span>Qté reçue :</span>
                    <strong class="{{ $stockTransfer->has_discrepancy ? 'text-warning' : 'text-success' }}">
                        {{ $stockTransfer->items->sum('quantity_received') }}
                    </strong>
                </div>
                @endif
                <hr>
                <div class="d-flex justify-content-between">
                    <span>Valeur :</span>
                    <strong class="text-primary">{{ number_format($stockTransfer->total_value, 0, ',', ' ') }} FCFA</strong>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Confirmation de réception --}}
@if($stockTransfer->status === 'in_transit')
<div class="modal fade" id="receiveModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.stock-transfers.receive', $stockTransfer) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-down me-2"></i>Confirmer la réception</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-1"></i>
                        Saisissez les quantités <strong>réellement reçues</strong>. Si une quantité diffère, l'écart sera tracé
                        automatiquement et le stock sera ajusté en conséquence.
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-center">Qté expédiée</th>
                                    <th class="text-center" style="width:140px">Qté reçue</th>
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

                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes de réception <small class="text-muted">(optionnel)</small></label>
                        <textarea name="reception_notes" class="form-control" rows="2"
                                  placeholder="Décrivez tout écart, problème ou remarque sur la marchandise reçue..."></textarea>
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

{{-- Modal: Annulation --}}
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
    // init
    input.dispatchEvent(new Event('input'));
});
</script>
@endpush
@endsection
