@extends('layouts.app')

@section('title', 'Boutique ' . $shop->name)

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-shop me-2"></i>{{ $shop->name }}</h2>
        <span class="badge bg-dark">{{ $shop->code }}</span>
        @if($shop->is_active)
            <span class="badge bg-success">Active</span>
        @else
            <span class="badge bg-danger">Inactive</span>
        @endif
    </div>
    <div>
        <a href="{{ route('admin.shops.edit', $shop) }}" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Modifier
        </a>
        <a href="{{ route('admin.shops.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour
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

<!-- Statistiques -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $stats['users_count'] }}</h3>
                <small>Utilisateurs</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $stats['products_count'] }}</h3>
                <small>Produits</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $stats['sales_today'] }}</h3>
                <small>Ventes aujourd'hui</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $stats['repairs_pending'] }}</h3>
                <small>Réparations en cours</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Informations -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informations</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th width="30%">Adresse:</th>
                        <td>{{ $shop->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Téléphone:</th>
                        <td>{{ $shop->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>{{ $shop->email ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Description:</th>
                        <td>{{ $shop->description ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Créée le:</th>
                        <td>{{ $shop->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>CA du jour:</th>
                        <td class="text-success fw-bold">{{ number_format($stats['revenue_today'], 0, ',', ' ') }} F</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Utilisateurs assignés -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Utilisateurs assignés</h5>
            </div>
            <div class="card-body">
                <!-- Formulaire d'assignation -->
                @if($availableUsers->count() > 0)
                <form action="{{ route('admin.shops.assign-user', $shop) }}" method="POST" class="mb-3">
                    @csrf
                    <div class="input-group">
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Sélectionner un utilisateur --</option>
                            @foreach($availableUsers as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->name }} ({{ $user->roles->first()?->name ?? 'Aucun rôle' }})
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus"></i> Assigner
                        </button>
                    </div>
                </form>
                @endif

                <!-- Liste des utilisateurs -->
                @if($shop->users->count() > 0)
                    <ul class="list-group">
                        @foreach($shop->users as $user)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-person me-2"></i>
                                    <strong>{{ $user->name }}</strong>
                                    <span class="badge bg-secondary ms-2">{{ $user->roles->first()?->name ?? '-' }}</span>
                                </div>
                                <form action="{{ route('admin.shops.remove-user', [$shop, $user]) }}" method="POST"
                                      onsubmit="return confirm('Retirer cet utilisateur de la boutique ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Retirer">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted text-center mb-0">Aucun utilisateur assigné</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Actions dangereuses -->
<div class="card border-danger">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Zone dangereuse</h5>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>Supprimer cette boutique</strong>
                <p class="text-muted mb-0 small">Cette action est irréversible. Tous les utilisateurs doivent être retirés avant.</p>
            </div>
            <form action="{{ route('admin.shops.destroy', $shop) }}" method="POST"
                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette boutique ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" {{ $shop->users->count() > 0 ? 'disabled' : '' }}>
                    <i class="bi bi-trash"></i> Supprimer
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
