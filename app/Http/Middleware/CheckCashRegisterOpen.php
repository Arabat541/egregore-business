<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated Ce middleware est un alias interne.
 * Utilise RequireOpenCashRegister (alias: cash.open) dans les routes.
 * Ce fichier existe pour éviter une rupture si une référence directe est ajoutée.
 */
class CheckCashRegisterOpen extends RequireOpenCashRegister
{
    // Hérite de RequireOpenCashRegister — aucune surcharge nécessaire.
}
