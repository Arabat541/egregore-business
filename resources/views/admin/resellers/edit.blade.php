@extends('layouts.app')

@section('title', 'Modifier Revendeur')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil"></i> Modifier: {{ $reseller->company_name }}</h2>
    <a href="{{ route('admin.resellers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.resellers.update', $reseller) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.resellers.form')
        </form>
    </div>
</div>
@endsection
