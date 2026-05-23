@extends('layouts.app')

@section('title', 'Réparation ' . $repair->repair_number)

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="bi bi-tools"></i> {{ $repair->repair_number }}
        <span class="badge bg-{{ $repair->status_color }}">{{ $repair->status_label }}</span>
    </h2>
    <div>
        <a href="{{ route('cashier.repairs.ticket', $repair) }}" class="btn btn-primary" target="_blank">
            <i class="bi bi-printer"></i> Ticket
        </a>
        <a href="{{ route('cashier.repairs.sticker', $repair) }}" class="btn btn-info" target="_blank" title="Étiquette autocollante">
            <i class="bi bi-tag"></i> Étiquette
        </a>
        <a href="{{ route('cashier.repairs.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
</div>

{{-- Timeline statut réparation --}}
@include('cashier.repairs._timeline', ['repair' => $repair])

<div class="row">
    <div class="col-md-8">
        <!-- Informations client -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-person"></i> Client
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nom:</strong> {{ $repair->customer->full_name }}</p>
                        <p><strong>Téléphone:</strong> {{ $repair->customer->phone }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Email:</strong> {{ $repair->customer->email ?: '-' }}</p>
                        <p><strong>Adresse:</strong> {{ $repair->customer->address ?: '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations appareil -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-phone"></i> Appareil
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Type:</strong> {{ ucfirst($repair->device_type) }}</p>
                        <p><strong>Marque:</strong> {{ $repair->device_brand }}</p>
                        <p><strong>Modèle:</strong> {{ $repair->device_model }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>IMEI/Série:</strong> {{ $repair->device_serial ?: '-' }}</p>
                        <p><strong>Code:</strong> {{ $repair->device_password ?: '-' }}</p>
                        <p><strong>Accessoires:</strong> {{ $repair->accessories ?: '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Problème -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-exclamation-triangle"></i> Problème signalé
            </div>
            <div class="card-body">
                <p>{{ $repair->reported_issue }}</p>
                @if($repair->physical_condition)
                    <hr>
                    <p class="mb-0"><strong>État physique:</strong> {{ $repair->physical_condition }}</p>
                @endif
            </div>
        </div>

        <!-- Diagnostic -->
        @if($repair->diagnosis)
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <i class="bi bi-search"></i> Diagnostic
            </div>
            <div class="card-body">
                {{ $repair->diagnosis }}
            </div>
        </div>
        @endif

        <!-- Travaux effectués -->
        @if($repair->work_done)
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <i class="bi bi-check-circle"></i> Travaux effectués
            </div>
            <div class="card-body">
                {{ $repair->work_done }}
            </div>
        </div>
        @endif

        <!-- Pièces utilisées -->
        @if($repair->parts->count() > 0)
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-box-seam"></i> Pièces utilisées
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Pièce</th>
                            <th>Catégorie</th>
                            <th>Qté</th>
                            <th>Prix unit.</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($repair->parts as $part)
                        <tr>
                            <td>{{ $part->product->name ?? $part->description }}</td>
                            <td><span class="badge bg-secondary">{{ $part->product->category->name ?? 'N/A' }}</span></td>
                            <td>{{ $part->quantity }}</td>
                            <td>{{ number_format($part->unit_cost, 0, ',', ' ') }} FCFA</td>
                            <td>{{ number_format($part->total_cost, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4">Total pièces:</th>
                            <th>{{ number_format($repair->parts->sum('total_cost'), 0, ',', ' ') }} FCFA</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <!-- Statut et dates -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-clock-history"></i> Suivi
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td>Créée le:</td>
                        <td>{{ $repair->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @if($repair->started_at)
                    <tr>
                        <td>Démarrée le:</td>
                        <td>{{ $repair->started_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endif
                    @if($repair->completed_at)
                    <tr>
                        <td>Terminée le:</td>
                        <td>{{ $repair->completed_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endif
                    @if($repair->delivered_at)
                    <tr>
                        <td>Livrée le:</td>
                        <td>{{ $repair->delivered_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>Retrait estimé:</td>
                        <td>{{ $repair->estimated_completion_date ? $repair->estimated_completion_date->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td>Technicien:</td>
                        <td>{{ $repair->technician->name ?? 'Non assigné' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Facturation -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-currency-dollar"></i> Facturation
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    @if($repair->parts_cost > 0)
                    <tr>
                        <td>Pièces:</td>
                        <td>{{ number_format($repair->parts_cost, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @endif
                    @if($repair->labor_cost > 0)
                    <tr>
                        <td>Main d'œuvre:</td>
                        <td>{{ number_format($repair->labor_cost, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @endif
                    <tr class="table-secondary">
                        <td><strong>Total:</strong></td>
                        <td><strong>{{ number_format($repair->final_cost ?: $repair->estimated_cost, 0, ',', ' ') }} FCFA</strong></td>
                    </tr>
                    <tr class="table-success">
                        <td>Acompte versé:</td>
                        <td>{{ number_format($repair->deposit_amount, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @php
                        $totalCost = $repair->final_cost ?: $repair->estimated_cost;
                        $restant = $totalCost - $repair->deposit_amount - $repair->amount_paid;
                    @endphp
                    @if($restant > 0)
                    <tr class="table-warning">
                        <td><strong>Reste à payer (main d'œuvre):</strong></td>
                        <td><strong>{{ number_format($restant, 0, ',', ' ') }} FCFA</strong></td>
                    </tr>
                    @else
                    <tr class="table-success">
                        <td colspan="2" class="text-center"><strong>✅ PAYÉ EN TOTALITÉ</strong></td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Actions -->
        @if($repair->status === 'pending_payment')
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-cash-coin"></i> Paiement acompte
            </div>
            <div class="card-body">
                <form action="{{ route('cashier.repairs.pay', $repair) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Montant estimé</label>
                        <input type="text" class="form-control" value="{{ number_format($repair->estimated_cost, 0, ',', ' ') }} FCFA" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Acompte à verser <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="deposit_amount" 
                               value="{{ $repair->estimated_cost }}" min="0" step="100" required>
                        <small class="text-muted">Montant minimum conseillé: {{ number_format($repair->estimated_cost * 0.5, 0, ',', ' ') }} FCFA (50%)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mode de paiement <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_method_id" required>
                            <option value="">-- Sélectionner --</option>
                            @foreach($paymentMethods ?? [] as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="bi bi-check-lg"></i> Valider le paiement
                    </button>
                </form>
            </div>
        </div>
        @endif

        @if(in_array($repair->status, ['repaired', 'ready_for_pickup']))
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-check-circle"></i> Livraison
            </div>
            <div class="card-body">
                <form action="{{ route('cashier.repairs.deliver', $repair) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Montant à payer</label>
                        <input type="number" class="form-control" name="paid_amount" 
                               value="{{ ($repair->final_cost ?: $repair->estimated_cost) - $repair->deposit_amount }}" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mode de paiement</label>
                        <select class="form-select" name="payment_method_id" required>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-lg"></i> Confirmer la livraison
                    </button>
                </form>
            </div>
        </div>
        @endif

        @if($repair->internal_notes)
        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-sticky"></i> Notes internes
            </div>
            <div class="card-body">
                {{ $repair->internal_notes }}
            </div>
        </div>
        @endif

        {{-- Bouton annulation --}}
        @if(!in_array($repair->status, ['delivered', 'cancelled']))
        <div class="card mt-3 border-danger">
            <div class="card-body">
                <button type="button" class="btn btn-outline-danger w-100"
                        data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-x-circle me-1"></i>Annuler cette réparation
                </button>
            </div>
        </div>
        @endif
    </div>
</div>

@if(session('show_print_modal'))
{{-- Modal impression post-création --}}
<div class="modal fade" id="printModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle-fill me-2"></i>Réparation créée avec succès
                </h5>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">Imprimez le reçu et l'étiquette dans l'ordre.</p>

                {{-- Étape 1 : Reçu --}}
                <div class="card mb-3 border-primary">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="fs-1">🧾</div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">Étape 1 — Reçu client</h6>
                            <small class="text-muted">Ticket de dépôt à remettre au client</small>
                        </div>
                        <button class="btn btn-primary" id="btnPrintReceipt">
                            <i class="bi bi-printer"></i> Imprimer
                        </button>
                    </div>
                </div>

                {{-- Étape 2 : Étiquette --}}
                <div class="card border-info">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="fs-1">🏷️</div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">Étape 2 — Étiquette autocollante</h6>
                            <small class="text-muted">À coller sur l'appareil</small>
                        </div>
                        <button class="btn btn-info text-white" id="btnPrintSticker">
                            <i class="bi bi-tag"></i> Imprimer
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    <i class="bi bi-check-lg"></i> Terminer
                </button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('printModal'));
    modal.show();

    const ticketUrl = "{{ route('cashier.repairs.ticket', ['repair' => $repair, 'format' => 'thermal', 'amount_given' => session('print_amount_given', 0), 'change' => session('print_change', 0)]) }}";
    const stickerUrl = "{{ route('cashier.repairs.sticker', $repair) }}";

    document.getElementById('btnPrintReceipt').addEventListener('click', function() {
        const w = window.open(ticketUrl, 'PrintTicket', 'width=500,height=750,scrollbars=yes');
        if (w) { w.focus(); setTimeout(() => { try { w.print(); } catch(e){} }, 1500); }
        this.classList.replace('btn-primary', 'btn-success');
        this.innerHTML = '<i class="bi bi-check-lg"></i> Imprimé';
    });

    document.getElementById('btnPrintSticker').addEventListener('click', function() {
        const w = window.open(stickerUrl, 'PrintSticker', 'width=400,height=300,scrollbars=yes');
        if (w) { w.focus(); setTimeout(() => { try { w.print(); } catch(e){} }, 1500); }
        this.classList.replace('btn-info', 'btn-success');
        this.innerHTML = '<i class="bi bi-check-lg"></i> Imprimé';
    });
});
</script>
@endif

{{-- Modal confirmation d'annulation --}}
@if(!in_array($repair->status, ['delivered', 'cancelled']))
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-x-circle me-2"></i>Annuler la réparation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('cashier.repairs.cancel', $repair) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning d-flex gap-2 mb-3">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                        <div>
                            <strong>Attention !</strong> Cette action est irréversible.
                            @if($repair->parts->count() > 0)
                                <br><strong>{{ $repair->parts->count() }} pièce(s)</strong> seront automatiquement remise(s) en stock.
                            @endif
                            @if($repair->amount_paid > 0)
                                <br>L'acompte de <strong>{{ number_format($repair->amount_paid, 0, ',', ' ') }} F</strong> devra être remboursé manuellement si nécessaire.
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Raison de l'annulation <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="cancel_reason" rows="3"
                                  placeholder="Ex: Client a annulé, pièce introuvable, réparation impossible..."
                                  required maxlength="500"></textarea>
                        <div class="form-text text-muted">500 caractères maximum</div>
                    </div>

                    <div class="bg-light rounded p-3 small text-muted">
                        <strong>Réparation :</strong> {{ $repair->repair_number }}<br>
                        <strong>Appareil :</strong> {{ $repair->device_brand }} {{ $repair->device_model }}<br>
                        <strong>Client :</strong> {{ $repair->customer->full_name }}<br>
                        <strong>Statut actuel :</strong> {{ ucfirst(str_replace('_', ' ', $repair->status)) }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-left me-1"></i>Retour
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i>Confirmer l'annulation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
