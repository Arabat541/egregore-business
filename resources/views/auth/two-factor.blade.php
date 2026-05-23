<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification 2FA — EGREGORE BUSINESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-2fa {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
            padding: 40px;
            max-width: 420px;
            width: 100%;
        }
        .otp-input {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: .5rem;
            text-align: center;
            font-family: monospace;
        }
        .app-badge {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: .82rem;
            color: #166534;
        }
    </style>
</head>
<body>
    <div class="card-2fa">
        <div class="text-center mb-4">
            <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                <i class="bi bi-shield-lock-fill fs-1 text-success"></i>
            </div>
            <h4 class="fw-bold mb-1">Vérification en 2 étapes</h4>
            <p class="text-muted small mb-0">Ouvrez <strong>Google Authenticator</strong> et entrez le code à 6 chiffres affiché pour <strong>{{ config('app.name') }}</strong>.</p>
        </div>

        <div class="app-badge d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-phone fs-5"></i>
            <span>Le code change toutes les <strong>30 secondes</strong> — entrez-le rapidement.</span>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2">
                <i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('2fa.verify') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="form-label fw-semibold">Code à 6 chiffres</label>
                <input type="text"
                       name="code"
                       id="code"
                       class="form-control otp-input @error('code') is-invalid @enderror"
                       placeholder="000000"
                       maxlength="6"
                       inputmode="numeric"
                       pattern="[0-9]{6}"
                       autocomplete="one-time-code"
                       autofocus>
            </div>

            <button type="submit" class="btn btn-success w-100 py-2 mb-3 fw-semibold">
                <i class="bi bi-check-circle me-1"></i> Vérifier et se connecter
            </button>
        </form>

        <div class="text-center">
            <a href="{{ route('login') }}" class="text-muted" style="font-size:.85rem;">
                <i class="bi bi-arrow-left me-1"></i>Retour à la connexion
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit quand 6 chiffres sont saisis
        document.getElementById('code').addEventListener('input', function () {
            const digits = this.value.replace(/\D/g, '');
            this.value = digits;
            if (digits.length === 6) {
                this.form.submit();
            }
        });
    </script>
</body>
</html>
