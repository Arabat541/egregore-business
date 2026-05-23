@extends('layouts.app')

@section('title', 'Dépense ' . $expense->reference)

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ $expense->reference }}</h1>
            <p class="text-muted mb-0">Détails de la dépense</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('cashier.expenses.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Retour
            </a>
            @if($expense->status !== 'approved')
                <a href="{{ route('cashier.expenses.edit', $expense) }}" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i> Modifier
                </a>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Informations principales -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-receipt me-2"></i>Informations
                    </h5>
                    <span class="badge bg-{{ $expense->status_color }} fs-6">
                        {{ $expense->status_label }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="text-muted small">Référence</label>
                            <p class="fw-bold mb-2">{{ $expense->reference }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Date de la dépense</label>
                            <p class="fw-bold mb-2">{{ $expense->expense_date->format('d/m/Y') }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Catégorie</label>
                            <p class="mb-2">
                                <span class="badge" style="background-color: {{ $expense->category->color }}">
                                    <i class="bi {{ $expense->category->icon ?? 'bi-tag' }} me-1"></i>
                                    {{ $expense->category->name }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Mode de paiement</label>
                            <p class="mb-2">
                                @switch($expense->payment_method)
                                    @case('cash')
                                        <span class="badge bg-success"><i class="bi bi-cash me-1"></i>Espèces</span>
                                        @break
                                    @case('bank_transfer')
                                        <span class="badge bg-info"><i class="bi bi-bank me-1"></i>Virement bancaire</span>
                                        @break
                                    @case('mobile_money')
                                        <span class="badge bg-warning text-dark"><i class="bi bi-phone me-1"></i>Mobile Money</span>
                                        @break
                                    @case('check')
                                        <span class="badge bg-secondary"><i class="bi bi-credit-card me-1"></i>Chèque</span>
                                        @break
                                @endswitch
                            </p>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small">Description</label>
                            <p class="fw-bold mb-2">{{ $expense->description }}</p>
                        </div>
                        @if($expense->beneficiary)
                            <div class="col-md-6">
                                <label class="text-muted small">Bénéficiaire</label>
                                <p class="mb-2">{{ $expense->beneficiary }}</p>
                            </div>
                        @endif
                        @if($expense->receipt_number)
                            <div class="col-md-6">
                                <label class="text-muted small">N° Reçu/Facture</label>
                                <p class="mb-2">{{ $expense->receipt_number }}</p>
                            </div>
                        @endif
                        @if($expense->notes)
                            <div class="col-12">
                                <label class="text-muted small">Notes</label>
                                <p class="mb-2">{{ $expense->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Image du reçu -->
            @if($expense->receipt_image)
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-image me-2"></i>Photo du reçu</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="{{ asset('storage/' . $expense->receipt_image) }}" 
                             alt="Reçu" class="img-fluid rounded" style="max-height: 400px">
                        <div class="mt-2">
                            <a href="{{ asset('storage/' . $expense->receipt_image) }}" 
                               target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Voir en grand
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Montant -->
            <div class="card shadow-sm bg-danger text-white mb-4">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Montant</h6>
                    <h2 class="mb-0">{{ number_format($expense->amount, 0, ',', ' ') }} F</h2>
                </div>
            </div>

            <!-- Informations de suivi -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Suivi</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Enregistrée par</label>
                        <p class="mb-0 fw-bold">{{ $expense->user->name }}</p>
                        <small class="text-muted">{{ $expense->created_at->format('d/m/Y à H:i') }}</small>
                    </div>

                    @if($expense->approver)
                        <div class="mb-3">
                            <label class="text-muted small">
                                {{ $expense->status === 'approved' ? 'Approuvée par' : 'Traitée par' }}
                            </label>
                            <p class="mb-0 fw-bold">{{ $expense->approver->name }}</p>
                            @if($expense->approved_at)
                                <small class="text-muted">{{ $expense->approved_at->format('d/m/Y à H:i') }}</small>
                            @endif
                        </div>
                    @endif

                    @if($expense->cashRegister)
                        <div class="mb-0">
                            <label class="text-muted small">Caisse</label>
                            <p class="mb-0">Session #{{ $expense->cashRegister->id }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Actions</h6>
                </div>
                <div class="card-body">
                    @if($expense->status !== 'approved')
                        <a href="{{ route('cashier.expenses.edit', $expense) }}" 
                           class="btn btn-warning w-100 mb-2">
                            <i class="bi bi-pencil me-1"></i> Modifier
                        </a>
                    @endif

                    <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Imprimer
                    </button>

                    @if($expense->status === 'pending')
                        <form action="{{ route('cashier.expenses.destroy', $expense) }}" method="POST" 
                              onsubmit="return confirm('Supprimer cette dépense ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-trash me-1"></i> Supprimer
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
