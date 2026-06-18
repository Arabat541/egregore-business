@extends('layouts.app')

@section('title', 'Annuler le paiement')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">

    {{-- En-tête --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="h4 text-danger mb-1">
                <i class="bi bi-x-circle me-2"></i>Annuler un paiement
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('cashier.reseller-payments.index') }}">Créances</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('cashier.reseller-payments.show', $reseller) }}">{{ $reseller->company_name }}</a></li>
                    <li class="breadcrumb-item active">Annulation</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('cashier.reseller-payments.show', $reseller) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
    </div>

    {{-- Alerte danger --}}
    <div class="alert alert-danger border-danger mb-4">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            <div>
                <strong>Action irréversible.</strong> L'annulation va :
                <ul class="mb-0 mt-1">
                    <li>Remettre la dette du revendeur à son état avant ce paiement</li>
                    <li>Recalculer les soldes de toutes les factures concernées</li>
                    @if($payment->has_product_return)
                    <li>Annuler les retours produits et déduire les stocks remis</li>
                    @endif
                    @if($payment->cash_amount > 0)
                    <li>Créer une écriture corrective dans le registre de caisse</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Récapitulatif du paiement --}}
        <div class="col-md-5">
            <div class="card border-danger h-100">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-receipt me-1"></i>
                    Paiement à annuler —
                    <strong>PAY-{{ str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}</strong>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Revendeur</td>
                            <td class="fw-bold">{{ $reseller->company_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Date</td>
                            <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Saisi par</td>
                            <td>{{ $payment->user->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Mode</td>
                            <td>{{ $payment->payment_method_label }}</td>
                        </tr>
                        <tr class="border-top">
                            <td class="text-muted fw-semibold">Montant total</td>
                            <td class="fw-bold fs-5 text-danger">
                                {{ number_format($payment->amount, 0, ',', ' ') }} FCFA
                            </td>
                        </tr>
                        @if($payment->cash_amount > 0 && $payment->return_amount > 0)
                        <tr>
                            <td class="text-muted ps-3 small">dont espèces</td>
                            <td class="small">{{ number_format($payment->cash_amount, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-3 small">dont retours</td>
                            <td class="small">{{ number_format($payment->return_amount, 0, ',', ' ') }} FCFA</td>
                        </tr>
                        @endif
                    </table>

                    @if($payment->sale)
                    <div class="alert alert-warning mt-3 mb-0 py-2 px-3">
                        <small><strong>Facture concernée :</strong>
                            <code>{{ $payment->sale->invoice_number }}</code>
                            ({{ number_format($payment->sale->total_amount, 0, ',', ' ') }} FCFA)
                        </small>
                    </div>
                    @endif

                    @if($payment->productReturns->isNotEmpty())
                    <div class="mt-3">
                        <p class="small fw-semibold mb-1"><i class="bi bi-box-arrow-in-left me-1"></i>Retours inclus :</p>
                        <ul class="small mb-0 ps-3">
                            @foreach($payment->productReturns as $ret)
                            <li>
                                {{ $ret->product->name ?? 'Produit supprimé' }}
                                (x{{ $ret->quantity }})
                                @if($ret->restock)
                                    <span class="text-warning">— stock sera retiré</span>
                                @endif
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="mt-3 p-2 bg-light rounded border small">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Dette avant paiement</span>
                            <strong>{{ number_format($payment->debt_before, 0, ',', ' ') }} FCFA</strong>
                        </div>
                        <div class="d-flex justify-content-between text-success">
                            <span>Dette après paiement</span>
                            <strong>{{ number_format($payment->debt_after, 0, ',', ' ') }} FCFA</strong>
                        </div>
                        <div class="d-flex justify-content-between text-danger fw-bold border-top mt-1 pt-1">
                            <span>Dette si annulé</span>
                            <strong>≈ {{ number_format($payment->debt_before, 0, ',', ' ') }} FCFA</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulaire d'annulation --}}
        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <i class="bi bi-pencil me-1"></i>Motif d'annulation
                </div>
                <div class="card-body">
                    <form action="{{ route('cashier.reseller-payments.cancel', [$reseller, $payment]) }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Motif <span class="text-danger">*</span>
                            </label>
                            <textarea name="cancellation_reason"
                                      class="form-control @error('cancellation_reason') is-invalid @enderror"
                                      rows="4"
                                      required minlength="5"
                                      placeholder="Ex : Paiement enregistré sur le mauvais revendeur (devait être sur X au lieu de Y)…">{{ old('cancellation_reason') }}</textarea>
                            @error('cancellation_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Minimum 5 caractères. Ce motif sera conservé dans l'historique.</div>
                        </div>

                        {{-- Suggestions rapides --}}
                        <div class="mb-4">
                            <p class="small text-muted mb-2">Motifs fréquents :</p>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm reason-btn"
                                        data-reason="Paiement enregistré sur le mauvais revendeur">
                                    Mauvais revendeur
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm reason-btn"
                                        data-reason="Montant saisi incorrectement">
                                    Montant incorrect
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm reason-btn"
                                        data-reason="Double saisie — paiement en doublon">
                                    Doublon
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm reason-btn"
                                        data-reason="Paiement annulé à la demande du revendeur">
                                    Demande revendeur
                                </button>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger"
                                    onclick="return confirm('Confirmer l\'annulation de ce paiement ? Cette action est irréversible.')">
                                <i class="bi bi-x-circle me-1"></i>Confirmer l'annulation
                            </button>
                            <a href="{{ route('cashier.reseller-payments.show', $reseller) }}"
                               class="btn btn-outline-secondary">
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.reason-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelector('textarea[name="cancellation_reason"]').value = this.dataset.reason;
    });
});
</script>
@endpush
@endsection
