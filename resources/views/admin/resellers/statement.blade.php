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
                    <li class="breadcrumb-item"><a href="{{ route('admin.resellers.index') }}">Revendeurs</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.resellers.show', $reseller) }}">{{ $reseller->company_name }}</a></li>
                    <li class="breadcrumb-item active">Relevé</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.resellers.export-statement', ['reseller' => $reseller, 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
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

    <!-- Informations du revendeur -->
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
                    <p class="mb-1"><strong>Remise appliquée :</strong> {{ $reseller->discount_percentage }}%</p>
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
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Référence</th>
                            <th>Description</th>
                            <th class="text-end">Débit (Achats)</th>
                            <th class="text-end">Crédit (Paiements)</th>
                            <th class="text-end">Créance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $runningBalance = $openingBalance; @endphp
                        
                        <!-- Créance d'ouverture -->
                        <tr class="table-secondary">
                            <td>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</td>
                            <td><span class="badge bg-secondary">Ouverture</span></td>
                            <td>-</td>
                            <td>Créance d'ouverture</td>
                            <td class="text-end">-</td>
                            <td class="text-end">-</td>
                            <td class="text-end fw-bold">{{ number_format($openingBalance, 0, ',', ' ') }} F</td>
                        </tr>

                        @forelse($movements as $movement)
                            @php
                                if ($movement['type'] === 'sale') {
                                    $runningBalance += $movement['debit'];
                                } else {
                                    $runningBalance -= $movement['credit'];
                                }
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($movement['type'] === 'sale')
                                        <span class="badge bg-warning text-dark">Achat</span>
                                    @else
                                        <span class="badge bg-success">Paiement</span>
                                    @endif
                                </td>
                                <td>
                                    @if($movement['type'] === 'sale' && isset($movement['sale_id']))
                                        <a href="{{ route('cashier.sales.show', $movement['sale_id']) }}" target="_blank">
                                            #{{ $movement['reference'] }}
                                        </a>
                                    @else
                                        {{ $movement['reference'] }}
                                    @endif
                                </td>
                                <td>
                                    {{ $movement['description'] }}
                                    @if(isset($movement['products']) && count($movement['products']) > 0)
                                        <button class="btn btn-sm btn-link p-0 ms-2" type="button" 
                                                data-bs-toggle="collapse" data-bs-target="#products-{{ $loop->index }}">
                                            <i class="bi bi-eye"></i> Détails
                                        </button>
                                        <div class="collapse mt-2" id="products-{{ $loop->index }}">
                                            <ul class="list-unstyled small text-muted mb-0">
                                                @foreach($movement['products'] as $product)
                                                    <li>• {{ $product['name'] }} x{{ $product['quantity'] }} = {{ number_format($product['total'], 0, ',', ' ') }} F</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end text-danger">
                                    @if($movement['debit'] > 0)
                                        {{ number_format($movement['debit'], 0, ',', ' ') }} F
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end text-success">
                                    @if($movement['credit'] > 0)
                                        {{ number_format($movement['credit'], 0, ',', ' ') }} F
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end fw-bold">{{ number_format($runningBalance, 0, ',', ' ') }} F</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Aucun mouvement sur cette période
                                </td>
                            </tr>
                        @endforelse

                        <!-- Créance de clôture -->
                        <tr class="table-dark">
                            <td>{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</td>
                            <td><span class="badge bg-dark">Clôture</span></td>
                            <td>-</td>
                            <td>Créance de clôture</td>
                            <td class="text-end fw-bold">{{ number_format($summary['total_purchases'], 0, ',', ' ') }} F</td>
                            <td class="text-end fw-bold">{{ number_format($summary['total_payments'], 0, ',', ' ') }} F</td>
                            <td class="text-end fw-bold">{{ number_format($runningBalance, 0, ',', ' ') }} F</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
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
