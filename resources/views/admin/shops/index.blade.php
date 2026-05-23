@extends('layouts.app')

@section('title', 'Gestion des Boutiques')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-shop me-2"></i>Gestion des Boutiques</h2>
    <div>
        <a href="{{ route('admin.shops.dashboard') }}" class="btn btn-outline-primary me-2">
            <i class="bi bi-graph-up"></i> Dashboard Multi-Boutiques
        </a>
        <a href="{{ route('admin.shops.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nouvelle Boutique
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Boutiques avec caisse en temps réel -->
@forelse($shops as $shop)
    @php $cash = $shopCashData[$shop->id]; @endphp
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <!-- Infos boutique -->
                <div class="col-lg-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                             style="width: 50px; height: 50px; background: linear-gradient(135deg, #0d6efd, #6610f2);">
                            <i class="bi bi-shop text-white fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">
                                <a href="{{ route('admin.shops.show', $shop) }}" class="text-decoration-none">{{ $shop->name }}</a>
                            </h5>
                            <span class="badge bg-dark">{{ $shop->code }}</span>
                            @if($shop->is_active)
                                <span class="badge bg-success ms-1">Active</span>
                            @else
                                <span class="badge bg-danger ms-1">Inactive</span>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex gap-3 mt-3">
                        <div class="text-center">
                            <span class="badge bg-info rounded-pill fs-6">{{ $shop->users_count }}</span>
                            <small class="text-muted d-block">Employés</small>
                        </div>
                        <div class="text-center">
                            <span class="badge bg-secondary rounded-pill fs-6">{{ $shop->products_count }}</span>
                            <small class="text-muted d-block">Produits</small>
                        </div>
                        <div class="text-center">
                            <span class="badge bg-success rounded-pill fs-6">{{ $shop->sales_count }}</span>
                            <small class="text-muted d-block">Ventes</small>
                        </div>
                        <div class="text-center">
                            <span class="badge bg-warning text-dark rounded-pill fs-6">{{ $shop->repairs_count }}</span>
                            <small class="text-muted d-block">Répar.</small>
                        </div>
                    </div>
                </div>

                <!-- CA du jour -->
                <div class="col-lg-3">
                    <div class="text-center p-3 rounded-3" style="background: linear-gradient(135deg, #e8f4fd, #f0e6ff);">
                        <small class="text-muted text-uppercase fw-semibold">CA du jour</small>
                        <h2 class="fw-bold text-primary mb-1">{{ number_format($cash['today_ca'], 0, ',', ' ') }} <small class="fs-6">F</small></h2>
                        <div class="d-flex justify-content-center gap-4">
                            <div>
                                <i class="bi bi-cart text-success"></i>
                                <span class="small fw-medium">{{ number_format($cash['today_sales'], 0, ',', ' ') }} F</span>
                            </div>
                            <div>
                                <i class="bi bi-tools text-warning"></i>
                                <span class="small fw-medium">{{ number_format($cash['today_repairs'], 0, ',', ' ') }} F</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- État caisse -->
                <div class="col-lg-4">
                    @if($cash['register'])
                        <div class="border border-success rounded p-3 bg-white">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-success fs-6 me-2"><i class="bi bi-unlock-fill me-1"></i>Caisse ouverte</span>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Caissier(ère)</small>
                                    <strong>{{ $cash['register']->user->name ?? '-' }}</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Ouverture</small>
                                    <strong>{{ $cash['register']->opened_at->format('H:i') }}</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Fond de caisse</small>
                                    <span>{{ number_format($cash['register']->opening_balance, 0, ',', ' ') }} F</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Solde calculé</small>
                                    <strong class="text-success fs-5">{{ number_format($cash['register']->calculated_balance, 0, ',', ' ') }} F</strong>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="border rounded p-3 bg-light text-center">
                            <span class="badge bg-secondary fs-6 mb-2"><i class="bi bi-lock-fill me-1"></i>Caisse fermée</span>
                            <p class="text-muted mb-0 small">Aucune caisse ouverte actuellement</p>
                        </div>
                    @endif
                </div>

                <!-- Actions -->
                <div class="col-lg-2 text-end">
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('admin.shops.show', $shop) }}" class="btn btn-primary">
                            <i class="bi bi-eye me-1"></i>Détails
                        </a>
                        <a href="{{ route('admin.shops.edit', $shop) }}" class="btn btn-outline-warning">
                            <i class="bi bi-pencil me-1"></i>Modifier
                        </a>
                        <form action="{{ route('admin.shops.toggle-status', $shop) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-{{ $shop->is_active ? 'danger' : 'success' }} w-100">
                                <i class="bi bi-{{ $shop->is_active ? 'x-circle' : 'check-circle' }} me-1"></i>{{ $shop->is_active ? 'Désactiver' : 'Activer' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="text-center py-5 text-muted">
        <i class="bi bi-shop fs-1 d-block mb-2"></i>
        Aucune boutique créée. <a href="{{ route('admin.shops.create') }}">Créer la première boutique</a>
    </div>
@endforelse

@if($shops->hasPages())
    <div class="d-flex justify-content-center">
        {{ $shops->links() }}
    </div>
@endif
@endsection
