@extends('layouts.app')

@section('title', 'Modifier Fournisseur')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-truck me-2"></i>Modifier: {{ $supplier->company_name }}</h2>
    <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Retour
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.suppliers.update', $supplier) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.suppliers.form')
            
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
