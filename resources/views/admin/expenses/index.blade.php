@extends('layouts.app')

@section('title', 'Gestion des Dépenses')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Gestion des Dépenses</h1>
            <p class="text-muted mb-0">Suivi et contrôle des dépenses</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.expenses.dashboard') }}" class="btn btn-outline-info">
                <i class="fas fa-chart-pie me-1"></i> Tableau de bord
            </a>
            <a href="{{ route('admin.expenses.categories') }}" class="btn btn-outline-secondary">
                <i class="fas fa-tags me-1"></i> Catégories
            </a>
            <a href="{{ route('admin.expenses.export', request()->all()) }}" class="btn btn-success">
                <i class="fas fa-file-excel me-1"></i> Exporter
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Aujourd'hui</h6>
                            <h3 class="mb-0">{{ number_format($todayTotal, 0, ',', ' ') }} F</h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Ce mois</h6>
                            <h3 class="mb-0">{{ number_format($monthTotal, 0, ',', ' ') }} F</h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark-50 mb-1">En attente</h6>
                            <h3 class="mb-0">{{ $pendingCount }}</h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Montant en attente</h6>
                            <h3 class="mb-0">{{ number_format($pendingTotal, 0, ',', ' ') }} F</h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('admin.expenses.index') }}" method="GET" class="row g-3">
                @if($shops->isNotEmpty())
                    <div class="col-md-2">
                        <label class="form-label">Boutique</label>
                        <select name="shop_id" class="form-select">
                            <option value="">Toutes</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                                    {{ $shop->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label">Recherche</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Référence, description..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Catégorie</label>
                    <select name="category" class="form-select">
                        <option value="">Toutes</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approuvée</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejetée</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Du</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Au</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Filtrer
                    </button>
                    <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des dépenses -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            @if($expenses->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Aucune dépense trouvée</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Référence</th>
                                <th>Date</th>
                                @if($shops->isNotEmpty())
                                    <th>Boutique</th>
                                @endif
                                <th>Catégorie</th>
                                <th>Description</th>
                                <th>Bénéficiaire</th>
                                <th class="text-end">Montant</th>
                                <th>Mode</th>
                                <th>Par</th>
                                <th>Statut</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenses as $expense)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.expenses.show', $expense) }}" class="text-decoration-none fw-bold">
                                            {{ $expense->reference }}
                                        </a>
                                    </td>
                                    <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                                    @if($shops->isNotEmpty())
                                        <td>
                                            <span class="badge bg-dark">{{ $expense->shop->name ?? '-' }}</span>
                                        </td>
                                    @endif
                                    <td>
                                        <span class="badge" style="background-color: {{ $expense->category->color ?? '#6c757d' }}">
                                            <i class="fas {{ $expense->category->icon ?? 'fa-tag' }} me-1"></i>
                                            {{ $expense->category->name ?? '-' }}
                                        </span>
                                    </td>
                                    <td>{{ Str::limit($expense->description, 30) }}</td>
                                    <td>{{ $expense->beneficiary ?? '-' }}</td>
                                    <td class="text-end fw-bold text-danger">
                                        {{ number_format($expense->amount, 0, ',', ' ') }} F
                                    </td>
                                    <td>
                                        @switch($expense->payment_method)
                                            @case('cash')
                                                <span class="badge bg-success"><i class="fas fa-money-bill"></i></span>
                                                @break
                                            @case('bank_transfer')
                                                <span class="badge bg-info"><i class="fas fa-university"></i></span>
                                                @break
                                            @case('mobile_money')
                                                <span class="badge bg-warning text-dark"><i class="fas fa-mobile-alt"></i></span>
                                                @break
                                            @case('check')
                                                <span class="badge bg-secondary"><i class="fas fa-money-check"></i></span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        <small>{{ $expense->user->name ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $expense->status_color }}">
                                            {{ $expense->status_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.expenses.show', $expense) }}" 
                                               class="btn btn-outline-primary" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($expense->status === 'pending')
                                                <form action="{{ route('admin.expenses.approve', $expense) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success" title="Approuver">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.expenses.reject', $expense) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-danger" title="Rejeter">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center py-3">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
