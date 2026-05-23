<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de connexion</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; margin: 0; padding: 32px 0;">
    <div style="max-width: 480px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,.08);">
        <div style="background: #0d6efd; padding: 24px 32px;">
            <h1 style="color: #fff; margin: 0; font-size: 1.4rem;">EGREGORE BUSINESS</h1>
            <p style="color: rgba(255,255,255,.8); margin: 4px 0 0; font-size: .9rem;">Vérification en 2 étapes</p>
        </div>
        <div style="padding: 32px;">
            <p style="color: #334155; margin-top: 0;">Bonjour <strong>{{ $userName }}</strong>,</p>
            <p style="color: #64748b;">Voici votre code de connexion :</p>

            <div style="text-align: center; margin: 32px 0;">
                <div style="display: inline-block; background: #f8fafc; border: 2px dashed #0d6efd; border-radius: 12px; padding: 16px 48px;">
                    <span style="font-size: 2.5rem; font-weight: 700; letter-spacing: .4rem; color: #0d6efd; font-family: monospace;">{{ $code }}</span>
                </div>
            </div>

            <p style="color: #64748b; font-size: .9rem;">
                Ce code est valable <strong>10 minutes</strong>.<br>
                Si vous n'avez pas tenté de vous connecter, ignorez cet email.
            </p>
        </div>
        <div style="background: #f8fafc; padding: 16px 32px; text-align: center; border-top: 1px solid #e2e8f0;">
            <small style="color: #94a3b8;">{{ config('app.name') }} — Ne pas répondre à cet email</small>
        </div>
    </div>
</body>
</html>
