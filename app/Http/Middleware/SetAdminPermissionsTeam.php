<?php

namespace App\Http\Middleware;

use Closure;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Setzt Team-ID 0 (global) für Super-Admin Permission-Prüfungen.
 * Muss im Admin-Panel als authMiddleware registriert sein.
 */
class SetAdminPermissionsTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isSuperAdmin()) {
            app(PermissionRegistrar::class)
                ->setPermissionsTeamId(RolesAndPermissionsSeeder::GLOBAL_TEAM_ID);
        }

        return $next($request);
    }
}
