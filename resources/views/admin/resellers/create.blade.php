@extends('layouts.app')

@section('title', 'Nouveau Revendeur')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-plus"></i> Nouveau Revendeur</h2>
    <a href="{{ route('admin.resellers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.resellers.store') }}" method="POST">
            @csrf
            @include('admin.resellers.form')
        </form>
    </div>
</div>
@endsection
