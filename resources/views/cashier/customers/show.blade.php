@extends('layouts.app')

@section('title', $customer->full_name . ' — Fiche client')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0"><i class="bi bi-person-circle"></i> {{ $customer->full_name }}</h2>
        <small class="text-muted">Client depuis {{ $customer->created_at->format('d/m/Y') }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('cashier.customers.edit', $customer) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil"></i> Modifier
        </a>
        <a href="{{ route('cashier.customers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-primary">{{ $stats['total_sales'] }}</div>
                <div class="small text-muted">Achats</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="fs-5 fw-bold text-success">{{ number_format($stats['total_sales_amount'], 0, ',', ' ') }} F</div>
                <div class="small text-muted">CA total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-warning">{{ $stats['total_repairs'] }}</div>
                <div class="small text-muted">Réparations</div>
                @if($stats['active_repairs'] > 0)
                    <span class="badge bg-warning text-dark small">{{ $stats['active_repairs'] }} en cours</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-info">{{ $stats['total_sav'] }}</div>
                <div class="small text-muted">Tickets SAV</div>
                @if($stats['open_sav'] > 0)
                    <span class="badge bg-info small">{{ $stats['open_sav'] }} ouvert(s)</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                @if($stats['credit_outstanding'] > 0)
                    <div class="fs-5 fw-bold text-danger">{{ number_format($stats['credit_outstanding'], 0, ',', ' ') }} F</div>
                    <div class="small text-muted">Crédit impayé</div>
                @else
                    <div class="fs-5 fw-bold text-success"><i class="bi bi-check-circle"></i></div>
                    <div class="small text-muted">Aucun impayé</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Colonne gauche : infos + dernière visite --}}
    <div class="col-md-3">
        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-info-circle me-1"></i>Coordonnées</div>
            <div class="card-body p-3">
                <dl class="row mb-0" style="font-size:.9rem;">
                    <dt class="col-5 text-muted">Téléphone</dt>
                    <dd class="col-7">
                        <a href="tel:{{ $customer->phone }}" class="text-decoration-none">{{ $customer->phone }}</a>
                    </dd>
                    @if($customer->email)
                    <dt class="col-5 text-muted">Email</dt>
                    <dd class="col-7">
                        <a href="mailto:{{ $customer->email }}" class="text-decoration-none text-truncate d-block">{{ $customer->email }}</a>
                    </dd>
                    @endif
                    @if($customer->address)
                    <dt class="col-5 text-muted">Adresse</dt>
                    <dd class="col-7">{{ $customer->address }}</dd>
                    @endif
                    @if($stats['last_visit'])
                    <dt class="col-5 text-muted">Dernière visite</dt>
                    <dd class="col-7">{{ \Carbon\Carbon::parse($stats['last_visit'])->format('d/m/Y') }}</dd>
                    @endif
                </dl>
                @if($customer->notes)
                    <hr class="my-2">
                    <small class="text-muted fst-italic">{{ $customer->notes }}</small>
                @endif
            </div>
        </div>

        <div class="d-grid gap-2">
            <a href="{{ route('cashier.sales.create') }}?customer_id={{ $customer->id }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-cart-plus"></i> Nouvelle vente
            </a>
            <a href="{{ route('cashier.repairs.create') }}?customer_id={{ $customer->id }}"
               class="btn btn-outline-primary btn-sm">
                <i class="bi bi-tools"></i> Nouvelle réparation
            </a>
        </div>
    </div>

    {{-- Colonne droite : historique complet --}}
    <div class="col-md-9">

        {{-- Onglets --}}
        <ul class="nav nav-tabs mb-3" id="customerTabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabSales">
                    <i class="bi bi-cart"></i> Ventes
                    <span class="badge bg-primary ms-1">{{ $stats['total_sales'] }}</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRepairs">
                    <i class="bi bi-tools"></i> Réparations
                    <span class="badge bg-warning text-dark ms-1">{{ $stats['total_repairs'] }}</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSav">
                    <i class="bi bi-ticket"></i> SAV
                    <span class="badge bg-info ms-1">{{ $stats['total_sav'] }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content">
            {{-- Onglet Ventes --}}
            <div class="tab-pane fade show active" id="tabSales">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>N° Facture</th>
                                <th>Articles</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Payé</th>
                                <th>Statut</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->sales as $sale)
                            <tr>
                                <td class="text-nowrap">{{ $sale->created_at->format('d/m/Y') }}</td>
                                <td><code>{{ $sale->invoice_number }}</code></td>
                                <td class="text-muted">{{ $sale->items->count() }} art.</td>
                                <td class="text-end fw-semibold">{{ number_format($sale->total_amount, 0, ',', ' ') }} F</td>
                                <td class="text-end">{{ number_format($sale->amount_paid, 0, ',', ' ') }} F</td>
                                <td>
                                    @if($sale->payment_status === 'paid')
                                        <span class="badge bg-success">Payé</span>
                                    @elseif($sale->payment_status === 'credit')
                                        <span class="badge bg-warning text-dark">Crédit</span>
                                    @else
                                        <span class="badge bg-danger">Annulé</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('cashier.sales.show', $sale) }}" class="btn btn-xs btn-outline-secondary py-0 px-1">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">Aucune vente</td></tr>
                            @endforelse
                        </tbody>
                        @if($customer->sales->isNotEmpty())
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">Total</td>
                                <td class="text-end">{{ number_format($customer->sales->where('payment_status', '!=', 'cancelled')->sum('total_amount'), 0, ',', ' ') }} F</td>
                                <td class="text-end">{{ number_format($customer->sales->where('payment_status', '!=', 'cancelled')->sum('amount_paid'), 0, ',', ' ') }} F</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Onglet Réparations --}}
            <div class="tab-pane fade" id="tabRepairs">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>N° Ticket</th>
                                <th>Appareil</th>
                                <th>Problème</th>
                                <th class="text-end">Coût</th>
                                <th>Statut</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->repairs as $repair)
                            @php
                                $statusColors = ['pending_payment'=>'warning','in_diagnosis'=>'info','in_repair'=>'primary','repaired'=>'success','ready_for_pickup'=>'success','delivered'=>'dark','cancelled'=>'danger','unrepairable'=>'danger'];
                                $statusLabels = ['pending_payment'=>'Attente pmt','in_diagnosis'=>'Diagnostic','in_repair'=>'En réparation','repaired'=>'Réparé','ready_for_pickup'=>'À livrer','delivered'=>'Livré','cancelled'=>'Annulé','unrepairable'=>'Irréparable'];
                            @endphp
                            <tr>
                                <td class="text-nowrap">{{ $repair->created_at->format('d/m/Y') }}</td>
                                <td><code>{{ $repair->repair_number }}</code></td>
                                <td>{{ $repair->device_brand }} {{ $repair->device_model }}</td>
                                <td class="text-muted small">{{ Str::limit($repair->problem_description, 35) }}</td>
                                <td class="text-end">{{ number_format($repair->final_cost ?? 0, 0, ',', ' ') }} F</td>
                                <td>
                                    <span class="badge bg-{{ $statusColors[$repair->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$repair->status] ?? $repair->status }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('cashier.repairs.show', $repair) }}" class="btn btn-xs btn-outline-secondary py-0 px-1">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">Aucune réparation</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Onglet SAV --}}
            <div class="tab-pane fade" id="tabSav">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>N° Ticket</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Statut</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($savTickets as $ticket)
                            <tr>
                                <td class="text-nowrap">{{ $ticket->created_at->format('d/m/Y') }}</td>
                                <td><code>{{ $ticket->ticket_number }}</code></td>
                                <td>{{ $ticket->type_name ?? $ticket->type }}</td>
                                <td class="text-muted small">{{ Str::limit($ticket->issue_description, 40) }}</td>
                                <td>
                                    <span class="badge bg-{{ in_array($ticket->status, ['resolved','closed']) ? 'success' : 'info' }}">
                                        {{ ucfirst($ticket->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('sav.show', $ticket) }}" class="btn btn-xs btn-outline-secondary py-0 px-1">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">Aucun ticket SAV</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
