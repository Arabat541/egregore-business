@extends('storefront.layouts.app')

@section('title', 'Finaliser la commande')

@section('styles')
    .sf-step { display: flex; align-items: center; gap: .5rem; padding: .5rem 1rem; border-radius: 50px; font-size: .82rem; font-weight: 600; }
    .sf-step.active { background: rgba(99,102,241,.08); color: var(--sf-primary); }
    .sf-step.done { color: var(--sf-success); }
    .sf-step:not(.active):not(.done) { color: var(--sf-gray-light); }
    .sf-step .step-num { width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; border: 2px solid currentColor; }
    .sf-step.active .step-num { background: var(--sf-primary); color: #fff; border-color: var(--sf-primary); }
    .sf-step.done .step-num { background: var(--sf-success); color: #fff; border-color: var(--sf-success); }
    .sf-checkout-card { background: #fff; border: 1px solid var(--sf-border); border-radius: var(--sf-radius); padding: 1.5rem; margin-bottom: 1.25rem; transition: var(--sf-transition); }
    .sf-checkout-card:hover { box-shadow: var(--sf-shadow); }
    .sf-checkout-card h5 { font-size: 1rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: .5rem; }
    .sf-checkout-card h5 i { color: var(--sf-primary); }
    .sf-radio-card { border: 2px solid var(--sf-border); border-radius: var(--sf-radius-sm); padding: 1rem; cursor: pointer; transition: var(--sf-transition); text-align: center; }
    .sf-radio-card:hover { border-color: var(--sf-primary-light); }
    .sf-radio-card.selected { border-color: var(--sf-primary); background: rgba(99,102,241,.04); }
    .sf-radio-card .radio-icon { width: 48px; height: 48px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: .5rem; }
@endsection

@section('content')
<div class="container py-4 py-lg-5">
    {{-- Steps indicator --}}
    <div class="d-flex justify-content-center gap-2 mb-4">
        <div class="sf-step done"><span class="step-num"><i class="bi bi-check"></i></span> Panier</div>
        <i class="bi bi-chevron-right" style="color:var(--sf-border);align-self:center;"></i>
        <div class="sf-step active"><span class="step-num">2</span> Commande</div>
        <i class="bi bi-chevron-right" style="color:var(--sf-border);align-self:center;"></i>
        <div class="sf-step"><span class="step-num">3</span> Confirmation</div>
    </div>

    <form action="{{ route('storefront.checkout.store') }}" method="POST">
        @csrf
        <div class="row g-4">
            {{-- Form --}}
            <div class="col-lg-7">
                {{-- Informations client --}}
                <div class="sf-checkout-card">
                    <h5><i class="bi bi-person-circle"></i> Vos informations</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror" value="{{ old('customer_name') }}" required style="border-radius:var(--sf-radius-sm);border-color:var(--sf-border);">
                            @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">Téléphone <span class="text-danger">*</span></label>
                            <input type="tel" name="customer_phone" class="form-control @error('customer_phone') is-invalid @enderror" value="{{ old('customer_phone') }}" required placeholder="Ex: 07 XX XX XX XX" style="border-radius:var(--sf-radius-sm);border-color:var(--sf-border);">
                            @error('customer_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">Email</label>
                            <input type="email" name="customer_email" class="form-control @error('customer_email') is-invalid @enderror" value="{{ old('customer_email') }}" style="border-radius:var(--sf-radius-sm);border-color:var(--sf-border);">
                            @error('customer_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">Ville</label>
                            <input type="text" name="customer_city" class="form-control @error('customer_city') is-invalid @enderror" value="{{ old('customer_city') }}" style="border-radius:var(--sf-radius-sm);border-color:var(--sf-border);">
                            @error('customer_city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">Adresse de livraison</label>
                            <input type="text" name="customer_address" class="form-control @error('customer_address') is-invalid @enderror" value="{{ old('customer_address') }}" style="border-radius:var(--sf-radius-sm);border-color:var(--sf-border);">
                            @error('customer_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- Mode de livraison --}}
                <div class="sf-checkout-card">
                    <h5><i class="bi bi-truck"></i> Mode de livraison</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="sf-radio-card d-block" id="pickup-card">
                                <input class="d-none" type="radio" name="delivery_method" value="pickup" id="pickup" {{ old('delivery_method', 'pickup') == 'pickup' ? 'checked' : '' }} onchange="updateRadioCards()">
                                <div class="radio-icon" style="background:rgba(99,102,241,.08); color:var(--sf-primary);">
                                    <i class="bi bi-shop"></i>
                                </div>
                                <div class="fw-bold" style="font-size:.9rem;">Retrait en boutique</div>
                                <div style="color:var(--sf-gray); font-size:.8rem;">Gratuit</div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="sf-radio-card d-block" id="delivery-card">
                                <input class="d-none" type="radio" name="delivery_method" value="delivery" id="delivery" {{ old('delivery_method') == 'delivery' ? 'checked' : '' }} onchange="updateRadioCards()">
                                <div class="radio-icon" style="background:rgba(16,185,129,.08); color:var(--sf-success);">
                                    <i class="bi bi-truck"></i>
                                </div>
                                <div class="fw-bold" style="font-size:.9rem;">Livraison à domicile</div>
                                <div style="color:var(--sf-gray); font-size:.8rem;">Livraison à votre adresse</div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Mode de paiement --}}
                <div class="sf-checkout-card">
                    <h5><i class="bi bi-wallet2"></i> Mode de paiement</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="sf-radio-card d-block" id="cod-card">
                                <input class="d-none" type="radio" name="payment_method" value="cash_on_delivery" id="cod" {{ old('payment_method', 'cash_on_delivery') == 'cash_on_delivery' ? 'checked' : '' }} onchange="updateRadioCards()">
                                <div class="radio-icon" style="background:rgba(16,185,129,.08); color:var(--sf-success);">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                                <div class="fw-bold" style="font-size:.82rem;">À la livraison</div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="sf-radio-card d-block" id="momo-card">
                                <input class="d-none" type="radio" name="payment_method" value="mobile_money" id="momo" {{ old('payment_method') == 'mobile_money' ? 'checked' : '' }} onchange="updateRadioCards()">
                                <div class="radio-icon" style="background:rgba(245,158,11,.08); color:var(--sf-accent);">
                                    <i class="bi bi-phone"></i>
                                </div>
                                <div class="fw-bold" style="font-size:.82rem;">Mobile Money</div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="sf-radio-card d-block" id="bank-card">
                                <input class="d-none" type="radio" name="payment_method" value="bank_transfer" id="bank" {{ old('payment_method') == 'bank_transfer' ? 'checked' : '' }} onchange="updateRadioCards()">
                                <div class="radio-icon" style="background:rgba(99,102,241,.08); color:var(--sf-primary);">
                                    <i class="bi bi-bank"></i>
                                </div>
                                <div class="fw-bold" style="font-size:.82rem;">Virement</div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="sf-checkout-card">
                    <h5><i class="bi bi-chat-left-text"></i> Notes <span class="fw-normal" style="color:var(--sf-gray-light); font-size:.8rem;">(optionnel)</span></h5>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Instructions spéciales pour votre commande..." style="border-radius:var(--sf-radius-sm);border-color:var(--sf-border);">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- Order summary --}}
            <div class="col-lg-5">
                <div class="sf-card" style="position:sticky; top:80px;">
                    <div class="card-body" style="padding:1.5rem;">
                        <h5 class="fw-bold mb-3" style="font-size:1.05rem;">Votre commande</h5>

                        <div class="d-flex flex-column gap-2 mb-3">
                            @foreach($cartItems as $item)
                                <div class="d-flex justify-content-between align-items-start gap-2 pb-2" style="border-bottom:1px solid var(--sf-border);">
                                    <div class="d-flex gap-2 align-items-start">
                                        <div class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;background:var(--sf-light);border-radius:8px;">
                                            <i class="bi bi-phone" style="color:var(--sf-gray-light); font-size:.9rem;"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold" style="font-size:.85rem; line-height:1.3;">{{ Str::limit($item['product']->name, 30) }}</div>
                                            <div style="font-size:.78rem; color:var(--sf-gray);">{{ $item['quantity'] }} x {{ number_format($item['product']->normal_price, 0, ',', ' ') }} F</div>
                                        </div>
                                    </div>
                                    <span class="fw-semibold" style="font-size:.85rem; white-space:nowrap;">{{ number_format($item['line_total'], 0, ',', ' ') }} F</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-between mb-2" style="font-size:.9rem;">
                            <span style="color:var(--sf-gray);">Sous-total</span>
                            <span class="fw-semibold">{{ number_format($total, 0, ',', ' ') }} F</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3" style="font-size:.9rem;">
                            <span style="color:var(--sf-gray);">Livraison</span>
                            <span style="color:var(--sf-success); font-weight:600;">Gratuit</span>
                        </div>
                        <div style="height:1px; background:var(--sf-border);"></div>
                        <div class="d-flex justify-content-between my-3">
                            <span class="fw-bold" style="font-size:1.1rem;">Total</span>
                            <span class="fw-bold" style="font-size:1.1rem; color:var(--sf-primary);">{{ number_format($total, 0, ',', ' ') }} FCFA</span>
                        </div>

                        <button type="submit" class="btn btn-sf-primary w-100" style="padding:.75rem; font-size:.95rem;">
                            <i class="bi bi-check-circle me-2"></i>Confirmer la commande
                        </button>

                        <a href="{{ route('storefront.cart') }}" class="btn btn-sf-ghost w-100 mt-2" style="font-size:.85rem;">
                            <i class="bi bi-arrow-left me-1"></i>Retour au panier
                        </a>

                        <div class="text-center mt-3">
                            <small style="color:var(--sf-gray-light); font-size:.75rem;">
                                <i class="bi bi-shield-check me-1"></i>Vos données sont protégées
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function updateRadioCards() {
    document.querySelectorAll('.sf-radio-card').forEach(card => {
        const input = card.querySelector('input[type="radio"]');
        card.classList.toggle('selected', input && input.checked);
    });
}
updateRadioCards();
</script>
@endsection
