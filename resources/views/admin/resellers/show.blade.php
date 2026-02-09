@extends('layouts.app')

@section('title', $reseller->company_name)

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-building"></i> {{ $reseller->company_name }}</h2>
    <div>
        <a href="{{ route('admin.resellers.statement', $reseller) }}" class="btn btn-info">
            <i class="bi bi-file-text"></i> Relevé de compte
        </a>
        <a href="{{ route('admin.resellers.edit', $reseller) }}" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Modifier
        </a>
        <a href="{{ route('admin.resellers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th>Code</th>
                        <td><code>{{ $reseller->reseller_code ?: '-' }}</code></td>
                    </tr>
                    <tr>
                        <th>Contact</th>
                        <td>{{ $reseller->contact_name }}</td>
                    </tr>
                    <tr>
                        <th>Téléphone</th>
                        <td>{{ $reseller->phone }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $reseller->email ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Adresse</th>
                        <td>{{ $reseller->address ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Statut</th>
                        <td>
                            @if($reseller->is_active)
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-danger">Inactif</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-credit-card"></i> Conditions commerciales
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th>Limite crédit</th>
                        <td>{{ number_format($reseller->credit_limit, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <th>Crédit disponible</th>
                        <td class="text-success">{{ number_format($reseller->available_credit, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card {{ $reseller->current_debt > 0 ? 'border-danger' : 'border-success' }}">
            <div class="card-header {{ $reseller->current_debt > 0 ? 'bg-danger text-white' : 'bg-success text-white' }}">
                <i class="bi bi-wallet2"></i> Situation financière
            </div>
            <div class="card-body text-center">
                <h2 class="{{ $reseller->current_debt > 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($reseller->current_debt, 0, ',', ' ') }} FCFA
                </h2>
                <p class="text-muted mb-0">Dette actuelle</p>
                <hr>
                <p class="mb-0">
                    <small>
                        Crédit disponible: 
                        <strong>{{ number_format($reseller->credit_limit - $reseller->current_debt, 0, ',', ' ') }} FCFA</strong>
                    </small>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Programme de fidélité -->
<div class="card mt-4">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-award"></i> Programme de Fidélité
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3 border-end">
                <span class="badge bg-{{ $reseller->loyalty_tier_color }} fs-5 px-4 py-2">{{ $reseller->loyalty_tier }}</span>
                <p class="text-muted mb-0 mt-2">Niveau actuel</p>
            </div>
            <div class="col-md-3 border-end">
                <h4 class="mb-0">{{ number_format($reseller->total_purchases_year, 0, ',', ' ') }} F</h4>
                <p class="text-muted mb-0">Achats {{ now()->year }}</p>
            </div>
            <div class="col-md-3 border-end">
                <h4 class="mb-0">{{ number_format($reseller->loyalty_points, 0, ',', ' ') }}</h4>
                <p class="text-muted mb-0">Points fidélité</p>
            </div>
            <div class="col-md-3">
                <h4 class="mb-0 text-success">{{ $reseller->loyalty_bonus_rate }}%</h4>
                <p class="text-muted mb-0">Taux bonus</p>
                <small class="text-success fw-bold">≈ {{ number_format($reseller->expected_bonus, 0, ',', ' ') }} F</small>
            </div>
        </div>
    </div>
    <div class="card-footer text-center">
        <a href="{{ route('admin.resellers.loyalty') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-trophy"></i> Voir le classement fidélité
        </a>
    </div>
</div>

<!-- Historique des ventes -->
<div class="card mt-4">
    <div class="card-header">
        <i class="bi bi-receipt"></i> Historique des ventes
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>N° Facture</th>
                        <th>Montant</th>
                        <th>Payé</th>
                        <th>Reste</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reseller->sales()->latest()->take(10)->get() as $sale)
                    <tr>
                        <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                        <td><code>{{ $sale->invoice_number }}</code></td>
                        <td>{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</td>
                        <td>{{ number_format($sale->amount_paid, 0, ',', ' ') }} FCFA</td>
                        <td>
                            @if($sale->remaining_amount > 0)
                                <span class="text-danger">{{ number_format($sale->remaining_amount, 0, ',', ' ') }} FCFA</span>
                            @else
                                <span class="text-success">0</span>
                            @endif
                        </td>
                        <td>
                            @if($sale->payment_status === 'paid')
                                <span class="badge bg-success">Payé</span>
                            @elseif($sale->payment_status === 'credit')
                                <span class="badge bg-warning">Crédit</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Aucune vente</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Historique des paiements -->
<div class="card mt-4">
    <div class="card-header">
        <i class="bi bi-cash"></i> Historique des paiements
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Montant</th>
                        <th>Mode</th>
                        <th>Reçu par</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reseller->payments()->latest()->take(10)->get() as $payment)
                    <tr>
                        <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                        <td><code>{{ $payment->reference }}</code></td>
                        <td class="text-success fw-bold">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                        <td>{{ $payment->paymentMethod->name ?? '-' }}</td>
                        <td>{{ $payment->user->name ?? '-' }}</td>
                        <td>{{ $payment->notes ?: '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Aucun paiement</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
