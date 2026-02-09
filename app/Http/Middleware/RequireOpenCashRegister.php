<?php

namespace App\Http\Middleware;

use App\Models\CashRegister;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour vérifier qu'une caisse est ouverte
 * avant de permettre les opérations financières
 */
class RequireOpenCashRegister
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Les admins peuvent passer (ils gèrent, ne font pas de transactions)
        if ($user && $user->hasRole('admin')) {
            return $next($request);
        }

        // Vérifier si l'utilisateur a une caisse ouverte
        $openCashRegister = CashRegister::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if (!$openCashRegister) {
            // Si c'est une requête AJAX
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez ouvrir une caisse avant de faire cette opération.',
                ], 403);
            }

            // Redirection avec message d'erreur
            return redirect()->route('cashier.cash-register.index')
                ->with('error', 'Vous devez ouvrir une caisse avant de faire des transactions (ventes, réparations, paiements).');
        }

        // Stocker la caisse ouverte dans la requête pour usage ultérieur
        $request->attributes->set('openCashRegister', $openCashRegister);

        return $next($request);
    }
}
