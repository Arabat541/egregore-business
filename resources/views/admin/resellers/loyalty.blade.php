@extends('layouts.app')

@section('title', 'Rapport de Fidélité - Revendeurs')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="bi bi-award me-2"></i>Rapport de Fidélité
            </h1>
            <p class="text-muted mb-0">Programme de fidélité revendeurs - Année {{ $year }}</p>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <select name="year" class="form-select" onchange="this.form.submit()">
                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Imprimer
            </button>
        </div>
    </div>

    <!-- Statistiques globales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body text-center">
                    <i class="bi bi-shop fs-1 mb-2"></i>
                    <h6 class="text-white-50">Revendeurs Actifs</h6>
                    <h2>{{ $stats['active_resellers'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body text-center">
                    <i class="bi bi-cash-stack fs-1 mb-2"></i>
                    <h6 class="text-white-50">CA Total Année</h6>
                    <h2>{{ number_format($stats['total_revenue'], 0, ',', ' ') }} <small>F</small></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-dark">
                <div class="card-body text-center">
                    <i class="bi bi-gift fs-1 mb-2"></i>
                    <h6 class="text-muted">Bonus à Distribuer</h6>
                    <h2>{{ number_format($stats['total_bonus'], 0, ',', ' ') }} <small>F</small></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle fs-1 mb-2"></i>
                    <h6 class="text-white-50">Bonus Versés</h6>
                    <h2>{{ number_format($stats['paid_bonus'], 0, ',', ' ') }} <small>F</small></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Règles du programme de fidélité -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Règles du Programme de Fidélité</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-secondary me-2">Bronze</span>
                        <span>CA < 500 000 F → <strong>0%</strong></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-warning text-dark me-2">Argent</span>
                        <span>500 000 - 1 000 000 F → <strong>2%</strong></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-info me-2">Or</span>
                        <span>1 000 000 - 2 500 000 F → <strong>3%</strong></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-primary me-2">Platine</span>
                        <span>2 500 000 - 5 000 000 F → <strong>4%</strong></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-danger me-2">Diamant</span>
                        <span>> 5 000 000 F → <strong>5%</strong></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">* Bonus calculé sur le CA annuel payé</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des revendeurs -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i>Détail par Revendeur</h5>
            @if($year == date('Y') && date('m') == 12)
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payAllBonusModal">
                    <i class="bi bi-cash-coin me-1"></i>Verser tous les bonus
                </button>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Revendeur</th>
                            <th>Boutique</th>
                            <th class="text-end">CA Annuel</th>
                            <th class="text-end">CA Payé</th>
                            <th class="text-center">Niveau</th>
                            <th class="text-center">Taux</th>
                            <th class="text-end">Bonus</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resellers as $reseller)
                            @php
                                $data = $resellerData[$reseller->id] ?? [
                                    'total_purchases' => 0,
                                    'total_paid' => 0,
                                    'tier' => 'bronze',
                                    'rate' => 0,
                                    'bonus' => 0,
                                    'is_paid' => false
                                ];
                                
                                $tierColors = [
                                    'bronze' => 'secondary',
                                    'silver' => 'warning',
                                    'gold' => 'info',
                                    'platinum' => 'primary',
                                    'diamond' => 'danger'
                                ];
                                $tierNames = [
                                    'bronze' => 'Bronze',
                                    'silver' => 'Argent',
                                    'gold' => 'Or',
                                    'platinum' => 'Platine',
                                    'diamond' => 'Diamant'
                                ];
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.resellers.show', $reseller) }}">
                                        <strong>{{ $reseller->company_name }}</strong>
                                    </a>
                                    <br><small class="text-muted">{{ $reseller->contact_name }}</small>
                                </td>
                                <td>{{ $reseller->shop->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format($data['total_purchases'], 0, ',', ' ') }} F</td>
                                <td class="text-end">{{ number_format($data['total_paid'], 0, ',', ' ') }} F</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $tierColors[$data['tier']] ?? 'secondary' }}">
                                        {{ $tierNames[$data['tier']] ?? 'Bronze' }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $data['rate'] }}%</td>
                                <td class="text-end fw-bold text-success">{{ number_format($data['bonus'], 0, ',', ' ') }} F</td>
                                <td class="text-center">
                                    @if($data['is_paid'])
                                        <span class="badge bg-success"><i class="bi bi-check"></i> Versé</span>
                                    @elseif($data['bonus'] > 0)
                                        <span class="badge bg-warning text-dark">En attente</span>
                                    @else
                                        <span class="badge bg-secondary">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.resellers.statement', $reseller) }}?start_date={{ $year }}-01-01&end_date={{ $year }}-12-31" 
                                           class="btn btn-outline-primary" title="Relevé annuel">
                                            <i class="bi bi-file-text"></i>
                                        </a>
                                        @if(!$data['is_paid'] && $data['bonus'] > 0)
                                            <button class="btn btn-outline-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#payBonusModal"
                                                    data-reseller-id="{{ $reseller->id }}"
                                                    data-reseller-name="{{ $reseller->company_name }}"
                                                    data-bonus="{{ $data['bonus'] }}"
                                                    title="Verser le bonus">
                                                <i class="bi bi-cash-coin"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Aucun revendeur trouvé
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="2">TOTAL</td>
                            <td class="text-end">{{ number_format($stats['total_revenue'], 0, ',', ' ') }} F</td>
                            <td class="text-end">{{ number_format($stats['total_paid_revenue'], 0, ',', ' ') }} F</td>
                            <td colspan="2"></td>
                            <td class="text-end text-success">{{ number_format($stats['total_bonus'], 0, ',', ' ') }} F</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Historique des bonus versés -->
    @if($bonusHistory->count() > 0)
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Historique des Bonus Versés ({{ $year }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Revendeur</th>
                            <th>CA Annuel</th>
                            <th>Niveau</th>
                            <th>Taux</th>
                            <th class="text-end">Montant</th>
                            <th>Méthode</th>
                            <th>Par</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bonusHistory as $bonus)
                            <tr>
                                <td>{{ $bonus->paid_at ? $bonus->paid_at->format('d/m/Y') : '-' }}</td>
                                <td>{{ $bonus->reseller->company_name ?? '-' }}</td>
                                <td>{{ number_format($bonus->yearly_purchases, 0, ',', ' ') }} F</td>
                                <td>
                                    @php
                                        $tierColors = ['bronze' => 'secondary', 'silver' => 'warning', 'gold' => 'info', 'platinum' => 'primary', 'diamond' => 'danger'];
                                        $tierNames = ['bronze' => 'Bronze', 'silver' => 'Argent', 'gold' => 'Or', 'platinum' => 'Platine', 'diamond' => 'Diamant'];
                                    @endphp
                                    <span class="badge bg-{{ $tierColors[$bonus->tier] ?? 'secondary' }}">
                                        {{ $tierNames[$bonus->tier] ?? 'Bronze' }}
                                    </span>
                                </td>
                                <td>{{ $bonus->bonus_rate }}%</td>
                                <td class="text-end fw-bold">{{ number_format($bonus->bonus_amount, 0, ',', ' ') }} F</td>
                                <td>{{ ucfirst($bonus->payment_method ?? '-') }}</td>
                                <td>{{ $bonus->paidBy->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal Paiement Bonus Individuel -->
<div class="modal fade" id="payBonusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.resellers.pay-bonus') }}">
                @csrf
                <input type="hidden" name="reseller_id" id="bonusResellerId">
                <input type="hidden" name="year" value="{{ $year }}">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>Verser le Bonus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Verser le bonus de fidélité à <strong id="bonusResellerName"></strong></p>
                    <div class="alert alert-success">
                        <strong>Montant :</strong> <span id="bonusAmount"></span> F
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mode de paiement</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Espèces</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="bank_transfer">Virement bancaire</option>
                            <option value="credit">Crédit sur compte</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (optionnel)</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check me-1"></i>Confirmer le versement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const payBonusModal = document.getElementById('payBonusModal');
    if (payBonusModal) {
        payBonusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('bonusResellerId').value = button.dataset.resellerId;
            document.getElementById('bonusResellerName').textContent = button.dataset.resellerName;
            document.getElementById('bonusAmount').textContent = new Intl.NumberFormat('fr-FR').format(button.dataset.bonus);
        });
    }
});
</script>
@endpush

@push('styles')
<style>
    @media print {
        .btn, nav, .sidebar, .modal, form:not(.d-print-block) {
            display: none !important;
        }
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
    }
</style>
@endpush
@endsection
