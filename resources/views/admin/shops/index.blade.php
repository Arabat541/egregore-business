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

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Code</th>
                        <th>Nom</th>
                        <th>Adresse</th>
                        <th>Téléphone</th>
                        <th class="text-center">Utilisateurs</th>
                        <th class="text-center">Produits</th>
                        <th class="text-center">Ventes</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shops as $shop)
                        <tr>
                            <td><span class="badge bg-dark">{{ $shop->code }}</span></td>
                            <td>
                                <a href="{{ route('admin.shops.show', $shop) }}" class="text-decoration-none fw-bold">
                                    {{ $shop->name }}
                                </a>
                            </td>
                            <td>{{ Str::limit($shop->address, 30) ?? '-' }}</td>
                            <td>{{ $shop->phone ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ $shop->users_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $shop->products_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success">{{ $shop->sales_count }}</span>
                            </td>
                            <td class="text-center">
                                @if($shop->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.shops.show', $shop) }}" class="btn btn-outline-primary" title="Voir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.shops.edit', $shop) }}" class="btn btn-outline-warning" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.shops.toggle-status', $shop) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-{{ $shop->is_active ? 'danger' : 'success' }}" 
                                                title="{{ $shop->is_active ? 'Désactiver' : 'Activer' }}">
                                            <i class="bi bi-{{ $shop->is_active ? 'x-circle' : 'check-circle' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-shop fs-1 d-block mb-2"></i>
                                Aucune boutique créée. <a href="{{ route('admin.shops.create') }}">Créer la première boutique</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($shops->hasPages())
        <div class="card-footer">
            {{ $shops->links() }}
        </div>
    @endif
</div>
@endsection
