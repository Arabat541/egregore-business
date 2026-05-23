@extends('storefront.layouts.app')

@section('title', 'Suivi - ' . $order->order_number)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-4">
                <span style="font-size:.78rem; color:var(--sf-gray); text-transform:uppercase; letter-spacing:.5px; font-weight:600;">Suivi de commande</span>
                <h2 style="font-weight:800; letter-spacing:-.5px; color:var(--sf-dark);">{{ $order->order_number }}</h2>
            </div>

            {{-- Status timeline --}}
            <div class="sf-card mb-4">
                <div class="card-body" style="padding:2rem;">
                    @php
                        $allStatuses = ['pending', 'confirmed', 'processing', 'ready', 'shipped', 'delivered'];
                        $statusLabels = \App\Models\OnlineOrder::getStatusLabels();
                        $statusIcons = [
                            'pending' => 'bi-clock',
                            'confirmed' => 'bi-check-circle',
                            'processing' => 'bi-gear',
                            'ready' => 'bi-box-seam',
                            'shipped' => 'bi-truck',
                            'delivered' => 'bi-house-check',
                        ];
                        $currentIndex = array_search($order->status, $allStatuses);
                        $cancelled = $order->status === 'cancelled';
                    @endphp

                    @if($cancelled)
                        <div class="text-center py-3" style="background:rgba(239,68,68,.06); border-radius:var(--sf-radius-sm);">
                            <i class="bi bi-x-circle-fill" style="font-size:2rem; color:var(--sf-danger);"></i>
                            <div class="fw-bold mt-2" style="color:var(--sf-danger);">Commande annulée</div>
                        </div>
                    @else
                        <div class="d-flex justify-content-between position-relative mb-3" style="padding:0 10px;">
                            {{-- Progress line --}}
                            <div class="position-absolute" style="top:18px; left:50px; right:50px; height:3px; background:var(--sf-border); z-index:0; border-radius:2px;">
                                <div style="height:100%; width:{{ $currentIndex !== false ? ($currentIndex / (count($allStatuses) - 1)) * 100 : 0 }}%; background:linear-gradient(90deg,var(--sf-primary),var(--sf-primary-light)); border-radius:2px; transition:width .6s cubic-bezier(.4,0,.2,1);"></div>
                            </div>
                            @foreach($allStatuses as $i => $st)
                                @php
                                    $isComplete = $currentIndex !== false && $i <= $currentIndex;
                                    $isCurrent = $order->status === $st;
                                @endphp
                                <div class="text-center position-relative" style="z-index:1; flex:1;">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle"
                                         style="width:36px; height:36px; transition:var(--sf-transition);
                                         {{ $isComplete ? 'background:linear-gradient(135deg,var(--sf-primary),var(--sf-primary-light)); color:#fff;' : 'background:#fff; border:2px solid var(--sf-border); color:var(--sf-gray-light);' }}
                                         {{ $isCurrent ? 'box-shadow:0 0 0 4px rgba(99,102,241,.2);' : '' }}">
                                        <i class="bi {{ $statusIcons[$st] ?? 'bi-circle' }}" style="font-size:.85rem;"></i>
                                    </div>
                                    <div class="mt-1" style="font-size:.72rem; font-weight:{{ $isCurrent ? '700' : '500' }}; color:{{ $isCurrent ? 'var(--sf-primary)' : 'var(--sf-gray-light)' }};">
                                        {{ $statusLabels[$st] ?? $st }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Dates --}}
                    <div class="d-flex flex-wrap justify-content-center gap-3 mt-4 pt-3" style="border-top:1px solid var(--sf-border);">
                        <span style="font-size:.8rem; color:var(--sf-gray);">
                            <i class="bi bi-calendar3 me-1"></i>Commandé le {{ $order->created_at->format('d/m/Y à H:i') }}
                        </span>
                        @if($order->confirmed_at)
                            <span style="font-size:.8rem; color:var(--sf-gray);">
                                <i class="bi bi-check me-1"></i>Confirmé le {{ $order->confirmed_at->format('d/m/Y') }}
                            </span>
                        @endif
                        @if($order->shipped_at)
                            <span style="font-size:.8rem; color:var(--sf-gray);">
                                <i class="bi bi-truck me-1"></i>Expédié le {{ $order->shipped_at->format('d/m/Y') }}
                            </span>
                        @endif
                        @if($order->delivered_at)
                            <span style="font-size:.8rem; color:var(--sf-success);">
                                <i class="bi bi-house-check me-1"></i>Livré le {{ $order->delivered_at->format('d/m/Y') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Order details --}}
            <div class="sf-card">
                <div class="card-body" style="padding:2rem;">
                    <div class="row g-4 mb-4 pb-3" style="border-bottom:1px solid var(--sf-border);">
                        <div class="col-md-6">
                            <span style="font-size:.75rem; color:var(--sf-gray); text-transform:uppercase; letter-spacing:.5px; font-weight:600;">Client</span>
                            <div class="mt-2">
                                <div class="fw-bold mb-1">{{ $order->customer_name }}</div>
                                <div style="font-size:.9rem; color:var(--sf-gray);">{{ $order->customer_phone }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <span style="font-size:.75rem; color:var(--sf-gray); text-transform:uppercase; letter-spacing:.5px; font-weight:600;">Livraison & Paiement</span>
                            <div class="mt-2" style="font-size:.9rem; color:var(--sf-gray); line-height:1.8;">
                                {{ $order->delivery_label }}<br>
                                {{ $order->payment_label }}
                                <span class="d-inline-block px-2 py-0 ms-1" style="font-size:.75rem; font-weight:600; border-radius:4px;
                                    {{ $order->payment_status === 'paid' ? 'background:rgba(16,185,129,.1); color:#065f46;' : ($order->payment_status === 'refunded' ? 'background:rgba(239,68,68,.1); color:#991b1b;' : 'background:rgba(245,158,11,.1); color:#92400e;') }}">
                                    {{ $order->payment_status === 'paid' ? 'Payé' : ($order->payment_status === 'refunded' ? 'Remboursé' : 'En attente') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <span style="font-size:.75rem; color:var(--sf-gray); text-transform:uppercase; letter-spacing:.5px; font-weight:600;">Articles</span>
                    <div class="mt-2">
                        @foreach($order->items as $item)
                            <div class="d-flex justify-content-between align-items-center py-2" style="{{ !$loop->last ? 'border-bottom:1px solid var(--sf-border);' : '' }}">
                                <div>
                                    <span class="fw-semibold" style="font-size:.9rem;">{{ $item->product_name }}</span>
                                    <span style="color:var(--sf-gray); font-size:.85rem;">x {{ $item->quantity }}</span>
                                </div>
                                <span class="fw-semibold" style="font-size:.9rem;">{{ number_format($item->total_price, 0, ',', ' ') }} F</span>
                            </div>
                        @endforeach
                    </div>

                    <div style="height:1px; background:var(--sf-border); margin:1rem 0;"></div>
                    <div class="d-flex justify-content-between">
                        <span style="font-weight:800; font-size:1.1rem;">Total</span>
                        <span style="font-weight:800; font-size:1.1rem; color:var(--sf-primary);">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="{{ route('storefront.track') }}" class="btn btn-sf-outline">
                    <i class="bi bi-arrow-left me-1"></i>Autre commande
                </a>
                <a href="{{ route('storefront.home') }}" class="btn btn-sf-primary">
                    <i class="bi bi-house me-1"></i>Accueil
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
