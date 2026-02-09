@extends('layouts.app')

@section('title', 'Gestion des catégories')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-folder"></i> Gestion des catégories</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
        <i class="bi bi-plus-circle"></i> Nouvelle catégorie
    </button>
</div>

<!-- Filtre par boutique -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label"><i class="bi bi-shop me-1"></i>Boutique</label>
                <select name="shop_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Toutes les catégories --</option>
                    <option value="global" {{ $shopId === 'global' ? 'selected' : '' }}>Catégories globales uniquement</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ $shopId == $shop->id ? 'selected' : '' }}>
                            {{ $shop->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Boutique</th>
                        <th>Produits</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td>
                            @if($category->color)
                                <span class="badge" style="background-color: {{ $category->color }};">&nbsp;</span>
                            @endif
                            <strong>{{ $category->name }}</strong>
                        </td>
                        <td>{{ $category->description ?: '-' }}</td>
                        <td>
                            @if($category->is_global || !$category->shop_id)
                                <span class="badge bg-secondary"><i class="bi bi-globe me-1"></i>Globale</span>
                            @else
                                <span class="badge bg-info"><i class="bi bi-shop me-1"></i>{{ $category->shop->name ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td><span class="badge bg-info">{{ $category->products_count }}</span></td>
                        <td>
                            @if($category->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="editCategory({{ $category->id }}, '{{ addslashes($category->name) }}', '{{ addslashes($category->description ?? '') }}', '{{ $category->color ?? '#6c757d' }}', {{ $category->is_active ? 'true' : 'false' }}, {{ $category->shop_id ?? 'null' }}, {{ $category->is_global ? 'true' : 'false' }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @if($category->products_count === 0)
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                            onclick="return confirm('Supprimer cette catégorie ?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Aucune catégorie</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{ $categories->appends(request()->query())->links() }}
    </div>
</div>

<!-- Modal Catégorie -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="categoryForm" method="POST" action="{{ route('admin.categories.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="bi bi-plus-circle"></i> Nouvelle catégorie
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="categoryName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="categoryDescription" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Couleur</label>
                        <input type="color" class="form-control form-control-color" name="color" id="categoryColor" value="#6c757d">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-shop me-1"></i>Boutique</label>
                        <select name="shop_id" id="categoryShop" class="form-select">
                            <option value="">-- Catégorie Globale --</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Laisser vide pour une catégorie visible par toutes les boutiques</small>
                    </div>
                    <div class="mb-3" id="globalCheckboxContainer" style="display: none;">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_global" id="categoryGlobal" value="1">
                            <label class="form-check-label">Rendre visible pour toutes les boutiques</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="categoryActive" value="1" checked>
                            <label class="form-check-label">Catégorie active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Afficher/masquer la checkbox global selon la boutique
document.getElementById('categoryShop').addEventListener('change', function() {
    document.getElementById('globalCheckboxContainer').style.display = this.value ? 'block' : 'none';
});

function editCategory(id, name, description, color, isActive, shopId, isGlobal) {
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil"></i> Modifier la catégorie';
    document.getElementById('categoryForm').action = '/admin/categories/' + id;
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('categoryName').value = name;
    document.getElementById('categoryDescription').value = description;
    document.getElementById('categoryColor').value = color || '#6c757d';
    document.getElementById('categoryActive').checked = isActive;
    document.getElementById('categoryShop').value = shopId || '';
    document.getElementById('categoryGlobal').checked = isGlobal;
    document.getElementById('globalCheckboxContainer').style.display = shopId ? 'block' : 'none';
    
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}

// Reset form when modal is hidden
document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-plus-circle"></i> Nouvelle catégorie';
    document.getElementById('categoryForm').action = '{{ route("admin.categories.store") }}';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryActive').checked = true;
    document.getElementById('globalCheckboxContainer').style.display = 'none';
});
</script>
@endpush
