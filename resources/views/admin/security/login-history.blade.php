@extends('layouts.app')

@section('title', 'Historique des Connexions')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-clock-history me-2"></i>Historique des Connexions
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.security.index') }}">Sécurité</a></li>
                    <li class="breadcrumb-item active">Historique</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.security.export-login-history') }}?{{ http_build_query(request()->only(['status', 'email', 'ip'])) }}" 
           class="btn btn-success">
            <i class="bi bi-download me-1"></i>Exporter CSV
        </a>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous</option>
                        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Succès</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échec</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ request('email') }}" 
                           placeholder="Filtrer par email...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Adresse IP</label>
                    <input type="text" name="ip" class="form-control" value="{{ request('ip') }}" 
                           placeholder="Filtrer par IP...">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <a href="{{ route('admin.security.login-history') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-success text-white">
                <div class="card-body">
                    <h6 class="text-uppercase opacity-75">Connexions réussies (24h)</h6>
                    <h3 class="mb-0">
                        {{ \App\Models\LoginAttempt::successful()->where('created_at', '>=', now()->subDay())->count() }}
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-danger text-white">
                <div class="card-body">
                    <h6 class="text-uppercase opacity-75">Connexions échouées (24h)</h6>
                    <h3 class="mb-0">
                        {{ \App\Models\LoginAttempt::failed()->where('created_at', '>=', now()->subDay())->count() }}
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-info text-white">
                <div class="card-body">
                    <h6 class="text-uppercase opacity-75">IPs uniques (24h)</h6>
                    <h3 class="mb-0">
                        {{ \App\Models\LoginAttempt::where('created_at', '>=', now()->subDay())->distinct('ip_address')->count() }}
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-warning">
                <div class="card-body">
                    <h6 class="text-uppercase opacity-75">Comptes tentés (24h)</h6>
                    <h3 class="mb-0">
                        {{ \App\Models\LoginAttempt::where('created_at', '>=', now()->subDay())->distinct('email')->count() }}
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des connexions -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date/Heure</th>
                            <th>Statut</th>
                            <th>Email</th>
                            <th>Utilisateur</th>
                            <th>Adresse IP</th>
                            <th>Raison échec</th>
                            <th>Navigateur</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attempts as $attempt)
                        <tr class="{{ !$attempt->successful ? 'table-warning' : '' }}">
                            <td>
                                <strong>{{ $attempt->created_at->format('d/m/Y') }}</strong>
                                <br>
                                <small class="text-muted">{{ $attempt->created_at->format('H:i:s') }}</small>
                            </td>
                            <td>
                                @if($attempt->successful)
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Succès
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle me-1"></i>Échec
                                    </span>
                                @endif
                            </td>
                            <td>
                                <code>{{ $attempt->email }}</code>
                            </td>
                            <td>
                                @if($attempt->user)
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                             style="width: 30px; height: 30px; font-size: 0.8rem;">
                                            {{ strtoupper(substr($attempt->user->name, 0, 1)) }}
                                        </div>
                                        <span>{{ $attempt->user->name }}</span>
                                    </div>
                                @else
                                    <span class="text-muted">Non trouvé</span>
                                @endif
                            </td>
                            <td>
                                <code>{{ $attempt->ip_address }}</code>
                            </td>
                            <td>
                                @if(!$attempt->successful)
                                    @switch($attempt->failure_reason)
                                        @case('invalid_password')
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-key me-1"></i>Mot de passe invalide
                                            </span>
                                            @break
                                        @case('account_disabled')
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-person-x me-1"></i>Compte désactivé
                                            </span>
                                            @break
                                        @case('account_locked')
                                            <span class="badge bg-danger">
                                                <i class="bi bi-lock me-1"></i>Compte verrouillé
                                            </span>
                                            @break
                                        @case('user_not_found')
                                            <span class="badge bg-info">
                                                <i class="bi bi-person-question me-1"></i>Utilisateur inconnu
                                            </span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ $attempt->failure_reason ?? 'Inconnu' }}</span>
                                    @endswitch
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted text-truncate d-block" style="max-width: 200px;" 
                                       title="{{ $attempt->user_agent }}">
                                    {{ Str::limit($attempt->user_agent, 40) ?? 'N/A' }}
                                </small>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-inbox text-muted fs-1"></i>
                                <p class="text-muted mb-0 mt-2">Aucune tentative de connexion trouvée</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($attempts->hasPages())
        <div class="card-footer">
            {{ $attempts->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
