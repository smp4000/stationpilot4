<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Setzt die tenant_id des Users als Team-ID für Permission-Prüfungen.
 * Muss im App-Panel als authMiddleware registriert sein.
 */
class SetPartnerPermissionsTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = $request->user();

            if ($user?->tenant_id) {
                app(PermissionRegistrar::class)
                    ->setPermissionsTeamId($user->tenant_id);

                if (! session()->has('tenant_id')) {
                    session(['tenant_id' => $user->tenant_id]);
                }
            }
        } catch (\Throwable) {
            // DB noch nicht bereit (z. B. frische Migration) — still weitermachen
        }

        return $next($request);
    }
}
