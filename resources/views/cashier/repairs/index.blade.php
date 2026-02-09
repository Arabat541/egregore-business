@extends('layouts.app')

@section('title', 'Réparations')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-tools"></i> Réparations</h2>
    <a href="{{ route('cashier.repairs.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nouvelle réparation
    </a>
</div>

<!-- Statistiques rapides -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center py-2">
                <h4 class="mb-0">{{ $stats['in_repair'] ?? 0 }}</h4>
                <small>En cours</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center py-2">
                <h4 class="mb-0">{{ $stats['ready'] ?? 0 }}</h4>
                <small>À livrer</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center py-2">
                <h4 class="mb-0">{{ $stats['today'] ?? 0 }}</h4>
                <small>Aujourd'hui</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center py-2">
                <h4 class="mb-0">{{ $stats['delivered_today'] ?? 0 }}</h4>
                <small>Livrées (j)</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body text-center py-2">
                <h4 class="mb-0">{{ number_format($stats['revenue_today'] ?? 0, 0, ',', ' ') }} F</h4>
                <small>CA du jour</small>
            </div>
        </div>
    </div>
</div>

<!-- Filtres rapides par statut -->
<div class="mb-4">
    <div class="btn-group flex-wrap">
        <a href="{{ route('cashier.repairs.index') }}" class="btn btn-outline-secondary {{ !request('status') ? 'active' : '' }}">Toutes</a>
        <a href="{{ route('cashier.repairs.index', ['status' => 'pending_payment']) }}" class="btn btn-outline-warning {{ request('status') === 'pending_payment' ? 'active' : '' }}">En attente paiement</a>
        <a href="{{ route('cashier.repairs.index', ['status' => 'in_diagnosis']) }}" class="btn btn-outline-info {{ request('status') === 'in_diagnosis' ? 'active' : '' }}">En diagnostic</a>
        <a href="{{ route('cashier.repairs.index', ['status' => 'in_repair']) }}" class="btn btn-outline-primary {{ request('status') === 'in_repair' ? 'active' : '' }}">En réparation</a>
        <a href="{{ route('cashier.repairs.index', ['status' => 'repaired']) }}" class="btn btn-outline-success {{ request('status') === 'repaired' ? 'active' : '' }}">Réparé</a>
        <a href="{{ route('cashier.repairs.index', ['status' => 'ready_for_pickup']) }}" class="btn btn-outline-success {{ request('status') === 'ready_for_pickup' ? 'active' : '' }}">À livrer</a>
        <a href="{{ route('cashier.repairs.index', ['status' => 'delivered']) }}" class="btn btn-outline-secondary {{ request('status') === 'delivered' ? 'active' : '' }}">Livrées</a>
    </div>
</div>

