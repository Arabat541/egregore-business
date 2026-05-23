<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

abstract class Controller
{
    /**
     * Loggue une exception et retourne un message générique à l'utilisateur.
     * Ne jamais exposer $e->getMessage() directement — il peut contenir
     * des informations sensibles (noms de tables, colonnes, stack trace).
     */
    protected function handleException(\Throwable $e, string $context = '', array $extra = []): string
    {
        Log::error("Exception [{$context}]", array_merge([
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ], $extra));

        return 'Une erreur est survenue. Veuillez réessayer ou contacter l\'administrateur.';
    }

    /**
     * Réponse JSON standardisée pour succès
     */
    protected function successResponse($data = null, string $message = 'Opération réussie', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Réponse JSON standardisée pour erreur
     */
    protected function errorResponse(string $message = 'Une erreur est survenue', int $code = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
