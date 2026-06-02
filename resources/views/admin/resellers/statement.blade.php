@extends('layouts.app')

@section('title', 'Relevé — ' . $reseller->company_name)

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
@php
    $itemDiscounts   = $sales->flatMap->items->sum('discount');
    $globalDiscounts = $summary['total_discount'];
    $totalDiscounts  = $itemDiscounts + $globalDiscounts;
    $grossTotal      = $sales->flatMap->items->sum(fn($i) => $i->unit_price * $i->quantity);
@endphp

<div class="container-fluid">

    {{-- En-tête --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-file-text me-2"></i>Relevé de compte</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.resellers.index') }}">Réparateurs</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.resellers.show', $reseller) }}">{{ $reseller->company_name }}</a></li>
                    <li class="breadcrumb-item active">Relevé</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('admin.resellers.export-statement', ['reseller' => $reseller, 'start_date' => $startDate, 'end_date' => $endDate, 'shop_id' => $shopId]) }}"
               class="btn btn-danger btn-sm">
                <i class="bi bi-file-pdf me-1"></i>PDF
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer me-1"></i>Imprimer
            </button>
            <a href="{{ route('admin.resellers.show', $reseller) }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Date début</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Date fin</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>
                @if($shops->isNotEmpty())
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Boutique</label>
                    <select name="shop_id" class="form-select form-select-sm">
                        <option value="">Toutes les boutiques</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" @selected($shopId === $shop->id)>{{ $shop->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm me-1">
                        <i class="bi bi-filter me-1"></i>Filtrer
                    </button>
                    <a href="{{ route('admin.resellers.statement', $reseller) }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Identité réparateur --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-1">{{ $reseller->company_name }}</h4>
                    <div class="text-muted small">
                        <i class="bi bi-person me-1"></i>{{ $reseller->contact_name }}
                        @if($reseller->phone)
                            &nbsp;·&nbsp;<i class="bi bi-telephone me-1"></i>{{ $reseller->phone }}
                        @endif
                        @if($reseller->email)
                            &nbsp;·&nbsp;<i class="bi bi-envelope me-1"></i>{{ $reseller->email }}
                        @endif
                    </div>
                    @if($reseller->loyalty_tier && $reseller->loyalty_tier !== 'Nouveau')
                        <div class="mt-1">
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-star-fill me-1"></i>{{ $reseller->loyalty_tier }}
                                @if($reseller->loyalty_bonus_rate > 0)(+{{ $reseller->loyalty_bonus_rate }}%)@endif
                            </span>
                        </div>
                    @endif
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="text-muted small mb-1">
                        Période : <strong>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</strong>
                        au <strong>{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</strong>
                    </div>
                    <div class="text-muted small mb-2">Édité le {{ now()->format('d/m/Y à H:i') }}</div>
                    <span class="badge fs-6 bg-{{ $reseller->current_debt > 0 ? 'danger' : 'success' }}">
                        <i class="bi bi-{{ $reseller->current_debt > 0 ? 'exclamation-circle' : 'check-circle' }} me-1"></i>
                        Dette actuelle : {{ number_format($reseller->current_debt, 0, ',', ' ') }} F
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- 4 cartes résumé --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small mb-1"><i class="bi bi-cart3 me-1"></i>Total achats</div>
                    <div class="fw-bold fs-4 text-primary">{{ number_format($summary['total_purchases'], 0, ',', ' ') }} F</div>
                    @if($grossTotal > $summary['total_purchases'])
                    <div class="text-muted" style="font-size:.78rem;">
                        Brut : {{ number_format($grossTotal, 0, ',', ' ') }} F
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small mb-1"><i class="bi bi-tag me-1"></i>Total remises</div>
                    <div class="fw-bold fs-4 text-info">{{ number_format($totalDiscounts, 0, ',', ' ') }} F</div>
                    @if($totalDiscounts > 0)
                    <div class="text-muted" style="font-size:.78rem;">
                        @if($itemDiscounts > 0)Produits : {{ number_format($itemDiscounts, 0, ',', ' ') }} F@endif
                        @if($itemDiscounts > 0 && $globalDiscounts > 0) · @endif
                        @if($globalDiscounts > 0)Globales : {{ number_format($globalDiscounts, 0, ',', ' ') }} F@endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small mb-1"><i class="bi bi-cash-stack me-1"></i>Total payé</div>
                    <div class="fw-bold fs-4 text-success">{{ number_format($summary['total_payments'], 0, ',', ' ') }} F</div>
                    @if($payments->isNotEmpty())
                    <div class="text-muted" style="font-size:.78rem;">{{ $payments->count() }} versement(s)</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small mb-1"><i class="bi bi-hourglass-split me-1"></i>Reste à payer</div>
                    <div class="fw-bold fs-4 text-{{ $summary['balance'] > 0 ? 'danger' : 'success' }}">
                        {{ number_format($summary['balance'], 0, ',', ' ') }} F
                    </div>
                    @if($openingBalance > 0)
                    <div class="text-muted" style="font-size:.78rem;">Ouverture : {{ number_format($openingBalance, 0, ',', ' ') }} F</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Section 1 : Achats de la période ───────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-bag me-2 text-primary"></i>Achats de la période</h5>
            <span class="badge bg-primary rounded-pill">{{ $sales->count() }} facture(s)</span>
        </div>

        @if($sales->isEmpty())
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-inbox fs-2 d-block mb-2"></i>Aucun achat sur cette période
        </div>
        @else
        <div class="accordion accordion-flush" id="salesAccordion">
            @foreach($sales as $idx => $sale)
            @php
                $saleItemDiscounts   = $sale->items->sum('discount');
                $saleGlobalDiscount  = (float) ($sale->discount_amount ?? 0);
                $saleTotalDiscounts  = $saleItemDiscounts + $saleGlobalDiscount;
                $saleGrossTotal      = $sale->items->sum(fn($i) => $i->unit_price * $i->quantity);
                $salePaid            = (float) $sale->amount_paid;
                $saleRemaining       = max(0, (float) $sale->total_amount - $salePaid);
            @endphp
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2" type="button"
                            data-bs-toggle="collapse" data-bs-target="#sale-{{ $sale->id }}">
                        <div class="d-flex w-100 align-items-center gap-3 flex-wrap" style="font-size:.92rem;">
                            {{-- Date --}}
                            <span class="text-muted" style="min-width:85px;">
                                {{ $sale->created_at->format('d/m/Y') }}
                            </span>
                            {{-- Numéro --}}
                            <span class="fw-bold font-monospace">{{ $sale->invoice_number }}</span>
                            {{-- Nb articles --}}
                            <span class="badge bg-secondary">{{ $sale->items->count() }} article(s)</span>
                            {{-- Remises --}}
                            @if($saleTotalDiscounts > 0)
                                <span class="badge bg-info text-dark">
                                    <i class="bi bi-tag me-1"></i>Remise : {{ number_format($saleTotalDiscounts, 0, ',', ' ') }} F
                                </span>
                            @endif
                            {{-- Total --}}
                            <span class="ms-auto fw-bold text-primary">
                                {{ number_format($sale->total_amount, 0, ',', ' ') }} F
                            </span>
                            {{-- Statut paiement --}}
                            @if($saleRemaining <= 0)
                                <span class="badge bg-success">Payé</span>
                            @elseif($salePaid > 0)
                                <span class="badge bg-warning text-dark">Partiel</span>
                            @else
                                <span class="badge bg-danger">Non payé</span>
                            @endif
                        </div>
                    </button>
                </h2>
                <div id="sale-{{ $sale->id }}" class="accordion-collapse collapse">
                    <div class="accordion-body p-0">
                        <table class="table table-sm mb-0" style="font-size:.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-center">Qté</th>
                                    <th class="text-end">Prix unit.</th>
                                    <th class="text-end text-info">Remise</th>
                                    <th class="text-end">Total ligne</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sale->items as $item)
                                <tr>
                                    <td>{{ $item->product->name ?? '—' }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end text-muted">{{ number_format($item->unit_price, 0, ',', ' ') }} F</td>
                                    <td class="text-end text-info fw-semibold">
                                        {{ $item->discount > 0 ? '- ' . number_format($item->discount, 0, ',', ' ') . ' F' : '—' }}
                                    </td>
                                    <td class="text-end fw-semibold">{{ number_format($item->total_price, 0, ',', ' ') }} F</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-semibold" style="font-size:.88rem;">
                                @if($saleItemDiscounts > 0)
                                <tr>
                                    <td colspan="3" class="text-end text-muted">Sous-total brut</td>
                                    <td colspan="2" class="text-end text-muted">{{ number_format($saleGrossTotal, 0, ',', ' ') }} F</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end text-info">Remises produits</td>
                                    <td colspan="2" class="text-end text-info">- {{ number_format($saleItemDiscounts, 0, ',', ' ') }} F</td>
                                </tr>
                                @endif
                                @if($saleGlobalDiscount > 0)
                                <tr>
                                    <td colspan="3" class="text-end text-info">Remise globale</td>
                                    <td colspan="2" class="text-end text-info">- {{ number_format($saleGlobalDiscount, 0, ',', ' ') }} F</td>
                                </tr>
                                @endif
                                <tr class="table-primary">
                                    <td colspan="3" class="text-end">Total facture</td>
                                    <td colspan="2" class="text-end text-primary fs-6">{{ number_format($sale->total_amount, 0, ',', ' ') }} F</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end text-muted">Déjà payé</td>
                                    <td colspan="2" class="text-end text-success">{{ number_format($salePaid, 0, ',', ' ') }} F</td>
                                </tr>
                                @if($saleRemaining > 0)
                                <tr>
                                    <td colspan="3" class="text-end text-danger">Reste à régler</td>
                                    <td colspan="2" class="text-end text-danger fw-bold">{{ number_format($saleRemaining, 0, ',', ' ') }} F</td>
                                </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── Section 2 : Historique des paiements ───────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-cash-coin me-2 text-success"></i>Historique des paiements</h5>
            <span class="badge bg-success rounded-pill">{{ $payments->count() }} versement(s)</span>
        </div>

        @if($payments->isEmpty())
        <div class="card-body text-center text-muted py-4">
            <i class="bi bi-cash fs-2 d-block mb-2"></i>Aucun paiement reçu sur cette période
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.9rem;">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Mode de paiement</th>
                        <th class="text-end">Montant versé</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $p)
                    <tr class="{{ (float)($p->debt_before ?? 0) <= 0 ? 'text-muted' : '' }}">
                        <td>{{ $p->created_at->format('d/m/Y') }}</td>
                        <td class="font-monospace small">{{ $p->reference ?? 'PAY-' . str_pad($p->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td>
                            <span class="badge bg-{{ $p->payment_method === 'cash' ? 'success' : 'info text-dark' }}">
                                {{ $p->payment_method ?? 'Espèces' }}
                            </span>
                        </td>
                        <td class="text-end fw-bold text-success">
                            {{ number_format($p->amount, 0, ',', ' ') }} F
                        </td>
                        <td class="small text-muted">
                            @if((float)($p->debt_before ?? 0) <= 0)
                                <em>Avance (aucune dette au moment du versement)</em>
                            @else
                                {{ $p->notes ?? '' }}
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="3">Total versements reçus</td>
                        <td class="text-end text-success">{{ number_format($payments->sum('amount'), 0, ',', ' ') }} F</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

    {{-- ── Section 3 : Bilan de la période ────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-calculator me-2"></i>Bilan de la période</h5>
        </div>
        <div class="card-body">
            <div class="col-md-5 mx-auto">
                <table class="table table-borderless mb-0" style="font-size:.95rem;">
                    <tr>
                        <td class="text-muted">Dette d'ouverture</td>
                        <td class="text-end fw-semibold">{{ number_format($openingBalance, 0, ',', ' ') }} F</td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="bi bi-plus-circle text-danger me-1"></i>Achats de la période</td>
                        <td class="text-end fw-semibold text-danger">+ {{ number_format($summary['total_purchases'], 0, ',', ' ') }} F</td>
                    </tr>
                    @if($totalDiscounts > 0)
                    <tr>
                        <td class="text-muted"><i class="bi bi-tag text-info me-1"></i>Remises obtenues</td>
                        <td class="text-end fw-semibold text-info">
                            @if($itemDiscounts > 0)
                                <small class="d-block text-muted">Produits : - {{ number_format($itemDiscounts, 0, ',', ' ') }} F</small>
                            @endif
                            @if($globalDiscounts > 0)
                                <small class="d-block text-muted">Globales : - {{ number_format($globalDiscounts, 0, ',', ' ') }} F</small>
                            @endif
                            <span class="text-info">déjà incluses dans les achats</span>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted"><i class="bi bi-dash-circle text-success me-1"></i>Paiements reçus</td>
                        <td class="text-end fw-semibold text-success">- {{ number_format($summary['total_payments'], 0, ',', ' ') }} F</td>
                    </tr>
                    <tr class="border-top">
                        <td class="fw-bold fs-5">Solde restant dû</td>
                        <td class="text-end fw-bold fs-5 text-{{ $summary['balance'] > 0 ? 'danger' : 'success' }}">
                            {{ number_format($summary['balance'], 0, ',', ' ') }} F
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

</div>

@push('styles')
<style>
    .accordion-button:not(.collapsed) { background: #f0f7ff; color: #0d6efd; }
    .accordion-button::after { flex-shrink: 0; }
    @@media print {
        .no-print, .btn, .breadcrumb, nav, .sidebar, form, .accordion-button::after { display: none !important; }
        .accordion-collapse { display: block !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; }
        .table { font-size: 10px; }
    }
</style>
@endpush
@endsection
