<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Réparateur — Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .portal-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            overflow: hidden;
            max-width: 420px;
            width: 100%;
        }
        .portal-header {
            background: linear-gradient(135deg, #e65100, #f57c00);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .portal-header .icon-wrap {
            width: 70px; height: 70px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }
        .portal-body { padding: 2rem; }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 1.1rem;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #f57c00;
            box-shadow: 0 0 0 0.2rem rgba(245,124,0,0.15);
        }
        .btn-login {
            background: linear-gradient(135deg, #e65100, #f57c00);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 1.05rem;
            padding: 0.75rem;
            width: 100%;
            transition: opacity 0.2s;
        }
        .btn-login:hover { opacity: 0.9; color: white; }
        .info-text { color: #6c757d; font-size: 0.88rem; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-sm-10 col-md-8 col-lg-5">
            <div class="portal-card">
                <div class="portal-header">
                    <div class="icon-wrap"><i class="bi bi-shop"></i></div>
                    <h4 class="mb-1 fw-bold">Espace Réparateur</h4>
                    <p class="mb-0 opacity-75 small">Consultez votre compte en ligne</p>
                </div>
                <div class="portal-body">

                    @if(session('success'))
                    <div class="alert alert-success rounded-3 mb-3">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    </div>
                    @endif

                    @if(session('info'))
                    <div class="alert alert-info rounded-3 mb-3">
                        <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
                    </div>
                    @endif

                    <p class="text-muted mb-4 text-center">
                        Entrez votre numéro de téléphone pour accéder à votre espace.
                    </p>

                    <form method="POST" action="{{ route('reseller-portal.authenticate') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-telephone me-1"></i>Numéro de téléphone
                            </label>
                            <input type="tel" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   placeholder="Ex : 07 00 00 00 00"
                                   value="{{ old('phone') }}"
                                   autofocus autocomplete="tel">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Accéder à mon espace
                        </button>
                    </form>

                    <hr class="my-4">
                    <p class="info-text">
                        <i class="bi bi-lock me-1"></i>
                        Votre numéro de téléphone est votre identifiant.<br>
                        Aucun mot de passe requis.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
