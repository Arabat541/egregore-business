<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    protected SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Vérifier si la tentative de connexion est autorisée
        $canAttempt = $this->securityService->canAttemptLogin($credentials['email']);
        if (!$canAttempt['allowed']) {
            return back()->withErrors([
                'email' => $canAttempt['message'],
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Vérifier si l'utilisateur est actif
            if (!$user->is_active) {
                Auth::logout();
                $this->securityService->recordFailedLogin($credentials['email'], 'account_disabled');
                return back()->withErrors([
                    'email' => 'Votre compte a été désactivé. Contactez l\'administrateur.',
                ]);
            }

            // Enregistrer la connexion réussie
            $this->securityService->recordSuccessfulLogin($user);

            $request->session()->regenerate();

            // Redirection selon le rôle
            return $this->redirectBasedOnRole($user);
        }

        // Enregistrer l'échec de connexion
        $this->securityService->recordFailedLogin($credentials['email'], 'invalid_password');

        return back()->withErrors([
            'email' => 'Les informations d\'identification sont incorrectes.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            $this->securityService->logout($user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    protected function redirectBasedOnRole($user)
    {
        // Vérifier si changement de mot de passe requis
        if ($user->force_password_change) {
            return redirect()->route('profile.edit')
                ->with('warning', 'Vous devez changer votre mot de passe pour des raisons de sécurité.');
        }

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('caissiere')) {
            return redirect()->route('cashier.dashboard');
        } elseif ($user->hasRole('technicien')) {
            return redirect()->route('technician.dashboard');
        }

        return redirect()->route('home');
    }
}
