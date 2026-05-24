@extends('layouts.app')

@section('title', 'Relevé de Compte - ' . $reseller->company_name)

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="bi bi-file-text me-2"></i>Relevé de Compte
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.resellers.index') }}">Réparateurs</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.resellers.show', $reseller) }}">{{ $reseller->company_name }}</a></li>
                    <li class="breadcrumb-item active">Relevé</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.resellers.export-statement', ['reseller' => $reseller, 'start_date' => $startDate, 'end_date' => $endDate, 'shop_id' => $shopId]) }}"
               class="btn btn-danger">
                <i class="bi bi-file-pdf me-1"></i>Exporter PDF
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Imprimer
            </button>
            <a href="{{ route('admin.resellers.show', $reseller) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>

    <!-- Filtres de période -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Date début</label>
                    <input type="date" name="start_date" class="form-control"
                           value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="end_date" class="form-control"
                           value="{{ $endDate }}">
                </div>
                @if($shops->isNotEmpty())
                <div class="col-md-3">
                    <label class="form-label">Boutique</label>
                    <select name="shop_id" class="form-select">
                        <option value="">Toutes les boutiques</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" @selected($shopId === $shop->id)>
                                {{ $shop->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter me-1"></i>Filtrer
                    </button>
                    <a href="{{ route('admin.resellers.statement', $reseller) }}" class="btn btn-outline-secondary">
                        Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Informations du réparateur -->
    <div class="card border-0 shadow-sm mb-4" id="print-header">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3">{{ $reseller->company_name }}</h5>
                    <p class="mb-1"><strong>Contact :</strong> {{ $reseller->contact_name }}</p>
                    <p class="mb-1"><strong>Téléphone :</strong> {{ $reseller->phone }}</p>
                    @if($reseller->email)
                    <p class="mb-1"><strong>Email :</strong> {{ $reseller->email }}</p>
                    @endif
                    @if($reseller->address)
                    <p class="mb-0"><strong>Adresse :</strong> {{ $reseller->address }}</p>
                    @endif
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1"><strong>Période :</strong> {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                    <p class="mb-1"><strong>Date d'édition :</strong> {{ now()->format('d/m/Y H:i') }}</p>
                    @if($reseller->loyalty_tier !== 'Nouveau')
                    <p class="mb-1"><strong>Palier fidélité :</strong>
                        {{ $reseller->loyalty_tier }}
                        @if($reseller->loyalty_bonus_rate > 0)({{ $reseller->loyalty_bonus_rate }}% bonus)@endif
                    </p>
                    @endif
                    <div class="mt-3">
                        <span class="badge bg-{{ $reseller->current_debt > 0 ? 'danger' : 'success' }} fs-5">
                            Créance actuelle : {{ number_format($reseller->current_debt, 0, ',', ' ') }} F
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Résumé de la période -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Total Achats</h6>
                    <h3>{{ number_format($summary['total_purchases'], 0, ',', ' ') }} F</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Total Paiements</h6>
                    <h3>{{ number_format($summary['total_payments'], 0, ',', ' ') }} F</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Remise Obtenue</h6>
                    <h3>{{ number_format($summary['total_discount'], 0, ',', ' ') }} F</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-{{ $summary['balance'] > 0 ? 'danger' : 'success' }} text-white">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Créance Période</h6>
                    <h3>{{ number_format($summary['balance'], 0, ',', ' ') }} F</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des mouvements -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Détail des Mouvements</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="font-size: 0.88rem;">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:110px">Date</th>
                            <th style="width:90px">Type</th>
                            <th>Référence / Produit</th>
                            @if($shops->isNotEmpty())
                            <th style="width:110px">Boutique</th>
                            @endif
                            <th class="text-center" style="width:60px">Qté</th>
                            <th class="text-end" style="width:120px">Prix unit.</th>
                            <th class="text-end" style="width:110px">Débit (Achat)</th>
                            <th class="text-end" style="width:110px">Crédit (Paiement)</th>
                            <th class="text-end" style="width:120px">Créance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $runningBalance = $openingBalance; @endphp

                        <!-- Créance d'ouverture -->
                        <tr class="table-secondary fw-semibold">
                            <td>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</td>
                            <td><span class="badge bg-secondary">Ouverture</span></td>
                            <td>Créance d'ouverture</td>
                            @if($shops->isNotEmpty())<td>—</td>@endif
                            <td></td>
                            <td></td>
                            <td class="text-end">—</td>
                            <td class="text-end">—</td>
                            <td class="text-end">{{ number_format($openingBalance, 0, ',', ' ') }} F</td>
                        </tr>

                        @forelse($movements as $movement)
                            @php
                                if ($movement['type'] === 'sale') {
                                    $runningBalance += $movement['debit'];
                                } else {
                                    $runningBalance = max(0.0, $runningBalance - $movement['credit']);
                                }
                                $isSale     = $movement['type'] === 'sale';
                                $hasProducts = $isSale && !empty($movement['products']);
                            @endphp

                            {{-- Ligne principale du mouvement --}}
                            <tr class="{{ $isSale ? 'table-warning' : 'table-success bg-opacity-10' }} fw-semibold">
                                <td>{{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }}</td>
                                <td>
                                    @if($isSale)
                                        <span class="badge bg-warning text-dark">Achat</span>
                                    @else
                                        <span class="badge bg-success">Paiement</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-bold">{{ $movement['reference'] }}</span>
                                    <span class="text-muted fw-normal ms-2">{{ $movement['description'] }}</span>
                                    @if($isSale && isset($movement['shop']))
                                        <small class="text-muted d-block">{{ $movement['shop'] }}</small>
                                    @endif
                                </td>
                                @if($shops->isNotEmpty())
                                <td class="text-muted small">{{ $movement['shop'] ?? '—' }}</td>
                                @endif
                                <td></td>
                                <td></td>
                                <td class="text-end text-danger">
                                    @if($movement['debit'] > 0)
                                        {{ number_format($movement['debit'], 0, ',', ' ') }} F
                                    @else —
                                    @endif
                                </td>
                                <td class="text-end text-success">
                                    @if($movement['credit'] > 0)
                                        {{ number_format($movement['credit'], 0, ',', ' ') }} F
                                    @else —
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($runningBalance, 0, ',', ' ') }} F</td>
                            </tr>

                            {{-- Sous-lignes produits pour les ventes --}}
                            @if($hasProducts)
                                @foreach($movement['products'] as $product)
                                <tr style="background:#fffde7; font-size:0.83rem;">
                                    <td class="text-muted ps-3">
                                        {{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }}
                                    </td>
                                    <td class="text-muted ps-3">
                                        <i class="bi bi-box-seam text-muted"></i>
                                    </td>
                                    <td class="ps-4">
                                        └ {{ $product['name'] }}
                                        @if(isset($product['discount']) && $product['discount'] > 0)
                                            <small class="text-warning ms-1">(-{{ number_format($product['discount'], 0, ',', ' ') }} F remise)</small>
                                        @endif
                                    </td>
                                    @if($shops->isNotEmpty())<td></td>@endif
                                    <td class="text-center text-muted">{{ $product['quantity'] }}</td>
                                    <td class="text-end text-muted">{{ number_format($product['unit_price'], 0, ',', ' ') }} F</td>
                                    <td class="text-end text-muted">{{ number_format($product['total'], 0, ',', ' ') }} F</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                @endforeach
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ $shops->isNotEmpty() ? 9 : 8 }}" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Aucun mouvement sur cette période
                                </td>
                            </tr>
                        @endforelse

                        <!-- Créance de clôture -->
                        <tr class="table-dark fw-bold">
                            <td>{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</td>
                            <td><span class="badge bg-dark">Clôture</span></td>
                            <td>Créance de clôture</td>
                            @if($shops->isNotEmpty())<td></td>@endif
                            <td></td>
                            <td></td>
                            <td class="text-end">{{ number_format($summary['total_purchases'], 0, ',', ' ') }} F</td>
                            <td class="text-end">{{ number_format($summary['total_payments'], 0, ',', ' ') }} F</td>
                            <td class="text-end">{{ number_format($runningBalance, 0, ',', ' ') }} F</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($payments->isNotEmpty())
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-cash-coin me-2 text-success"></i>Versements reçus sur la période</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="font-size:0.88rem;">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:110px">Date</th>
                            <th>Référence</th>
                            <th>Mode</th>
                            <th class="text-end" style="width:140px">Montant</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $p)
                        <tr class="{{ (float)($p->debt_before ?? 0) <= 0 ? 'table-light text-muted' : 'table-success bg-opacity-10' }}">
                            <td>{{ $p->created_at->format('d/m/Y') }}</td>
                            <td>{{ $p->reference ?? 'PAY-' . $p->id }}</td>
                            <td><span class="badge bg-info text-dark">{{ $p->payment_method ?? 'Espèces' }}</span></td>
                            <td class="text-end fw-bold {{ (float)($p->debt_before ?? 0) <= 0 ? 'text-muted' : 'text-success' }}">
                                {{ number_format($p->amount, 0, ',', ' ') }} F
                            </td>
                            <td class="text-muted small">
                                @if((float)($p->debt_before ?? 0) <= 0)
                                    <em>Avance (dette = 0 au moment du versement)</em>
                                @else
                                    {{ $p->notes ?? '' }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-secondary fw-bold">
                        <tr>
                            <td colspan="3">Total versements</td>
                            <td class="text-end">{{ number_format($payments->sum('amount'), 0, ',', ' ') }} F</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
    @media print {
        .btn, .breadcrumb, nav, .sidebar, form, .collapse:not(.show) {
            display: none !important;
        }
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
        .table {
            font-size: 10px;
        }
    }
</style>
@endpush
@endsection
