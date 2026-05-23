@extends('layouts.app')

@section('title', 'Modifier Réparateur')

@section('sidebar')
    @if(auth()->user()->hasRole('admin'))
        @include('admin.partials.sidebar')
    @else
        @include('cashier.partials.sidebar')
    @endif
@endsection

@php $routePrefix = $routePrefix ?? 'admin'; @endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil"></i> Modifier: {{ $reseller->company_name }}</h2>
    <a href="{{ route($routePrefix . '.resellers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route($routePrefix . '.resellers.update', $reseller) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.resellers._form')
        </form>
    </div>
</div>
@endsection
