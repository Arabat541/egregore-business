@extends('layouts.app')

@section('title', isset($reseller) ? 'Modifier revendeur' : 'Nouveau revendeur')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="bi bi-{{ isset($reseller) ? 'pencil' : 'plus-circle' }}"></i> 
        {{ isset($reseller) ? 'Modifier le revendeur' : 'Nouveau revendeur' }}
    </h2>
    <a href="{{ route('admin.resellers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ isset($reseller) ? route('admin.resellers.update', $reseller) : route('admin.resellers.store') }}" method="POST">
            @csrf
            @if(isset($reseller))
                @method('PUT')
            @endif

            <h5 class="mb-3"><i class="bi bi-building"></i> Informations entreprise</h5>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Boutique <span class="text-danger">*</span></label>
                        <select class="form-select @error('shop_id') is-invalid @enderror" name="shop_id" required>
                            <option value="">Sélectionner une boutique</option>
                            @php
                                $shops = \App\Models\Shop::active()->orderBy('name')->get();
                            @endphp
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" 
                                        {{ old('shop_id', $reseller->shop_id ?? '') == $shop->id ? 'selected' : '' }}>
                                    {{ $shop->name }} ({{ $shop->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('shop_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Nom de l'entreprise <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('company_name') is-invalid @enderror" 
                               name="company_name" value="{{ old('company_name', $reseller->company_name ?? '') }}" required>
                        @error('company_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Code revendeur</label>
                        <input type="text" class="form-control @error('reseller_code') is-invalid @enderror" 
                               name="reseller_code" value="{{ old('reseller_code', $reseller->reseller_code ?? '') }}" 
                               placeholder="Ex: REV-001">
                        @error('reseller_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nom du contact <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('contact_name') is-invalid @enderror" 
                               name="contact_name" value="{{ old('contact_name', $reseller->contact_name ?? '') }}" required>
                        @error('contact_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                               name="phone" value="{{ old('phone', $reseller->phone ?? '') }}" required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               name="email" value="{{ old('email', $reseller->email ?? '') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" 
                               name="address" value="{{ old('address', $reseller->address ?? '') }}">
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <hr>
            <h5 class="mb-3"><i class="bi bi-credit-card"></i> Conditions commerciales</h5>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Limite de crédit (FCFA) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('credit_limit') is-invalid @enderror" 
                               name="credit_limit" value="{{ old('credit_limit', $reseller->credit_limit ?? 0) }}" min="0" required>
                        @error('credit_limit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Remise par défaut (%)</label>
                        <input type="number" class="form-control @error('default_discount') is-invalid @enderror" 
                               name="default_discount" value="{{ old('default_discount', $reseller->default_discount ?? 0) }}" 
                               min="0" max="100" step="0.5">
                        @error('default_discount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Délai de paiement (jours)</label>
                        <input type="number" class="form-control @error('payment_terms_days') is-invalid @enderror" 
                               name="payment_terms_days" value="{{ old('payment_terms_days', $reseller->payment_terms_days ?? 30) }}" min="0">
                        @error('payment_terms_days')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control @error('notes') is-invalid @enderror" 
                          name="notes" rows="2">{{ old('notes', $reseller->notes ?? '') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                           {{ old('is_active', $reseller->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label">Revendeur actif</label>
                </div>
            </div>

            <hr>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.resellers.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> {{ isset($reseller) ? 'Mettre à jour' : 'Créer' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
