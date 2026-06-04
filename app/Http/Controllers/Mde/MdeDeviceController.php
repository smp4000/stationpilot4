<?php

namespace App\Http\Controllers\Mde;

use App\Http\Controllers\Controller;
use App\Models\MdeDevice;
use App\Models\Station;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Gerät an Station registrieren.
 *
 * POST /api/mde/device/register
 *   → Gerät wird via Station-QR / NFC / Code einer Station zugewiesen
 *   → Gibt Device-Token zurück (dauerhaft im SecureStorage der App)
 */
class MdeDeviceController extends Controller
{
    /**
     * Gerät registrieren oder erneut binden.
     */
    public function register(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'station_code'  => ['required', 'string'],   // ULID oder slug der Station
            'device_name'   => ['required', 'string', 'max:100'],
            'device_model'  => ['nullable', 'string', 'max:100'],
            'android_id'    => ['nullable', 'string', 'max:64'],
            'app_version'   => ['nullable', 'string', 'max:20'],
        ]);

        if ($v->fails()) {
            return response()->json(['message' => 'Ungültige Eingabe.', 'errors' => $v->errors()], 422);
        }

        // Station anhand ULID oder Stationsnummer suchen
        $station = Station::where('ulid', $request->station_code)
            ->orWhere('station_number', $request->station_code)
            ->first();

        if (! $station || ! $station->is_active) {
            return response()->json(['message' => 'Station nicht gefunden oder inaktiv.'], 404);
        }

        $tenant = Tenant::find($station->tenant_id);
        if (! $tenant) {
            return response()->json(['message' => 'Mandant nicht gefunden.'], 403);
        }
        if (in_array($tenant->subscription_status ?? '', ['archived', 'cancelled'])) {
            return response()->json(['message' => 'Mandant gesperrt.'], 403);
        }

        // Bestehendes Gerät anhand android_id wiederfinden oder neu anlegen
        $device = MdeDevice::withTrashed()
            ->when($request->android_id, fn ($q) => $q->where('android_id', $request->android_id))
            ->first();

        if ($device) {
            $device->restore();
            $device->update([
                'tenant_id'    => $tenant->id,
                'station_id'   => $station->id,
                'device_name'  => $request->device_name,
                'device_model' => $request->device_model,
                'app_version'  => $request->app_version,
                'is_active'    => true,
                'last_seen_at' => now(),
            ]);
            // Alten Token löschen und neu ausstellen
            $tokenName = 'mde-device-' . $device->ulid;
            PersonalAccessToken::where('name', $tokenName)->delete();
        } else {
            $device = MdeDevice::create([
                'tenant_id'    => $tenant->id,
                'station_id'   => $station->id,
                'device_name'  => $request->device_name,
                'device_model' => $request->device_model,
                'android_id'   => $request->android_id,
                'app_version'  => $request->app_version,
                'last_seen_at' => now(),
            ]);
        }

        // Sanctum Token für das Gerät (gehört dem Tenant-Owner)
        $tokenName = 'mde-device-' . $device->ulid;
        $device->update(['token_name' => $tokenName]);

        // Token dem Tenant-Owner zuweisen (Fallback: erster aktiver User des Tenants)
        $tokenUser = $tenant->owner
            ?? \App\Models\User::where('tenant_id', $tenant->id)->first();

        if (! $tokenUser) {
            return response()->json(['message' => 'Kein Benutzer für diesen Mandanten gefunden.'], 500);
        }

        $token = $tokenUser->createToken($tokenName, ['mde-device']);

        return response()->json([
            'message'      => 'Gerät erfolgreich registriert.',
            'device_token' => $token->plainTextToken,
            'device'       => [
                'ulid'         => $device->ulid,
                'device_name'  => $device->device_name,
                'station'      => [
                    'ulid' => $station->ulid,
                    'name' => $station->name,
                    'city' => $station->city,
                ],
                'tenant' => [
                    'ulid' => $tenant->ulid,
                    'name' => $tenant->name,
                ],
            ],
        ]);
    }

    /**
     * Gerät deregistrieren (z.B. bei Gerätewechsel).
     */
    public function unregister(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (! $device) {
            return response()->json(['message' => 'Gerät nicht gefunden.'], 404);
        }

        $request->user()->tokens()->where('name', $device->token_name)->delete();
        $device->delete();

        return response()->json(['message' => 'Gerät abgemeldet.']);
    }

    /**
     * Heartbeat — App meldet sich regelmäßig, letzter Kontakt wird aktualisiert.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if ($device) {
            $device->update([
                'last_seen_at' => now(),
                'app_version'  => $request->input('app_version', $device->app_version),
            ]);
        }

        return response()->json(['ok' => true, 'server_time' => now()->toIso8601String()]);
    }

    private function resolveDevice(Request $request): ?MdeDevice
    {
        $tokenName = $request->user()?->tokens()->latest()->value('name');
        if (! $tokenName) return null;
        return MdeDevice::where('token_name', $tokenName)->first();
    }
}
