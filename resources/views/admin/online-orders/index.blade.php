@extends('layouts.app')

@section('title', 'Commandes en ligne')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-globe me-2"></i>Commandes en ligne</h4>
            <small class="text-muted">Gestion des commandes de la boutique en ligne</small>
        </div>
        <a href="{{ route('storefront.home') }}" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i>Voir la boutique
        </a>
    </div>

    {{-- Status tabs --}}
    @php
        $statuses = \App\Models\OnlineOrder::getStatusLabels();
        $badges = \App\Models\OnlineOrder::getStatusBadgeClass();
        $totalOrders = $statusCounts->sum();
    @endphp
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('admin.online-orders.index') }}" class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">
            Toutes <span class="badge bg-light text-dark ms-1">{{ $totalOrders }}</span>
        </a>
        @foreach($statuses as $key => $label)
            <a href="{{ route('admin.online-orders.index', ['status' => $key]) }}"
               class="btn btn-sm {{ request('status') === $key ? 'btn-'.$badges[$key] : 'btn-outline-secondary' }}">
                {{ $label }} <span class="badge bg-light text-dark ms-1">{{ $statusCounts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    {{-- Search --}}
    <form class="mb-3" action="{{ route('admin.online-orders.index') }}" method="GET">
        @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
        <div class="input-group" style="max-width: 400px;">
            <input type="text" name="search" class="form-control" placeholder="Rechercher (n° commande, nom, tél)..." value="{{ request('search') }}">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
        </div>
    </form>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Commande</th>
                            <th>Client</th>
                            <th>Boutique</th>
                            <th>Articles</th>
                            <th class="text-end">Montant</th>
                            <th>Statut</th>
                            <th>Paiement</th>
                            <th>Livraison</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.online-orders.show', $order) }}" class="fw-bold text-primary text-decoration-none">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $order->customer_name }}</span>
                                    <br><small class="text-muted">{{ $order->customer_phone }}</small>
                                </td>
                                <td><small>{{ $order->shop->name ?? '-' }}</small></td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $order->items->count() }} article(s)</span>
                                </td>
                                <td class="text-end fw-bold">{{ number_format($order->total_amount, 0, ',', ' ') }} F</td>
                                <td>
                                    <span class="badge bg-{{ $badges[$order->status] ?? 'secondary' }}">{{ $statuses[$order->status] ?? $order->status }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'refunded' ? 'danger' : 'warning') }}">
                                        {{ $order->payment_status === 'paid' ? 'Payé' : ($order->payment_status === 'refunded' ? 'Remboursé' : 'En attente') }}
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        @if($order->delivery_method === 'pickup')
                                            <i class="bi bi-shop"></i> Retrait
                                        @else
                                            <i class="bi bi-truck"></i> Livraison
                                        @endif
                                    </small>
                                </td>
                                <td><small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small></td>
                                <td>
                                    <a href="{{ route('admin.online-orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Aucune commande trouvée
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $orders->links() }}
    </div>
</div>
@endsection
