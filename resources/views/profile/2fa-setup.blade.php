@extends('layouts.app')

@section('title', 'Configurer Google Authenticator')

@section('sidebar')
    @if(auth()->user()->hasRole('admin'))
        @include('admin.partials.sidebar')
    @elseif(auth()->user()->hasRole('caissiere'))
        @include('cashier.partials.sidebar')
    @elseif(auth()->user()->hasRole('technicien'))
        @include('technician.partials.sidebar')
    @endif
@endsection

@section('content')
<div class="d-flex align-items-center mb-4">
    <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary btn-sm me-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="mb-0"><i class="bi bi-shield-lock"></i> Configurer Google Authenticator</h2>
</div>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">

        {{-- Étapes --}}
        <div class="card mb-4 border-0 bg-light">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Comment configurer :</h6>
                <ol class="mb-0 small text-muted">
                    <li class="mb-1">Installez <strong>Google Authenticator</strong> sur votre téléphone (<a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Android</a> / <a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank">iOS</a>)</li>
                    <li class="mb-1">Ouvrez l'application et appuyez sur <strong>+</strong> → <strong>Scanner un QR code</strong></li>
                    <li class="mb-1">Scannez le QR code ci-dessous</li>
                    <li>Entrez le code à 6 chiffres affiché pour confirmer</li>
                </ol>
            </div>
        </div>

        {{-- QR Code --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                <i class="bi bi-qr-code"></i> Étape 1 — Scannez ce QR code
            </div>
            <div class="card-body text-center py-4">
                <div class="d-inline-block p-3 border rounded bg-white shadow-sm">
                    {!! base64_decode($qrCode) !!}
                </div>
                <p class="text-muted small mt-3 mb-0">
                    Impossible de scanner ?<br>
                    Entrez ce code manuellement dans l'application :
                </p>
                <div class="mt-2">
                    <code class="fs-6 fw-bold letter-spacing-2 user-select-all">{{ chunk_split($secret, 4, ' ') }}</code>
                </div>
            </div>
        </div>

        {{-- Confirmation --}}
        <div class="card">
            <div class="card-header fw-semibold">
                <i class="bi bi-check2-circle"></i> Étape 2 — Confirmez le code
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger py-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first() }}
                    </div>
                @endif

                <p class="text-muted small mb-3">
                    Après avoir scanné le QR code, entrez le code à 6 chiffres affiché dans Google Authenticator pour valider la configuration.
                </p>

                <form action="{{ route('profile.2fa.confirm') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Code de vérification <span class="text-danger">*</span></label>
                        <input type="text"
                               name="code"
                               id="code"
                               class="form-control form-control-lg text-center fw-bold @error('code') is-invalid @enderror"
                               placeholder="000000"
                               maxlength="6"
                               inputmode="numeric"
                               pattern="[0-9]{6}"
                               autocomplete="one-time-code"
                               autofocus
                               style="letter-spacing:.5rem; font-size:1.6rem; font-family:monospace;">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary">Annuler</a>
                        <button type="submit" class="btn btn-success flex-grow-1 fw-semibold">
                            <i class="bi bi-shield-check me-1"></i> Activer Google Authenticator
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
    document.getElementById('code').addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
        if (this.value.length === 6) {
            this.form.submit();
        }
    });
</script>
@endsection
