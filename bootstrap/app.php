<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Session\TokenMismatchException;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // CSRF expired
    $exceptions->render(function (TokenMismatchException $e, $request) {
        if ($request->is('admin/*')) {
            auth()->logout();
            return redirect('/admin/login');
        }
    });

    // Permissions / Policies / Shield (403)
    $exceptions->render(function (
        AuthorizationException|AccessDeniedHttpException $e,
        $request
    ) {
        if ($request->is('admin/*')) {
            auth()->logout();
            return redirect('/admin/login');
        }
    });
    })->create();
