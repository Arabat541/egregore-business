@extends('layouts.app')

@section('title', 'Catégories de Dépenses')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Catégories de Dépenses</h1>
            <p class="text-muted mb-0">Gérer les catégories pour organiser les dépenses</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
            <a href="{{ route('admin.expenses.dashboard') }}" class="btn btn-outline-info">
                <i class="fas fa-chart-pie me-1"></i> Tableau de bord
            </a>
        </div>
    </div>

    <!-- Filtre boutique -->
    @if($shops->isNotEmpty())
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="{{ route('admin.expenses.categories') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Boutique</label>
                        <select name="shop_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Toutes les boutiques</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                                    {{ $shop->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Liste des catégories -->
    <div class="row">
        @forelse($categories as $category)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center" 
                         style="background-color: {{ $category->color }}20; border-left: 4px solid {{ $category->color }}">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle p-2 me-2" style="background-color: {{ $category->color }}">
                                <i class="fas {{ $category->icon ?? 'fa-tag' }} text-white"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $category->name }}</h6>
                                <small class="text-muted">{{ $category->shop->name ?? '-' }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($category->description)
                            <p class="text-muted small mb-3">{{ $category->description }}</p>
                        @endif

                        <div class="mb-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Dépenses ce mois</span>
                                <span class="fw-bold">{{ number_format($category->month_total, 0, ',', ' ') }} F</span>
                            </div>
                            @if($category->monthly_budget)
                                <div class="progress" style="height: 8px">
                                    @php
                                        $percentage = $category->budgetUsagePercentage();
                                        $color = $percentage >= 100 ? 'danger' : ($percentage >= 80 ? 'warning' : 'success');
                                    @endphp
                                    <div class="progress-bar bg-{{ $color }}" 
                                         style="width: {{ min(100, $percentage) }}%"></div>
                                </div>
                                <small class="text-muted">
                                    Budget: {{ number_format($category->monthly_budget, 0, ',', ' ') }} F
                                    ({{ number_format($percentage, 0) }}% utilisé)
                                </small>
                            @else
                                <small class="text-muted">Pas de budget défini</small>
                            @endif
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-secondary">
                                <i class="fas fa-receipt me-1"></i>{{ $category->expenses_count }} dépenses
                            </span>
                            <div>
                                @if(!$category->is_active)
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                                @if($category->requires_approval)
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-lock me-1"></i>Approbation
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucune catégorie</h5>
                        <p class="text-muted">Les catégories sont créées par les caissières</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Résumé -->
    @if($categories->isNotEmpty())
        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Résumé</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <h4 class="text-primary">{{ $categories->count() }}</h4>
                            <p class="text-muted mb-0">Catégories</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h4 class="text-info">{{ $categories->sum('expenses_count') }}</h4>
                            <p class="text-muted mb-0">Dépenses totales</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h4 class="text-danger">{{ number_format($categories->sum('month_total'), 0, ',', ' ') }} F</h4>
                            <p class="text-muted mb-0">Ce mois</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
