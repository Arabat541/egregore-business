@extends('layouts.app')

@section('title', 'Nouveau Produit')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-plus-circle"></i> Nouveau Produit</h2>
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.products.store') }}" method="POST">
            @csrf
            @include('admin.products.form')
        </form>
    </div>
</div>
@endsection
