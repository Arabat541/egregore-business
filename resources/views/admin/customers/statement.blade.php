@extends('layouts.app')

@section('title', 'Relevé de créance — ' . $reseller->company_name)

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-file-earmark-text me-2"></i>Relevé de Créance
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.reports.customers') }}">Clients & Revendeurs</a></li>
                    <li class="breadcrumb-item active">{{ $reseller->company_name }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.resellers.report-statement.pdf', $reseller) }}?start_date={{ $startDate }}&end_date={{ $endDate }}{{ $shopId ? '&shop_id='.$shopId : '' }}"
               class="btn btn-danger" target="_blank">
                <i class="bi bi-file-pdf me-1"></i>Exporter PDF
            </a>
            <a href="{{ route('admin.reports.customers') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.resellers.report-statement', $reseller) }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Boutique</label>
                    <select name="shop_id" class="form-select">
                        <option value="">Toutes les boutiques</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" {{ $shopId == $shop->id ? 'selected' : '' }}>{{ $shop->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Du</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Au</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Fiche revendeur + KPIs -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-warning bg-opacity-20 d-flex align-items-center justify-content-center me-3" style="width:52px;height:52px;font-size:1.4rem;">
                            <i class="bi bi-shop text-warning"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $reseller->company_name }}</h5>
                            <div class="text-muted small">
                                <i class="bi bi-person me-1"></i>{{ $reseller->contact_name }}
                            </div>
                            <div class="text-muted small">
                                <i class="bi bi-telephone me-1"></i>{{ $reseller->phone }}
                                @if($reseller->email)
                                    · <i class="bi bi-envelope me-1"></i>{{ $reseller->email }}
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Plafond crédit :</span>
                        <strong>{{ number_format($reseller->credit_limit, 0, ',', ' ') }} F</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-1">
                        <span>Dette totale actuelle :</span>
                        <strong class="{{ $reseller->current_debt > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($reseller->current_debt, 0, ',', ' ') }} F</strong>
                    </div>
                    <div class="text-muted small mt-2">
                        <strong>Période :</strong> {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="card border-0 shadow-sm h-100 bg-danger bg-opacity-10">
                <div class="card-body text-center py-4">
                    <div class="fw-bold fs-4 text-danger">{{ number_format($totalOutstanding, 0, ',', ' ') }} F</div>
                    <div class="text-muted small mt-1">Restant dû (période)</div>
                </div>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="fw-bold fs-5">{{ number_format($totalAmount, 0, ',', ' ') }} F</div>
                    <div class="text-muted small mt-1">Crédit accordé</div>
                </div>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="card border-0 shadow-sm h-100 bg-success bg-opacity-10">
                <div class="card-body text-center py-4">
                    <div class="fw-bold fs-5 text-success">{{ number_format($totalPaid, 0, ',', ' ') }} F</div>
                    <div class="text-muted small mt-1">Déjà payé</div>
                </div>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="fw-bold fs-5">{{ $sales->count() }}</div>
                    <div class="text-muted small mt-1">Commande(s)</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des commandes à crédit -->
    @if($sales->count() > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-cart me-2 text-primary"></i>Commandes à crédit</h5>
            <span class="badge bg-primary">{{ $sales->count() }} commande(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Date</th>
                            <th>N° Facture</th>
                            <th>Boutique</th>
                            <th>Vendeur</th>
                            <th>Produit(s)</th>
                            <th class="text-end">Montant</th>
                            <th class="text-end">Payé</th>
                            <th class="text-end text-danger">Restant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sales as $sale)
                        <tr>
                            <td class="ps-3">{{ $sale->created_at->format('d/m/Y') }}</td>
                            <td>
                                <span class="fw-bold">{{ $sale->invoice_number }}</span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $sale->shop->name ?? '—' }}</span>
                            </td>
                            <td class="text-muted small">{{ $sale->user->name ?? '—' }}</td>
                            <td>
                                @foreach($sale->items as $item)
                                    <div class="small">
                                        <i class="bi bi-box me-1 text-muted"></i>{{ $item->product->name ?? $item->product_name ?? '—' }}
                                        <span class="text-muted">×{{ $item->quantity }}</span>
                                    </div>
                                @endforeach
                            </td>
                            <td class="text-end fw-bold">{{ number_format($sale->total_amount, 0, ',', ' ') }} F</td>
                            <td class="text-end text-success">{{ number_format($sale->amount_paid, 0, ',', ' ') }} F</td>
                            <td class="text-end fw-bold text-danger">{{ number_format($sale->amount_due, 0, ',', ' ') }} F</td>
                            <td>
                                @if($sale->payment_status === 'credit')
                                    <span class="badge bg-danger">Crédit</span>
                                @else
                                    <span class="badge bg-warning text-dark">Partiel</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="5" class="ps-3 text-end">Sous-total ventes</td>
                            <td class="text-end">{{ number_format($totalAmount, 0, ',', ' ') }} F</td>
                            <td class="text-end text-success">{{ number_format($totalPaid, 0, ',', ' ') }} F</td>
                            <td class="text-end text-danger">{{ number_format($totalOutstanding, 0, ',', ' ') }} F</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Tableau des paiements reçus -->
    @if($payments->count() > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-cash-coin me-2 text-success"></i>Paiements reçus sur la période</h5>
            <span class="badge bg-success">{{ $payments->count() }} paiement(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Date</th>
                            <th>Facture liée</th>
                            <th>Encaissé par</th>
                            <th>Mode</th>
                            <th class="text-end">Montant</th>
                            <th class="text-end">Dette avant</th>
                            <th class="text-end text-success">Dette après</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                        <tr>
                            <td class="ps-3">{{ $payment->created_at->format('d/m/Y') }}</td>
                            <td>{{ $payment->sale->invoice_number ?? '—' }}</td>
                            <td class="text-muted small">{{ $payment->user->name ?? '—' }}</td>
                            <td><span class="badge bg-info text-dark">{{ $payment->payment_method ?? 'Espèces' }}</span></td>
                            <td class="text-end fw-bold text-success">{{ number_format($payment->amount, 0, ',', ' ') }} F</td>
                            <td class="text-end text-muted">{{ number_format($payment->debt_before, 0, ',', ' ') }} F</td>
                            <td class="text-end text-success fw-bold">{{ number_format($payment->debt_after, 0, ',', ' ') }} F</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="ps-3 text-end">Total paiements reçus</td>
                            <td class="text-end text-success">{{ number_format($totalPaymentsAmount, 0, ',', ' ') }} F</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($sales->count() === 0)
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-check-circle fs-1 text-success d-block mb-3"></i>
            Aucune créance en cours pour ce revendeur sur cette période.
        </div>
    </div>
    @endif

    <!-- Récapitulatif final -->
    @if($totalOutstanding > 0)
    <div class="card border-danger border-2 shadow-sm mt-2">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0 text-danger fw-bold">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        TOTAL DÛ PAR {{ strtoupper($reseller->company_name) }}
                    </h5>
                    <div class="text-muted small mt-1">
                        Période : {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                        · Dette totale actuelle : <strong>{{ number_format($reseller->current_debt, 0, ',', ' ') }} F</strong>
                    </div>
                </div>
                <div class="col-auto text-end">
                    <div class="fs-2 fw-bold text-danger">{{ number_format($totalOutstanding, 0, ',', ' ') }} F</div>
                    <div class="text-muted small">sur {{ number_format($totalAmount, 0, ',', ' ') }} F accordés (période)</div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
