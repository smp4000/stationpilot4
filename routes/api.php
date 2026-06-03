<?php

use App\Http\Controllers\Mde\MdeAuthController;
use App\Http\Controllers\Mde\MdeDeviceController;
use App\Http\Controllers\Mde\MdeNavigationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| GoPilot MDE API
|--------------------------------------------------------------------------
|
| Alle Routen für die GoPilot Android-App.
|
| Authentifizierung: Laravel Sanctum (Bearer Token)
|   - Device-Token: Gerät-Registrierung (dauerhaft)
|   - Kein separater Employee-Token — Employee-Login gibt nur Daten zurück
|
*/

Route::prefix('mde')->name('mde.')->group(function () {

    // ── Gerät registrieren (kein Auth nötig, nur Station-Code) ──────────────
    Route::post('device/register', [MdeDeviceController::class, 'register'])
        ->name('device.register');

    // ── Authentifizierte Geräterouten (Device-Token erforderlich) ───────────
    Route::middleware('auth:sanctum')->group(function () {

        // Gerät
        Route::delete('device/unregister', [MdeDeviceController::class, 'unregister'])
            ->name('device.unregister');
        Route::post('device/heartbeat', [MdeDeviceController::class, 'heartbeat'])
            ->name('device.heartbeat');

        // Mitarbeiter Login / Logout
        Route::post('auth/login',  [MdeAuthController::class, 'login'])->name('auth.login');
        Route::post('auth/logout', [MdeAuthController::class, 'logout'])->name('auth.logout');

        // Navigation + Kacheln (nach Mitarbeiter-Login)
        Route::get('navigation', [MdeNavigationController::class, 'index'])
            ->name('navigation');
    });
});
