<?php

namespace App\Http\Controllers;

abstract class Controller
{
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
