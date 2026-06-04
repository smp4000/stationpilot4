<?php

namespace App\Http\Controllers\Mde;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\MdeDevice;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
     * Alle aktiven Stationen des Tenants.
     */
    public function stations(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (! $device) {
            return response()->json(['message' => 'Gerät nicht registriert.'], 401);
        }

        $stations = Station::where('tenant_id', $device->tenant_id)
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
        $device = $this->resolveDevice($request);
        if (! $device) {
            return response()->json(['message' => 'Gerät nicht registriert.'], 401);
        }

        $station = Station::where('ulid', $stationUlid)
            ->where('tenant_id', $device->tenant_id)
            ->firstOrFail();

        $employees = Employee::where('tenant_id', $device->tenant_id)
            ->where(function ($q) use ($station) {
                $q->where('station_id', $station->id)
                  ->orWhereHas('stations', fn ($s) => $s->where('gas_stations.id', $station->id));
            })
            ->whereIn('status', ['aktiv', 'neu'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'ulid', 'first_name', 'last_name', 'job_title', 'scan_code', 'nfc_uid']);

        return response()->json([
            'employees' => $employees->map(fn ($e) => [
                'ulid'      => $e->ulid,
                'name'      => $e->fullName(),
                'job_title' => $e->job_title,
                'scan_code' => $e->scan_code,
                'has_nfc'   => ! empty($e->nfc_uid),
                'nfc_uid'   => $e->nfc_uid,
            ]),
        ]);
    }

    /**
     * NFC-UID eines Mitarbeiters speichern (nach Chip-Beschreibung).
     *
     * Body: { "nfc_uid": "A1B2C3D4", "scan_code": "3A18FC6E" }
     */
    public function saveNfc(Request $request, string $employeeUlid): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (! $device) {
            return response()->json(['message' => 'Gerät nicht registriert.'], 401);
        }

        $v = Validator::make($request->all(), [
            'nfc_uid'   => ['required', 'string', 'max:50'],
            'scan_code' => ['nullable', 'string', 'max:50'],
        ]);

        if ($v->fails()) {
            return response()->json(['message' => 'Ungültige Eingabe.', 'errors' => $v->errors()], 422);
        }

        $employee = Employee::where('ulid', $employeeUlid)
            ->where('tenant_id', $device->tenant_id)
            ->firstOrFail();

        // Prüfen ob NFC-UID schon einem anderen Mitarbeiter gehört
        $existing = Employee::where('nfc_uid', strtoupper($request->nfc_uid))
            ->where('id', '!=', $employee->id)
            ->where('tenant_id', $device->tenant_id)
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

    private function resolveDevice(Request $request): ?MdeDevice
    {
        $tokenName = $request->user()?->tokens()->latest()->value('name');
        if (! $tokenName) return null;
        return MdeDevice::where('token_name', $tokenName)->first();
    }
}
