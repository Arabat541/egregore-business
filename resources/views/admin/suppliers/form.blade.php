<div class="row g-4">
    <!-- Informations principales -->
    <div class="col-md-6">
        <h5 class="border-bottom pb-2 mb-3">Informations principales</h5>
        
        <div class="mb-3">
            <label class="form-label">Nom de l'entreprise <span class="text-danger">*</span></label>
            <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
                   value="{{ old('company_name', $supplier->company_name ?? '') }}" required>
            @error('company_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Nom du contact</label>
            <input type="text" name="contact_name" class="form-control @error('contact_name') is-invalid @enderror"
                   value="{{ old('contact_name', $supplier->contact_name ?? '') }}">
            @error('contact_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Téléphone principal <span class="text-danger">*</span></label>
            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                   value="{{ old('phone', $supplier->phone ?? '') }}" required>
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Téléphone secondaire</label>
            <input type="text" name="phone_secondary" class="form-control @error('phone_secondary') is-invalid @enderror"
                   value="{{ old('phone_secondary', $supplier->phone_secondary ?? '') }}">
            @error('phone_secondary')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">WhatsApp</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-whatsapp text-success"></i></span>
                <input type="text" name="whatsapp" class="form-control @error('whatsapp') is-invalid @enderror"
                       value="{{ old('whatsapp', $supplier->whatsapp ?? '') }}" placeholder="+225...">
            </div>
            @error('whatsapp')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $supplier->email ?? '') }}">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Adresse et autres -->
    <div class="col-md-6">
        <h5 class="border-bottom pb-2 mb-3">Adresse et catégories</h5>

        <div class="mb-3">
            <label class="form-label">Adresse</label>
            <textarea name="address" class="form-control @error('address') is-invalid @enderror"
                      rows="2">{{ old('address', $supplier->address ?? '') }}</textarea>
            @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Ville</label>
                <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
                       value="{{ old('city', $supplier->city ?? '') }}">
                @error('city')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Pays</label>
                <input type="text" name="country" class="form-control @error('country') is-invalid @enderror"
                       value="{{ old('country', $supplier->country ?? 'Côte d\'Ivoire') }}">
                @error('country')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Catégories de produits fournis</label>
            <select name="categories[]" class="form-select" multiple size="5">
                @foreach($categories as $id => $name)
                    <option value="{{ $name }}" 
                        {{ in_array($name, old('categories', $supplier->categories ?? [])) ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Maintenez Ctrl pour sélectionner plusieurs catégories</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Boutique associée</label>
            <select name="shop_id" class="form-select">
                <option value="">Toutes les boutiques</option>
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" 
                        {{ old('shop_id', $supplier->shop_id ?? '') == $shop->id ? 'selected' : '' }}>
                        {{ $shop->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                      rows="3" placeholder="Conditions de paiement, délais de livraison...">{{ old('notes', $supplier->notes ?? '') }}</textarea>
            @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        @isset($supplier)
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                       {{ old('is_active', $supplier->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Fournisseur actif</label>
            </div>
        @endisset
    </div>
</div>
