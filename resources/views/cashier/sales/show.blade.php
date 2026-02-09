@extends('layouts.app')

@section('title', 'Détail vente ' . $sale->invoice_number)

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-receipt"></i> Vente {{ $sale->invoice_number }}</h2>
    <div>
        <a href="{{ route('cashier.sales.receipt', $sale) }}" class="btn btn-primary" target="_blank">
            <i class="bi bi-printer"></i> Imprimer ticket
        </a>
        <a href="{{ route('cashier.sales.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-cart"></i> Articles vendus
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-end">Prix unit.</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $item)
                        <tr>
                            <td>
                                {{ $item->product->name ?? 'Produit supprimé' }}
                                @if($item->product && $item->product->sku)
                                    <br><small class="text-muted">{{ $item->product->sku }}</small>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
                            <td class="text-end fw-bold">{{ number_format($item->total_price, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end">Sous-total:</td>
                            <td class="text-end">{{ number_format($sale->subtotal_amount, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        @if($sale->discount_amount > 0)
                        <tr>
                            <td colspan="3" class="text-end">Remise:</td>
                            <td class="text-end text-danger">-{{ number_format($sale->discount_amount, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        @endif
                        @if($sale->tax_amount > 0)
                        <tr>
                            <td colspan="3" class="text-end">TVA:</td>
                            <td class="text-end">{{ number_format($sale->tax_amount, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        @endif
                        <tr class="table-primary">
                            <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                            <td class="text-end"><strong>{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">N° Facture:</td>
                        <td><code>{{ $sale->invoice_number }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Date:</td>
                        <td>{{ $sale->created_at->format('d/m/Y à H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Caissière:</td>
                        <td>{{ $sale->user->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Statut:</td>
                        <td>
                            @if($sale->payment_status === 'paid')
                                <span class="badge bg-success">Payé</span>
                            @elseif($sale->payment_status === 'credit')
                                <span class="badge bg-warning">Crédit</span>
                            @else
                                <span class="badge bg-danger">Annulé</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-person"></i> Client
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">Type:</td>
                        <td>
                            @if($sale->client_type === 'walk-in')
                                Client comptoir
                            @elseif($sale->client_type === 'customer')
                                Client enregistré
                            @elseif($sale->client_type === 'reseller')
                                Revendeur
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Nom:</td>
                        <td>{{ $sale->client_name }}</td>
                    </tr>
                    @if($sale->customer)
                    <tr>
                        <td class="text-muted">Téléphone:</td>
                        <td>{{ $sale->customer->phone }}</td>
                    </tr>
                    @endif
                    @if($sale->reseller)
                    <tr>
                        <td class="text-muted">Téléphone:</td>
                        <td>{{ $sale->reseller->phone }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-credit-card"></i> Paiement
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">Mode:</td>
                        <td>{{ $sale->paymentMethod->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total:</td>
                        <td>{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Payé:</td>
                        <td class="text-success">{{ number_format($sale->amount_paid, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @if($sale->remaining_amount > 0)
                    <tr>
                        <td class="text-muted">Reste:</td>
                        <td class="text-danger fw-bold">{{ number_format($sale->remaining_amount, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        @if($sale->notes)
        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-sticky"></i> Notes
            </div>
            <div class="card-body">
                {{ $sale->notes }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
