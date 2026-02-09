@extends('layouts.app')

@section('title', 'Alertes de Sécurité')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@push('styles')
<style>
    .alert-critical { background-color: #dc3545; color: white; }
    .alert-high { background-color: #fd7e14; color: white; }
    .alert-medium { background-color: #ffc107; color: dark; }
    .alert-low { background-color: #17a2b8; color: white; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-exclamation-triangle me-2"></i>Alertes de Sécurité
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.security.index') }}">Sécurité</a></li>
                    <li class="breadcrumb-item active">Alertes</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.security.export-alerts') }}?{{ http_build_query(request()->only(['status', 'type', 'severity'])) }}" 
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
                        <option value="unresolved" {{ request('status') === 'unresolved' ? 'selected' : '' }}>Non résolues</option>
                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Résolues</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">Tous</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sévérité</label>
                    <select name="severity" class="form-select">
                        <option value="">Toutes</option>
                        <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critique</option>
                        <option value="high" {{ request('severity') === 'high' ? 'selected' : '' }}>Haute</option>
                        <option value="medium" {{ request('severity') === 'medium' ? 'selected' : '' }}>Moyenne</option>
                        <option value="low" {{ request('severity') === 'low' ? 'selected' : '' }}>Basse</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <a href="{{ route('admin.security.alerts') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des alertes -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Sévérité</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Utilisateur</th>
                            <th>IP</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alerts as $alert)
                        <tr>
                            <td>
                                <small>{{ $alert->created_at->format('d/m/Y H:i:s') }}</small>
                            </td>
                            <td>
                                <span class="badge alert-{{ $alert->severity }}">
                                    {{ strtoupper($alert->severity) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $types[$alert->type] ?? $alert->type }}</span>
                            </td>
                            <td style="max-width: 300px;">
                                {{ $alert->message }}
                            </td>
                            <td>
                                @if($alert->user)
                                    <strong>{{ $alert->user->name }}</strong>
                                    <br><small class="text-muted">{{ $alert->user->email }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($alert->ip_address)
                                    <code>{{ $alert->ip_address }}</code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($alert->resolved_at)
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Résolu
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        {{ $alert->resolved_at->format('d/m H:i') }}
                                        @if($alert->resolvedBy)
                                            par {{ $alert->resolvedBy->name }}
                                        @endif
                                    </small>
                                @else
                                    <span class="badge bg-warning">
                                        <i class="bi bi-clock me-1"></i>Non résolu
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if(!$alert->resolved_at)
                                <button type="button" class="btn btn-success btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#resolveModal{{ $alert->id }}">
                                    <i class="bi bi-check-lg"></i> Résoudre
                                </button>
                                @endif
                                <button type="button" class="btn btn-outline-info btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#detailModal{{ $alert->id }}">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Résoudre -->
                        <div class="modal fade" id="resolveModal{{ $alert->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.security.resolve-alert', $alert) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Résoudre l'alerte</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>{{ $alert->message }}</strong></p>
                                            <div class="mb-3">
                                                <label class="form-label">Notes de résolution (optionnel)</label>
                                                <textarea name="notes" class="form-control" rows="3"
                                                          placeholder="Décrivez les actions prises..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-check-lg me-1"></i>Marquer résolu
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Détails -->
                        <div class="modal fade" id="detailModal{{ $alert->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Détails de l'alerte #{{ $alert->id }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4">Date</dt>
                                            <dd class="col-sm-8">{{ $alert->created_at->format('d/m/Y H:i:s') }}</dd>

                                            <dt class="col-sm-4">Type</dt>
                                            <dd class="col-sm-8">{{ $types[$alert->type] ?? $alert->type }}</dd>

                                            <dt class="col-sm-4">Sévérité</dt>
                                            <dd class="col-sm-8">
                                                <span class="badge alert-{{ $alert->severity }}">{{ strtoupper($alert->severity) }}</span>
                                            </dd>

                                            <dt class="col-sm-4">Message</dt>
                                            <dd class="col-sm-8">{{ $alert->message }}</dd>

                                            <dt class="col-sm-4">Utilisateur</dt>
                                            <dd class="col-sm-8">{{ $alert->user?->name ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4">Adresse IP</dt>
                                            <dd class="col-sm-8"><code>{{ $alert->ip_address ?? 'N/A' }}</code></dd>

                                            @if($alert->metadata)
                                            <dt class="col-sm-4">Données</dt>
                                            <dd class="col-sm-8">
                                                <pre class="bg-light p-2 rounded">{{ json_encode($alert->metadata, JSON_PRETTY_PRINT) }}</pre>
                                            </dd>
                                            @endif

                                            @if($alert->resolved_at)
                                            <dt class="col-sm-4">Résolu le</dt>
                                            <dd class="col-sm-8">{{ $alert->resolved_at->format('d/m/Y H:i:s') }}</dd>

                                            <dt class="col-sm-4">Résolu par</dt>
                                            <dd class="col-sm-8">{{ $alert->resolvedBy?->name ?? 'N/A' }}</dd>

                                            @if($alert->resolution_notes)
                                            <dt class="col-sm-4">Notes</dt>
                                            <dd class="col-sm-8">{{ $alert->resolution_notes }}</dd>
                                            @endif
                                            @endif
                                        </dl>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-shield-check text-success fs-1"></i>
                                <p class="text-muted mb-0 mt-2">Aucune alerte trouvée</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($alerts->hasPages())
        <div class="card-footer">
            {{ $alerts->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
