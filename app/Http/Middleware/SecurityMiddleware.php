<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de sécurité - Vérifie l'état de la session et du compte
 */
class SecurityMiddleware
{
    protected SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return $next($request);
        }

        // Vérifier si le compte est verrouillé
        if ($user->locked_until && $user->locked_until > now()) {
            Auth::guard('web')->logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Votre compte est verrouillé. Contactez un administrateur.']);
        }

        // Vérifier si le compte est actif
        if (!$user->is_active) {
            Auth::guard('web')->logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Votre compte a été désactivé.']);
        }

        // Vérifier si un changement de mot de passe est requis
        if ($user->force_password_change && !$request->routeIs('profile.*') && !$request->routeIs('logout')) {
            return redirect()->route('profile.edit')
                ->with('warning', 'Vous devez changer votre mot de passe avant de continuer.');
        }

        // Mettre à jour l'activité de la session
        UserSession::updateActivity(session()->getId());

        return $next($request);
    }
}
