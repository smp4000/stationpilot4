<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Leitet Mitarbeiter-User nach dem Login zur Profil-Seite weiter,
 * wenn must_change_password = true gesetzt ist.
 */
class RedirectIfMustChangePassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->must_change_password
            && $user->isEmployee()
            && ! $request->routeIs('filament.app.pages.mein-profil')
            && ! $request->routeIs('filament.app.auth.*')
            && $request->method() === 'GET'
        ) {
            return redirect()->to(\App\Filament\App\Pages\MeinProfil::getUrl());
        }

        return $next($request);
    }
}
