@extends('layouts.app')

@section('title', 'Ticket SAV #' . $sav->ticket_number)

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
                <i class="bi bi-ticket me-2"></i>Ticket {{ $sav->ticket_number }}
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('sav.index') }}">SAV</a></li>
                    <li class="breadcrumb-item active">{{ $sav->ticket_number }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-{{ $sav->priority_color }} fs-6">{{ $sav->priority_name }}</span>
            <span class="badge bg-{{ $sav->status_color }} fs-6">{{ $sav->status_name }}</span>
        </div>
    </div>

    <div class="row g-4">
        <!-- Colonne principale -->
        <div class="col-lg-8">
            <!-- Informations du ticket -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Détails du ticket</h5>
                    <span class="badge bg-secondary">{{ $sav->type_name }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Client :</strong>
                            @if($sav->customer)
                                <p class="mb-1">{{ $sav->customer->full_name }}</p>
                                <small class="text-muted">
                                    <i class="bi bi-telephone"></i> {{ $sav->customer->phone }}
                                    @if($sav->customer->email)
                                        <br><i class="bi bi-envelope"></i> {{ $sav->customer->email }}
                                    @endif
                                </small>
                            @else
                                <p class="text-muted mb-0">Non spécifié</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <strong>Produit :</strong>
                            @if($sav->product)
                                <p class="mb-1">{{ $sav->product->name }}</p>
                                <small class="text-muted">SKU: {{ $sav->product->sku }}</small>
                            @elseif($sav->product_name)
                                <p class="mb-1">{{ $sav->product_name }}</p>
                                @if($sav->product_serial)
                                    <small class="text-muted">S/N: {{ $sav->product_serial }}</small>
                                @endif
                            @else
                                <p class="text-muted mb-0">Non spécifié</p>
                            @endif
                        </div>
                        @if($sav->sale)
                            <div class="col-12">
                                <strong><i class="bi bi-cart text-primary"></i> Vente associée :</strong>
                                <p class="mb-0">
                                    <a href="{{ route('cashier.sales.show', $sav->sale) }}">
                                        {{ $sav->sale->invoice_number }}
                                    </a>
                                    - {{ $sav->sale->created_at->format('d/m/Y') }}
                                </p>
                            </div>
                        @endif
                        @if($sav->repair)
                            <div class="col-12">
                                <strong><i class="bi bi-tools text-success"></i> Réparation associée :</strong>
                                <p class="mb-0">
                                    <a href="{{ route('cashier.repairs.show', $sav->repair) }}">
                                        {{ $sav->repair->repair_number }}
                                    </a>
                                    - {{ $sav->repair->device_brand }} {{ $sav->repair->device_model }}
                                    @if($sav->repair->delivered_at)
                                        <br><small class="text-muted">Livrée le {{ $sav->repair->delivered_at->format('d/m/Y') }}</small>
                                    @endif
                                </p>
                            </div>
                        @endif
                        @if($sav->purchase_date)
                            <div class="col-md-6">
                                <strong>Date d'achat :</strong>
                                <p class="mb-0">{{ $sav->purchase_date->format('d/m/Y') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Description du problème -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-chat-text me-2"></i>Description du problème</h5>
                </div>
                <div class="card-body">
                    <div class="p-3 bg-light rounded mb-3">
                        {!! nl2br(e($sav->issue_description)) !!}
                    </div>
                    @if($sav->customer_request)
                        <strong>Demande du client :</strong>
                        <p class="mb-0">{!! nl2br(e($sav->customer_request)) !!}</p>
                    @endif
                </div>
            </div>

            <!-- Résolution (si résolue) -->
            @if($sav->resolution_notes || $sav->resolution_type)
                <div class="card border-0 shadow-sm mb-4 border-start border-success border-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 text-success"><i class="bi bi-check-circle me-2"></i>Résolution</h5>
                    </div>
                    <div class="card-body">
                        @if($sav->resolution_type)
                            <p><strong>Type de résolution :</strong> 
                                @switch($sav->resolution_type)
                                    @case('repaired') Réparé @break
                                    @case('exchanged') Échangé @break
                                    @case('refunded') Remboursé @break
                                    @case('rejected') Rejeté @break
                                    @case('no_action') Aucune action nécessaire @break
                                    @default Autre
                                @endswitch
                            </p>
                        @endif
                        @if($sav->refund_amount > 0)
                            <p><strong>Montant remboursé :</strong> {{ number_format($sav->refund_amount, 0, ',', ' ') }} FCFA</p>
                        @endif
                        @if($sav->resolution_notes)
                            <p class="mb-0"><strong>Notes :</strong><br>{!! nl2br(e($sav->resolution_notes)) !!}</p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Historique / Commentaires -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Historique & Commentaires</h5>
                </div>
                <div class="card-body">
                    <!-- Formulaire d'ajout de commentaire -->
                    <form action="{{ route('sav.add-comment', $sav) }}" method="POST" class="mb-4">
                        @csrf
                        <div class="mb-3">
                            <textarea name="comment" class="form-control" rows="2" required 
                                      placeholder="Ajouter un commentaire..."></textarea>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_internal" id="isInternal" checked>
                                <label class="form-check-label" for="isInternal">
                                    Commentaire interne (non visible client)
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-send me-2"></i>Ajouter
                            </button>
                        </div>
                    </form>

                    <!-- Liste des commentaires -->
                    <div class="timeline">
                        @forelse($sav->comments as $comment)
                            <div class="d-flex mb-3 {{ $comment->is_internal ? 'ps-3 border-start border-warning border-2' : '' }}">
                                <div class="flex-shrink-0">
                                    <div class="bg-{{ $comment->is_internal ? 'warning' : 'primary' }} rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        <i class="bi bi-{{ $comment->is_internal ? 'lock' : 'chat' }} text-white"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $comment->user->name }}</strong>
                                        <small class="text-muted">{{ $comment->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <p class="mb-0">{!! nl2br(e($comment->comment)) !!}</p>
                                    @if($comment->is_internal)
                                        <small class="text-warning"><i class="bi bi-lock"></i> Commentaire interne</small>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted text-center">Aucun commentaire pour le moment</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne latérale -->
        <div class="col-lg-4">
            <!-- Actions rapides -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Actions</h5>
                </div>
                <div class="card-body">
                    <!-- Changer le statut -->
                    <form action="{{ route('sav.update-status', $sav) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select" id="statusSelect">
                                <option value="open" {{ $sav->status == 'open' ? 'selected' : '' }}>Ouvert</option>
                                <option value="in_progress" {{ $sav->status == 'in_progress' ? 'selected' : '' }}>En cours</option>
                                <option value="waiting_customer" {{ $sav->status == 'waiting_customer' ? 'selected' : '' }}>Attente client</option>
                                <option value="waiting_parts" {{ $sav->status == 'waiting_parts' ? 'selected' : '' }}>Attente pièces</option>
                                <option value="resolved" {{ $sav->status == 'resolved' ? 'selected' : '' }}>Résolu</option>
                                <option value="closed" {{ $sav->status == 'closed' ? 'selected' : '' }}>Fermé</option>
                                <option value="rejected" {{ $sav->status == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                            </select>
                        </div>

                        <!-- Champs de résolution (affichés conditionnellement) -->
                        <div id="resolutionFields" class="d-none">
                            <div class="mb-3">
                                <label class="form-label">Type de résolution</label>
                                <select name="resolution_type" class="form-select">
                                    <option value="">-- Sélectionner --</option>
                                    <option value="repaired" {{ $sav->resolution_type == 'repaired' ? 'selected' : '' }}>Réparé</option>
                                    <option value="exchanged" {{ $sav->resolution_type == 'exchanged' ? 'selected' : '' }}>Échangé</option>
                                    <option value="refunded" {{ $sav->resolution_type == 'refunded' ? 'selected' : '' }}>Remboursé</option>
                                    <option value="rejected" {{ $sav->resolution_type == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                                    <option value="no_action" {{ $sav->resolution_type == 'no_action' ? 'selected' : '' }}>Aucune action</option>
                                    <option value="other" {{ $sav->resolution_type == 'other' ? 'selected' : '' }}>Autre</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Montant remboursé (FCFA)</label>
                                <input type="number" name="refund_amount" class="form-control" 
                                       value="{{ $sav->refund_amount }}" min="0">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes de résolution</label>
                                <textarea name="resolution_notes" class="form-control" rows="3">{{ $sav->resolution_notes }}</textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-2"></i>Mettre à jour
                        </button>
                    </form>

                    <hr class="my-3">

                    <!-- Retour en stock -->
                    @if(in_array($sav->type, ['return', 'exchange', 'refund']))
                        @if($sav->stock_returned)
                        <div class="alert alert-success py-2 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle me-2"></i>
                                <div class="small">
                                    <strong>Retour en stock effectué</strong><br>
                                    {{ $sav->quantity_returned }} article(s) le {{ $sav->stock_returned_at->format('d/m/Y') }}
                                    @if($sav->refund_amount)
                                        <br><span class="text-danger">Remboursement: {{ number_format($sav->refund_amount, 0, ',', ' ') }} F</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <!-- Bouton d'annulation du retour -->
                        <form action="{{ route('sav.cancel-stock-return', $sav) }}" method="POST" 
                              onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce retour en stock ?\n\nCela va:\n- Retirer les produits du stock\n- Annuler le remboursement (si applicable)\n- Remettre le ticket en état précédent');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                <i class="bi bi-x-circle me-2"></i>Annuler le retour en stock
                            </button>
                        </form>
                        @else
                        <a href="{{ route('sav.stock-return', $sav) }}" class="btn btn-success w-100">
                            <i class="bi bi-box-arrow-in-down me-2"></i>
                            Retour en stock
                        </a>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Assignation -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-person-check me-2"></i>Assignation</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('sav.assign', $sav) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <select name="assigned_to" class="form-select">
                                <option value="">-- Non assigné --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ $sav->assigned_to == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-person-plus me-2"></i>Assigner
                        </button>
                    </form>
                </div>
            </div>

            <!-- Informations -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informations</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted">Créé par</td>
                            <td>{{ $sav->creator->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Créé le</td>
                            <td>{{ $sav->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @if($sav->assignedUser)
                            <tr>
                                <td class="text-muted">Assigné à</td>
                                <td>{{ $sav->assignedUser->name }}</td>
                            </tr>
                        @endif
                        @if($sav->resolved_at)
                            <tr>
                                <td class="text-muted">Résolu le</td>
                                <td>{{ $sav->resolved_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endif
                        @if($sav->closed_at)
                            <tr>
                                <td class="text-muted">Fermé le</td>
                                <td>{{ $sav->closed_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('statusSelect');
    const resolutionFields = document.getElementById('resolutionFields');

    function toggleResolutionFields() {
        const status = statusSelect.value;
        if (status === 'resolved' || status === 'closed' || status === 'rejected') {
            resolutionFields.classList.remove('d-none');
        } else {
            resolutionFields.classList.add('d-none');
        }
    }

    statusSelect.addEventListener('change', toggleResolutionFields);
    toggleResolutionFields(); // Initial check
});
</script>
@endpush
