@extends('layouts.app')

@section('title', 'Sessions Actives')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-people-fill me-2"></i>Sessions Actives
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.security.index') }}">Sécurité</a></li>
                    <li class="breadcrumb-item active">Sessions</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Utilisateur</th>
                            <th>Adresse IP</th>
                            <th>Appareil</th>
                            <th>Navigateur</th>
                            <th>Plateforme</th>
                            <th>Connecté depuis</th>
                            <th>Dernière activité</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sessions as $session)
                        <tr>
                            <td>
                                @if($session->user)
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                             style="width: 35px; height: 35px;">
                                            {{ strtoupper(substr($session->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <strong>{{ $session->user->name }}</strong>
                                            <br><small class="text-muted">{{ $session->user->email }}</small>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">Utilisateur inconnu</span>
                                @endif
                            </td>
                            <td>
                                <code>{{ $session->ip_address }}</code>
                            </td>
                            <td>
                                @if($session->device_type === 'Mobile')
                                    <i class="bi bi-phone text-primary me-1"></i>
                                @elseif($session->device_type === 'Tablet')
                                    <i class="bi bi-tablet text-info me-1"></i>
                                @else
                                    <i class="bi bi-laptop text-secondary me-1"></i>
                                @endif
                                {{ $session->device_type ?? 'Inconnu' }}
                            </td>
                            <td>
                                @if($session->browser)
                                    <i class="bi bi-globe me-1"></i>{{ $session->browser }}
                                @else
                                    <span class="text-muted">Inconnu</span>
                                @endif
                            </td>
                            <td>
                                @if($session->platform)
                                    @if(str_contains(strtolower($session->platform), 'windows'))
                                        <i class="bi bi-windows text-primary me-1"></i>
                                    @elseif(str_contains(strtolower($session->platform), 'mac'))
                                        <i class="bi bi-apple text-secondary me-1"></i>
                                    @elseif(str_contains(strtolower($session->platform), 'linux'))
                                        <i class="bi bi-ubuntu text-warning me-1"></i>
                                    @elseif(str_contains(strtolower($session->platform), 'android'))
                                        <i class="bi bi-android2 text-success me-1"></i>
                                    @elseif(str_contains(strtolower($session->platform), 'ios'))
                                        <i class="bi bi-apple text-secondary me-1"></i>
                                    @endif
                                    {{ $session->platform }}
                                @else
                                    <span class="text-muted">Inconnu</span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $session->created_at->format('d/m/Y H:i') }}</small>
                                <br>
                                <small class="text-muted">{{ $session->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                @if($session->last_activity_at->diffInMinutes(now()) < 5)
                                    <span class="badge bg-success">
                                        <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>En ligne
                                    </span>
                                @elseif($session->last_activity_at->diffInMinutes(now()) < 30)
                                    <span class="badge bg-warning">
                                        {{ $session->last_activity_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        {{ $session->last_activity_at->diffForHumans() }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.security.terminate-session', $session) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Terminer cette session ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Terminer la session">
                                        <i class="bi bi-x-circle me-1"></i>Terminer
                                    </button>
                                </form>
                                @if($session->user)
                                <form action="{{ route('admin.security.terminate-user-sessions', $session->user) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Terminer TOUTES les sessions de {{ $session->user->name }} ?');">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm" title="Terminer toutes les sessions">
                                        <i class="bi bi-x-octagon me-1"></i>Toutes
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-person-x text-muted fs-1"></i>
                                <p class="text-muted mb-0 mt-2">Aucune session active</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($sessions->hasPages())
        <div class="card-footer">
            {{ $sessions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
