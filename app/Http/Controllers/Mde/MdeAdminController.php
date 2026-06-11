<?php

namespace App\Http\Controllers\Mde;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\MdeDevice;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Admin-Funktionen für GoPilot MDE App.
 *
 * GET  /api/mde/admin/stations              — Stationen des Tenants
 * GET  /api/mde/admin/stations/{id}/employees — Mitarbeiter einer Station
 * POST /api/mde/admin/employees/{ulid}/nfc  — NFC-UID + scan_code speichern
 */
class MdeAdminController extends Controller
{
    /**
     * Tenant-ID aus dem Request ermitteln.
     * Weg: Auth-User → token_name → MdeDevice → tenant_id
     * Fallback: Auth-User → tenant_id direkt
     */
    private function resolveTenantId(Request $request): ?int
    {
        // Weg 1: Bearer-Token → PersonalAccessToken → MdeDevice → tenant_id
        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            $accessToken = PersonalAccessToken::findToken($bearerToken);
            if ($accessToken) {
                $device = MdeDevice::where('token_name', $accessToken->name)->first();
                if ($device) return $device->tenant_id;
            }
        }

        // Weg 2: Auth-User → tenant_id (Fallback)
        return $request->user()?->tenant_id ?? null;
    }

    /**
     * Alle aktiven Stationen des Tenants.
     */
    public function stations(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        if (! $tenantId) {
            return response()->json(['message' => 'Mandant nicht gefunden.'], 401);
        }

        $stations = Station::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'ulid', 'name', 'city', 'street']);

        return response()->json(['stations' => $stations]);
    }

    /**
     * Mitarbeiter einer Station.
     */
    public function employees(Request $request, string $stationUlid): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        if (! $tenantId) {
            return response()->json(['message' => 'Mandant nicht gefunden.'], 401);
        }

        $station = Station::where('ulid', $stationUlid)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $employees = Employee::where('tenant_id', $tenantId)
            ->where(function ($q) use ($station) {
                $q->where('station_id', $station->id)
                  ->orWhereHas('stations', fn ($s) => $s->where('gas_stations.id', $station->id));
            })
            ->whereIn('status', ['aktiv', 'neu'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return response()->json([
            'employees' => $employees->map(function ($e) {
                // Fester Zugangscode: einmal erzeugen, bleibt dann dauerhaft
                // (wird beim NFC-Beschreiben auf den Chip geschrieben)
                if (empty($e->scan_code)) {
                    $e->update(['scan_code' => strtoupper(Str::random(12))]);
                }

                // Geburtsdatum lesbar formatieren (Feld ist verschluesselt gespeichert)
                $dateOfBirth = null;
                if (! empty($e->date_of_birth)) {
                    try {
                        $dateOfBirth = \Carbon\Carbon::parse($e->date_of_birth)->format('d.m.Y');
                    } catch (\Exception $ex) {
                        $dateOfBirth = $e->date_of_birth;
                    }
                }

                // Ganzer Name fuer den Chip: Vorname Nachname, ggf. mit Geburtsname
                $fullName = $e->fullName();
                if (! empty($e->birth_name) && $e->birth_name !== $e->last_name) {
                    $fullName .= ' geb. ' . $e->birth_name;
                }

                return [
                    'ulid'          => $e->ulid,
                    'name'          => $e->fullName(),
                    'job_title'     => $e->job_title,
                    'scan_code'     => $e->scan_code,
                    'has_nfc'       => ! empty($e->nfc_uid),
                    'nfc_uid'       => $e->nfc_uid,
                    // Fuer das NFC-Beschreiben (Name, Adresse, Geburtsdatum auf dem Chip)
                    'full_name'     => $fullName,
                    'address'       => $e->fullAddress(),
                    'date_of_birth' => $dateOfBirth,
                ];
            }),
        ]);
    }

    /**
     * NFC-UID eines Mitarbeiters speichern (nach Chip-Beschreibung).
     */
    public function saveNfc(Request $request, string $employeeUlid): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        if (! $tenantId) {
            return response()->json(['message' => 'Mandant nicht gefunden.'], 401);
        }

        $v = Validator::make($request->all(), [
            'nfc_uid'   => ['required', 'string', 'max:50'],
            'scan_code' => ['nullable', 'string', 'max:50'],
        ]);

        if ($v->fails()) {
            return response()->json(['message' => 'Ungültige Eingabe.', 'errors' => $v->errors()], 422);
        }

        $employee = Employee::where('ulid', $employeeUlid)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $existing = Employee::where('nfc_uid', strtoupper($request->nfc_uid))
            ->where('id', '!=', $employee->id)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Diese NFC-UID ist bereits bei ' . $existing->fullName() . ' registriert.',
            ], 409);
        }

        $employee->update([
            'nfc_uid'   => strtoupper($request->nfc_uid),
            'scan_code' => $request->scan_code ?? $employee->scan_code,
        ]);

        return response()->json([
            'message'  => 'NFC-Chip erfolgreich gespeichert.',
            'employee' => [
                'ulid'    => $employee->ulid,
                'name'    => $employee->fullName(),
                'nfc_uid' => $employee->nfc_uid,
            ],
        ]);
    }
}
