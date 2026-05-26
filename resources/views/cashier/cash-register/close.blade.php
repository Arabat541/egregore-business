@extends('layouts.app')

@section('title', 'Fermeture de caisse')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-lock me-2"></i>Fermeture de caisse</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('cashier.cash-register.index') }}">Caisse</a></li>
                    <li class="breadcrumb-item active">Fermeture</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('cashier.cash-register.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">

            {{-- Résumé de la caisse --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-calculator me-2"></i>Résumé de la journée</h6>
                </div>
                <div class="card-body">
                    @php
                        $cashRegister->loadMissing('transactions');
                        $totalIn  = (float) $cashRegister->transactions->where('type', 'income')->sum('amount');
                        $totalOut = (float) $cashRegister->transactions->where('type', 'expense')->sum('amount');
                        $theoretical = $cashRegister->opening_balance + $totalIn - $totalOut;
                    @endphp
                    <dl class="row mb-0">
                        <dt class="col-sm-7">Fond d'ouverture</dt>
                        <dd class="col-sm-5 text-end">{{ number_format($cashRegister->opening_balance, 0, ',', ' ') }} FCFA</dd>

                        <dt class="col-sm-7 text-success">Total entrées</dt>
                        <dd class="col-sm-5 text-end text-success">+ {{ number_format($totalIn, 0, ',', ' ') }} FCFA</dd>

                        <dt class="col-sm-7 text-danger">Total sorties</dt>
                        <dd class="col-sm-5 text-end text-danger">- {{ number_format($totalOut, 0, ',', ' ') }} FCFA</dd>

                        <dt class="col-sm-7 fw-bold border-top pt-2">Solde théorique</dt>
                        <dd class="col-sm-5 text-end fw-bold border-top pt-2">{{ number_format($theoretical, 0, ',', ' ') }} FCFA</dd>
                    </dl>
                </div>
            </div>

            {{-- Formulaire de fermeture --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-lock me-2 text-warning"></i>Clôturer la caisse</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('cashier.cash-register.close') }}" method="POST">
                        @csrf
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Solde théorique : <strong>{{ number_format($theoretical, 0, ',', ' ') }} FCFA</strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Montant compté en caisse <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control form-control-lg @error('closing_balance') is-invalid @enderror"
                                       name="closing_balance" value="{{ old('closing_balance') }}"
                                       min="0" step="1" required placeholder="0">
                                <span class="input-group-text">FCFA</span>
                            </div>
                            @error('closing_balance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"
                                      placeholder="Observations, écarts constatés...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning btn-lg fw-bold"
                                    onclick="return confirm('Confirmer la fermeture de caisse ?')">
                                <i class="bi bi-lock me-2"></i>Fermer la caisse
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
