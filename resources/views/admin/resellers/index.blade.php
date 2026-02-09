@extends('layouts.app')

@section('title', 'Gestion des revendeurs')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-building"></i> Gestion des revendeurs</h2>
    <a href="{{ route('admin.resellers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nouveau revendeur
    </a>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('admin.resellers.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" 
                       placeholder="Rechercher..." value="{{ request('search') }}">
            </div>
            @if(auth()->user()->hasRole('admin') && $shops->count() > 0)
            <div class="col-md-3">
                <select class="form-select" name="shop_id">
                    <option value="">Toutes les boutiques</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                            {{ $shop->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">Tous les statuts</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actifs</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactifs</option>
                    <option value="with_debt" {{ request('status') == 'with_debt' ? 'selected' : '' }}>Avec dette</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filtrer
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.resellers.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle"></i> Reset
                </a>
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
                        <th>Entreprise</th>
                        <th>Contact</th>
                        <th>Téléphone</th>
                        @if(auth()->user()->hasRole('admin'))
                        <th>Boutique</th>
                        @endif
                        <th>Limite crédit</th>
                        <th>Dette actuelle</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resellers as $reseller)
                    <tr class="{{ !$reseller->is_active ? 'table-secondary opacity-75' : '' }}">
                        <td>
                            <strong>{{ $reseller->company_name }}</strong>
                            @if(!$reseller->is_active)
                                <span class="badge bg-danger ms-1">Inactif</span>
                            @endif
                            @if($reseller->reseller_code)
                                <br><small class="text-muted">{{ $reseller->reseller_code }}</small>
                            @endif
                        </td>
                        <td>{{ $reseller->contact_name }}</td>
                        <td>{{ $reseller->phone }}</td>
                        @if(auth()->user()->hasRole('admin'))
                        <td>
                            @if($reseller->shop)
                                <span class="badge bg-info">{{ $reseller->shop->code }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        @endif
                        <td>{{ number_format($reseller->credit_limit, 0, ',', ' ') }} FCFA</td>
                        <td>
                            @if($reseller->current_debt > 0)
                                <span class="text-danger fw-bold">{{ number_format($reseller->current_debt, 0, ',', ' ') }} FCFA</span>
                            @else
                                <span class="text-success">0 FCFA</span>
                            @endif
                        </td>
                        <td>
                            @if($reseller->is_active)
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-danger">Inactif</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.resellers.show', $reseller) }}" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('admin.resellers.edit', $reseller) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.resellers.destroy', $reseller) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                        onclick="return confirm('Supprimer ce revendeur ?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->hasRole('admin') ? 8 : 7 }}" class="text-center text-muted">Aucun revendeur</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $resellers->links() }}
    </div>
</div>
@endsection
