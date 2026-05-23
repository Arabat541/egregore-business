<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Vérification 2FA Google Authenticator (TOTP)
 *
 * Flow :
 * 1. LoginController vérifie email/password → si 2FA activé, stocke l'ID en
 *    session et redirige vers GET /2fa
 * 2. GET /2fa  → affiche le formulaire de saisie du code
 * 3. POST /2fa → vérifie le TOTP, connecte ou renvoie une erreur (5 essais max)
 */
class TwoFactorController extends Controller
{
    private const SESSION_KEY  = '2fa_pending_user_id';
    private const MAX_ATTEMPTS = 5;

    public function show(Request $request)
    {
        if (!$request->session()->has(self::SESSION_KEY)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $userId = $request->session()->get(self::SESSION_KEY);

        // Rate limiting : 5 tentatives par userId sur 1 minute
        $rateLimiterKey = '2fa:' . $userId;
        if (RateLimiter::tooManyAttempts($rateLimiterKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($rateLimiterKey);
            return back()->withErrors([
                'code' => "Trop de tentatives. Réessayez dans {$seconds} secondes.",
            ]);
        }

        $user = \App\Models\User::find($userId);

        if (!$user) {
            return redirect()->route('login')->with('error', 'Session expirée, veuillez vous reconnecter.');
        }

        if (!$user->verifyTwoFactorCode($request->code)) {
            RateLimiter::hit($rateLimiterKey, 60);
            return back()->withErrors(['code' => 'Code incorrect. Vérifiez votre application Google Authenticator.']);
        }

        // Code valide → nettoyer, connecter, enregistrer la session
        RateLimiter::clear($rateLimiterKey);
        $request->session()->forget(self::SESSION_KEY);

        auth()->login($user);

        // Régénérer la session AVANT d'enregistrer (évite la fixation de session)
        $request->session()->regenerate();

        app(SecurityService::class)->recordSuccessfulLogin($user);

        return redirect()->intended(route('dashboard'));
    }
}
