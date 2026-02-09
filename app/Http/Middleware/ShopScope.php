<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour filtrer les données par boutique
 * - Admin : accès global à toutes les boutiques
 * - Caissière/Technicien : accès limité à leur boutique uniquement
 */
class ShopScope
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Si l'utilisateur n'est pas admin et n'a pas de boutique assignée
            if (!$user->hasRole('admin') && !$user->shop_id) {
                auth()->logout();
                return redirect()->route('login')
                    ->with('error', 'Votre compte n\'est pas associé à une boutique. Contactez l\'administrateur.');
            }
            
            // Partager la boutique actuelle avec toutes les vues
            if ($user->shop_id) {
                view()->share('currentShop', $user->shop);
            } else {
                view()->share('currentShop', null);
            }
            
            // Partager le statut admin
            view()->share('isAdmin', $user->hasRole('admin'));
        }
        
        return $next($request);
    }
}
