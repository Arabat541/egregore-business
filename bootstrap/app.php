<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'security' => \App\Http\Middleware\SecurityMiddleware::class,
            'shop.scope' => \App\Http\Middleware\ShopScope::class,
            'cash.open' => \App\Http\Middleware\RequireOpenCashRegister::class,
        ]);
        
        // Ajouter le middleware de sécurité et de scope boutique à toutes les requêtes authentifiées
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SecurityMiddleware::class,
            \App\Http\Middleware\ShopScope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
