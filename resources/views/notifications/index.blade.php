@extends('layouts.app')

@section('title', 'Notifications')

@section('sidebar')
    @if(auth()->user()->hasRole('admin'))
        @include('admin.partials.sidebar')
    @elseif(auth()->user()->hasRole('caissiere'))
        @include('cashier.partials.sidebar')
    @else
        @include('technician.partials.sidebar')
    @endif
@endsection

@section('content')
<div class="container-fluid">
    {{-- En-tête --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-bell me-2"></i>Notifications
            </h1>
            <p class="text-muted mb-0">Gérez vos notifications et alertes</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" id="markAllReadBtn">
                <i class="bi bi-check-all me-1"></i>Tout marquer comme lu
            </button>
            <button type="button" class="btn btn-outline-danger" id="clearReadBtn">
                <i class="bi bi-trash me-1"></i>Supprimer les lues
            </button>
        </div>
    </div>

    {{-- Statistiques --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['total'] ?? 0 }}</h3>
                    <small>Total</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-warning text-dark">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['unread'] ?? 0 }}</h3>
                    <small>Non lues</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-danger text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['important'] ?? 0 }}</h3>
                    <small>Importantes</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <form action="{{ route('notifications.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-auto">
                    <div class="btn-group" role="group">
                        <a href="{{ route('notifications.index') }}" 
                           class="btn btn-{{ !request('status') ? 'primary' : 'outline-primary' }}">
                            Toutes
                        </a>
                        <a href="{{ route('notifications.index', ['status' => 'unread']) }}" 
                           class="btn btn-{{ request('status') === 'unread' ? 'primary' : 'outline-primary' }}">
                            Non lues
                        </a>
                        <a href="{{ route('notifications.index', ['status' => 'read']) }}" 
                           class="btn btn-{{ request('status') === 'read' ? 'primary' : 'outline-primary' }}">
                            Lues
                        </a>
                    </div>
                </div>
                <div class="col-auto">
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">Tous les types</option>
                        <option value="repair_new" {{ request('type') === 'repair_new' ? 'selected' : '' }}>🔧 Nouvelle réparation</option>
                        <option value="repair_ready" {{ request('type') === 'repair_ready' ? 'selected' : '' }}>✅ Réparation prête</option>
                        <option value="stock_low" {{ request('type') === 'stock_low' ? 'selected' : '' }}>⚠️ Stock bas</option>
                        <option value="stock_critical" {{ request('type') === 'stock_critical' ? 'selected' : '' }}>🔴 Stock critique</option>
                        <option value="sav_new" {{ request('type') === 'sav_new' ? 'selected' : '' }}>🎫 Nouveau SAV</option>
                        <option value="sav_urgent" {{ request('type') === 'sav_urgent' ? 'selected' : '' }}>🔴 SAV urgent</option>
                        <option value="sale_completed" {{ request('type') === 'sale_completed' ? 'selected' : '' }}>🛒 Vente</option>
                        <option value="system" {{ request('type') === 'system' ? 'selected' : '' }}>⚙️ Système</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- Liste des notifications --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @forelse($notifications as $notification)
                <div class="d-flex align-items-start p-3 border-bottom {{ $notification->is_read ? '' : 'bg-light' }} {{ $notification->is_important ? 'border-start border-danger border-3' : '' }}"
                     id="notification-{{ $notification->id }}">
                    {{-- Icône --}}
                    <div class="flex-shrink-0 me-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-{{ $notification->color }} bg-opacity-10" 
                              style="width: 50px; height: 50px;">
                            <i class="bi {{ $notification->icon }} text-{{ $notification->color }} fs-4"></i>
                        </span>
                    </div>
                    
                    {{-- Contenu --}}
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1 {{ $notification->is_read ? 'fw-normal' : 'fw-bold' }}">
                                    {{ $notification->title }}
                                    @if(!$notification->is_read)
                                        <span class="badge bg-primary rounded-pill ms-2" style="font-size: 0.65rem;">Nouveau</span>
                                    @endif
                                    @if($notification->is_important)
                                        <span class="badge bg-danger rounded-pill ms-1" style="font-size: 0.65rem;">Important</span>
                                    @endif
                                </h6>
                                <p class="mb-1 text-muted">{{ $notification->message }}</p>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>{{ $notification->time_ago }}
                                    @if($notification->read_at)
                                        <span class="ms-2"><i class="bi bi-check2 me-1"></i>Lu {{ $notification->read_at->diffForHumans() }}</span>
                                    @endif
                                </small>
                            </div>
                            
                            {{-- Actions --}}
                            <div class="d-flex gap-1">
                                @if($notification->link)
                                    <a href="{{ $notification->link }}" class="btn btn-sm btn-outline-primary" 
                                       onclick="markAsRead('{{ $notification->id }}')" title="Voir">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                @endif
                                @if(!$notification->is_read)
                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                            onclick="markAsRead('{{ $notification->id }}')" title="Marquer comme lu">
                                        <i class="bi bi-check"></i>
                                    </button>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteNotification('{{ $notification->id }}')" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bell-slash fs-1"></i>
                    <p class="mt-2 mb-0">Aucune notification</p>
                </div>
            @endforelse
        </div>
        
        @if($notifications->hasPages())
            <div class="card-footer bg-white">
                {{ $notifications->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
const csrfToken = '{{ csrf_token() }}';

function markAsRead(id) {
    fetch(`/notifications/${id}/read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const el = document.getElementById(`notification-${id}`);
            if (el) {
                el.classList.remove('bg-light');
                el.querySelector('.fw-bold')?.classList.replace('fw-bold', 'fw-normal');
                el.querySelector('.badge.bg-primary')?.remove();
            }
        }
    });
}

function deleteNotification(id) {
    if (!confirm('Supprimer cette notification ?')) return;
    
    fetch(`/notifications/${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const el = document.getElementById(`notification-${id}`);
            if (el) {
                el.style.transition = 'opacity 0.3s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            }
        }
    });
}

document.getElementById('markAllReadBtn')?.addEventListener('click', function() {
    fetch('{{ route("notifications.mark-all-read") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
});

document.getElementById('clearReadBtn')?.addEventListener('click', function() {
    if (!confirm('Supprimer toutes les notifications lues ?')) return;
    
    fetch('{{ route("notifications.clear-read") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
});
</script>
@endpush
@endsection
