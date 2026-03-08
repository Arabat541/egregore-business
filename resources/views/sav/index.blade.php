@extends('layouts.app')

@section('title', 'Service Après-Vente')

@section('sidebar')
    @if(auth()->user()->hasRole('admin'))
        @include('admin.partials.sidebar')
    @else
        @include('cashier.partials.sidebar')
    @endif
@endsection

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-headset me-2"></i>Service Après-Vente
            </h1>
            <small class="text-muted">Gestion des retours, échanges et réclamations</small>
        </div>
        <div>
            <a href="{{ route('sav.dashboard') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard SAV
            </a>
            <a href="{{ route('sav.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Nouveau Ticket
            </a>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-white-50">Total Tickets</h6>
                            <h3 class="mb-0">{{ $stats['total'] }}</h3>
                        </div>
                        <i class="bi bi-ticket fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 opacity-75">En cours</h6>
                            <h3 class="mb-0">{{ $stats['open'] }}</h3>
                        </div>
                        <i class="bi bi-hourglass-split fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-white-50">Urgents</h6>
                            <h3 class="mb-0">{{ $stats['urgent'] }}</h3>
                        </div>
                        <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-white-50">Résolus aujourd'hui</h6>
                            <h3 class="mb-0">{{ $stats['resolved_today'] }}</h3>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." 
                           value="{{ request('search') }}">
                </div>
                @if(auth()->user()->hasRole('admin') && isset($shops) && $shops->count() > 0)
                <div class="col-md-2">
                    <select name="shop_id" class="form-select">
                        <option value="">Toutes boutiques</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                                {{ $shop->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Ouvert</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>En cours</option>
                        <option value="waiting_customer" {{ request('status') == 'waiting_customer' ? 'selected' : '' }}>Attente client</option>
                        <option value="waiting_parts" {{ request('status') == 'waiting_parts' ? 'selected' : '' }}>Attente pièces</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Résolu</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Fermé</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">Tous les types</option>
                        <option value="return" {{ request('type') == 'return' ? 'selected' : '' }}>Retour</option>
                        <option value="exchange" {{ request('type') == 'exchange' ? 'selected' : '' }}>Échange</option>
                        <option value="warranty" {{ request('type') == 'warranty' ? 'selected' : '' }}>Garantie</option>
                        <option value="complaint" {{ request('type') == 'complaint' ? 'selected' : '' }}>Réclamation</option>
                        <option value="refund" {{ request('type') == 'refund' ? 'selected' : '' }}>Remboursement</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="priority" class="form-select">
                        <option value="">Toutes priorités</option>
                        <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgente</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Haute</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Moyenne</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Basse</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-2"></i>Filtrer
                    </button>
                    <a href="{{ route('sav.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des tickets -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N° Ticket</th>
                            <th>Type</th>
                            <th>Client</th>
                            <th>Produit</th>
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th>Assigné à</th>
                            <th>Créé le</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                            <tr class="{{ $ticket->priority == 'urgent' ? 'table-danger' : ($ticket->priority == 'high' ? 'table-warning' : '') }}">
                                <td>
                                    <a href="{{ route('sav.show', $ticket) }}" class="fw-bold text-decoration-none">
                                        {{ $ticket->ticket_number }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $ticket->type_name }}</span>
                                </td>
                                <td>
                                    @if($ticket->customer)
                                        {{ $ticket->customer->full_name }}
                                        <small class="text-muted d-block">{{ $ticket->customer->phone }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ticket->product)
                                        {{ Str::limit($ticket->product->name, 30) }}
                                    @elseif($ticket->product_name)
                                        {{ Str::limit($ticket->product_name, 30) }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $ticket->priority_color }}">
                                        {{ $ticket->priority_name }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $ticket->status_color }}">
                                        {{ $ticket->status_name }}
                                    </span>
                                </td>
                                <td>
                                    @if($ticket->assignedUser)
                                        <small>{{ $ticket->assignedUser->name }}</small>
                                    @else
                                        <span class="text-muted">Non assigné</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $ticket->created_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('sav.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Aucun ticket SAV trouvé
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($tickets->hasPages())
            <div class="card-footer">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
