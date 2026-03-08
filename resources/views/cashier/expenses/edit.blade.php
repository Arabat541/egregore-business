@extends('layouts.app')

@section('title', 'Modifier la Dépense')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Modifier la Dépense</h1>
            <p class="text-muted mb-0">{{ $expense->reference }}</p>
        </div>
        <a href="{{ route('cashier.expenses.show', $expense) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Modifier les informations</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('cashier.expenses.update', $expense) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <!-- Catégorie -->
                            <div class="col-md-6">
                                <label for="expense_category_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
                                <select name="expense_category_id" id="expense_category_id" 
                                        class="form-select @error('expense_category_id') is-invalid @enderror" required>
                                    <option value="">-- Sélectionner --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" 
                                                {{ old('expense_category_id', $expense->expense_category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('expense_category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Date -->
                            <div class="col-md-6">
                                <label for="expense_date" class="form-label">Date de la dépense <span class="text-danger">*</span></label>
                                <input type="date" name="expense_date" id="expense_date" 
                                       class="form-control @error('expense_date') is-invalid @enderror"
                                       value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" 
                                       max="{{ date('Y-m-d') }}" required>
                                @error('expense_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Montant -->
                            <div class="col-md-6">
                                <label for="amount" class="form-label">Montant <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="amount" id="amount" 
                                           class="form-control @error('amount') is-invalid @enderror"
                                           value="{{ old('amount', $expense->amount) }}" min="1" step="1" required>
                                    <span class="input-group-text">F CFA</span>
                                </div>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Mode de paiement -->
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">Mode de paiement <span class="text-danger">*</span></label>
                                <select name="payment_method" id="payment_method" 
                                        class="form-select @error('payment_method') is-invalid @enderror" required>
                                    <option value="cash" {{ old('payment_method', $expense->payment_method) == 'cash' ? 'selected' : '' }}>
                                        💵 Espèces
                                    </option>
                                    <option value="bank_transfer" {{ old('payment_method', $expense->payment_method) == 'bank_transfer' ? 'selected' : '' }}>
                                        🏦 Virement bancaire
                                    </option>
                                    <option value="mobile_money" {{ old('payment_method', $expense->payment_method) == 'mobile_money' ? 'selected' : '' }}>
                                        📱 Mobile Money
                                    </option>
                                    <option value="check" {{ old('payment_method', $expense->payment_method) == 'check' ? 'selected' : '' }}>
                                        📄 Chèque
                                    </option>
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <input type="text" name="description" id="description" 
                                       class="form-control @error('description') is-invalid @enderror"
                                       value="{{ old('description', $expense->description) }}" required>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Bénéficiaire -->
                            <div class="col-md-6">
                                <label for="beneficiary" class="form-label">Bénéficiaire</label>
                                <input type="text" name="beneficiary" id="beneficiary" 
                                       class="form-control @error('beneficiary') is-invalid @enderror"
                                       value="{{ old('beneficiary', $expense->beneficiary) }}">
                                @error('beneficiary')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Numéro de reçu -->
                            <div class="col-md-6">
                                <label for="receipt_number" class="form-label">N° Reçu/Facture</label>
                                <input type="text" name="receipt_number" id="receipt_number" 
                                       class="form-control @error('receipt_number') is-invalid @enderror"
                                       value="{{ old('receipt_number', $expense->receipt_number) }}">
                                @error('receipt_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Notes -->
                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea name="notes" id="notes" rows="3" 
                                          class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $expense->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Image du reçu -->
                            <div class="col-12">
                                <label for="receipt_image" class="form-label">Photo du reçu</label>
                                @if($expense->receipt_image)
                                    <div class="mb-2">
                                        <img src="{{ asset('storage/' . $expense->receipt_image) }}" 
                                             alt="Reçu actuel" class="img-thumbnail" style="max-height: 150px">
                                        <p class="small text-muted mt-1">Image actuelle. Téléchargez une nouvelle image pour la remplacer.</p>
                                    </div>
                                @endif
                                <input type="file" name="receipt_image" id="receipt_image" 
                                       class="form-control @error('receipt_image') is-invalid @enderror"
                                       accept="image/*">
                                @error('receipt_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="bi bi-check-lg me-2"></i>Mettre à jour
                            </button>
                            <a href="{{ route('cashier.expenses.show', $expense) }}" class="btn btn-outline-secondary btn-lg ms-2">
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Infos actuelles -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informations actuelles</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Référence:</strong> {{ $expense->reference }}</p>
                    <p class="mb-2"><strong>Créée le:</strong> {{ $expense->created_at->format('d/m/Y H:i') }}</p>
                    <p class="mb-2"><strong>Par:</strong> {{ $expense->user->name }}</p>
                    <p class="mb-0">
                        <strong>Statut:</strong> 
                        <span class="badge bg-{{ $expense->status_color }}">{{ $expense->status_label }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
