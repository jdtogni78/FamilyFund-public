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
    ->withMiddleware(function (Middleware $middleware) {
        // Dev-only: route a stray ?as= through /dev-login so impersonation always
        // takes effect (prepended so it runs before auth — works even when logged
        // out). No-op in production. See RedirectStrayImpersonation.
        $middleware->web(prepend: [
            \App\Http\Middleware\RedirectStrayImpersonation::class,
        ]);

        // Append to web middleware group
        $middleware->web(append: [
            \App\Http\Middleware\SetFundPermissions::class,
            \App\Http\Middleware\EnsureTwoFactorIsCompleted::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'fund.full' => \App\Http\Middleware\RequireFullFundAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
