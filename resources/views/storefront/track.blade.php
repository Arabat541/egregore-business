@extends('storefront.layouts.app')

@section('title', 'Suivi de commande')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:80px;height:80px;background:rgba(99,102,241,.08);">
                    <i class="bi bi-search" style="font-size:2rem; color:var(--sf-primary);"></i>
                </div>
                <h2 style="font-weight:800; letter-spacing:-.5px; color:var(--sf-dark);">Suivre ma commande</h2>
                <p style="color:var(--sf-gray);">Entrez votre numéro de commande pour voir son état d'avancement.</p>
            </div>

            <div class="sf-card">
                <div class="card-body" style="padding:2rem;">
                    <form action="{{ route('storefront.track.search') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">Numéro de commande</label>
                            <input type="text" name="order_number" class="form-control form-control-lg" placeholder="Ex: WEB-20250327-0001" value="{{ old('order_number') }}" required
                                   style="border-radius:var(--sf-radius-sm); border-color:var(--sf-border); font-size:.95rem;">
                        </div>
                        <button type="submit" class="btn btn-sf-primary w-100" style="padding:.7rem;">
                            <i class="bi bi-search me-2"></i>Rechercher
                        </button>
                    </form>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="{{ route('storefront.home') }}" class="btn btn-sf-ghost" style="font-size:.85rem;">
                    <i class="bi bi-arrow-left me-1"></i>Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
