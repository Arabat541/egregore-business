@extends('layouts.app')

@section('title', $customer->full_name)

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person"></i> {{ $customer->full_name }}</h2>
    <a href="{{ route('cashier.customers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">Téléphone:</td>
                        <td>{{ $customer->phone }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Email:</td>
                        <td>{{ $customer->email ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Adresse:</td>
                        <td>{{ $customer->address ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Client depuis:</td>
                        <td>{{ $customer->created_at->format('d/m/Y') }}</td>
                    </tr>
                </table>
                @if($customer->notes)
                    <hr>
                    <small class="text-muted">{{ $customer->notes }}</small>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col">
                        <h3 class="mb-0">{{ $customer->sales->count() }}</h3>
                        <small class="text-muted">Achats</small>
                    </div>
                    <div class="col">
                        <h3 class="mb-0">{{ $customer->repairs->count() }}</h3>
                        <small class="text-muted">Réparations</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Dernières ventes -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-cart"></i> Derniers achats
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>N° Facture</th>
                                <th>Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->sales()->latest()->take(5)->get() as $sale)
                            <tr>
                                <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                                <td><code>{{ $sale->invoice_number }}</code></td>
                                <td>{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</td>
                                <td>
                                    @if($sale->payment_status === 'paid')
                                        <span class="badge bg-success">Payé</span>
                                    @else
                                        <span class="badge bg-warning">{{ $sale->payment_status }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-muted text-center">Aucun achat</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Dernières réparations -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-tools"></i> Réparations
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>N° Réparation</th>
                                <th>Appareil</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->repairs()->latest()->take(5)->get() as $repair)
                            <tr>
                                <td>{{ $repair->created_at->format('d/m/Y') }}</td>
                                <td><code>{{ $repair->repair_number }}</code></td>
                                <td>{{ $repair->device_brand }} {{ $repair->device_model }}</td>
                                <td>
                                    <span class="badge bg-{{ $repair->status_color }}">{{ $repair->status_label }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-muted text-center">Aucune réparation</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
