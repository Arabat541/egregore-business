@extends('layouts.app')

@section('title', 'Gestion de la caisse')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cash-stack"></i> Gestion de la caisse</h2>
</div>

@if($cashRegister)
    @php
        $totalIn = $cashRegister->transactions->where('type', '!=', 'cash_out')->sum('amount');
        $totalOut = abs($cashRegister->transactions->where('type', 'cash_out')->sum('amount'));
        $calculatedAmount = $cashRegister->opening_balance + $totalIn - $totalOut;
        $transactionCount = $cashRegister->transactions->count();
    @endphp

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5><i class="bi bi-unlock"></i> Caisse ouverte</h5>
                    <p class="mb-0">{{ $cashRegister->user->name }}</p>
                    <small>{{ $cashRegister->opened_at->format('d/m/Y H:i') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Fond initial</h6>
                    <h3>{{ number_format($cashRegister->opening_balance, 0, ',', ' ') }} FCFA</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">En caisse</h6>
                    <h3 class="text-primary">{{ number_format($calculatedAmount, 0, ',', ' ') }} FCFA</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">Actions</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <button class="btn btn-outline-success w-100" data-bs-toggle="modal" data-bs-target="#cashInModal">
                        <i class="bi bi-plus-circle"></i> Entree
                    </button>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#cashOutModal">
                        <i class="bi bi-dash-circle"></i> Sortie
                    </button>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#closeModal">
                        <i class="bi bi-lock"></i> Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">Transactions</div>
        <div class="card-body">
            <table class="table table-sm">
                <thead>
                    <tr><th>Heure</th><th>Type</th><th>Description</th><th>Montant</th></tr>
                </thead>
                <tbody>
                    @forelse($cashRegister->transactions->sortByDesc('created_at') as $t)
                    <tr>
                        <td>{{ $t->created_at->format('H:i') }}</td>
                        <td><span class="badge bg-{{ $t->type == 'cash_out' ? 'danger' : 'success' }}">{{ $t->type }}</span></td>
                        <td>{{ $t->description ?? '-' }}</td>
                        <td class="{{ $t->amount >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($t->amount, 0, ',', ' ') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted">Aucune transaction</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="cashInModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('cashier.cash-register.cash-in') }}" method="POST">
                    @csrf
                    <div class="modal-header"><h5>Entree de caisse</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label>Montant</label><input type="number" class="form-control" name="amount" required></div>
                        <div class="mb-3"><label>Description</label><input type="text" class="form-control" name="description"></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-success">Valider</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cashOutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('cashier.cash-register.cash-out') }}" method="POST">
                    @csrf
                    <div class="modal-header"><h5>Sortie de caisse</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label>Montant</label><input type="number" class="form-control" name="amount" required></div>
                        <div class="mb-3"><label>Motif</label><input type="text" class="form-control" name="description" required></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-danger">Valider</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="closeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('cashier.cash-register.close') }}" method="POST">
                    @csrf
                    <div class="modal-header"><h5>Fermer la caisse</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="alert alert-info">Theorique: {{ number_format($calculatedAmount, 0, ',', ' ') }} FCFA</div>
                        <div class="mb-3"><label>Montant compte</label><input type="number" class="form-control" name="closing_balance" required></div>
                        <div class="mb-3"><label>Notes</label><textarea class="form-control" name="closing_notes"></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-warning">Fermer</button></div>
                </form>
            </div>
        </div>
    </div>

@else
    <div class="text-center py-5">
        <i class="bi bi-lock display-1 text-muted"></i>
        <h3 class="mt-3">La caisse est fermee</h3>
        <p class="text-muted">Ouvrez la caisse pour commencer</p>
        <a href="{{ route('cashier.cash-register.open-form') }}" class="btn btn-success btn-lg">
            <i class="bi bi-unlock"></i> Ouvrir la caisse
        </a>
    </div>

    @if($history->count() > 0)
    <div class="card mt-4">
        <div class="card-header">Historique</div>
        <div class="card-body">
            <table class="table table-sm">
                <thead><tr><th>Date</th><th>Ouverture</th><th>Cloture</th><th>Initial</th><th>Final</th></tr></thead>
                <tbody>
                    @foreach($history as $r)
                    <tr>
                        <td>{{ $r->opened_at->format('d/m/Y') }}</td>
                        <td>{{ $r->opened_at->format('H:i') }}</td>
                        <td>{{ $r->closed_at ? $r->closed_at->format('H:i') : '-' }}</td>
                        <td>{{ number_format($r->opening_balance, 0, ',', ' ') }}</td>
                        <td>{{ $r->closing_balance ? number_format($r->closing_balance, 0, ',', ' ') : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $history->links() }}
        </div>
    </div>
    @endif
@endif
@endsection
