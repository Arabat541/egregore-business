@extends('layouts.app')

@section('title', 'Nouvelle vente')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cart-plus"></i> Nouvelle vente</h2>
</div>

<form action="{{ route('cashier.sales.store') }}" method="POST" id="saleForm">
    @csrf
    
    <div class="row">
        <!-- Colonne gauche - Produits -->
        <div class="col-md-8">
            <!-- Recherche produit -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <label class="form-label">Rechercher un produit</label>
                            <input type="text" class="form-control form-control-lg" id="productSearch" 
                                   placeholder="Scanner ou rechercher par nom/code..." autofocus>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type de client</label>
                            <select class="form-select form-select-lg" name="client_type" id="clientType">
                                <option value="walk-in">Client comptoir</option>
                                <option value="customer">Client enregistré</option>
                                <option value="reseller">Revendeur</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client info (conditionally shown) -->
            <div class="card mb-3 d-none" id="customerSection">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <label class="form-label">Sélectionner le client</label>
                            <select class="form-select" name="customer_id" id="customerSelect">
                                <option value="">Rechercher un client...</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->full_name }} - {{ $customer->phone }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#newCustomerModal">
                                <i class="bi bi-plus"></i> Nouveau client
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3 d-none" id="resellerSection">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <label class="form-label">Sélectionner le revendeur</label>
                            <select class="form-select" name="reseller_id" id="resellerSelect">
                                <option value="">Choisir un revendeur...</option>
                                @foreach($resellers as $reseller)
                                    <option value="{{ $reseller->id }}" data-credit="{{ $reseller->credit_limit - $reseller->current_debt }}">
                                        {{ $reseller->company_name }} - Crédit dispo: {{ number_format($reseller->credit_limit - $reseller->current_debt, 0, ',', ' ') }} FCFA
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Crédit disponible</label>
                            <div class="form-control bg-light" id="availableCredit">-</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des articles -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-cart"></i> Articles
                </div>
                <div class="card-body">
                    <table class="table" id="cartTable">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th style="width: 100px;">Qté</th>
                                <th style="width: 150px;">Prix unit.</th>
                                <th style="width: 150px;">Total</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="cartItems">
                            <!-- Items ajoutés dynamiquement -->
                        </tbody>
                    </table>
                    <div class="text-center text-muted py-4" id="emptyCart">
                        <i class="bi bi-cart3 display-4"></i>
                        <p>Aucun article dans le panier</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne droite - Paiement -->
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-calculator"></i> Récapitulatif
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Sous-total:</span>
                        <span id="subtotal">0 FCFA</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remise</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="discount_amount" id="discountAmount" value="0" min="0">
                            <span class="input-group-text">FCFA</span>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>TOTAL:</strong>
                        <strong class="text-primary fs-4" id="totalAmount">0 FCFA</strong>
                    </div>
                    <input type="hidden" name="total_amount" id="totalAmountInput" value="0">

                    <hr>

                    <div class="mb-3">
                        <label class="form-label">Mode de paiement</label>
                        <select class="form-select" name="payment_method_id" id="paymentMethod" required>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Montant reçu</label>
                        <input type="number" class="form-control form-control-lg" name="paid_amount" id="paidAmount" value="0" min="0">
                    </div>

                    <div class="d-flex justify-content-between mb-3" id="changeSection" style="display: none !important;">
                        <span>Monnaie à rendre:</span>
                        <span class="text-success fs-5" id="changeAmount">0 FCFA</span>
                    </div>

                    <div class="form-check mb-3 d-none" id="creditSection">
                        <input class="form-check-input" type="checkbox" name="is_credit" id="isCredit" value="1">
                        <label class="form-check-label">Vente à crédit</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Notes optionnelles..."></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg" id="submitSale" disabled>
                            <i class="bi bi-check-lg"></i> Valider la vente
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="clearCart()">
                            <i class="bi bi-trash"></i> Vider le panier
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden inputs for cart items -->
    <div id="cartInputs"></div>
</form>

<!-- Liste des produits pour la recherche -->
<script>
const products = @json($products);
let cart = [];

document.getElementById('productSearch').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    if (search.length < 2) return;
    
    const found = products.find(p => 
        p.barcode === e.target.value || 
        p.sku === e.target.value ||
        p.name.toLowerCase().includes(search)
    );
    
    if (found && found.barcode === e.target.value) {
        addToCart(found);
        e.target.value = '';
    }
});

document.getElementById('productSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const search = e.target.value.toLowerCase();
        const found = products.find(p => 
            p.barcode === e.target.value || 
            p.name.toLowerCase().includes(search)
        );
        if (found) {
            addToCart(found);
            e.target.value = '';
        }
    }
});

