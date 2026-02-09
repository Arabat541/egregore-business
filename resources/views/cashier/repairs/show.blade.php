@extends('layouts.app')

@section('title', 'Réparation ' . $repair->repair_number)

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="bi bi-tools"></i> {{ $repair->repair_number }}
        <span class="badge bg-{{ $repair->status_color }}">{{ $repair->status_label }}</span>
    </h2>
    <div>
        <a href="{{ route('cashier.repairs.ticket', $repair) }}" class="btn btn-primary" target="_blank">
            <i class="bi bi-printer"></i> Ticket
        </a>
        <a href="{{ route('cashier.repairs.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Informations client -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-person"></i> Client
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nom:</strong> {{ $repair->customer->full_name }}</p>
                        <p><strong>Téléphone:</strong> {{ $repair->customer->phone }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Email:</strong> {{ $repair->customer->email ?: '-' }}</p>
                        <p><strong>Adresse:</strong> {{ $repair->customer->address ?: '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations appareil -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-phone"></i> Appareil
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Type:</strong> {{ ucfirst($repair->device_type) }}</p>
                        <p><strong>Marque:</strong> {{ $repair->device_brand }}</p>
                        <p><strong>Modèle:</strong> {{ $repair->device_model }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>IMEI/Série:</strong> {{ $repair->device_serial ?: '-' }}</p>
                        <p><strong>Code:</strong> {{ $repair->device_password ?: '-' }}</p>
                        <p><strong>Accessoires:</strong> {{ $repair->accessories ?: '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Problème -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-exclamation-triangle"></i> Problème signalé
            </div>
            <div class="card-body">
                <p>{{ $repair->reported_issue }}</p>
                @if($repair->physical_condition)
                    <hr>
                    <p class="mb-0"><strong>État physique:</strong> {{ $repair->physical_condition }}</p>
                @endif
            </div>
        </div>

        <!-- Diagnostic -->
        @if($repair->diagnosis)
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <i class="bi bi-search"></i> Diagnostic
            </div>
            <div class="card-body">
                {{ $repair->diagnosis }}
            </div>
        </div>
        @endif

        <!-- Travaux effectués -->
        @if($repair->work_done)
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <i class="bi bi-check-circle"></i> Travaux effectués
            </div>
            <div class="card-body">
                {{ $repair->work_done }}
            </div>
        </div>
        @endif

        <!-- Pièces utilisées -->
        @if($repair->parts->count() > 0)
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-box-seam"></i> Pièces utilisées
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Pièce</th>
                            <th>Qté</th>
                            <th>Prix unit.</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($repair->parts as $part)
                        <tr>
                            <td>{{ $part->product->name ?? $part->description }}</td>
                            <td>{{ $part->quantity }}</td>
                            <td>{{ number_format($part->unit_cost, 0, ',', ' ') }} FCFA</td>
                            <td>{{ number_format($part->total_cost, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3">Total pièces:</th>
                            <th>{{ number_format($repair->parts->sum('total_cost'), 0, ',', ' ') }} FCFA</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <!-- Statut et dates -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-clock-history"></i> Suivi
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td>Créée le:</td>
                        <td>{{ $repair->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @if($repair->started_at)
                    <tr>
                        <td>Démarrée le:</td>
                        <td>{{ $repair->started_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endif
                    @if($repair->completed_at)
                    <tr>
                        <td>Terminée le:</td>
                        <td>{{ $repair->completed_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endif
                    @if($repair->delivered_at)
                    <tr>
                        <td>Livrée le:</td>
                        <td>{{ $repair->delivered_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>Retrait estimé:</td>
                        <td>{{ $repair->estimated_completion_date ? $repair->estimated_completion_date->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td>Technicien:</td>
                        <td>{{ $repair->technician->name ?? 'Non assigné' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Facturation -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-currency-dollar"></i> Facturation
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    @if($repair->parts_cost > 0)
                    <tr>
                        <td>Pièces:</td>
                        <td>{{ number_format($repair->parts_cost, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @endif
                    @if($repair->labor_cost > 0)
                    <tr>
                        <td>Main d'œuvre:</td>
                        <td>{{ number_format($repair->labor_cost, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @endif
                    <tr class="table-secondary">
                        <td><strong>Total:</strong></td>
                        <td><strong>{{ number_format($repair->final_cost ?: $repair->estimated_cost, 0, ',', ' ') }} FCFA</strong></td>
                    </tr>
                    <tr class="table-success">
                        <td>Montant payé:</td>
                        <td>{{ number_format($repair->amount_paid, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @php
                        $totalCost = $repair->final_cost ?: $repair->estimated_cost;
                        $restant = $totalCost - $repair->amount_paid;
                    @endphp
                    @if($restant > 0)
                    <tr class="table-warning">
                        <td><strong>Reste à payer:</strong></td>
                        <td><strong>{{ number_format($restant, 0, ',', ' ') }} FCFA</strong></td>
                    </tr>
                    @else
                    <tr class="table-success">
                        <td colspan="2" class="text-center"><strong>✅ PAYÉ EN TOTALITÉ</strong></td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Actions -->
        @if($repair->status === 'pending_payment')
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-cash-coin"></i> Paiement acompte
            </div>
            <div class="card-body">
                <form action="{{ route('cashier.repairs.pay', $repair) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Montant estimé</label>
                        <input type="text" class="form-control" value="{{ number_format($repair->estimated_cost, 0, ',', ' ') }} FCFA" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Acompte à verser <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="deposit_amount" 
                               value="{{ $repair->estimated_cost }}" min="0" step="100" required>
                        <small class="text-muted">Montant minimum conseillé: {{ number_format($repair->estimated_cost * 0.5, 0, ',', ' ') }} FCFA (50%)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mode de paiement <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_method_id" required>
                            <option value="">-- Sélectionner --</option>
                            @foreach($paymentMethods ?? [] as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="bi bi-check-lg"></i> Valider le paiement
                    </button>
                </form>
            </div>
        </div>
        @endif

        @if($repair->status === 'ready')
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-check-circle"></i> Livraison
            </div>
            <div class="card-body">
                <form action="{{ route('cashier.repairs.deliver', $repair) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Montant à payer</label>
                        <input type="number" class="form-control" name="paid_amount" 
                               value="{{ ($repair->final_cost ?: $repair->estimated_cost) - $repair->deposit_amount }}" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mode de paiement</label>
                        <select class="form-select" name="payment_method_id" required>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-lg"></i> Confirmer la livraison
                    </button>
                </form>
            </div>
        </div>
        @endif

        @if($repair->internal_notes)
        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-sticky"></i> Notes internes
            </div>
            <div class="card-body">
                {{ $repair->internal_notes }}
            </div>
        </div>
        @endif
    </div>
</div>

@if(session('print_ticket') && session('ticket_url'))
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ouvrir le ticket en popup pour impression
    const ticketUrl = "{{ session('ticket_url') }}";
    const printWindow = window.open(ticketUrl, 'PrintTicket', 'width=400,height=600,scrollbars=yes');
    if (printWindow) {
        printWindow.focus();
    }
});
</script>
@endif
@endsection
