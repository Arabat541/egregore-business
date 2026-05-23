@extends('layouts.app')

@section('title', 'Maintenance Système')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    {{-- En-tête --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-gear-fill text-primary me-2"></i>
                Maintenance Système
            </h1>
            <p class="text-muted mb-0">Sauvegarde et nettoyage de l'application</p>
        </div>
    </div>

    {{-- Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('cleanup_output'))
        <div class="alert alert-info alert-dismissible fade show">
            <h6><i class="bi bi-info-circle me-2"></i>Résultat du nettoyage :</h6>
            <pre class="mb-0 small">{{ session('cleanup_output') }}</pre>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- Colonne Gauche : Actions --}}
        <div class="col-lg-4">
            {{-- Espace Disque --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-hdd me-2"></i>
                        Espace Disque
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Utilisé</span>
                            <span>{{ $diskInfo['used_formatted'] }} / {{ $diskInfo['total_formatted'] }}</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            @php
                                $progressClass = $diskInfo['percent_used'] > 90 ? 'bg-danger' : 
                                    ($diskInfo['percent_used'] > 70 ? 'bg-warning' : 'bg-success');
                            @endphp
                            <div class="progress-bar {{ $progressClass }}" 
                                 style="width: {{ $diskInfo['percent_used'] }}%">
                                {{ $diskInfo['percent_used'] }}%
                            </div>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Espace libre : {{ $diskInfo['free_formatted'] }}
                    </p>
                </div>
            </div>

            {{-- Sauvegarde --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-cloud-arrow-up me-2 text-primary"></i>
                        Sauvegarde
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Créez une sauvegarde de la base de données et des fichiers.
                    </p>

                    <form action="{{ route('admin.maintenance.backup') }}" method="POST" class="mb-3">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Type de sauvegarde</label>
                            <select name="type" class="form-select">
                                <option value="full">📦 Complète (BDD + Fichiers)</option>
                                <option value="database">💾 Base de données uniquement</option>
                                <option value="files">📂 Fichiers uniquement</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-cloud-arrow-up me-2"></i>
                            Lancer la sauvegarde
                        </button>
                    </form>

                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-clock me-1"></i>
                        <strong>Automatique :</strong> 
                        @if($config['schedule']['backup_daily'])
                            Tous les jours à {{ $config['schedule']['backup_time'] }}
                        @else
                            Désactivée
                        @endif
                    </div>
                </div>
            </div>

            {{-- Nettoyage --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-trash me-2 text-warning"></i>
                        Nettoyage
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Supprimez les anciennes données selon les périodes de rétention configurées.
                    </p>

                    <form action="{{ route('admin.maintenance.cleanup') }}" method="POST" id="cleanupForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Type de nettoyage</label>
                            <select name="type" class="form-select">
                                <option value="all">🔄 Tout nettoyer</option>
                                <option value="logs">📋 Logs uniquement</option>
                                <option value="sessions">👤 Sessions uniquement</option>
                                <option value="soft-deleted">🗑️ Données supprimées</option>
                            </select>
                        </div>
                        
                        <input type="hidden" name="dry_run" id="dryRunInput" value="0">
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-info" onclick="runSimulation()">
                                <i class="bi bi-eye me-2"></i>
                                Simuler (voir ce qui sera supprimé)
                            </button>
                            <button type="button" class="btn btn-danger" onclick="runCleanup()">
                                <i class="bi bi-trash me-2"></i>
                                Exécuter le nettoyage
                            </button>
                        </div>
                    </form>

                    <div class="alert alert-info small mb-0 mt-3">
                        <i class="bi bi-clock me-1"></i>
                        <strong>Automatique :</strong> 
                        @if($config['schedule']['cleanup_weekly'] ?? false)
                            Chaque {{ ucfirst($config['schedule']['cleanup_day'] ?? 'dimanche') }} à {{ $config['schedule']['cleanup_time'] ?? '03:00' }}
                        @else
                            Désactivé
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonne Droite : Détails --}}
        <div class="col-lg-8">
            {{-- Statistiques de nettoyage --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart me-2"></i>
                        Données à nettoyer
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Logs d'activité --}}
                        <div class="col-md-6 col-lg-3">
                            <div class="border rounded p-3 text-center">
                                <i class="bi bi-journal-text text-primary fs-2"></i>
                                <h4 class="mb-0 mt-2">{{ number_format($cleanupStats['activity_logs']['count']) }}</h4>
                                <small class="text-muted">{{ $cleanupStats['activity_logs']['label'] }}</small>
                                <div class="text-muted small">> {{ $cleanupStats['activity_logs']['retention'] }} jours</div>
                            </div>
                        </div>

                        {{-- Tentatives connexion --}}
                        <div class="col-md-6 col-lg-3">
                            <div class="border rounded p-3 text-center">
                                <i class="bi bi-shield-exclamation text-warning fs-2"></i>
                                <h4 class="mb-0 mt-2">{{ number_format($cleanupStats['login_attempts']['count']) }}</h4>
                                <small class="text-muted">{{ $cleanupStats['login_attempts']['label'] }}</small>
                                <div class="text-muted small">> {{ $cleanupStats['login_attempts']['retention'] }} jours</div>
                            </div>
                        </div>

                        {{-- Sessions --}}
                        <div class="col-md-6 col-lg-3">
                            <div class="border rounded p-3 text-center">
                                <i class="bi bi-person-x text-info fs-2"></i>
                                <h4 class="mb-0 mt-2">{{ number_format($cleanupStats['user_sessions']['count']) }}</h4>
                                <small class="text-muted">{{ $cleanupStats['user_sessions']['label'] }}</small>
                                <div class="text-muted small">> {{ $cleanupStats['user_sessions']['retention'] }} jours</div>
                            </div>
                        </div>

                        {{-- Alertes --}}
                        <div class="col-md-6 col-lg-3">
                            <div class="border rounded p-3 text-center">
                                <i class="bi bi-bell-slash text-danger fs-2"></i>
                                <h4 class="mb-0 mt-2">{{ number_format($cleanupStats['security_alerts']['count']) }}</h4>
                                <small class="text-muted">{{ $cleanupStats['security_alerts']['label'] }}</small>
                                <div class="text-muted small">> {{ $cleanupStats['security_alerts']['retention'] }} jours</div>
                            </div>
                        </div>
                    </div>

                    {{-- Données soft-deleted --}}
                    <hr>
                    <h6 class="text-muted mb-3">
                        <i class="bi bi-trash me-2"></i>
                        Données supprimées (soft-deleted)
                    </h6>
                    <div class="row g-2">
                        @php
                            $softLabels = [
                                'customers' => ['Clients', 'bi-people'],
                                'resellers' => ['Revendeurs', 'bi-shop'],
                                'products' => ['Produits', 'bi-box'],
                                'sales' => ['Ventes', 'bi-cart'],
                                'repairs' => ['Réparations', 'bi-tools'],
                            ];
                        @endphp
                        @foreach($cleanupStats['soft_deleted'] as $key => $count)
                            <div class="col-6 col-md-4 col-lg-2">
                                <div class="border rounded p-2 text-center {{ $count > 0 ? 'bg-light' : '' }}">
                                    <i class="bi {{ $softLabels[$key][1] ?? 'bi-file' }}"></i>
                                    <strong class="d-block">{{ $count }}</strong>
                                    <small class="text-muted">{{ $softLabels[$key][0] ?? $key }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Liste des sauvegardes --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-archive me-2"></i>
                        Sauvegardes disponibles
                    </h5>
                    <span class="badge bg-secondary">{{ count($backups) }} fichier(s)</span>
                </div>
                <div class="card-body p-0">
                    @if(count($backups) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fichier</th>
                                        <th>Type</th>
                                        <th>Taille</th>
                                        <th>Date</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($backups as $backup)
                                        <tr>
                                            <td>
                                                <i class="bi bi-file-earmark-zip text-primary me-1"></i>
                                                <code class="small">{{ $backup['filename'] }}</code>
                                            </td>
                                            <td>
                                                @if($backup['type'] === 'full')
                                                    <span class="badge bg-success">📦 Complète</span>
                                                @elseif($backup['type'] === 'database')
                                                    <span class="badge bg-primary">💾 BDD</span>
                                                @elseif($backup['type'] === 'files')
                                                    <span class="badge bg-info">📂 Fichiers</span>
                                                @endif
                                            </td>
                                            <td>{{ $backup['size_formatted'] }}</td>
                                            <td>
                                                <span title="{{ $backup['date']->format('d/m/Y H:i:s') }}">
                                                    {{ $backup['date']->diffForHumans() }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.maintenance.backup.download', $backup['filename']) }}" 
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Télécharger">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <form action="{{ route('admin.maintenance.backup.delete', $backup['filename']) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Supprimer cette sauvegarde ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-archive fs-1"></i>
                            <p class="mt-2">Aucune sauvegarde disponible</p>
                            <p class="small">Lancez une sauvegarde pour commencer</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Dernières opérations --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Dernières opérations
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($recentOperations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Opération</th>
                                        <th>Détails</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentOperations as $log)
                                        <tr>
                                            <td class="text-muted small">
                                                {{ $log->created_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td>
                                                @if($log->action === 'system_backup')
                                                    <span class="badge bg-primary">Sauvegarde</span>
                                                @else
                                                    <span class="badge bg-warning">Nettoyage</span>
                                                @endif
                                            </td>
                                            <td class="small">{{ $log->description }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-clock-history fs-2"></i>
                            <p class="mt-2 mb-0">Aucune opération récente</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Configuration actuelle --}}
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="bi bi-sliders me-2"></i>
                Configuration actuelle
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6>📋 Rétention des logs</h6>
                    <ul class="list-unstyled small">
                        <li>Logs d'activité : <strong>{{ $config['retention']['activity_logs'] }} jours</strong></li>
                        <li>Tentatives connexion : <strong>{{ $config['retention']['login_attempts'] }} jours</strong></li>
                        <li>Sessions : <strong>{{ $config['retention']['user_sessions'] }} jours</strong></li>
                        <li>Alertes résolues : <strong>{{ $config['retention']['security_alerts_resolved'] }} jours</strong></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6>🗑️ Rétention données supprimées</h6>
                    <ul class="list-unstyled small">
                        @foreach($config['retention']['soft_deleted'] as $key => $days)
                            <li>{{ ucfirst($key) }} : <strong>{{ $days }} jours</strong></li>
                        @endforeach
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6>💾 Sauvegardes</h6>
                    <ul class="list-unstyled small">
                        <li>Conserver : <strong>{{ $config['backup']['keep_last'] }} dernières</strong></li>
                        <li>Compression : <strong>{{ $config['backup']['compress'] ? 'Oui' : 'Non' }}</strong></li>
                        <li>Inclure uploads : <strong>{{ $config['backup']['include_uploads'] ? 'Oui' : 'Non' }}</strong></li>
                    </ul>
                </div>
            </div>
            <div class="alert alert-secondary small mb-0 mt-3">
                <i class="bi bi-info-circle me-1"></i>
                Pour modifier ces paramètres, éditez le fichier <code>config/maintenance.php</code> 
                ou définissez les variables d'environnement correspondantes dans <code>.env</code>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function runSimulation() {
    document.getElementById('dryRunInput').value = '1';
    document.getElementById('cleanupForm').submit();
}

function runCleanup() {
    const type = document.querySelector('select[name="type"]').value;
    const typeLabels = {
        'all': 'toutes les anciennes données',
        'logs': 'les anciens logs',
        'sessions': 'les sessions expirées',
        'soft-deleted': 'les données supprimées définitivement'
    };
    
    if (confirm(`⚠️ ATTENTION !\n\nVous êtes sur le point de supprimer ${typeLabels[type]}.\n\nCette action est IRRÉVERSIBLE.\n\nVoulez-vous continuer ?`)) {
        document.getElementById('dryRunInput').value = '0';
        document.getElementById('cleanupForm').submit();
    }
}
</script>
@endpush
