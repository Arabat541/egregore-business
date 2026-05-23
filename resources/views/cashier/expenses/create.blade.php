@extends('layouts.app')

@section('title', 'Nouvelle Dépense')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Nouvelle Dépense</h1>
            <p class="text-muted mb-0">Enregistrer une dépense courante</p>
        </div>
        <a href="{{ route('cashier.expenses.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
    </div>

    @if(!$openCashRegister)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Attention:</strong> Vous n'avez pas de caisse ouverte. Les dépenses en espèces ne pourront pas être enregistrées.
        </div>
    @endif

    @if($categories->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Aucune catégorie de dépense n'est configurée. 
            <a href="{{ route('cashier.expenses.categories') }}">Créez d'abord une catégorie</a>.
        </div>
    @else
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Informations de la dépense</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('cashier.expenses.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="row g-3">
                                <!-- Catégorie -->
                                <div class="col-md-6">
                                    <label for="expense_category_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
                                    <select name="expense_category_id" id="expense_category_id" 
                                            class="form-select @error('expense_category_id') is-invalid @enderror" required>
                                        <option value="">-- Sélectionner --</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" 
                                                    data-requires-approval="{{ $category->requires_approval ? '1' : '0' }}"
                                                    data-budget="{{ $category->monthly_budget }}"
                                                    data-spent="{{ $category->currentMonthExpenses() }}"
                                                    {{ old('expense_category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                                @if($category->monthly_budget)
                                                    (Budget: {{ number_format($category->monthly_budget, 0, ',', ' ') }} F)
                                                @endif
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
                                           value="{{ old('expense_date', date('Y-m-d')) }}" 
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
                                               value="{{ old('amount') }}" min="1" step="1" required>
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
                                        <option value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'selected' : '' }}>
                                            💵 Espèces
                                        </option>
                                        <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>
                                            🏦 Virement bancaire
                                        </option>
                                        <option value="mobile_money" {{ old('payment_method') == 'mobile_money' ? 'selected' : '' }}>
                                            📱 Mobile Money
                                        </option>
                                        <option value="check" {{ old('payment_method') == 'check' ? 'selected' : '' }}>
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
                                           value="{{ old('description') }}" 
                                           placeholder="Ex: Facture électricité janvier 2026" required>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Bénéficiaire -->
                                <div class="col-md-6">
                                    <label for="beneficiary" class="form-label">Bénéficiaire</label>
                                    <input type="text" name="beneficiary" id="beneficiary" 
                                           class="form-control @error('beneficiary') is-invalid @enderror"
                                           value="{{ old('beneficiary') }}" 
                                           placeholder="Ex: CIE, Propriétaire, Fournisseur...">
                                    @error('beneficiary')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Numéro de reçu -->
                                <div class="col-md-6">
                                    <label for="receipt_number" class="form-label">N° Reçu/Facture</label>
                                    <input type="text" name="receipt_number" id="receipt_number" 
                                           class="form-control @error('receipt_number') is-invalid @enderror"
                                           value="{{ old('receipt_number') }}" 
                                           placeholder="Numéro de référence">
                                    @error('receipt_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                <div class="col-12">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea name="notes" id="notes" rows="3" 
                                              class="form-control @error('notes') is-invalid @enderror"
                                              placeholder="Informations complémentaires...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Image du reçu -->
                                <div class="col-12">
                                    <label for="receipt_image" class="form-label">Photo du reçu (optionnel)</label>
                                    <input type="file" name="receipt_image" id="receipt_image" 
                                           class="form-control @error('receipt_image') is-invalid @enderror"
                                           accept="image/*">
                                    @error('receipt_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Format: JPG, PNG. Max: 2 Mo</small>
                                </div>
                            </div>

                            <!-- Alerte approbation -->
                            <div id="approval-alert" class="alert alert-warning mt-3 d-none">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Cette catégorie nécessite une approbation. La dépense sera mise en attente.
                            </div>

                            <!-- Alerte budget -->
                            <div id="budget-alert" class="alert alert-danger mt-3 d-none">
                                <i class="bi bi-graph-up me-2"></i>
                                <span id="budget-message"></span>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-lg me-2"></i>Enregistrer la dépense
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Catégories rapides -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Catégories fréquentes</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($categories->take(8) as $category)
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-category"
                                        data-category="{{ $category->id }}" 
                                        style="border-color: {{ $category->color }}; color: {{ $category->color }}">
                                    <i class="bi {{ $category->icon ?? 'bi-tag' }} me-1"></i>{{ $category->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Aide -->
                <div class="card shadow-sm bg-light">
                    <div class="card-body">
                        <h6><i class="bi bi-info-circle me-2 text-info"></i>Conseils</h6>
                        <ul class="mb-0 small text-muted">
                            <li>Enregistrez chaque dépense le jour même</li>
                            <li>Gardez une copie des reçus importants</li>
                            <li>Précisez toujours le bénéficiaire</li>
                            <li>Les dépenses en espèces sont déduites de la caisse</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('expense_category_id');
    const amountInput = document.getElementById('amount');
    const approvalAlert = document.getElementById('approval-alert');
    const budgetAlert = document.getElementById('budget-alert');
    const budgetMessage = document.getElementById('budget-message');

    // Sélection rapide de catégorie
    document.querySelectorAll('.quick-category').forEach(btn => {
        btn.addEventListener('click', function() {
            categorySelect.value = this.dataset.category;
            categorySelect.dispatchEvent(new Event('change'));
        });
    });

    // Vérification catégorie et budget
    function checkCategory() {
        const option = categorySelect.options[categorySelect.selectedIndex];
        if (!option.value) {
            approvalAlert.classList.add('d-none');
            budgetAlert.classList.add('d-none');
            return;
        }

        // Vérifier si approbation requise
        if (option.dataset.requiresApproval === '1') {
            approvalAlert.classList.remove('d-none');
        } else {
            approvalAlert.classList.add('d-none');
        }

        // Vérifier le budget
        checkBudget(option);
    }

    function checkBudget(option) {
        const budget = parseFloat(option.dataset.budget) || 0;
        const spent = parseFloat(option.dataset.spent) || 0;
        const amount = parseFloat(amountInput.value) || 0;

        if (budget > 0) {
            const remaining = budget - spent;
            const newTotal = spent + amount;

            if (newTotal > budget) {
                budgetMessage.textContent = `Attention: Le budget mensuel (${formatNumber(budget)} F) sera dépassé. Dépensé: ${formatNumber(spent)} F, Nouveau total: ${formatNumber(newTotal)} F`;
                budgetAlert.classList.remove('d-none');
            } else if (amount > remaining) {
                budgetMessage.textContent = `Le montant dépasse le reste du budget (${formatNumber(remaining)} F)`;
                budgetAlert.classList.remove('d-none');
            } else {
                budgetAlert.classList.add('d-none');
            }
        } else {
            budgetAlert.classList.add('d-none');
        }
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('fr-FR').format(num);
    }

    categorySelect.addEventListener('change', checkCategory);
    amountInput.addEventListener('input', function() {
        const option = categorySelect.options[categorySelect.selectedIndex];
        if (option.value) checkBudget(option);
    });

    // Vérification initiale
    checkCategory();
});
</script>
@endpush
