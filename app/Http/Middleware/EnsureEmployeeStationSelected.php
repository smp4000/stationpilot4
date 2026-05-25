<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mitarbeiter müssen vor der Arbeit eine Station auswählen.
 * Leitet auf die Stationsauswahl-Seite weiter wenn keine aktive Station gesetzt ist.
 */
class EnsureEmployeeStationSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->isEmployee()
            && ! session('active_station_id')
            && ! $request->routeIs('filament.app.pages.station-waehlen')
            && ! $request->routeIs('filament.app.pages.mein-profil')
            && ! $request->routeIs('filament.app.auth.*')
            && $request->method() === 'GET'
        ) {
            return redirect()->to(\App\Filament\App\Pages\StationWaehlen::getUrl());
        }

        return $next($request);
    }
}
