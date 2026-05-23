@extends('layouts.app')

@section('title', 'Historique des ventes')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-receipt"></i> Historique des ventes</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('cashier.sales.export-pdf', request()->only(['search','client_type','payment_status','date_from','date_to'])) }}"
           class="btn btn-outline-danger" target="_blank">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>
        <a href="{{ route('cashier.sales.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nouvelle vente
        </a>
    </div>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('cashier.sales.index') }}" method="GET" class="row g-3">
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}" placeholder="Du">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}" placeholder="Au">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="payment_status">
                    <option value="">Tous statuts</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Payé</option>
                    <option value="credit" {{ request('payment_status') === 'credit' ? 'selected' : '' }}>Crédit</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="client_type">
                    <option value="">Tous types</option>
                    <option value="walk-in" {{ request('client_type') === 'walk-in' ? 'selected' : '' }}>Comptoir</option>
                    <option value="customer" {{ request('client_type') === 'customer' ? 'selected' : '' }}>Client</option>
                    <option value="reseller" {{ request('client_type') === 'reseller' ? 'selected' : '' }}>Réparateur</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="N° facture ou client">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i>
                </button>
                <a href="{{ route('cashier.sales.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Articles</th>
                        <th>Total</th>
                        <th>Payé</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td><code>{{ $sale->invoice_number }}</code></td>
                        <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            {{ $sale->client_name }}
                            @if($sale->client_type === 'reseller')
                                <span class="badge bg-info">Réparateur</span>
                            @endif
                        </td>
                        <td>{{ $sale->items->count() }} articles</td>
                        <td class="fw-bold">{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</td>
                        <td>{{ number_format($sale->amount_paid, 0, ',', ' ') }} FCFA</td>
                        <td>
                            @if($sale->payment_status === 'paid')
                                <span class="badge bg-success">Payé</span>
                            @elseif($sale->payment_status === 'credit')
                                <span class="badge bg-warning">Crédit</span>
                                @if($sale->remaining_amount > 0)
                                    <br><small class="text-danger">Reste: {{ number_format($sale->remaining_amount, 0, ',', ' ') }}</small>
                                @endif
                            @else
                                <span class="badge bg-danger">Annulé</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('cashier.sales.show', $sale) }}" class="btn btn-sm btn-info text-white" title="Détail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('cashier.sales.receipt', $sale) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Ticket">
                                <i class="bi bi-printer"></i>
                            </a>
                            @if($sale->payment_status !== 'cancelled')
                                <button type="button" class="btn btn-sm btn-danger" title="Annuler"
                                        data-bs-toggle="modal" data-bs-target="#cancelModal{{ $sale->id }}">
                                    <i class="bi bi-x-circle"></i>
                                </button>

                                <!-- Modal annulation -->
                                <div class="modal fade" id="cancelModal{{ $sale->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('cashier.sales.cancel', $sale) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <div class="modal-header">
                                                    <h5 class="modal-title text-danger">
                                                        <i class="bi bi-exclamation-triangle me-1"></i> Annuler la vente
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="mb-1">
                                                        Facture <strong>{{ $sale->invoice_number }}</strong> —
                                                        <strong>{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</strong>
                                                    </p>
                                                    <p class="text-muted small mb-3">Le stock sera restitué et la vente annulée définitivement.</p>
                                                    <label class="form-label fw-semibold">Motif de l'annulation <span class="text-danger">*</span></label>
                                                    <textarea name="cancel_reason" class="form-control" rows="3"
                                                              placeholder="Expliquez la raison de l'annulation…" required maxlength="500"></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="bi bi-x-circle me-1"></i> Confirmer l'annulation
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-0">
                            <x-empty-state
                                icon="bi-cart-x"
                                title="Aucune vente trouvée"
                                :message="request()->hasAny(['search','client_type','payment_status','date_from','date_to'])
                                    ? 'Aucun résultat pour ces filtres. Modifiez ou supprimez les critères.'
                                    : 'Il n\'y a pas encore de vente enregistrée aujourd\'hui.'"
                                :action-url="route('cashier.sales.create')"
                                action-label="Nouvelle vente"
                            />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
            <div class="text-muted small">
                {{ $sales->firstItem() }}–{{ $sales->lastItem() }} sur {{ $sales->total() }} ventes
            </div>
            <div class="d-flex align-items-center gap-3">
                <x-per-page-selector :current="$perPage ?? 20" />
                {{ $sales->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
