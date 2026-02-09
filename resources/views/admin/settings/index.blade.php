@extends('layouts.app')

@section('title', 'Paramètres')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-gear me-2"></i>Paramètres
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Tableau de bord</a></li>
                    <li class="breadcrumb-item active">Paramètres</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($shops->count() > 0)
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <label for="shop_selector" class="form-label fw-bold">
                        <i class="bi bi-shop me-1"></i>Boutique
                    </label>
                    <select id="shop_selector" class="form-select" onchange="changeShop(this.value)">
                        <option value="">-- Paramètres Globaux --</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>
                                {{ $shop->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8">
                    @if($selectedShopId)
                        <div class="alert alert-info mb-0 mt-3 mt-md-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Vous modifiez les paramètres de la boutique <strong>{{ $shops->find($selectedShopId)->name ?? '' }}</strong>.
                        </div>
                    @else
                        <div class="alert alert-secondary mb-0 mt-3 mt-md-0">
                            <i class="bi bi-globe me-2"></i>
                            Vous modifiez les <strong>paramètres globaux</strong>.
                        </div>
                    @endif
                </div>
            </div>
            @if($selectedShopId)
            <div class="mt-3">
                <form action="{{ route('admin.settings.reset-to-global', $selectedShopId) }}" method="POST" class="d-inline" 
                      onsubmit="return confirm('Réinitialiser aux valeurs globales ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-building me-2"></i>Informations entreprise</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="company_name" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="{{ $settings['company_name'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="company_phone" class="form-label">Téléphone</label>
                                <input type="text" class="form-control" id="company_phone" name="company_phone" 
                                       value="{{ $settings['company_phone'] ?? '' }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="company_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="company_email" name="company_email" 
                                       value="{{ $settings['company_email'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="company_website" class="form-label">Site web</label>
                                <input type="text" class="form-control" id="company_website" name="company_website" 
                                       value="{{ $settings['company_website'] ?? '' }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="company_address" class="form-label">Adresse</label>
                            <textarea class="form-control" id="company_address" name="company_address" rows="2">{{ $settings['company_address'] ?? '' }}</textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="company_siret" class="form-label">SIRET</label>
                                <input type="text" class="form-control" id="company_siret" name="company_siret" 
                                       value="{{ $settings['company_siret'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="company_tva" class="form-label">N° TVA</label>
                                <input type="text" class="form-control" id="company_tva" name="company_tva" 
                                       value="{{ $settings['company_tva'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Facturation</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="invoice_prefix" class="form-label">Préfixe facture</label>
                                <input type="text" class="form-control" id="invoice_prefix" name="invoice_prefix" 
                                       value="{{ $settings['invoice_prefix'] ?? 'FAC' }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="quote_prefix" class="form-label">Préfixe devis</label>
                                <input type="text" class="form-control" id="quote_prefix" name="quote_prefix" 
                                       value="{{ $settings['quote_prefix'] ?? 'DEV' }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="repair_prefix" class="form-label">Préfixe réparation</label>
                                <input type="text" class="form-control" id="repair_prefix" name="repair_prefix" 
                                       value="{{ $settings['repair_prefix'] ?? 'REP' }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="default_tva_rate" class="form-label">Taux TVA (%)</label>
                                <input type="number" step="0.01" class="form-control" id="default_tva_rate" name="default_tva_rate" 
                                       value="{{ $settings['default_tva_rate'] ?? '20' }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="invoice_footer" class="form-label">Pied de page facture</label>
                            <textarea class="form-control" id="invoice_footer" name="invoice_footer" rows="2">{{ $settings['invoice_footer'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-tools me-2"></i>Réparations</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="repair_warranty_days" class="form-label">Garantie (jours)</label>
                                <input type="number" class="form-control" id="repair_warranty_days" name="repair_warranty_days" 
                                       value="{{ $settings['repair_warranty_days'] ?? '30' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="repair_default_diagnostic_fee" class="form-label">Frais diagnostic (€)</label>
                                <input type="number" step="0.01" class="form-control" id="repair_default_diagnostic_fee" name="repair_default_diagnostic_fee" 
                                       value="{{ $settings['repair_default_diagnostic_fee'] ?? '0' }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="repair_terms" class="form-label">Conditions</label>
                            <textarea class="form-control" id="repair_terms" name="repair_terms" rows="3">{{ $settings['repair_terms'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bell me-2"></i>Notifications</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notify_low_stock" name="notify_low_stock" value="1"
                                           {{ ($settings['notify_low_stock'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_low_stock">Stock bas</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="low_stock_threshold" class="form-label">Seuil stock bas</label>
                                <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" 
                                       value="{{ $settings['low_stock_threshold'] ?? '5' }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notify_repair_ready" name="notify_repair_ready" value="1"
                                           {{ ($settings['notify_repair_ready'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_repair_ready">Réparation terminée</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="send_sms_notifications" name="send_sms_notifications" value="1"
                                           {{ ($settings['send_sms_notifications'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="send_sms_notifications">SMS clients</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-printer me-2"></i>Impression</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="receipt_printer_name" class="form-label">Imprimante ticket</label>
                                <input type="text" class="form-control" id="receipt_printer_name" name="receipt_printer_name" 
                                       value="{{ $settings['receipt_printer_name'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="receipt_width" class="form-label">Largeur (mm)</label>
                                <input type="number" class="form-control" id="receipt_width" name="receipt_width" 
                                       value="{{ $settings['receipt_width'] ?? '80' }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="auto_print_receipt" name="auto_print_receipt" value="1"
                                           {{ ($settings['auto_print_receipt'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="auto_print_receipt">Impression auto</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="print_logo" name="print_logo" value="1"
                                           {{ ($settings['print_logo'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="print_logo">Logo sur tickets</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Modes de paiement</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#paymentMethodModal">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($paymentMethods as $method)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-{{ $method->icon ?? 'credit-card' }} me-2"></i>
                                    {{ $method->name }}
                                </div>
                                <div>
                                    @if($method->is_active)
                                        <span class="badge bg-success me-2">Actif</span>
                                    @else
                                        <span class="badge bg-secondary me-2">Inactif</span>
                                    @endif
                                    <form action="{{ route('admin.payment-methods.destroy', $method) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Supprimer ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-muted text-center">Aucun mode configuré</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-image me-2"></i>Logo</h5>
                </div>
                <div class="card-body text-center">
                    @if(!empty($settings['company_logo']))
                        <img src="{{ asset('storage/' . $settings['company_logo']) }}" alt="Logo" class="img-fluid mb-3" style="max-height: 100px;">
                    @else
                        <div class="bg-light rounded p-4 mb-3">
                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mb-0 mt-2">Aucun logo</p>
                        </div>
                    @endif
                    <form action="{{ route('admin.settings.upload-logo') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">
                        <div class="mb-3">
                            <input type="file" class="form-control" name="logo" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-upload me-1"></i>Télécharger
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-database me-2"></i>Sauvegarde</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.backup') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-download me-1"></i>Créer sauvegarde
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Système</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td class="text-muted">PHP</td><td class="text-end">{{ phpversion() }}</td></tr>
                        <tr><td class="text-muted">Laravel</td><td class="text-end">{{ app()->version() }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentMethodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.payment-methods.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter mode de paiement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="pm_name" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="pm_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="pm_code" class="form-label">Code</label>
                        <input type="text" class="form-control" id="pm_code" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="pm_icon" class="form-label">Icône</label>
                        <input type="text" class="form-control" id="pm_icon" name="icon" value="credit-card">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="pm_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="pm_active">Actif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function changeShop(shopId) {
    const url = new URL(window.location.href);
    if (shopId) {
        url.searchParams.set('shop_id', shopId);
    } else {
        url.searchParams.delete('shop_id');
    }
    window.location.href = url.toString();
}
</script>
@endpush
