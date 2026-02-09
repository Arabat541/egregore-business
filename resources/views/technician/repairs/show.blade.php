@extends('layouts.app')

@section('title', 'Réparation ' . $repair->repair_number)

@section('sidebar')
    @include('technician.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="bi bi-tools"></i> {{ $repair->repair_number }}
        <span class="badge bg-{{ $repair->status_color }}">{{ $repair->status_label }}</span>
    </h2>
    <a href="{{ route('technician.repairs.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Info appareil -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-phone"></i> Appareil
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Type:</strong> {{ ucfirst($repair->device_type) }}</p>
                        <p><strong>Marque:</strong> {{ $repair->device_brand }}</p>
                        <p><strong>Modèle:</strong> {{ $repair->device_model }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>IMEI/Série:</strong> {{ $repair->device_serial ?: '-' }}</p>
                        <p><strong>Code déverrouillage:</strong> 
                            @if($repair->device_password)
                                <code class="bg-warning text-dark px-2">{{ $repair->device_password }}</code>
                            @else
                                -
                            @endif
                        </p>
                        <p><strong>Accessoires:</strong> {{ $repair->accessories ?: '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Problème signalé -->
        <div class="card mb-3">
            <div class="card-header bg-warning">
                <i class="bi bi-exclamation-triangle"></i> Problème signalé par le client
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $repair->reported_issue }}</p>
                @if($repair->physical_condition)
                    <hr>
                    <p class="mb-0"><strong>État physique:</strong> {{ $repair->physical_condition }}</p>
                @endif
            </div>
        </div>

        <!-- Formulaire de travail -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-wrench"></i> Mon travail
            </div>
            <div class="card-body">
                <form action="{{ route('technician.repairs.update', $repair) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Diagnostic</label>
                        <textarea class="form-control @error('diagnosis') is-invalid @enderror" 
                                  name="diagnosis" rows="3" placeholder="Résultat du diagnostic...">{{ old('diagnosis', $repair->diagnosis) }}</textarea>
                        @error('diagnosis')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Travaux effectués</label>
                        <textarea class="form-control @error('work_done') is-invalid @enderror" 
                                  name="work_done" rows="3" placeholder="Décrivez les réparations effectuées...">{{ old('work_done', $repair->work_done) }}</textarea>
                        @error('work_done')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Coût main d'œuvre (FCFA)</label>
                                <input type="number" class="form-control @error('labor_cost') is-invalid @enderror" 
                                       name="labor_cost" value="{{ old('labor_cost', $repair->labor_cost) }}" min="0">
                                @error('labor_cost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Coût final total (FCFA)</label>
                                <input type="number" class="form-control @error('final_cost') is-invalid @enderror" 
                                       name="final_cost" value="{{ old('final_cost', $repair->final_cost ?: $repair->estimated_cost) }}" min="0">
                                @error('final_cost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes techniques</label>
                        <textarea class="form-control" name="technician_notes" rows="2" 
                                  placeholder="Notes pour référence future...">{{ old('technician_notes', $repair->technician_notes) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                </form>
            </div>
        </div>

        <!-- Pièces utilisées -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-box-seam"></i> Pièces utilisées</span>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addPartModal">
                    <i class="bi bi-plus"></i> Ajouter
                </button>
            </div>
            <div class="card-body">
                @if($repair->parts->count() > 0)
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Pièce</th>
                            <th>Qté</th>
                            <th>Prix</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($repair->parts as $part)
                        <tr>
                            <td>{{ $part->product->name ?? $part->description }}</td>
                            <td>{{ $part->quantity }}</td>
                            <td>{{ number_format($part->unit_price, 0, ',', ' ') }}</td>
                            <td>{{ number_format($part->total_price, 0, ',', ' ') }}</td>
                            <td>
                                <form action="{{ route('technician.repairs.remove-part', [$repair, $part]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette pièce ?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3">Total pièces:</th>
                            <th colspan="2">{{ number_format($repair->parts->sum('total_price'), 0, ',', ' ') }} FCFA</th>
                        </tr>
                    </tfoot>
                </table>
                @else
                <p class="text-muted text-center mb-0">Aucune pièce ajoutée</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Client -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-person"></i> Client
            </div>
            <div class="card-body">
                <p><strong>{{ $repair->customer->full_name }}</strong></p>
                <p><i class="bi bi-telephone"></i> {{ $repair->customer->phone }}</p>
            </div>
        </div>

        <!-- Infos réparation -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td>Créée le:</td>
                        <td>{{ $repair->created_at->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td>Retrait prévu:</td>
                        <td>{{ $repair->estimated_completion_date?->format('d/m/Y') ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td>Coût estimé:</td>
                        <td>{{ number_format($repair->estimated_cost, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <td>Acompte:</td>
                        <td>{{ number_format($repair->deposit_amount, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($repair->internal_notes)
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <i class="bi bi-sticky"></i> Notes caisse
            </div>
            <div class="card-body">
                {{ $repair->internal_notes }}
            </div>
        </div>
        @endif

        <!-- Actions de statut -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-arrow-right-circle"></i> Changer le statut
            </div>
            <div class="card-body">
                <form action="{{ route('technician.repairs.update-status', $repair) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="d-grid gap-2">
                        @if($repair->status === 'pending_payment')
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-exclamation-triangle"></i> En attente de paiement par le client.
                                <br><small>La caissière doit d'abord encaisser le client.</small>
                            </div>
                        @elseif(in_array($repair->status, ['paid_pending_diagnosis', 'pending']))
                            <button type="submit" name="status" value="in_diagnosis" class="btn btn-info">
                                <i class="bi bi-search"></i> Commencer le diagnostic
                            </button>
                        @elseif($repair->status === 'in_diagnosis')
                            <button type="submit" name="status" value="waiting_parts" class="btn btn-secondary">
                                <i class="bi bi-hourglass"></i> En attente de pièces
                            </button>
                            <button type="submit" name="status" value="in_repair" class="btn btn-primary">
                                <i class="bi bi-wrench"></i> Commencer la réparation
                            </button>
                            <button type="submit" name="status" value="unrepairable" class="btn btn-danger">
                                <i class="bi bi-x-circle"></i> Non réparable
                            </button>
                        @elseif($repair->status === 'waiting_parts')
                            <button type="submit" name="status" value="in_repair" class="btn btn-primary">
                                <i class="bi bi-wrench"></i> Pièces reçues, réparer
                            </button>
                        @elseif($repair->status === 'in_repair')
                            <button type="submit" name="status" value="repaired" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Réparation terminée
                            </button>
                        @elseif($repair->status === 'repaired')
                            <div class="alert alert-success mb-0">
                                <i class="bi bi-check-circle"></i> Réparation terminée, en attente de validation caisse.
                            </div>
                        @elseif($repair->status === 'unrepairable')
                            <div class="alert alert-danger mb-0">
                                <i class="bi bi-x-circle"></i> Appareil non réparable.
                            </div>
                        @elseif(in_array($repair->status, ['ready_for_pickup', 'delivered']))
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> Cette réparation est terminée.
                            </div>
                        @elseif($repair->status === 'cancelled')
                            <div class="alert alert-secondary mb-0">
                                <i class="bi bi-slash-circle"></i> Cette réparation a été annulée.
                            </div>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajouter pièce -->
<div class="modal fade" id="addPartModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('technician.repairs.add-part', $repair) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Ajouter une pièce</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Produit du stock</label>
                        <select class="form-select" name="product_id" id="productSelect">
                            <option value="">-- Ou saisir manuellement --</option>
                            @foreach($products ?? [] as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->purchase_price }}">
                                    {{ $product->name }} (Stock: {{ $product->quantity_in_stock }}) - {{ number_format($product->purchase_price, 0, ',', ' ') }} FCFA
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ou description libre</label>
                        <input type="text" class="form-control" name="description" id="partDescription" placeholder="Ex: Écran LCD Samsung A50">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantité</label>
                                <input type="number" class="form-control" name="quantity" value="1" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prix unitaire (FCFA)</label>
                                <input type="number" class="form-control" name="unit_price" id="partPrice" min="0" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-plus"></i> Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('productSelect').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    if (option.value) {
        document.getElementById('partPrice').value = option.dataset.price;
        document.getElementById('partDescription').value = '';
    }
});
</script>
@endsection
