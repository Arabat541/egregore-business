@extends('layouts.app')

@section('title', 'Fournisseurs')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-truck me-2"></i>Fournisseurs</h2>
    <div>
        <a href="{{ route('admin.suppliers.low-stock') }}" class="btn btn-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>Produits à commander
        </a>
        <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nouveau fournisseur
        </a>
    </div>
</div>

<!-- Filtres -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" 
                       placeholder="Rechercher..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="shop_id" class="form-select">
                    <option value="">Toutes les boutiques</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                            {{ $shop->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actifs</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactifs</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Filtrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Liste des fournisseurs -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Fournisseur</th>
                        <th>Contact</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th class="text-center">Commandes</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                        <tr>
                            <td>
                                <strong>{{ $supplier->company_name }}</strong>
                                @if($supplier->city)
                                    <br><small class="text-muted">{{ $supplier->city }}</small>
                                @endif
                            </td>
                            <td>{{ $supplier->contact_name ?? '-' }}</td>
                            <td>
                                <a href="tel:{{ $supplier->phone }}">{{ $supplier->phone }}</a>
                                @if($supplier->whatsapp)
                                    <br>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supplier->whatsapp) }}" 
                                       target="_blank" class="text-success">
                                        <i class="bi bi-whatsapp"></i> WhatsApp
                                    </a>
                                @endif
                            </td>
                            <td>
                                @if($supplier->email)
                                    <a href="mailto:{{ $supplier->email }}">{{ $supplier->email }}</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $supplier->orders_count }}</span>
                            </td>
                            <td class="text-center">
                                @if($supplier->is_active)
                                    <span class="badge bg-success">Actif</span>
                                @else
                                    <span class="badge bg-danger">Inactif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.suppliers.show', $supplier) }}" 
                                   class="btn btn-sm btn-outline-primary" title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.suppliers.edit', $supplier) }}" 
                                   class="btn btn-sm btn-outline-secondary" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.suppliers.destroy', $supplier) }}" 
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Supprimer ce fournisseur ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-truck fs-1 d-block mb-2"></i>
                                Aucun fournisseur enregistré
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($suppliers->hasPages())
        <div class="card-footer bg-white">
            {{ $suppliers->links() }}
        </div>
    @endif
</div>
@endsection
