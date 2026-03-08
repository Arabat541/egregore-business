@extends('layouts.app')

@section('title', 'Sécurité - Dashboard')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@push('styles')
<style>
    .security-card {
        border-radius: 12px;
        transition: transform 0.2s;
    }
    .security-card:hover {
        transform: translateY(-2px);
    }
    .alert-critical { background-color: #dc3545; color: white; }
    .alert-high { background-color: #fd7e14; color: white; }
    .alert-medium { background-color: #ffc107; color: dark; }
    .alert-low { background-color: #17a2b8; color: white; }
    .status-badge {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
    }
    .ip-suspicious {
        background-color: #fff3cd;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-shield-lock me-2"></i>Centre de Sécurité
            </h1>
            <p class="text-muted mb-0">Surveillance et gestion de la sécurité du système</p>
        </div>
        <div>
            <a href="{{ route('admin.security.alerts') }}" class="btn btn-outline-danger me-2">
                <i class="bi bi-exclamation-triangle me-1"></i>Toutes les alertes
            </a>
            <a href="{{ route('admin.security.login-history') }}" class="btn btn-outline-secondary">
                <i class="bi bi-clock-history me-1"></i>Historique
            </a>
        </div>
    </div>

    <!-- Statistiques principales -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card security-card border-0 shadow-sm bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 opacity-75">Alertes critiques</h6>
                            <h2 class="mb-0">{{ $stats['critical_alerts'] }}</h2>
                        </div>
                        <i class="bi bi-exclamation-octagon fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card security-card border-0 shadow-sm bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 opacity-75">Alertes non résolues</h6>
                            <h2 class="mb-0">{{ $stats['unresolved_alerts'] }}</h2>
                        </div>
                        <i class="bi bi-bell-fill fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card security-card border-0 shadow-sm bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 opacity-75">Comptes verrouillés</h6>
                            <h2 class="mb-0">{{ $stats['locked_accounts'] }}</h2>
                        </div>
                        <i class="bi bi-lock-fill fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card security-card border-0 shadow-sm bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 opacity-75">Sessions actives</h6>
                            <h2 class="mb-0">{{ $stats['active_sessions'] }}</h2>
                        </div>
                        <i class="bi bi-people-fill fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques connexions -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card security-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Échecs connexion (aujourd'hui)</h6>
                            <h3 class="mb-0 text-danger">{{ $stats['failed_logins_today'] }}</h3>
                        </div>
                        <i class="bi bi-x-circle text-danger fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card security-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Échecs connexion (7 jours)</h6>
                            <h3 class="mb-0 text-warning">{{ $stats['failed_logins_week'] }}</h3>
                        </div>
                        <i class="bi bi-graph-down text-warning fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card security-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">IPs uniques (aujourd'hui)</h6>
                            <h3 class="mb-0 text-info">{{ $stats['unique_ips_today'] }}</h3>
                        </div>
                        <i class="bi bi-globe text-info fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Comptes verrouillés -->
        @if($lockedAccounts->count() > 0)
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-lock-fill me-2"></i>Comptes verrouillés
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Verrouillé jusqu'à</th>
                                    <th>Tentatives</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lockedAccounts as $account)
                                <tr>
                                    <td>
                                        <strong>{{ $account->name }}</strong>
                                        <br><small class="text-muted">{{ $account->email }}</small>
                                    </td>
                                    <td>{{ $account->locked_until->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="badge bg-danger">{{ $account->failed_login_attempts }}</span>
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.security.unlock-account', $account) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm" title="Débloquer">
                                                <i class="bi bi-unlock"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- IPs suspectes -->
        @if($suspiciousIps->count() > 0)
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>IPs suspectes (24h)
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Adresse IP</th>
                                    <th>Tentatives échouées</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($suspiciousIps as $ip)
                                <tr class="ip-suspicious">
                                    <td>
                                        <i class="bi bi-geo-alt me-1"></i>
                                        <code>{{ $ip->ip_address }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">{{ $ip->attempt_count }} échecs</span>
                                    </td>
                                    <td>
                                        @if($ip->attempt_count >= 20)
                                            <span class="badge bg-danger">Bloquée</span>
                                        @else
                                            <span class="badge bg-warning">Surveillée</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Alertes récentes -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-bell-fill me-2"></i>Alertes récentes</span>
                    <a href="{{ route('admin.security.alerts') }}" class="btn btn-sm btn-outline-primary">Voir tout</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($alerts->take(8) as $alert)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <span class="badge alert-{{ $alert->severity }} status-badge me-2">
                                        {{ strtoupper($alert->severity) }}
                                    </span>
                                    <small class="text-muted">{{ $alert->created_at->format('d/m H:i') }}</small>
                                    <p class="mb-1 mt-1">{{ $alert->message }}</p>
                                    @if($alert->user)
                                        <small class="text-muted">
                                            <i class="bi bi-person me-1"></i>{{ $alert->user->name }}
                                        </small>
                                    @endif
                                </div>
                                @if(!$alert->resolved_at)
                                <form action="{{ route('admin.security.resolve-alert', $alert) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success btn-sm" title="Marquer résolu">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                                @else
                                <span class="badge bg-success">Résolu</span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="list-group-item text-center text-muted py-4">
                            <i class="bi bi-shield-check fs-1"></i>
                            <p class="mb-0 mt-2">Aucune alerte récente</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Sessions actives -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people-fill me-2"></i>Sessions actives</span>
                    <a href="{{ route('admin.security.sessions') }}" class="btn btn-sm btn-outline-primary">Voir tout</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Appareil</th>
                                    <th>Dernière activité</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeSessions->take(8) as $session)
                                <tr>
                                    <td>
                                        <strong>{{ $session->user?->name ?? 'N/A' }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-geo-alt me-1"></i>{{ $session->ip_address }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($session->device_type === 'Mobile')
                                            <i class="bi bi-phone text-primary me-1"></i>
                                        @elseif($session->device_type === 'Tablet')
                                            <i class="bi bi-tablet text-info me-1"></i>
                                        @else
                                            <i class="bi bi-laptop text-secondary me-1"></i>
                                        @endif
                                        {{ $session->browser ?? 'Inconnu' }}
                                    </td>
                                    <td>
                                        <small>{{ $session->last_activity_at->diffForHumans() }}</small>
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.security.terminate-session', $session) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Terminer session">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Aucune session active
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tentatives de connexion échouées récentes -->
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-x-circle me-2 text-danger"></i>Tentatives de connexion échouées récentes</span>
                    <a href="{{ route('admin.security.login-history') }}?status=failed" class="btn btn-sm btn-outline-danger">
                        Voir tout
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date/Heure</th>
                                    <th>Email</th>
                                    <th>Adresse IP</th>
                                    <th>Raison</th>
                                    <th>Navigateur</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($failedAttempts as $attempt)
                                <tr>
                                    <td>
                                        {{ $attempt->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td>
                                        <code>{{ $attempt->email }}</code>
                                        @if($attempt->user)
                                            <br><small class="text-muted">{{ $attempt->user->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <code>{{ $attempt->ip_address }}</code>
                                    </td>
                                    <td>
                                        @switch($attempt->failure_reason)
                                            @case('invalid_password')
                                                <span class="badge bg-warning">Mot de passe invalide</span>
                                                @break
                                            @case('account_disabled')
                                                <span class="badge bg-secondary">Compte désactivé</span>
                                                @break
                                            @case('account_locked')
                                                <span class="badge bg-danger">Compte verrouillé</span>
                                                @break
                                            @default
                                                <span class="badge bg-info">{{ $attempt->failure_reason ?? 'Inconnu' }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 200px;">
                                            {{ Str::limit($attempt->user_agent, 50) }}
                                        </small>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-check-circle text-success fs-1"></i>
                                        <p class="mb-0 mt-2">Aucune tentative échouée récente</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
