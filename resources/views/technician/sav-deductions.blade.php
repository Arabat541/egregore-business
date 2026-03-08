@extends('layouts.app')

@section('title', 'Mes déductions SAV')

@section('sidebar')
    @include('technician.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-exclamation-triangle text-danger"></i> Mes déductions SAV</h2>
    <a href="{{ route('technician.dashboard') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<!-- Statistiques -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h3 class="text-danger mb-0">{{ number_format($stats['total_deductions'], 0, ',', ' ') }} F</h3>
                <small class="text-muted">Total déductions</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h3 class="text-warning mb-0">{{ number_format($stats['deductions_this_month'], 0, ',', ' ') }} F</h3>
                <small class="text-muted">Ce mois</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h3 class="text-info mb-0">{{ number_format($stats['deductions_today'], 0, ',', ' ') }} F</h3>
                <small class="text-muted">Aujourd'hui</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $stats['total_count'] }}</h3>
                <small class="text-muted">Pièces remplacées</small>
            </div>
        </div>
    </div>
</div>

<!-- Liste des déductions -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-list"></i> Historique des déductions</h5>
    </div>
    <div class="card-body">
        @if($savDeductions->isEmpty())
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle"></i> Aucune déduction SAV pour le moment.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Ticket SAV</th>
                            <th>Réparation</th>
                            <th>Client</th>
                            <th>Pièce défectueuse</th>
                            <th>Remplacement</th>
                            <th>Qté</th>
                            <th class="text-end text-danger">Déduction</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($savDeductions as $deduction)
                            <tr>
                                <td>
                                    <small>{{ $deduction->created_at->format('d/m/Y') }}</small>
                                    <br>
                                    <small class="text-muted">{{ $deduction->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $deduction->savTicket->ticket_number ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    @if($deduction->savTicket && $deduction->savTicket->repair)
                                        <span class="badge bg-info">
                                            {{ $deduction->savTicket->repair->repair_number }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($deduction->savTicket && $deduction->savTicket->repair && $deduction->savTicket->repair->customer)
                                        {{ $deduction->savTicket->repair->customer->full_name }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-danger">
                                        <i class="bi bi-x-circle"></i>
                                        {{ $deduction->defectiveProduct->name ?? 'Produit supprimé' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-success">
                                        <i class="bi bi-check-circle"></i>
                                        {{ $deduction->replacementProduct->name ?? 'Produit supprimé' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $deduction->quantity }}</span>
                                </td>
                                <td class="text-end">
                                    <strong class="text-danger">
                                        -{{ number_format($deduction->defective_part_cost, 0, ',', ' ') }} F
                                    </strong>
                                </td>
                            </tr>
                            @if($deduction->reason)
                                <tr class="table-light">
                                    <td colspan="8" class="small text-muted py-1 ps-4">
                                        <i class="bi bi-chat-text"></i> Raison: {{ $deduction->reason }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $savDeductions->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Explication -->
<div class="card mt-4">
    <div class="card-body">
        <h6 class="text-muted"><i class="bi bi-info-circle"></i> Comment fonctionnent les déductions SAV ?</h6>
        <p class="mb-0 small text-muted">
            Lorsqu'une pièce de rechange utilisée dans une réparation s'avère défectueuse et doit être remplacée 
            sous garantie (SAV), le coût de cette pièce est déduit de votre chiffre d'affaires. 
            Cela reflète le fait que la pièce n'a pas fonctionné correctement et a dû être échangée.
        </p>
    </div>
</div>
@endsection
