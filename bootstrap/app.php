<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Limiter la confiance aux proxies déclarés explicitement en env.
        // Ne jamais utiliser '*' en production : cela permet de forger
        // X-Forwarded-For et de contourner le système anti-brute-force.
        // Exemple : TRUSTED_PROXIES=10.0.0.1,192.168.1.0/24
        $trustedProxies = env('TRUSTED_PROXIES', '');
        if ($trustedProxies !== '') {
            $middleware->trustProxies(at: $trustedProxies);
        }

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'security' => \App\Http\Middleware\SecurityMiddleware::class,
            'shop.scope' => \App\Http\Middleware\ShopScope::class,
            'cash.open' => \App\Http\Middleware\RequireOpenCashRegister::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SecurityMiddleware::class,
            \App\Http\Middleware\ShopScope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
