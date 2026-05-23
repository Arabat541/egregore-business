@extends('layouts.app')

@section('title', 'Gestion des clients')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> Gestion des clients</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal">
        <i class="bi bi-plus-circle"></i> Nouveau client
    </button>
</div>

<!-- Recherche -->
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('cashier.customers.index') }}" method="GET" class="row g-3">
            <div class="col-md-6">
                <input type="text" class="form-control" name="search" value="{{ request('search') }}" 
                       placeholder="Rechercher par nom, téléphone, email...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Rechercher
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('cashier.customers.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th>Ventes</th>
                        <th>Réparations</th>
                        <th>Dernière visite</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td>
                            <strong>{{ $customer->full_name }}</strong>
                            @if($customer->company_name)
                                <br><small class="text-muted">{{ $customer->company_name }}</small>
                            @endif
                        </td>
                        <td>{{ $customer->phone }}</td>
                        <td>{{ $customer->email ?: '-' }}</td>
                        <td><span class="badge bg-primary">{{ $customer->sales_count ?? 0 }}</span></td>
                        <td><span class="badge bg-warning">{{ $customer->repairs_count ?? 0 }}</span></td>
                        <td>{{ $customer->updated_at->format('d/m/Y') }}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="editCustomer({{ json_encode($customer) }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="{{ route('cashier.customers.show', $customer) }}" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Aucun client trouvé</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $customers->appends(request()->query())->links() }}
    </div>
</div>

<!-- Modal Client -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="customerForm" method="POST" action="{{ route('cashier.customers.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="bi bi-plus-circle"></i> Nouveau client
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" id="firstName" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" id="lastName" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" name="phone" id="phone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <input type="text" class="form-control" name="address" id="address">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editCustomer(customer) {
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil"></i> Modifier le client';
    document.getElementById('customerForm').action = '/caisse/customers/' + customer.id;
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('firstName').value = customer.first_name;
    document.getElementById('lastName').value = customer.last_name;
    document.getElementById('phone').value = customer.phone;
    document.getElementById('email').value = customer.email || '';
    document.getElementById('address').value = customer.address || '';
    document.getElementById('notes').value = customer.notes || '';
    
    new bootstrap.Modal(document.getElementById('customerModal')).show();
}

document.getElementById('customerModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-plus-circle"></i> Nouveau client';
    document.getElementById('customerForm').action = '{{ route('cashier.customers.store') }}';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('customerForm').reset();
});
</script>
@endpush
