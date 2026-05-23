@extends('storefront.layouts.app')

@section('title', 'Commande confirmée')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            {{-- Success header --}}
            <div class="text-center mb-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:90px; height:90px; background:rgba(16,185,129,.08);">
                    <i class="bi bi-check-circle-fill" style="font-size:2.8rem; color:var(--sf-success);"></i>
                </div>
                <h2 style="font-weight:800; letter-spacing:-.5px; color:var(--sf-dark);">Commande confirmée !</h2>
                <p style="color:var(--sf-gray); max-width:400px; margin:0 auto;">Merci pour votre confiance. Nous vous contacterons rapidement.</p>
            </div>

            <div class="sf-card">
                <div class="card-body" style="padding:2rem;">
                    {{-- Order info --}}
                    <div class="d-flex justify-content-between align-items-start mb-4 pb-3" style="border-bottom:1px solid var(--sf-border);">
                        <div>
                            <span style="font-size:.78rem; color:var(--sf-gray); text-transform:uppercase; letter-spacing:.5px; font-weight:600;">N° de commande</span>
                            <h4 class="mb-0 mt-1" style="font-weight:800; color:var(--sf-primary);">{{ $order->order_number }}</h4>
                        </div>
                        <span class="badge bg-{{ $order->status_badge }}" style="font-size:.8rem; padding:.4rem .8rem; border-radius:6px;">{{ $order->status_label }}</span>
                    </div>

                    {{-- Customer & delivery info --}}
                    <div class="row g-4 mb-4 pb-3" style="border-bottom:1px solid var(--sf-border);">
                        <div class="col-md-6">
                            <span style="font-size:.75rem; color:var(--sf-gray); text-transform:uppercase; letter-spacing:.5px; font-weight:600;">Client</span>
                            <div class="mt-2">
                                <div class="fw-bold mb-1">{{ $order->customer_name }}</div>
                                <div style="font-size:.9rem; color:var(--sf-gray); line-height:1.8;">
                                    <i class="bi bi-telephone me-1" style="color:var(--sf-primary-light);"></i>{{ $order->customer_phone }}<br>
                                    @if($order->customer_email)
                                        <i class="bi bi-envelope me-1" style="color:var(--sf-primary-light);"></i>{{ $order->customer_email }}<br>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <span style="font-size:.75rem; color:var(--sf-gray); text-transform:uppercase; letter-spacing:.5px; font-weight:600;">Livraison & Paiement</span>
                            <div class="mt-2" style="font-size:.9rem; color:var(--sf-gray); line-height:1.8;">
                                <i class="bi bi-truck me-1" style="color:var(--sf-primary-light);"></i>{{ $order->delivery_label }}<br>
                                <i class="bi bi-wallet2 me-1" style="color:var(--sf-primary-light);"></i>{{ $order->payment_label }}<br>
                                @if($order->customer_address)
                                    <i class="bi bi-geo-alt me-1" style="color:var(--sf-primary-light);"></i>{{ $order->customer_address }} {{ $order->customer_city }}
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Items --}}
                    <span style="font-size:.75rem; color:var(--sf-gray); text-transform:uppercase; letter-spacing:.5px; font-weight:600;">Articles commandés</span>
                    <div class="mt-2">
                        @foreach($order->items as $item)
                            <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? '' : '' }}" style="{{ !$loop->last ? 'border-bottom:1px solid var(--sf-border);' : '' }}">
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

                    @if($order->notes)
                        <div class="mt-3 p-3" style="background:var(--sf-light); border-radius:var(--sf-radius-sm);">
                            <small style="color:var(--sf-gray);"><i class="bi bi-chat-left-text me-1"></i>{{ $order->notes }}</small>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Tracking reminder --}}
            <div class="text-center mt-4">
                <p class="small mb-2" style="color:var(--sf-gray);">Conservez votre numéro de commande pour suivre son état :</p>
                <div class="d-inline-block p-3 mb-3" style="background:rgba(99,102,241,.06); border-radius:var(--sf-radius-sm); border:1px dashed rgba(99,102,241,.2);">
                    <code style="font-size:1.15rem; font-weight:700; color:var(--sf-primary);">{{ $order->order_number }}</code>
                </div>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="{{ route('storefront.track') }}" class="btn btn-sf-outline">
                        <i class="bi bi-search me-1"></i>Suivre ma commande
                    </a>
                    <a href="{{ route('storefront.home') }}" class="btn btn-sf-primary">
                        <i class="bi bi-house me-1"></i>Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
