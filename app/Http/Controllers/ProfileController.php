<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Gestion du profil utilisateur - Accessible à tous les utilisateurs connectés
 */
class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return back()->with('success', 'Profil mis à jour avec succès.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        auth()->user()->update([
            'password'              => Hash::make($validated['password']),
            'force_password_change' => false,
            'password_changed_at'   => now(),
        ]);

        return back()->with('success', 'Mot de passe modifié avec succès.');
    }

    // ── 2FA Google Authenticator ─────────────────────────────────────────

    /**
     * Affiche la page de configuration avec le QR code à scanner.
     * Génère un secret temporaire stocké en session jusqu'à confirmation.
     */
    public function setup2fa(Request $request)
    {
        $user = auth()->user();

        if ($user->two_factor_enabled) {
            return redirect()->route('profile.edit')->with('info', 'La 2FA est déjà activée.');
        }

        /** @var TotpService $totp */
        $totp   = app(TotpService::class);
        $secret = $totp->generateSecret();

        // Stocker le secret temporairement en session
        $request->session()->put('2fa_setup_secret', $secret);

        $qrUrl  = $totp->getQrCodeUrl(config('app.name', 'EGREGORE BUSINESS'), $user->email, $secret);
        $qrCode = base64_encode((string) QrCode::format('svg')->size(200)->margin(1)->generate($qrUrl));

        return view('profile.2fa-setup', compact('secret', 'qrCode'));
    }

    /**
     * Confirme la configuration : vérifie un code TOTP avant d'activer.
     */
    public function confirm2fa(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $user   = auth()->user();
        $secret = $request->session()->get('2fa_setup_secret');

        if (!$secret) {
            return redirect()->route('profile.2fa.setup')
                ->with('error', 'Session expirée. Recommencez la configuration.');
        }

        if (!app(TotpService::class)->verify($secret, $request->code)) {
            return back()->withErrors(['code' => 'Code incorrect. Vérifiez votre application et réessayez.']);
        }

        $user->update([
            'two_factor_secret'  => $secret,
            'two_factor_enabled' => true,
        ]);

        $request->session()->forget('2fa_setup_secret');

        return redirect()->route('profile.edit')
            ->with('success', '✓ Google Authenticator activé avec succès. Votre compte est maintenant protégé.');
    }

    /**
     * Désactive la 2FA après vérification du mot de passe.
     */
    public function disable2fa(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);

        auth()->user()->update([
            'two_factor_enabled' => false,
            'two_factor_secret'  => null,
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'Double authentification désactivée.');
    }
}
