@extends('layouts.app')

@section('title', 'Nouvel inventaire')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clipboard-plus"></i> Nouvel inventaire</h2>
    <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations de l'inventaire
            </div>
            <div class="card-body">
                <form action="{{ route('admin.inventory.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">Boutique <span class="text-danger">*</span></label>
                        <select name="shop_id" class="form-select @error('shop_id') is-invalid @enderror" required>
                            <option value="">Sélectionner une boutique</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" {{ old('shop_id') == $shop->id ? 'selected' : '' }}>
                                    {{ $shop->name }} - {{ $shop->address }}
                                </option>
                            @endforeach
                        </select>
                        @error('shop_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (optionnel)</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" 
                                  rows="3" placeholder="Ex: Inventaire mensuel, Vérification après vol...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Comment ça fonctionne :</strong>
                        <ol class="mb-0 mt-2">
                            <li>Tous les produits actifs de la boutique seront listés</li>
                            <li>Scannez ou saisissez la quantité physique de chaque produit</li>
                            <li>Terminez l'inventaire pour voir les écarts</li>
                            <li>Validez pour corriger automatiquement le stock</li>
                        </ol>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-play-fill"></i> Démarrer l'inventaire
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
