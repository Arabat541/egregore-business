@extends('layouts.app')

@section('title', 'Détails Caisse')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-cash-stack me-2"></i>Détails de la Caisse
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.cash-registers.index') }}">Caisses</a></li>
                    <li class="breadcrumb-item active">{{ $cashRegister->date->format('d/m/Y') }}</li>
                </ol>
            </nav>
        </div>
        <div>
            @if($cashRegister->status === 'closed')
                <form action="{{ route('admin.cash-registers.reopen', $cashRegister) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Réouvrir cette caisse ?');">
                        <i class="bi bi-unlock me-1"></i>Réouvrir
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.cash-registers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Informations caisse -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-info-circle me-2"></i>Informations
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Date</dt>
                        <dd class="col-sm-7">
                            <strong>{{ $cashRegister->date->format('d/m/Y') }}</strong>
                            @if($cashRegister->date->isToday())
                                <span class="badge bg-primary">Aujourd'hui</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">Caissière</dt>
                        <dd class="col-sm-7">{{ $cashRegister->user?->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-5">Statut</dt>
                        <dd class="col-sm-7">
                            @if($cashRegister->status === 'open')
                                <span class="badge bg-success"><i class="bi bi-unlock"></i> Ouverte</span>
                            @else
                                <span class="badge bg-secondary"><i class="bi bi-lock"></i> Fermée</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">Ouverture</dt>
                        <dd class="col-sm-7">{{ $cashRegister->opened_at?->format('H:i:s') ?? '-' }}</dd>

                        <dt class="col-sm-5">Fermeture</dt>
                        <dd class="col-sm-7">{{ $cashRegister->closed_at?->format('H:i:s') ?? '-' }}</dd>
                    </dl>
                </div>
            </div>

            <!-- Soldes -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-calculator me-2"></i>Soldes
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Ouverture</dt>
                        <dd class="col-sm-6 text-end">{{ number_format($cashRegister->opening_balance, 0, ',', ' ') }} F</dd>

                        <dt class="col-sm-6">+ Entrées</dt>
                        <dd class="col-sm-6 text-end text-success">+{{ number_format($cashRegister->total_income, 0, ',', ' ') }} F</dd>

                        <dt class="col-sm-6">- Sorties</dt>
                        <dd class="col-sm-6 text-end text-danger">-{{ number_format($cashRegister->total_expense, 0, ',', ' ') }} F</dd>

                        <dt class="col-sm-6 border-top pt-2"><strong>= Solde attendu</strong></dt>
                        <dd class="col-sm-6 text-end border-top pt-2">
                            <strong>{{ number_format($cashRegister->calculated_balance, 0, ',', ' ') }} F</strong>
                        </dd>

                        @if($cashRegister->closing_balance !== null)
                        <dt class="col-sm-6">Solde déclaré</dt>
                        <dd class="col-sm-6 text-end">{{ number_format($cashRegister->closing_balance, 0, ',', ' ') }} F</dd>

                        <dt class="col-sm-6">Écart</dt>
                        <dd class="col-sm-6 text-end">
                            @if($cashRegister->difference > 0)
                                <span class="text-success">+{{ number_format($cashRegister->difference, 0, ',', ' ') }} F</span>
                            @elseif($cashRegister->difference < 0)
                                <span class="text-danger">{{ number_format($cashRegister->difference, 0, ',', ' ') }} F</span>
                            @else
                                <span class="text-success">0 F</span>
                            @endif
                        </dd>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Notes -->
            @if($cashRegister->opening_notes || $cashRegister->closing_notes)
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-sticky me-2"></i>Notes
                </div>
                <div class="card-body">
                    @if($cashRegister->opening_notes)
                        <p class="mb-2"><strong>Ouverture :</strong></p>
                        <p class="text-muted">{{ $cashRegister->opening_notes }}</p>
                    @endif
                    @if($cashRegister->closing_notes)
                        <p class="mb-2"><strong>Fermeture :</strong></p>
                        <p class="text-muted mb-0">{{ $cashRegister->closing_notes }}</p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Transactions -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-ul me-2"></i>Transactions ({{ $cashRegister->transactions->count() }})</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Heure</th>
                                    <th>Type</th>
                                    <th>Catégorie</th>
                                    <th>Description</th>
                                    <th>Mode</th>
                                    <th class="text-end">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cashRegister->transactions->sortByDesc('created_at') as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at->format('H:i:s') }}</td>
                                    <td>
                                        @if($transaction->type === 'income')
                                            <span class="badge bg-success">Entrée</span>
                                        @else
                                            <span class="badge bg-danger">Sortie</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($transaction->category)
                                            @case('sale')
                                                <i class="bi bi-cart text-primary me-1"></i>Vente
                                                @break
                                            @case('repair')
                                                <i class="bi bi-tools text-info me-1"></i>Réparation
                                                @break
                                            @case('reseller_payment')
                                                <i class="bi bi-building text-success me-1"></i>Paiement revendeur
                                                @break
                                            @case('expense')
                                                <i class="bi bi-cash-coin text-warning me-1"></i>Dépense
                                                @break
                                            @case('cash_in')
                                                <i class="bi bi-box-arrow-in-down text-success me-1"></i>Entrée caisse
                                                @break
                                            @case('cash_out')
                                                <i class="bi bi-box-arrow-up text-danger me-1"></i>Sortie caisse
                                                @break
                                            @default
                                                {{ $transaction->category }}
                                        @endswitch
                                    </td>
                                    <td>
                                        {{ $transaction->description ?? '-' }}
                                        @if($transaction->transactionable)
                                            <br>
                                            <small class="text-muted">
                                                @if($transaction->transactionable_type === 'App\Models\Sale')
                                                    Vente #{{ $transaction->transactionable->reference }}
                                                @elseif($transaction->transactionable_type === 'App\Models\Repair')
                                                    Réparation #{{ $transaction->transactionable->reference }}
                                                @endif
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($transaction->payment_method)
                                            @case('cash')
                                                <i class="bi bi-cash text-success me-1"></i>Espèces
                                                @break
                                            @case('mobile_money')
                                                <i class="bi bi-phone text-warning me-1"></i>Mobile Money
                                                @break
                                            @case('card')
                                                <i class="bi bi-credit-card text-info me-1"></i>Carte
                                                @break
                                            @case('credit')
                                                <i class="bi bi-clock text-secondary me-1"></i>Crédit
                                                @break
                                            @default
                                                {{ $transaction->payment_method }}
                                        @endswitch
                                    </td>
                                    <td class="text-end">
                                        @if($transaction->type === 'income')
                                            <span class="text-success">+{{ number_format($transaction->amount, 0, ',', ' ') }} F</span>
                                        @else
                                            <span class="text-danger">-{{ number_format($transaction->amount, 0, ',', ' ') }} F</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-1"></i>
                                        <p class="mb-0 mt-2">Aucune transaction</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($cashRegister->transactions->count() > 0)
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end"><strong>Total entrées :</strong></td>
                                    <td class="text-end text-success">
                                        <strong>+{{ number_format($cashRegister->total_income, 0, ',', ' ') }} F</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end"><strong>Total sorties :</strong></td>
                                    <td class="text-end text-danger">
                                        <strong>-{{ number_format($cashRegister->total_expense, 0, ',', ' ') }} F</strong>
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
