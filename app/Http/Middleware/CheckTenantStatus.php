<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
/**
 * Prüft den Abo-Status des Mandanten bei jedem Request.
 *
 * Status-Logik:
 * - archived  → Logout + Fehlermeldung (kein Datenzugriff mehr)
 * - cancelled → Logout + Fehlermeldung (Grace-Period vorbei)
 * - read_only → Login erlaubt, aber Schreibzugriff gesperrt (Flash-Warnung)
 * - past_due  → Login mit Zahlungswarnung (voller Zugriff bleibt)
 * - trial     → Prüfen ob noch aktiv, sonst → read_only
 * - active    → Normaler Zugriff
 *
 * Super-Admin und User ohne Mandant werden übersprungen.
 */
class CheckTenantStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        // Super-Admin und nicht eingeloggte User überspringen
        if (! $user || $user->isSuperAdmin() || ! $user->tenant_id) {
            return $next($request);
        }
        $tenant = $user->tenant;
        if (! $tenant) {
            return $next($request);
        }
        // Archiviert oder deaktiviert → sofort ausloggen
        if ($tenant->isArchived() || ! $tenant->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()
                ->route('filament.app.auth.login')
                ->withErrors(['email' => 'Ihr Zugang wurde deaktiviert. Bitte kontaktieren Sie den Support.']);
        }
        // Trial abgelaufen → Warnhinweis, Weiterleitung bleibt (read_only kommt per UI)
        if ($tenant->isTrialExpired()) {
            // Flash-Warnung setzen — Filament-Notification in späteren Prompts
            session(['tenant_warning' => 'trial_expired']);
        }
        // Überfällige Zahlung → Warnhinweis
        if ($tenant->isPastDue()) {
            session(['tenant_warning' => 'past_due']);
        }
        // Nur-Lesen-Modus → Warnhinweis
        if ($tenant->isReadOnly()) {
            session(['tenant_warning' => 'read_only']);
        }
        return $next($request);
    }
}
