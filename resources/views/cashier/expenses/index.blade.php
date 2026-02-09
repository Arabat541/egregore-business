@extends('layouts.app')

@section('title', 'Gestion des Dépenses')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Gestion des Dépenses</h1>
            <p class="text-muted mb-0">Enregistrez et suivez les dépenses courantes</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('cashier.expenses.categories') }}" class="btn btn-outline-secondary">
                <i class="bi bi-tags me-1"></i> Catégories
            </a>
            <a href="{{ route('cashier.expenses.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Nouvelle Dépense
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Dépenses Aujourd'hui</h6>
                            <h3 class="mb-0">{{ number_format($todayTotal, 0, ',', ' ') }} F</h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="bi bi-calendar-day"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Dépenses ce Mois</h6>
                            <h3 class="mb-0">{{ number_format($monthTotal, 0, ',', ' ') }} F</h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="bi bi-calendar-month"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark-50 mb-1">En Attente</h6>
                            <h3 class="mb-0">{{ $pendingCount }}</h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="bi bi-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('cashier.expenses.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
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
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des dépenses -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            @if($expenses->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-receipt fs-1 text-muted mb-3"></i>
                    <p class="text-muted">Aucune dépense trouvée</p>
                    <a href="{{ route('cashier.expenses.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Enregistrer une dépense
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Référence</th>
                                <th>Date</th>
                                <th>Catégorie</th>
                                <th>Description</th>
                                <th>Bénéficiaire</th>
                                <th class="text-end">Montant</th>
                                <th>Mode</th>
                                <th>Statut</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenses as $expense)
                                <tr>
                                    <td>
                                        <a href="{{ route('cashier.expenses.show', $expense) }}" class="text-decoration-none fw-bold">
                                            {{ $expense->reference }}
                                        </a>
                                    </td>
                                    <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $expense->category->color }}">
                                            <i class="bi {{ $expense->category->icon ?? 'bi-tag' }} me-1"></i>
                                            {{ $expense->category->name }}
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
                                                <span class="badge bg-success"><i class="bi bi-cash me-1"></i>Espèces</span>
                                                @break
                                            @case('bank_transfer')
                                                <span class="badge bg-info"><i class="bi bi-bank me-1"></i>Virement</span>
                                                @break
                                            @case('mobile_money')
                                                <span class="badge bg-warning text-dark"><i class="bi bi-phone me-1"></i>Mobile</span>
                                                @break
                                            @case('check')
                                                <span class="badge bg-secondary"><i class="bi bi-credit-card me-1"></i>Chèque</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $expense->status_color }}">
                                            {{ $expense->status_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('cashier.expenses.show', $expense) }}" 
                                               class="btn btn-outline-primary" title="Voir">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if($expense->status !== 'approved')
                                                <a href="{{ route('cashier.expenses.edit', $expense) }}" 
                                                   class="btn btn-outline-warning" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
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
