@extends('layouts.app')

@section('title', 'Créances revendeurs')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-credit-card"></i> Créances revendeurs</h2>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h4 class="mb-0">{{ number_format($totalDebt, 0, ',', ' ') }} FCFA</h4>
                <small>Total des créances</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning">
            <div class="card-body text-center">
                <h4 class="mb-0">{{ $resellersWithDebt->count() }}</h4>
                <small>Revendeurs avec créances</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h4 class="mb-0">{{ number_format($todayPayments, 0, ',', ' ') }} FCFA</h4>
                <small>Paiements du jour</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        @php
            $todayReturns = \App\Models\ProductReturn::whereDate('created_at', today())->sum('total_value');
        @endphp
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h4 class="mb-0">{{ number_format($todayReturns, 0, ',', ' ') }} FCFA</h4>
                <small>Retours produits (jour)</small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-building"></i> Revendeurs avec créances
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Revendeur</th>
                        <th>Contact</th>
                        <th>Téléphone</th>
                        <th>Limite crédit</th>
                        <th>Dette actuelle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resellersWithDebt as $reseller)
                    <tr>
                        <td>
                            <strong>{{ $reseller->company_name }}</strong>
                            @if($reseller->reseller_code)
                                <br><small class="text-muted">{{ $reseller->reseller_code }}</small>
                            @endif
                        </td>
                        <td>{{ $reseller->contact_name }}</td>
                        <td>{{ $reseller->phone }}</td>
                        <td>{{ number_format($reseller->credit_limit, 0, ',', ' ') }} FCFA</td>
                        <td>
                            <span class="badge bg-danger fs-6">{{ number_format($reseller->current_debt, 0, ',', ' ') }} FCFA</span>
                        </td>
                        <td>
                            <a href="{{ route('cashier.reseller-payments.show', $reseller) }}" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i> Détails
                            </a>
                            <button type="button" class="btn btn-sm btn-success" 
                                    data-bs-toggle="modal" data-bs-target="#paymentModal{{ $reseller->id }}">
                                <i class="bi bi-cash"></i> Paiement
                            </button>
                        </td>
                    </tr>
                    
                    <!-- Modal Paiement -->
                    <div class="modal fade" id="paymentModal{{ $reseller->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('cashier.reseller-payments.store', $reseller) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="bi bi-cash"></i> Paiement - {{ $reseller->company_name }}
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="alert alert-info">
                                            <strong>Dette actuelle:</strong> {{ number_format($reseller->current_debt, 0, ',', ' ') }} FCFA
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Montant du paiement (FCFA) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control form-control-lg" name="cash_amount" 
                                                   min="1" max="{{ $reseller->current_debt }}" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Mode de paiement <span class="text-danger">*</span></label>
                                            <select class="form-select" name="payment_method_id" required>
                                                @foreach($paymentMethods as $method)
                                                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Notes</label>
                                            <textarea class="form-control" name="notes" rows="2" placeholder="Notes optionnelles..."></textarea>
                                        </div>
                                        
                                        <div class="alert alert-warning small">
                                            <i class="bi bi-info-circle"></i> Pour un paiement avec retour de produits, utilisez 
                                            <a href="{{ route('cashier.reseller-payments.create', $reseller) }}">le formulaire complet</a>.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-check-lg"></i> Enregistrer le paiement
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Aucune créance en cours</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
