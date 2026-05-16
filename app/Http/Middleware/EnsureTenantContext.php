<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
/**
 * Stellt sicher dass der Tenant-Kontext korrekt in der Session steht.
 *
 * Prüfungen (in dieser Reihenfolge):
 * 1. Super-Admin → Redirect zu /admin (gehört nicht ins /app Panel)
 * 2. User ohne tenant_id → 403
 * 3. Session-tenant_id weicht vom User ab → Session reparieren
 * 4. tenant_id in Session setzen falls noch nicht gesetzt
 */
class EnsureTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }
        // Super-Admin gehört ins Admin-Panel, nicht ins App-Panel
        if ($user->isSuperAdmin()) {
            return redirect()->route('filament.admin.pages.dashboard');
        }
        // User ohne Mandant — darf nicht im App-Panel sein
        if (! $user->tenant_id) {
            abort(403, 'Kein Mandant zugeordnet. Bitte kontaktieren Sie den Support.');
        }
        // Sicherheitscheck: Session-Wert muss mit User übereinstimmen
        // Verhindert Session-Manipulation durch andere Mandanten-IDs
        if (session('tenant_id') !== $user->tenant_id) {
            session(['tenant_id' => $user->tenant_id]);
        }
        return $next($request);
    }
}
