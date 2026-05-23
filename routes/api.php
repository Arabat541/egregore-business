<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RepairController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST — Sanctum Token Authentication
|--------------------------------------------------------------------------
|
| Ces routes sont consommées par l'application mobile (React Native / Flutter).
| Toutes les réponses sont en JSON. Authentification par Bearer token.
|
| Pour obtenir un token :
|   POST /api/login  { email, password, device_name }
|
| Ensuite : Authorization: Bearer {token}
|
*/

// ── Authentification (publique) ──────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// ── Routes protégées ─────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Profil
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // Produits (lecture seule — admin + caissière)
    Route::get('/products', [ProductController::class, 'index'])->name('api.products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('api.products.show');

    // Réparations
    Route::get('/repairs', [RepairController::class, 'index'])->name('api.repairs.index');
    Route::get('/repairs/{repair}', [RepairController::class, 'show'])->name('api.repairs.show');
    Route::patch('/repairs/{repair}/status', [RepairController::class, 'updateStatus'])->name('api.repairs.update-status');

});
