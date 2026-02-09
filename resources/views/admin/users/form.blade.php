@extends('layouts.app')

@section('title', isset($user) ? 'Modifier utilisateur' : 'Nouvel utilisateur')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="bi bi-{{ isset($user) ? 'pencil' : 'plus-circle' }}"></i> 
        {{ isset($user) ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}
    </h2>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST">
            @csrf
            @if(isset($user))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nom complet <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               name="name" value="{{ old('name', $user->name ?? '') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               name="email" value="{{ old('email', $user->email ?? '') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            Mot de passe 
                            @if(!isset($user))
                                <span class="text-danger">*</span>
                            @else
                                <small class="text-muted">(laisser vide pour ne pas modifier)</small>
                            @endif
                        </label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               name="password" {{ !isset($user) ? 'required' : '' }}>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Confirmer le mot de passe</label>
                        <input type="password" class="form-control" name="password_confirmation">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Rôle <span class="text-danger">*</span></label>
                        <select class="form-select @error('role') is-invalid @enderror" name="role" required>
                            <option value="">Sélectionner un rôle</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" 
                                        {{ old('role', isset($user) ? $user->roles->first()?->name : '') === $role->name ? 'selected' : '' }}>
                                    @if($role->name === 'admin')
                                        Administrateur
                                    @elseif($role->name === 'caissiere')
                                        Caissière
                                    @elseif($role->name === 'technicien')
                                        Technicien
                                    @else
                                        {{ ucfirst($role->name) }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Boutique <small class="text-muted">(optionnel pour Admin)</small></label>
                        <select class="form-select @error('shop_id') is-invalid @enderror" name="shop_id" id="shop_id">
                            <option value="">-- Toutes boutiques (Admin) --</option>
                            @foreach(\App\Models\Shop::active()->orderBy('name')->get() as $shop)
                                <option value="{{ $shop->id }}" 
                                        {{ old('shop_id', $user->shop_id ?? '') == $shop->id ? 'selected' : '' }}>
                                    {{ $shop->name }} ({{ $shop->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('shop_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Les caissières et techniciens doivent être assignés à une boutique.</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Statut</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                                   {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label">Utilisateur actif</label>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> {{ isset($user) ? 'Mettre à jour' : 'Créer' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
