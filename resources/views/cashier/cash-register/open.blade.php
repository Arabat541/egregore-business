@extends('layouts.app')

@section('title', 'Ouvrir la caisse')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-unlock"></i> Ouvrir la caisse
            </div>
            <div class="card-body">
                <form action="{{ route('cashier.cash-register.open') }}" method="POST">
                    @csrf

                    <div class="text-center mb-4">
                        <i class="bi bi-cash-stack display-1 text-success"></i>
                        <h4 class="mt-3">Ouverture de caisse</h4>
                        <p class="text-muted">{{ now()->format('l d F Y') }}</p>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Fond de caisse initial (FCFA) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg text-center @error('opening_balance') is-invalid @enderror" 
                               name="opening_balance" value="{{ old('opening_balance', 0) }}" min="0" required autofocus>
                        @error('opening_balance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Montant en espèces présent dans la caisse</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Notes (optionnel)</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Notes d'ouverture...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-unlock"></i> Ouvrir la caisse
                        </button>
                        <a href="{{ route('cashier.dashboard') }}" class="btn btn-outline-secondary">
                            Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
