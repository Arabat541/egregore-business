@extends('layouts.app')

@section('title', 'Inventaire ' . $inventory->reference)

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-clipboard-check"></i> Inventaire {{ $inventory->reference }}</h2>
        <p class="text-muted mb-0">
            {{ $inventory->shop->name }} - Créé par {{ $inventory->user->name }} le {{ $inventory->created_at->format('d/m/Y H:i') }}
        </p>
    </div>
    <div>
        <span class="badge bg-{{ $inventory->status_color }} fs-6">{{ $inventory->status_label }}</span>
    </div>
</div>

<!-- Statistiques -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3 id="statTotal">{{ $stats['total'] }}</h3>
                <small>Produits total</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 id="statCounted">{{ $stats['counted'] }}</h3>
                <small>Comptés</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <h3 id="statWithDiff">{{ $stats['with_difference'] }}</h3>
                <small>Avec écart</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h3 id="statShortage">{{ $stats['shortage'] }}</h3>
                <small>Manquants</small>
            </div>
        </div>
    </div>
</div>

<!-- Barre de progression -->
@if($inventory->status == 'in_progress')
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
            <span>Progression</span>
            <span id="progressPercent">{{ $inventory->progress }}%</span>
        </div>
        <div class="progress" style="height: 20px;">
            <div class="progress-bar bg-success" id="progressBar" data-progress="{{ $inventory->progress }}">
                <span id="progressText">{{ $stats['counted'] }} / {{ $stats['total'] }}</span>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <!-- Recherche / Sélection produit -->
    @if($inventory->status == 'in_progress')
    <div class="col-md-4">
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-search"></i> Rechercher un produit
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" id="searchInput" class="form-control form-control-lg" 
                           placeholder="Tapez le nom du produit..." autofocus>
                </div>
                
                <!-- Liste des résultats de recherche -->
                <div id="searchResults" class="list-group mb-3 d-none" style="max-height: 200px; overflow-y: auto;">
                </div>
                
                <div id="productInfo" class="d-none">
                    <div class="alert alert-info">
                        <strong id="productName"></strong>
                        <div class="mt-2">
                            <small>Stock théorique: <span id="theoreticalQty" class="fw-bold"></span></small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Quantité physique comptée</label>
                        <input type="number" id="physicalQty" class="form-control form-control-lg" min="0" value="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (optionnel)</label>
                        <input type="text" id="itemNotes" class="form-control" placeholder="Ex: Produit endommagé...">
                    </div>
                    
                    <button type="button" id="saveCountBtn" class="btn btn-success w-100">
                        <i class="bi bi-check-lg"></i> Enregistrer
                    </button>
                </div>
                
                <div id="searchError" class="alert alert-danger d-none"></div>
            </div>
            
            <div class="card-footer">
                <div class="d-grid gap-2">
                    @if($stats['counted'] == $stats['total'] || $stats['counted'] > 0)
                        <form action="{{ route('admin.inventory.complete', $inventory) }}" method="POST" 
                              onsubmit="return confirm('Terminer l\'inventaire ? Les produits non comptés seront considérés comme 0.')">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="bi bi-check-circle"></i> Terminer l'inventaire
                            </button>
                        </form>
                    @endif
                    
                    <form action="{{ route('admin.inventory.cancel', $inventory) }}" method="POST" 
                          onsubmit="return confirm('Annuler cet inventaire ?')">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-x-circle"></i> Annuler
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Liste des produits -->
    <div class="{{ $inventory->status == 'in_progress' ? 'col-md-8' : 'col-12' }}">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list"></i> Liste des produits</span>
                <div class="d-flex gap-2 align-items-center">
                    <a href="{{ route('admin.inventory.print-list', $inventory) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
                        <i class="bi bi-printer"></i> Imprimer la liste
                    </a>
                    <input type="text" id="searchProduct" class="form-control form-control-sm" 
                           style="width: 250px;" placeholder="Rechercher...">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="productsTable">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Catégorie</th>
                                <th class="text-center">Théorique</th>
                                <th class="text-center">Physique</th>
                                <th class="text-center">Écart</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inventory->items as $item)
                                <tr id="item-{{ $item->id }}" class="{{ $item->counted ? '' : 'table-light' }}" 
                                    data-item-id="{{ $item->id }}" style="cursor: pointer;">
                                    <td>
                                        <strong>{{ $item->product->name }}</strong>
                                    </td>
                                    <td>{{ $item->product->category->name ?? '-' }}</td>
                                    <td class="text-center">{{ $item->theoretical_quantity }}</td>
                                    <td class="text-center physical-qty">
                                        @if($item->counted)
                                            {{ $item->physical_quantity }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center difference">
                                        @if($item->counted)
                                            <span class="badge bg-{{ $item->difference_color }}">
                                                {{ $item->difference > 0 ? '+' : '' }}{{ $item->difference }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="status">
                                        <span class="badge bg-{{ $item->difference_color }}">
                                            {{ $item->difference_label }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Actions post-inventaire -->
        @if($inventory->status == 'completed')
        <div class="card mt-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5><i class="bi bi-exclamation-triangle text-warning"></i> Inventaire terminé</h5>
                        <p class="mb-0">
                            {{ $inventory->products_with_difference }} produit(s) avec écart détecté(s).
                            Valeur totale des écarts: <strong class="{{ $inventory->total_difference_value < 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($inventory->total_difference_value, 0, ',', ' ') }} FCFA
                            </strong>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="{{ route('admin.inventory.report', $inventory) }}" class="btn btn-info me-2">
                            <i class="bi bi-file-text"></i> Voir le rapport
                        </a>
                        <form action="{{ route('admin.inventory.validate', $inventory) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Valider et corriger le stock ? Cette action est irréversible.')">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg"></i> Valider et corriger le stock
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        @if($inventory->status == 'validated')
        <div class="card mt-4 border-success">
            <div class="card-body bg-success bg-opacity-10">
                <h5 class="text-success"><i class="bi bi-check-circle"></i> Inventaire validé</h5>
                <p class="mb-0">
                    Validé par {{ $inventory->validatedBy->name ?? 'N/A' }} le {{ $inventory->validated_at?->format('d/m/Y à H:i') }}.
                    Le stock a été corrigé automatiquement.
                </p>
                <a href="{{ route('admin.inventory.report', $inventory) }}" class="btn btn-outline-success mt-2">
                    <i class="bi bi-file-text"></i> Voir le rapport final
                </a>
            </div>
        </div>
        @endif
    </div>
</div>

@if($inventory->status == 'in_progress')
<!-- Toast pour les notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle me-2"></i>
                <span id="toastMessage">Comptage enregistré !</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script type="application/json" id="inventoryData">{"id": {{ $inventory->id }}}</script>
<script>
const inventoryData = JSON.parse(document.getElementById('inventoryData').textContent);
const inventoryId = inventoryData.id;
let currentItemId = null;
let searchTimeout = null;

// Initialiser la barre de progression
document.addEventListener('DOMContentLoaded', function() {
    const progressBar = document.getElementById('progressBar');
    if (progressBar && progressBar.dataset.progress) {
        progressBar.style.width = progressBar.dataset.progress + '%';
    }
});

// Fonction pour afficher un toast
function showToast(message, type = 'success') {
    const toastEl = document.getElementById('successToast');
    document.getElementById('toastMessage').textContent = message;
    toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
    const toast = new bootstrap.Toast(toastEl, { delay: 2000 });
    toast.show();
}

// Recherche de produit par nom
document.getElementById('searchInput').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const search = e.target.value.trim();
    
    if (search.length < 2) {
        document.getElementById('searchResults').classList.add('d-none');
        return;
    }
    
    searchTimeout = setTimeout(() => {
        searchProducts(search);
    }, 300);
});

function searchProducts(search) {
    fetch(`/admin/inventory/${inventoryId}/search`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ search: search })
    })
    .then(response => response.json())
    .then(data => {
        const resultsDiv = document.getElementById('searchResults');
        
        if (data.error) {
            resultsDiv.classList.add('d-none');
            document.getElementById('searchError').textContent = data.error;
            document.getElementById('searchError').classList.remove('d-none');
        } else {
            document.getElementById('searchError').classList.add('d-none');
            resultsDiv.innerHTML = '';
            
            data.items.forEach(item => {
                const badge = item.counted 
                    ? '<span class="badge bg-success ms-2">Compté</span>' 
                    : '<span class="badge bg-secondary ms-2">Non compté</span>';
                    
                resultsDiv.innerHTML += `
                    <a href="#" class="list-group-item list-group-item-action" onclick="selectItem(${item.id}); return false;">
                        <strong>${item.product.name}</strong> ${badge}
                        <br><small class="text-muted">${item.product.category?.name || '-'} | Stock: ${item.theoretical_quantity}</small>
                    </a>
                `;
            });
            
            resultsDiv.classList.remove('d-none');
        }
    });
}

