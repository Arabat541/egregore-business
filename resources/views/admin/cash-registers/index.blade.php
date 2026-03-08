@extends('layouts.app')

@section('title', 'Gestion des Caisses')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-cash-stack me-2"></i>Gestion des Caisses
            </h1>
            <p class="text-muted mb-0">Superviser et gérer les caisses des caissières</p>
        </div>
        <a href="{{ route('admin.cash-registers.export', request()->query()) }}" class="btn btn-success">
            <i class="bi bi-download me-1"></i>Exporter CSV
        </a>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                @if(isset($shops) && $shops->count() > 0)
                <div class="col-md-2">
                    <label class="form-label">Boutique</label>
                    <select name="shop_id" class="form-select">
                        <option value="">Toutes</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                                {{ $shop->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label">Caissière</label>
                    <select name="user_id" class="form-select">
                        <option value="">Toutes</option>
                        @foreach($cashiers as $cashier)
                            <option value="{{ $cashier->id }}" {{ request('user_id') == $cashier->id ? 'selected' : '' }}>
                                {{ $cashier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous</option>
                        <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Ouvertes</option>
                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Fermées</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date début</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <a href="{{ route('admin.cash-registers.index') }}" class="btn btn-outline-secondary">
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
                    <h6 class="text-uppercase opacity-75">Caisses ouvertes</h6>
                    <h3 class="mb-0">{{ \App\Models\CashRegister::open()->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-secondary text-white">
                <div class="card-body">
                    <h6 class="text-uppercase opacity-75">Fermées aujourd'hui</h6>
                    <h3 class="mb-0">{{ \App\Models\CashRegister::closed()->whereDate('date', today())->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-warning">
                <div class="card-body">
                    <h6 class="text-uppercase opacity-75">Écarts aujourd'hui</h6>
                    <h3 class="mb-0">
                        {{ number_format(\App\Models\CashRegister::closed()->whereDate('date', today())->sum('difference'), 0, ',', ' ') }} F
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-info text-white">
                <div class="card-body">
                    <h6 class="text-uppercase opacity-75">Total caisses</h6>
                    <h3 class="mb-0">{{ \App\Models\CashRegister::count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des caisses -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Caissière</th>
                            <th>Ouverture</th>
                            <th>Fermeture</th>
                            <th class="text-end">Solde ouverture</th>
                            <th class="text-end">Solde attendu</th>
                            <th class="text-end">Solde fermeture</th>
                            <th class="text-end">Écart</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cashRegisters as $cr)
                        <tr class="{{ $cr->status === 'open' ? 'table-success' : '' }}">
                            <td>
                                <strong>{{ $cr->date->format('d/m/Y') }}</strong>
                                @if($cr->date->isToday())
                                    <span class="badge bg-primary ms-1">Aujourd'hui</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                         style="width: 30px; height: 30px; font-size: 0.8rem;">
                                        {{ strtoupper(substr($cr->user?->name ?? 'N', 0, 1)) }}
                                    </div>
                                    {{ $cr->user?->name ?? 'N/A' }}
                                </div>
                            </td>
                            <td>
                                @if($cr->opened_at)
                                    <i class="bi bi-clock text-success me-1"></i>
                                    {{ $cr->opened_at->format('H:i') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($cr->closed_at)
                                    <i class="bi bi-clock text-danger me-1"></i>
                                    {{ $cr->closed_at->format('H:i') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                {{ number_format($cr->opening_balance, 0, ',', ' ') }} F
                            </td>
                            <td class="text-end">
                                @if($cr->expected_balance !== null)
                                    {{ number_format($cr->expected_balance, 0, ',', ' ') }} F
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($cr->closing_balance !== null)
                                    {{ number_format($cr->closing_balance, 0, ',', ' ') }} F
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($cr->difference !== null)
                                    @if($cr->difference > 0)
                                        <span class="text-success">+{{ number_format($cr->difference, 0, ',', ' ') }} F</span>
                                    @elseif($cr->difference < 0)
                                        <span class="text-danger">{{ number_format($cr->difference, 0, ',', ' ') }} F</span>
                                    @else
                                        <span class="text-success">0 F</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($cr->status === 'open')
                                    <span class="badge bg-success">
                                        <i class="bi bi-unlock me-1"></i>Ouverte
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-lock me-1"></i>Fermée
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="{{ route('admin.cash-registers.show', $cr) }}" 
                                       class="btn btn-outline-info btn-sm" title="Voir détails">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @if($cr->status === 'closed')
                                        <button type="button" class="btn btn-outline-warning btn-sm" 
                                                data-bs-toggle="modal" data-bs-target="#reopenModal{{ $cr->id }}"
                                                title="Réouvrir">
                                            <i class="bi bi-unlock"></i>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#forceCloseModal{{ $cr->id }}"
                                                title="Forcer fermeture">
                                            <i class="bi bi-lock"></i>
                                        </button>
                                    @endif

                                    @if($cr->transactions->count() === 0)
                                        <form action="{{ route('admin.cash-registers.destroy', $cr) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Supprimer cette caisse vide ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <!-- Modal Réouvrir -->
                                <div class="modal fade" id="reopenModal{{ $cr->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.cash-registers.reopen', $cr) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Réouvrir la caisse</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <div class="alert alert-warning">
                                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                                        <strong>Attention !</strong> Réouvrir une caisse permet à la caissière de continuer à enregistrer des transactions.
                                                    </div>
                                                    <p><strong>Caissière :</strong> {{ $cr->user?->name }}</p>
                                                    <p><strong>Date :</strong> {{ $cr->date->format('d/m/Y') }}</p>
                                                    <p><strong>Solde fermeture :</strong> {{ number_format($cr->closing_balance ?? 0, 0, ',', ' ') }} F</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-warning">
                                                        <i class="bi bi-unlock me-1"></i>Réouvrir
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Forcer fermeture -->
                                <div class="modal fade" id="forceCloseModal{{ $cr->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.cash-registers.force-close', $cr) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Forcer la fermeture</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <div class="alert alert-danger">
                                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                                        <strong>Attention !</strong> Cette action fermera la caisse de force.
                                                    </div>
                                                    <p><strong>Caissière :</strong> {{ $cr->user?->name }}</p>
                                                    <p><strong>Solde calculé :</strong> {{ number_format($cr->calculated_balance, 0, ',', ' ') }} F</p>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Solde de fermeture *</label>
                                                        <input type="number" name="closing_balance" class="form-control" 
                                                               value="{{ $cr->calculated_balance }}" required min="0">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Notes</label>
                                                        <textarea name="notes" class="form-control" rows="2"
                                                                  placeholder="Raison de la fermeture forcée..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="bi bi-lock me-1"></i>Forcer fermeture
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <i class="bi bi-inbox text-muted fs-1"></i>
                                <p class="text-muted mb-0 mt-2">Aucune caisse trouvée</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($cashRegisters->hasPages())
        <div class="card-footer">
            {{ $cashRegisters->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
