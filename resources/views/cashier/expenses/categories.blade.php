@extends('layouts.app')

@section('title', 'Catégories de Dépenses')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Catégories de Dépenses</h1>
            <p class="text-muted mb-0">Gérer les catégories pour organiser vos dépenses</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('cashier.expenses.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Retour
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-lg me-1"></i> Nouvelle Catégorie
            </button>
        </div>
    </div>

    <!-- Liste des catégories -->
    <div class="row">
        @forelse($categories as $category)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center" 
                         style="background-color: {{ $category->color }}20; border-left: 4px solid {{ $category->color }}">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle p-2 me-2" style="background-color: {{ $category->color }}">
                                <i class="bi {{ $category->icon ?? 'bi-tag' }} text-white"></i>
                            </div>
                            <h6 class="mb-0">{{ $category->name }}</h6>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link text-muted" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item" data-bs-toggle="modal" 
                                            data-bs-target="#editCategoryModal{{ $category->id }}">
                                        <i class="bi bi-pencil me-2"></i>Modifier
                                    </button>
                                </li>
                                @if($category->expenses_count == 0)
                                    <li>
                                        <form action="{{ route('cashier.expenses.categories.destroy', $category) }}" method="POST"
                                              onsubmit="return confirm('Supprimer cette catégorie ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-trash me-2"></i>Supprimer
                                            </button>
                                        </form>
                                    </li>
                                @endif
                            </ul>
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
                            @endif
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-secondary">
                                <i class="bi bi-receipt me-1"></i>{{ $category->expenses_count }} dépenses
                            </span>
                            <div>
                                @if(!$category->is_active)
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                                @if($category->requires_approval)
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-lock me-1"></i>Approbation
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal édition -->
            <div class="modal fade" id="editCategoryModal{{ $category->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('cashier.expenses.categories.update', $category) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Modifier: {{ $category->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" 
                                           value="{{ $category->name }}" required>
                                </div>
                                <div class="row mb-3">
                                <div class="col-md-6">
                                        <label class="form-label">Icône</label>
                                        <select name="icon" class="form-select">
                                            @foreach(['bi-tag', 'bi-house', 'bi-lightning', 'bi-car-front', 'bi-wrench', 'bi-telephone', 'bi-cart', 'bi-person-badge', 'bi-building', 'bi-file-earmark-text', 'bi-truck', 'bi-tools'] as $icon)
                                                <option value="{{ $icon }}" {{ $category->icon == $icon ? 'selected' : '' }}>
                                                    {{ $icon }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Couleur</label>
                                        <input type="color" name="color" class="form-control form-control-color w-100" 
                                               value="{{ $category->color }}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="2">{{ $category->description }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Budget mensuel (optionnel)</label>
                                    <div class="input-group">
                                        <input type="number" name="monthly_budget" class="form-control" 
                                               value="{{ $category->monthly_budget }}" min="0">
                                        <span class="input-group-text">F</span>
                                    </div>
                                </div>
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="requires_approval" value="1" class="form-check-input"
                                           id="requires_approval_{{ $category->id }}"
                                           {{ $category->requires_approval ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_approval_{{ $category->id }}">
                                        Nécessite approbation admin
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                           id="is_active_{{ $category->id }}"
                                           {{ $category->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active_{{ $category->id }}">
                                        Catégorie active
                                    </label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-tags  text-muted mb-3"></i>
                        <h5 class="text-muted">Aucune catégorie</h5>
                        <p class="text-muted mb-3">Créez votre première catégorie de dépenses</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="bi bi-plus-lg me-1"></i> Créer une catégorie
                        </button>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>

<!-- Modal ajout catégorie -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('cashier.expenses.categories.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle Catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" 
                               placeholder="Ex: Loyer, Électricité, Transport..." required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Icône</label>
                            <select name="icon" class="form-select">
                                <option value="fa-tag">🏷️ Tag (défaut)</option>
                                <option value="fa-home">🏠 Maison (Loyer)</option>
                                <option value="fa-bolt">⚡ Électricité</option>
                                <option value="fa-car">🚗 Transport</option>
                                <option value="fa-wrench">🔧 Maintenance</option>
                                <option value="fa-phone">📞 Téléphone</option>
                                <option value="fa-shopping-cart">🛒 Achats</option>
                                <option value="fa-user-tie">👔 Salaires</option>
                                <option value="fa-building">🏢 Location</option>
                                <option value="fa-file-invoice">📄 Factures</option>
                                <option value="fa-truck">🚚 Livraison</option>
                                <option value="fa-tools">🛠️ Outils</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Couleur</label>
                            <input type="color" name="color" class="form-control form-control-color w-100" value="#6c757d">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" 
                                  placeholder="Description de cette catégorie..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Budget mensuel (optionnel)</label>
                        <div class="input-group">
                            <input type="number" name="monthly_budget" class="form-control" 
                                   placeholder="Ex: 50000" min="0">
                            <span class="input-group-text">F</span>
                        </div>
                        <small class="text-muted">Définir un budget pour suivre les dépenses</small>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="requires_approval" value="1" class="form-check-input" id="requires_approval_new">
                        <label class="form-check-label" for="requires_approval_new">
                            Nécessite approbation admin
                        </label>
                        <small class="d-block text-muted">Les dépenses de cette catégorie devront être approuvées</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer la catégorie</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