// Sélectionner un item depuis les résultats ou le tableau
function selectItem(itemId) {
    fetch(`/admin/inventory/${inventoryId}/items/${itemId}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('searchResults').classList.add('d-none');
            document.getElementById('searchError').classList.add('d-none');
            document.getElementById('productInfo').classList.remove('d-none');
            
            currentItemId = data.item.id;
            document.getElementById('productName').textContent = data.item.product.name;
            document.getElementById('theoreticalQty').textContent = data.item.theoretical_quantity;
            document.getElementById('physicalQty').value = data.item.physical_quantity || 0;
            document.getElementById('physicalQty').focus();
            document.getElementById('physicalQty').select();
            
            // Scroll vers le produit dans le tableau
            const row = document.getElementById(`item-${itemId}`);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.add('table-primary');
                setTimeout(() => row.classList.remove('table-primary'), 2000);
            }
        }
    });
}

// Clic sur une ligne du tableau
document.querySelectorAll('#productsTable tbody tr').forEach(row => {
    row.addEventListener('click', function() {
        const itemId = this.dataset.itemId;
        if (itemId) {
            selectItem(itemId);
        }
    });
});

// Enregistrer le comptage
document.getElementById('saveCountBtn').addEventListener('click', function() {
    if (!currentItemId) return;
    
    const physicalQty = document.getElementById('physicalQty').value;
    const notes = document.getElementById('itemNotes').value;
    
    fetch(`/admin/inventory/${inventoryId}/items/${currentItemId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            physical_quantity: parseInt(physicalQty),
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour la ligne du tableau
            const row = document.getElementById(`item-${currentItemId}`);
            row.classList.remove('table-light');
            row.querySelector('.physical-qty').textContent = physicalQty;
            
            const diff = parseInt(physicalQty) - data.item.theoretical_quantity;
            const diffCell = row.querySelector('.difference');
            const statusCell = row.querySelector('.status');
            
            let diffColor = diff === 0 ? 'success' : (diff > 0 ? 'info' : 'danger');
            let diffLabel = diff === 0 ? 'Conforme' : (diff > 0 ? `Surplus (+${diff})` : `Manque (${diff})`);
            
            diffCell.innerHTML = `<span class="badge bg-${diffColor}">${diff > 0 ? '+' : ''}${diff}</span>`;
            statusCell.innerHTML = `<span class="badge bg-${diffColor}">${diffLabel}</span>`;
            
            // Mettre à jour les statistiques
            if (data.stats) {
                document.getElementById('statCounted').textContent = data.stats.counted;
                document.getElementById('statWithDiff').textContent = data.stats.with_difference;
                document.getElementById('statShortage').textContent = data.stats.shortage;
            }
            
            // Mettre à jour la barre de progression
            if (data.progress !== undefined) {
                document.getElementById('progressPercent').textContent = data.progress + '%';
                document.getElementById('progressBar').style.width = data.progress + '%';
                document.getElementById('progressText').textContent = data.stats.counted + ' / ' + data.stats.total;
            }
            
            // Réinitialiser le formulaire
            document.getElementById('productInfo').classList.add('d-none');
            document.getElementById('itemNotes').value = '';
            document.getElementById('searchInput').value = '';
            currentItemId = null;
            document.getElementById('searchInput').focus();
            
            // Afficher un message de succès temporaire
            showToast('Comptage enregistré !', 'success');
        }
    });
});

// Recherche dans le tableau
document.getElementById('searchProduct').addEventListener('input', function() {
    const search = this.value.toLowerCase();
    document.querySelectorAll('#productsTable tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});

// Raccourci clavier Enter pour enregistrer
document.getElementById('physicalQty').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('saveCountBtn').click();
    }
});
</script>
@endif
@endsection
