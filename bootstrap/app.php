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
        // Team-ID für Spatie Permission bei jedem Web-Request setzen —
        // auch für Livewire-AJAX-Requests, die nicht durch Filamens authMiddleware laufen.
        $middleware->web(append: [
            \App\Http\Middleware\SetPartnerPermissionsTeam::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
