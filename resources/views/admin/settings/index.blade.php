@extends('layouts.app')

@section('title', 'Paramètres')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="container-fluid">

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-gear"></i> Paramètres</h2>
        @if($shops->count() > 0)
        <div class="d-flex align-items-center gap-2">
            <select id="shop_selector" class="form-select form-select-sm" style="min-width: 220px;" onchange="changeShop(this.value)">
                <option value="">Paramètres Globaux</option>
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>
                        {{ $shop->name }} ({{ $shop->code }})
                    </option>
                @endforeach
            </select>
            @if($selectedShopId)
                <span class="badge bg-info fs-6"><i class="bi bi-shop me-1"></i>{{ $shops->find($selectedShopId)->name ?? '' }}</span>
                <form action="{{ route('admin.settings.reset-to-global', $selectedShopId) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Réinitialiser aux valeurs globales ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </form>
            @else
                <span class="badge bg-secondary"><i class="bi bi-globe me-1"></i>Global</span>
            @endif
        </div>
        @endif
    </div>

    <div class="row">
        <!-- Colonne principale avec onglets -->
        <div class="col-lg-8">
            <form action="{{ route('admin.settings.update') }}" method="POST" id="settingsForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">

                <!-- Navigation par onglets -->
                <ul class="nav nav-pills mb-4" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-entreprise" data-bs-toggle="pill" data-bs-target="#pane-entreprise" type="button" role="tab">
                            <i class="bi bi-building me-1"></i>Entreprise
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-facturation" data-bs-toggle="pill" data-bs-target="#pane-facturation" type="button" role="tab">
                            <i class="bi bi-receipt me-1"></i>Facturation
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-reparations" data-bs-toggle="pill" data-bs-target="#pane-reparations" type="button" role="tab">
                            <i class="bi bi-tools me-1"></i>Réparations
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-ventes" data-bs-toggle="pill" data-bs-target="#pane-ventes" type="button" role="tab">
                            <i class="bi bi-cart-check me-1"></i>Ventes & SAV
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-notifications" data-bs-toggle="pill" data-bs-target="#pane-notifications" type="button" role="tab">
                            <i class="bi bi-bell me-1"></i>Notifications
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-impression" data-bs-toggle="pill" data-bs-target="#pane-impression" type="button" role="tab">
                            <i class="bi bi-printer me-1"></i>Impression
                        </button>
                    </li>
                </ul>

                <!-- Contenu des onglets -->
                <div class="tab-content" id="settingsTabContent">

                    <!-- ═══════ ENTREPRISE ═══════ -->
                    <div class="tab-pane fade show active" id="pane-entreprise" role="tabpanel">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><i class="bi bi-building me-2 text-primary"></i>Informations entreprise</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="company_name" class="form-label fw-semibold">Nom</label>
                                        <input type="text" class="form-control" id="company_name" name="company_name"
                                               value="{{ $settings['company_name'] ?? '' }}" placeholder="EGREGORE BUSINESS">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="company_phone" class="form-label fw-semibold">Téléphone</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                            <input type="text" class="form-control" id="company_phone" name="company_phone"
                                                   value="{{ $settings['company_phone'] ?? '' }}" placeholder="+225 XX XX XX XX">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="company_email" class="form-label fw-semibold">Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" id="company_email" name="company_email"
                                                   value="{{ $settings['company_email'] ?? '' }}" placeholder="contact@entreprise.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="company_whatsapp" class="form-label fw-semibold">WhatsApp</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-whatsapp text-success"></i></span>
                                            <input type="text" class="form-control" id="company_whatsapp" name="company_whatsapp"
                                                   value="{{ $settings['company_whatsapp'] ?? '' }}" placeholder="+225 XX XX XX XX">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="company_website" class="form-label fw-semibold">Site web</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                            <input type="text" class="form-control" id="company_website" name="company_website"
                                                   value="{{ $settings['company_website'] ?? '' }}" placeholder="www.entreprise.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="company_facebook" class="form-label fw-semibold">Page Facebook</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-facebook text-primary"></i></span>
                                            <input type="text" class="form-control" id="company_facebook" name="company_facebook"
                                                   value="{{ $settings['company_facebook'] ?? '' }}" placeholder="https://facebook.com/votrepage">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="company_address" class="form-label fw-semibold">Adresse</label>
                                        <textarea class="form-control" id="company_address" name="company_address" rows="2" placeholder="Adresse complète">{{ $settings['company_address'] ?? '' }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="company_siret" class="form-label fw-semibold">SIRET / RCCM</label>
                                        <input type="text" class="form-control" id="company_siret" name="company_siret"
                                               value="{{ $settings['company_siret'] ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="company_tva" class="form-label fw-semibold">N° TVA</label>
                                        <input type="text" class="form-control" id="company_tva" name="company_tva"
                                               value="{{ $settings['company_tva'] ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════ FACTURATION ═══════ -->
                    <div class="tab-pane fade" id="pane-facturation" role="tabpanel">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><i class="bi bi-receipt me-2 text-primary"></i>Facturation</h5>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="invoice_prefix" class="form-label fw-semibold">Préfixe facture</label>
                                        <input type="text" class="form-control" id="invoice_prefix" name="invoice_prefix"
                                               value="{{ $settings['invoice_prefix'] ?? 'FAC' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="quote_prefix" class="form-label fw-semibold">Préfixe devis</label>
                                        <input type="text" class="form-control" id="quote_prefix" name="quote_prefix"
                                               value="{{ $settings['quote_prefix'] ?? 'DEV' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="repair_prefix" class="form-label fw-semibold">Préfixe réparation</label>
                                        <input type="text" class="form-control" id="repair_prefix" name="repair_prefix"
                                               value="{{ $settings['repair_prefix'] ?? 'REP' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="default_tva_rate" class="form-label fw-semibold">Taux TVA (%)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" id="default_tva_rate" name="default_tva_rate"
                                                   value="{{ $settings['default_tva_rate'] ?? '20' }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label for="invoice_footer" class="form-label fw-semibold">Pied de page facture</label>
                                    <textarea class="form-control" id="invoice_footer" name="invoice_footer" rows="2" placeholder="Texte affiché en bas des factures">{{ $settings['invoice_footer'] ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════ RÉPARATIONS ═══════ -->
                    <div class="tab-pane fade" id="pane-reparations" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><i class="bi bi-tools me-2 text-primary"></i>Réparations</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="repair_warranty_days" class="form-label fw-semibold">Garantie réparation (jours)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="repair_warranty_days" name="repair_warranty_days"
                                                   value="{{ $settings['repair_warranty_days'] ?? '30' }}">
                                            <span class="input-group-text">jours</span>
                                        </div>
                                        <small class="text-muted">Durée de garantie après livraison</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="repair_default_diagnostic_fee" class="form-label fw-semibold">Frais diagnostic</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" id="repair_default_diagnostic_fee" name="repair_default_diagnostic_fee"
                                                   value="{{ $settings['repair_default_diagnostic_fee'] ?? '0' }}">
                                            <span class="input-group-text">FCFA</span>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="repair_terms" class="form-label fw-semibold">Conditions de réparation</label>
                                        <textarea class="form-control" id="repair_terms" name="repair_terms" rows="3" placeholder="Conditions affichées sur les fiches réparation">{{ $settings['repair_terms'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card border-0 shadow-sm" style="border-left: 4px solid #6610f2 !important;">
                            <div class="card-body">
                                <h5 class="card-title mb-1"><i class="bi bi-percent me-2" style="color: #6610f2;"></i>Partage main d'oeuvre</h5>
                                <p class="text-muted small mb-3">Répartition du prix de la main d'oeuvre entre l'admin (boutique) et le technicien.</p>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label for="technician_labor_share" class="form-label fw-semibold">Part technicien</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control form-control-lg text-center" id="technician_labor_share" name="technician_labor_share"
                                                   value="{{ $settings['technician_labor_share'] ?? '50' }}" min="0" max="100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="bg-light rounded p-3">
                                            @php $techPercent = (int)($settings['technician_labor_share'] ?? 50); @endphp
                                            <div class="d-flex justify-content-between mb-2">
                                                <span><i class="bi bi-person-gear me-1"></i>Technicien</span>
                                                <strong class="text-primary">{{ $techPercent }}%</strong>
                                            </div>
                                            <div class="progress mb-2" style="height: 10px;">
                                                <div class="progress-bar" role="progressbar" style="width: {{ $techPercent }}%; background: #6610f2;"></div>
                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ 100 - $techPercent }}%"></div>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span><i class="bi bi-shop me-1"></i>Boutique (admin)</span>
                                                <strong class="text-success">{{ 100 - $techPercent }}%</strong>
                                            </div>
                                            <hr class="my-2">
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Pièces = 100% boutique. Comptabilisé uniquement sur les réparations <strong>livrées</strong>.
                                                SAV = à la charge du technicien.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════ VENTES & SAV ═══════ -->
                    <div class="tab-pane fade" id="pane-ventes" role="tabpanel">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><i class="bi bi-cart-check me-2 text-primary"></i>Ventes & S.A.V</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="sale_warranty_days" class="form-label fw-semibold">Garantie vente (jours)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="sale_warranty_days" name="sale_warranty_days"
                                                   value="{{ $settings['sale_warranty_days'] ?? '7' }}">
                                            <span class="input-group-text">jours</span>
                                        </div>
                                        <small class="text-muted">Durée pour retour/échange après une vente</small>
                                    </div>
                                </div>
                                <div class="alert alert-info mt-3 mb-0 d-flex align-items-center">
                                    <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                                    <span>Après ce délai de garantie, aucun ticket S.A.V (retour, échange, remboursement) ne pourra être créé.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════ NOTIFICATIONS ═══════ -->
                    <div class="tab-pane fade" id="pane-notifications" role="tabpanel">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><i class="bi bi-bell me-2 text-primary"></i>Notifications</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="border rounded p-3">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" id="notify_low_stock" name="notify_low_stock" value="1"
                                                       {{ ($settings['notify_low_stock'] ?? '1') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="notify_low_stock">Alerte stock bas</label>
                                            </div>
                                            <label for="low_stock_threshold" class="form-label small text-muted">Seuil d'alerte</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold"
                                                       value="{{ $settings['low_stock_threshold'] ?? '5' }}">
                                                <span class="input-group-text">unités</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" id="notify_repair_ready" name="notify_repair_ready" value="1"
                                                       {{ ($settings['notify_repair_ready'] ?? '1') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="notify_repair_ready">Réparation terminée</label>
                                            </div>
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" id="send_sms_notifications" name="send_sms_notifications" value="1"
                                                       {{ ($settings['send_sms_notifications'] ?? '0') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="send_sms_notifications">SMS clients</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════ IMPRESSION ═══════ -->
                    <div class="tab-pane fade" id="pane-impression" role="tabpanel">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><i class="bi bi-printer me-2 text-primary"></i>Impression</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="receipt_printer_name" class="form-label fw-semibold">Imprimante ticket</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-printer"></i></span>
                                            <input type="text" class="form-control" id="receipt_printer_name" name="receipt_printer_name"
                                                   value="{{ $settings['receipt_printer_name'] ?? '' }}" placeholder="Nom de l'imprimante">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="receipt_width" class="form-label fw-semibold">Largeur ticket</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="receipt_width" name="receipt_width"
                                                   value="{{ $settings['receipt_width'] ?? '80' }}">
                                            <span class="input-group-text">mm</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="auto_print_receipt" name="auto_print_receipt" value="1"
                                                       {{ ($settings['auto_print_receipt'] ?? '0') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="auto_print_receipt">Impression automatique</label>
                                            </div>
                                            <small class="text-muted">Imprimer le ticket après chaque vente</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="print_logo" name="print_logo" value="1"
                                                       {{ ($settings['print_logo'] ?? '1') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="print_logo">Logo sur tickets</label>
                                            </div>
                                            <small class="text-muted">Afficher le logo en haut du ticket</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Bouton enregistrer (fixe en bas) -->
                <div class="d-flex justify-content-end mt-4 mb-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Colonne latérale -->
        <div class="col-lg-4">
            <!-- Modes de paiement -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                    <h6 class="mb-0"><i class="bi bi-credit-card me-2 text-primary"></i>Modes de paiement</h6>
                    <button type="button" class="btn btn-sm btn-primary rounded-circle" data-bs-toggle="modal" data-bs-target="#paymentMethodModal" style="width:30px;height:30px;padding:0;">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($paymentMethods as $method)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <i class="bi bi-{{ $method->icon ?? 'credit-card' }} me-2 text-muted"></i>
                                    <span class="fw-medium">{{ $method->name }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    @if($method->is_active)
                                        <span class="badge bg-success rounded-pill">Actif</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill">Inactif</span>
                                    @endif
                                    <form action="{{ route('admin.payment-methods.destroy', $method) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Supprimer ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-muted text-center py-3">Aucun mode configuré</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <!-- Logo (utilisé automatiquement depuis images/logo.png) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bi bi-image me-2 text-primary"></i>Logo</h6>
                </div>
                <div class="card-body text-center">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="img-fluid mb-2 rounded" style="max-height: 80px;">
                    <p class="text-muted small mb-0">Logo utilisé automatiquement sur les reçus et la boutique en ligne.</p>
                </div>
            </div>

            <!-- Sauvegarde -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bi bi-database me-2 text-primary"></i>Sauvegarde</h6>
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

            <!-- Système -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Système</h6>
                </div>
                <div class="card-body py-2">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">PHP</td><td class="text-end fw-medium">{{ phpversion() }}</td></tr>
                        <tr><td class="text-muted">Laravel</td><td class="text-end fw-medium">{{ app()->version() }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal ajout mode de paiement -->
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

// Persist active tab
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector('[data-bs-target="' + hash + '"]');
        if (tab) new bootstrap.Tab(tab).show();
    }
    document.querySelectorAll('#settingsTabs button').forEach(btn => {
        btn.addEventListener('shown.bs.tab', function(e) {
            history.replaceState(null, null, e.target.dataset.bsTarget);
        });
    });
});
</script>
@endpush