<!-- Formulaire de recherche -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form action="{{ route('cashier.repairs.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="N° ticket, client, téléphone..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="pending_payment" {{ request('status') === 'pending_payment' ? 'selected' : '' }}>En attente paiement</option>
                    <option value="paid_pending_diagnosis" {{ request('status') === 'paid_pending_diagnosis' ? 'selected' : '' }}>Payé - En attente diag.</option>
                    <option value="in_diagnosis" {{ request('status') === 'in_diagnosis' ? 'selected' : '' }}>En diagnostic</option>
                    <option value="waiting_parts" {{ request('status') === 'waiting_parts' ? 'selected' : '' }}>En attente pièces</option>
                    <option value="in_repair" {{ request('status') === 'in_repair' ? 'selected' : '' }}>En réparation</option>
                    <option value="repaired" {{ request('status') === 'repaired' ? 'selected' : '' }}>Réparé</option>
                    <option value="unrepairable" {{ request('status') === 'unrepairable' ? 'selected' : '' }}>Irréparable</option>
                    <option value="ready_for_pickup" {{ request('status') === 'ready_for_pickup' ? 'selected' : '' }}>Prêt pour retrait</option>
                    <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Livré</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulé</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <select name="technician" class="form-select">
                    <option value="">Tous techniciens</option>
                    @foreach($technicians ?? [] as $tech)
                        <option value="{{ $tech->id }}" {{ request('technician') == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</div>

<!-- Tableau des réparations -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>N° Ticket</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Appareil</th>
                        <th>Problème</th>
                        <th>Technicien</th>
                        <th>Statut</th>
                        <th class="text-end">Coût</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($repairs as $repair)
                        <tr>
                            <td>
                                <a href="{{ route('cashier.repairs.show', $repair) }}" class="text-decoration-none fw-bold">
                                    {{ $repair->repair_number }}
                                </a>
                            </td>
                            <td>{{ $repair->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <strong>{{ $repair->customer->name }}</strong>
                                <br><small class="text-muted">{{ $repair->customer->phone }}</small>
                            </td>
                            <td>
                                {{ $repair->device_type }} {{ $repair->device_brand }}
                                <br><small class="text-muted">{{ $repair->device_model }}</small>
                            </td>
                            <td>
                                <small>{{ Str::limit($repair->problem_description, 40) }}</small>
                            </td>
                            <td>
                                @if($repair->technician)
                                    {{ $repair->technician->name }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending_payment' => 'warning',
                                        'paid_pending_diagnosis' => 'info',
                                        'in_diagnosis' => 'info',
                                        'waiting_parts' => 'secondary',
                                        'in_repair' => 'primary',
                                        'repaired' => 'success',
                                        'unrepairable' => 'danger',
                                        'ready_for_pickup' => 'success',
                                        'delivered' => 'dark',
                                        'cancelled' => 'danger'
                                    ];
                                    $statusLabels = [
                                        'pending_payment' => 'En attente paiement',
                                        'paid_pending_diagnosis' => 'Payé - Attente diag.',
                                        'in_diagnosis' => 'En diagnostic',
                                        'waiting_parts' => 'Attente pièces',
                                        'in_repair' => 'En réparation',
                                        'repaired' => 'Réparé',
                                        'unrepairable' => 'Irréparable',
                                        'ready_for_pickup' => 'Prêt retrait',
                                        'delivered' => 'Livré',
                                        'cancelled' => 'Annulé'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$repair->status] ?? 'secondary' }}">
                                    {{ $statusLabels[$repair->status] ?? $repair->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <strong>{{ number_format($repair->total_cost ?? 0, 0, ',', ' ') }} F</strong>
                                @if($repair->amount_paid > 0)
                                    <br><small class="text-success">Payé: {{ number_format($repair->amount_paid, 0, ',', ' ') }} F</small>
                                @endif
                                @php $remaining = ($repair->total_cost ?? 0) - ($repair->amount_paid ?? 0); @endphp
                                @if($remaining > 0)
                                    <br><small class="text-danger">Reste: {{ number_format($remaining, 0, ',', ' ') }} F</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('cashier.repairs.show', $repair) }}" class="btn btn-outline-info" title="Voir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('cashier.repairs.ticket', $repair) }}" class="btn btn-outline-secondary" title="Imprimer" target="_blank">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    @if(in_array($repair->status, ['repaired', 'ready_for_pickup']))
                                        <button type="button" class="btn btn-outline-success" title="Livrer" 
                                                data-bs-toggle="modal" data-bs-target="#deliverModal{{ $repair->id }}">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="bi bi-inbox fs-1 text-muted"></i>
                                <p class="text-muted mb-0">Aucune réparation trouvée</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($repairs->hasPages())
        <div class="card-footer">
            {{ $repairs->withQueryString()->links() }}
        </div>
    @endif
</div>

<!-- Modals de livraison -->
@foreach($repairs as $repair)
    @if(in_array($repair->status, ['repaired', 'ready_for_pickup']))
    <div class="modal fade" id="deliverModal{{ $repair->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle"></i> Livraison - {{ $repair->repair_number }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('cashier.repairs.deliver', $repair) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <strong>Client:</strong> {{ $repair->customer->name }}<br>
                            <strong>Téléphone:</strong> {{ $repair->customer->phone }}<br>
                            <strong>Appareil:</strong> {{ $repair->device_type }} {{ $repair->device_brand }} {{ $repair->device_model }}
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <p><strong>Coût total:</strong></p>
                                <h4>{{ number_format($repair->total_cost ?? 0, 0, ',', ' ') }} F</h4>
                            </div>
                            <div class="col-6">
                                <p><strong>Déjà payé:</strong></p>
                                <h4 class="text-success">{{ number_format($repair->amount_paid ?? 0, 0, ',', ' ') }} F</h4>
                            </div>
                        </div>
                        @php $remainingAmount = ($repair->total_cost ?? 0) - ($repair->amount_paid ?? 0); @endphp
                        @if($remainingAmount > 0)
                            <div class="alert alert-warning">
                                <strong>Reste à payer:</strong> 
                                <span class="fs-4">{{ number_format($remainingAmount, 0, ',', ' ') }} F</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mode de paiement</label>
                                <select name="payment_method_id" class="form-select" required>
                                    @foreach($paymentMethods ?? [] as $method)
                                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Montant reçu</label>
                                <input type="number" name="amount_received" class="form-control" 
                                       value="{{ $remainingAmount }}" min="{{ $remainingAmount }}" required>
                            </div>
                        @else
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> Entièrement payé
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">Notes de livraison (optionnel)</label>
                            <textarea name="delivery_notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Confirmer la livraison
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach
@endsection
