<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckRoleRoutePermission;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\AutoLogout;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'check.permission' => CheckRoleRoutePermission::class,
            'auto.logout'      => AutoLogout::class,
        ]);
           $middleware->appendToGroup('web', [
            AutoLogout::class,
            // ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Define your exception handling if needed
    })
    ->create();
