@extends('layouts.app')

@section('title', $supplier->company_name)

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-truck me-2"></i>{{ $supplier->company_name }}</h2>
    <div>
        <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i>Modifier
        </a>
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Informations -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informations</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Entreprise</td>
                        <td><strong>{{ $supplier->company_name }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Contact</td>
                        <td>{{ $supplier->contact_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Téléphone</td>
                        <td>
                            <a href="tel:{{ $supplier->phone }}">{{ $supplier->phone }}</a>
                            @if($supplier->phone_secondary)
                                <br><a href="tel:{{ $supplier->phone_secondary }}">{{ $supplier->phone_secondary }}</a>
                            @endif
                        </td>
                    </tr>
                    @if($supplier->whatsapp)
                    <tr>
                        <td class="text-muted">WhatsApp</td>
                        <td>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supplier->whatsapp) }}" 
                               target="_blank" class="btn btn-sm btn-success">
                                <i class="bi bi-whatsapp me-1"></i>{{ $supplier->whatsapp }}
                            </a>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">Email</td>
                        <td>
                            @if($supplier->email)
                                <a href="mailto:{{ $supplier->email }}">{{ $supplier->email }}</a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Adresse</td>
                        <td>
                            {{ $supplier->address ?? '-' }}
                            @if($supplier->city)
                                <br>{{ $supplier->city }}, {{ $supplier->country }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Catégories</td>
                        <td>{{ $supplier->categories_list }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Statut</td>
                        <td>
                            @if($supplier->is_active)
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-danger">Inactif</span>
                            @endif
                        </td>
                    </tr>
                </table>

                @if($supplier->notes)
                    <div class="alert alert-info mb-0">
                        <h6 class="alert-heading"><i class="bi bi-info-circle me-1"></i>Notes</h6>
                        {{ $supplier->notes }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Statistiques</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="border rounded p-3">
                            <h3 class="text-primary mb-0">{{ $supplier->orders->count() }}</h3>
                            <small class="text-muted">Commandes</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-3">
                            <h3 class="text-success mb-0">{{ $supplier->orders->where('status', 'received')->count() }}</h3>
                            <small class="text-muted">Reçues</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-3">
                            <h3 class="text-warning mb-0">{{ $supplier->orders->whereIn('status', ['draft', 'sent', 'confirmed'])->count() }}</h3>
                            <small class="text-muted">En cours</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Actions rapides</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.suppliers.low-stock') }}" class="btn btn-warning">
                        <i class="bi bi-cart-plus me-2"></i>Créer une commande
                    </a>
                    @if($supplier->whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supplier->whatsapp) }}?text=Bonjour, je souhaite passer une commande." 
                           target="_blank" class="btn btn-success">
                            <i class="bi bi-whatsapp me-2"></i>Contacter via WhatsApp
                        </a>
                    @endif
                    @if($supplier->email)
                        <a href="mailto:{{ $supplier->email }}?subject=Nouvelle commande" class="btn btn-info">
                            <i class="bi bi-envelope me-2"></i>Envoyer un email
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dernières commandes -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Dernières commandes</h5>
        <a href="{{ route('admin.suppliers.orders', ['supplier_id' => $supplier->id]) }}" class="btn btn-sm btn-outline-primary">
            Voir tout
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Référence</th>
                        <th>Date</th>
                        <th class="text-center">Articles</th>
                        <th class="text-center">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supplier->orders as $order)
                        <tr>
                            <td><strong>{{ $order->reference }}</strong></td>
                            <td>{{ $order->order_date->format('d/m/Y') }}</td>
                            <td class="text-center">{{ $order->items->count() }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $order->status_color }}">{{ $order->status_label }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                Aucune commande passée
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Produits fournis par ce fournisseur -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-box-seam me-2"></i>Produits fournis
            <span class="badge bg-primary ms-2">{{ $supplier->productPrices->count() }}</span>
        </h5>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus-lg me-1"></i>Associer un produit
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th class="text-center">Stock actuel</th>
                        <th class="text-end">Prix fournisseur</th>
                        <th class="text-end">Ancien prix</th>
                        <th class="text-center">Dernière MAJ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supplier->productPrices()->with('product.category')->orderBy('updated_at', 'desc')->get() as $priceInfo)
                        @if($priceInfo->product)
                            <tr>
                                <td>
                                    <strong>{{ $priceInfo->product->name }}</strong>
                                    @if($priceInfo->product->sku)
                                        <small class="text-muted d-block">SKU: {{ $priceInfo->product->sku }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($priceInfo->product->category)
                                        <span class="badge bg-light text-dark">{{ $priceInfo->product->category->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($priceInfo->product->quantity_in_stock == 0)
                                        <span class="badge bg-danger">Rupture</span>
                                    @elseif($priceInfo->product->is_low_stock)
                                        <span class="badge bg-warning text-dark">{{ $priceInfo->product->quantity_in_stock }}</span>
                                    @else
                                        <span class="badge bg-success">{{ $priceInfo->product->quantity_in_stock }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <strong>{{ number_format($priceInfo->unit_price, 0, ',', ' ') }} F</strong>
                                    @if($priceInfo->has_decreased)
                                        <i class="bi bi-arrow-down-circle-fill text-success ms-1" title="Prix en baisse"></i>
                                    @elseif($priceInfo->has_increased)
                                        <i class="bi bi-arrow-up-circle-fill text-danger ms-1" title="Prix en hausse"></i>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($priceInfo->last_price)
                                        <span class="text-muted text-decoration-line-through">
                                            {{ number_format($priceInfo->last_price, 0, ',', ' ') }} F
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($priceInfo->price_updated_at)
                                        <small>{{ $priceInfo->price_updated_at->format('d/m/Y') }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="bi bi-box display-4 text-muted"></i>
                                <p class="text-muted mt-2 mb-0">Aucun produit associé à ce fournisseur</p>
                                <button type="button" class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                    <i class="bi bi-plus-lg me-1"></i>Associer un produit
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Associer Produit -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.suppliers.store-price') }}">
                @csrf
                <input type="hidden" name="supplier_id" value="{{ $supplier->id }}">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-link-45deg me-2"></i>Associer un produit à {{ $supplier->company_name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rechercher un produit <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-select" id="productSelect" required>
                            <option value="">Sélectionner un produit...</option>
                            @php
                                // Récupérer les produits non encore associés à ce fournisseur
                                $associatedIds = $supplier->productPrices->pluck('product_id')->toArray();
                                $availableProducts = \App\Models\Product::whereNotIn('id', $associatedIds)
                                    ->where('is_active', true)
                                    ->with('category')
                                    ->orderBy('name')
                                    ->get();
                            @endphp
                            @foreach($availableProducts as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->purchase_price }}">
                                    {{ $product->name }}
                                    @if($product->sku) ({{ $product->sku }}) @endif
                                    @if($product->category) - {{ $product->category->name }} @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ $availableProducts->count() }} produit(s) disponible(s)</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prix unitaire (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" name="unit_price" id="unitPriceInput" class="form-control" required min="0" step="1">
                                <small class="text-muted">Prix d'achat chez ce fournisseur</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantité min. commande</label>
                                <input type="number" name="min_order_quantity" class="form-control" value="1" min="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Délai de livraison (jours)</label>
                        <input type="number" name="lead_time_days" class="form-control" min="0" placeholder="Ex: 3">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Remarques sur ce produit chez ce fournisseur..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Associer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('productSelect').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const purchasePrice = selected.dataset.price;
    if (purchasePrice) {
        document.getElementById('unitPriceInput').value = purchasePrice;
    }
});
</script>
@endpush
@endsection
