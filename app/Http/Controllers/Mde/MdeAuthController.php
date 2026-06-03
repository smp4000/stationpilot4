<?php

namespace App\Http\Controllers\Mde;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\MdeDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\PermissionRegistrar;

/**
 * Mitarbeiter-Login am MDE-Gerät.
 *
 * POST /api/mde/auth/login
 *   → Login via PIN, Scan-Code oder NFC-UID
 *   → Gibt Mitarbeiterdaten + Permissions zurück
 *
 * POST /api/mde/auth/logout
 *   → Mitarbeiter abmelden (Session-Ende)
 */
class MdeAuthController extends Controller
{
    /**
     * Mitarbeiter einloggen.
     *
     * Body: { "method": "pin|scan|nfc", "value": "..." }
     */
    public function login(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'method' => ['required', 'in:pin,scan,nfc'],
            'value'  => ['required', 'string'],
        ]);

        if ($v->fails()) {
            return response()->json(['message' => 'Ungültige Eingabe.', 'errors' => $v->errors()], 422);
        }

        // Gerät aus Token auflösen → tenant_id + station_id
        $device = $this->resolveDevice($request);
        if (! $device) {
            return response()->json(['message' => 'Gerät nicht registriert.'], 401);
        }

        $tenantId = $device->tenant_id;

        // Mitarbeiter suchen
        $employee = match ($request->method_field ?? $request->method) {
            'pin'  => $this->findByPin($tenantId, $request->value),
            'scan' => $this->findByScanCode($tenantId, $request->value),
            'nfc'  => $this->findByNfc($tenantId, $request->value),
            default => null,
        };

        // Workaround: request->method() ist HTTP-Methode, also anders auslesen
        $loginMethod = $request->input('method');
        $employee = match ($loginMethod) {
            'pin'  => $this->findByPin($tenantId, $request->value),
            'scan' => $this->findByScanCode($tenantId, $request->value),
            'nfc'  => $this->findByNfc($tenantId, $request->value),
            default => null,
        };

        if (! $employee) {
            return response()->json(['message' => 'Code nicht erkannt. Bitte erneut scannen.'], 401);
        }

        if ($employee->employee_status === 'inaktiv') {
            return response()->json(['message' => 'Mitarbeiter ist inaktiv.'], 403);
        }

        // Spatie Permission Team auf Tenant setzen
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        // Permissions des Mitarbeiters via verknüpften User laden
        $permissions = [];
        $roles = [];
        if ($employee->user) {
            $permissions = $employee->user->getAllPermissions()->pluck('name')->toArray();
            $roles = $employee->user->getRoleNames()->toArray();
        }

        // Zugriffs-Log schreiben
        $employee->accessLog()->create([
            'accessed_by'  => null,
            'accessed_at'  => now(),
            'type'         => 'mde_login',
            'app_version'  => $request->header('X-App-Version'),
            'device_info'  => $device->device_name . ' (' . $device->device_model . ')',
        ]);

        $device->touchLastSeen();

        return response()->json([
            'message'  => 'Erfolgreich angemeldet.',
            'employee' => [
                'ulid'       => $employee->ulid,
                'name'       => $employee->fullName(),
                'job_title'  => $employee->job_title,
                'station'    => [
                    'ulid' => $device->station->ulid,
                    'name' => $device->station->name,
                ],
                'avatar_url' => null,
            ],
            'permissions' => $permissions,
            'roles'       => $roles,
            'session_expires_at' => now()->addHours(8)->toIso8601String(),
        ]);
    }

    /**
     * Mitarbeiter abmelden.
     */
    public function logout(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if ($device) {
            $device->touchLastSeen();
        }

        return response()->json(['message' => 'Mitarbeiter abgemeldet.']);
    }

    // ─── Private Hilfsmethoden ────────────────────────────────────────────────

    private function findByPin(int $tenantId, string $pin): ?Employee
    {
        $employees = Employee::where('tenant_id', $tenantId)
            ->whereNotNull('mde_pin')
            ->get();

        return $employees->first(fn (Employee $e) => Hash::check($pin, $e->mde_pin));
    }

    private function findByScanCode(int $tenantId, string $code): ?Employee
    {
        return Employee::where('tenant_id', $tenantId)
            ->where('scan_code', $code)
            ->first();
    }

    private function findByNfc(int $tenantId, string $uid): ?Employee
    {
        // Erst exakte NFC-UID prüfen, dann scan_code (falls Karte scan_code enthält)
        return Employee::where('tenant_id', $tenantId)
            ->where('nfc_uid', strtoupper($uid))
            ->first()
            ?? Employee::where('tenant_id', $tenantId)
                ->where('scan_code', $uid)
                ->first();
    }

    private function resolveDevice(Request $request): ?MdeDevice
    {
        $tokenName = $request->user()?->tokens()->latest()->value('name');
        if (! $tokenName) return null;
        return MdeDevice::where('token_name', $tokenName)->with('station')->first();
    }
}