function addToCart(product) {
    const clientType = document.getElementById('clientType').value;
    const price = clientType === 'reseller' && product.reseller_price ? product.reseller_price : product.selling_price;
    
    const existing = cart.find(item => item.product_id === product.id);
    if (existing) {
        if (existing.quantity < product.quantity_in_stock) {
            existing.quantity++;
        } else {
            alert('Stock insuffisant!');
            return;
        }
    } else {
        if (product.quantity_in_stock < 1) {
            alert('Produit en rupture de stock!');
            return;
        }
        cart.push({
            product_id: product.id,
            name: product.name,
            quantity: 1,
            unit_price: price,
            max_stock: product.quantity_in_stock
        });
    }
    renderCart();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function updateQuantity(index, qty) {
    const item = cart[index];
    if (qty > 0 && qty <= item.max_stock) {
        item.quantity = parseInt(qty);
        renderCart();
    }
}

function updatePrice(index, price) {
    cart[index].unit_price = parseFloat(price);
    renderCart();
}

function renderCart() {
    const tbody = document.getElementById('cartItems');
    const emptyMsg = document.getElementById('emptyCart');
    const cartInputs = document.getElementById('cartInputs');
    
    if (cart.length === 0) {
        tbody.innerHTML = '';
        emptyMsg.style.display = 'block';
        cartInputs.innerHTML = '';
        document.getElementById('submitSale').disabled = true;
    } else {
        emptyMsg.style.display = 'none';
        document.getElementById('submitSale').disabled = false;
        
        tbody.innerHTML = cart.map((item, i) => `
            <tr>
                <td>${item.name}</td>
                <td>
                    <input type="number" class="form-control form-control-sm" value="${item.quantity}" 
                           min="1" max="${item.max_stock}" onchange="updateQuantity(${i}, this.value)">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" value="${item.unit_price}" 
                           min="0" onchange="updatePrice(${i}, this.value)">
                </td>
                <td class="fw-bold">${formatNumber(item.quantity * item.unit_price)} FCFA</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${i})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
        
        cartInputs.innerHTML = cart.map((item, i) => `
            <input type="hidden" name="items[${i}][product_id]" value="${item.product_id}">
            <input type="hidden" name="items[${i}][quantity]" value="${item.quantity}">
            <input type="hidden" name="items[${i}][unit_price]" value="${item.unit_price}">
        `).join('');
    }
    
    calculateTotals();
}

function calculateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
    const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
    const total = Math.max(0, subtotal - discount);
    const paid = parseFloat(document.getElementById('paidAmount').value) || 0;
    const change = paid - total;
    
    document.getElementById('subtotal').textContent = formatNumber(subtotal) + ' FCFA';
    document.getElementById('totalAmount').textContent = formatNumber(total) + ' FCFA';
    document.getElementById('totalAmountInput').value = total;
    
    if (paid > 0 && paid >= total) {
        document.getElementById('changeSection').style.display = 'flex';
        document.getElementById('changeAmount').textContent = formatNumber(change) + ' FCFA';
    } else {
        document.getElementById('changeSection').style.display = 'none';
    }
}

function clearCart() {
    if (confirm('Vider le panier ?')) {
        cart = [];
        renderCart();
    }
}

function formatNumber(num) {
    return new Intl.NumberFormat('fr-FR').format(num);
}

// Event listeners
document.getElementById('discountAmount').addEventListener('input', calculateTotals);
document.getElementById('paidAmount').addEventListener('input', calculateTotals);

document.getElementById('clientType').addEventListener('change', function() {
    const type = this.value;
    document.getElementById('customerSection').classList.toggle('d-none', type !== 'customer');
    document.getElementById('resellerSection').classList.toggle('d-none', type !== 'reseller');
    document.getElementById('creditSection').classList.toggle('d-none', type !== 'reseller');
    
    // Recalculer les prix si on passe en revendeur
    if (type === 'reseller') {
        cart.forEach(item => {
            const product = products.find(p => p.id === item.product_id);
            if (product && product.reseller_price) {
                item.unit_price = product.reseller_price;
            }
        });
    } else {
        cart.forEach(item => {
            const product = products.find(p => p.id === item.product_id);
            if (product) {
                item.unit_price = product.selling_price;
            }
        });
    }
    renderCart();
});

document.getElementById('resellerSelect').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const credit = option.dataset.credit || 0;
    document.getElementById('availableCredit').textContent = formatNumber(credit) + ' FCFA';
});
</script>

<!-- Modal Nouveau client -->
<div class="modal fade" id="newCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('cashier.customers.store') }}" method="POST">
                @csrf
                <input type="hidden" name="redirect_back" value="1">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Nouveau client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prénom</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" name="phone" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
