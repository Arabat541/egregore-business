@extends('layouts.app')

@section('title', 'Modifier réparation')

@section('sidebar')
    @include('cashier.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil"></i> Modifier {{ $repair->repair_number }}</h2>
    <a href="{{ route('cashier.repairs.show', $repair) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form action="{{ route('cashier.repairs.update', $repair) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-md-8">

            {{-- Client --}}
            <div class="card mb-3">
                <div class="card-header bg-primary text-white"><i class="bi bi-person"></i> Client</div>
                <div class="card-body">
                    <select class="form-select @error('customer_id') is-invalid @enderror" name="customer_id" required>
                        <option value="">-- Sélectionner un client --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                {{ old('customer_id', $repair->customer_id) == $customer->id ? 'selected' : '' }}>
                                {{ $customer->full_name }} - {{ $customer->phone }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Appareil --}}
            <div class="card mb-3">
                <div class="card-header bg-info text-white"><i class="bi bi-phone"></i> Appareil</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="device_type" required>
                                @foreach(['phone' => 'Téléphone', 'tablet' => 'Tablette', 'laptop' => 'Laptop', 'other' => 'Autre'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('device_type', $repair->device_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Marque</label>
                            <input type="text" class="form-control" name="device_brand"
                                   value="{{ old('device_brand', $repair->device_brand) }}" placeholder="Samsung, Apple...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Modèle</label>
                            <input type="text" class="form-control" name="device_model"
                                   value="{{ old('device_model', $repair->device_model) }}" placeholder="Galaxy S21...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">IMEI</label>
                            <input type="text" class="form-control" name="device_imei"
                                   value="{{ old('device_imei', $repair->device_imei) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Code du téléphone</label>
                            <input type="text" class="form-control" name="device_password"
                                   value="{{ old('device_password', $repair->device_password) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">État de l'appareil</label>
                            <input type="text" class="form-control" name="device_condition"
                                   value="{{ old('device_condition', $repair->device_condition) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Problème signalé <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('reported_issue') is-invalid @enderror"
                                      name="reported_issue" rows="3" required>{{ old('reported_issue', $repair->reported_issue) }}</textarea>
                            @error('reported_issue')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-md-4">

            {{-- Technicien & Coût --}}
            <div class="card mb-3">
                <div class="card-header bg-warning text-dark"><i class="bi bi-tools"></i> Assignation</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Technicien</label>
                        <select class="form-select" name="technician_id">
                            <option value="">-- Non assigné --</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}"
                                    {{ old('technician_id', $repair->technician_id) == $tech->id ? 'selected' : '' }}>
                                    {{ $tech->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Coût estimé (FCFA)</label>
                        <input type="number" class="form-control" name="estimated_cost" min="0"
                               value="{{ old('estimated_cost', $repair->estimated_cost) }}">
                    </div>
                </div>
            </div>

            <div class="card bg-light mb-3">
                <div class="card-body">
                    <p class="mb-1"><strong>N° :</strong> {{ $repair->repair_number }}</p>
                    <p class="mb-1"><strong>Statut :</strong> <span class="badge bg-primary">{{ $repair->status_label }}</span></p>
                    <p class="mb-0"><strong>Créée le :</strong> {{ $repair->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-check-lg"></i> Enregistrer les modifications
                </button>
                <a href="{{ route('cashier.repairs.show', $repair) }}" class="btn btn-outline-secondary">
                    Annuler
                </a>
            </div>

        </div>
    </div>
</form>
@endsection
