<?php

namespace App\Http\Controllers\Mde;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\MdeDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

/**
 * Navigation + Kacheln für die GoPilot-App.
 *
 * GET /api/mde/navigation?employee_ulid=xxx
 *   → Permission-gefiltertes Navigationsmenü
 *   → Kacheln für Dashboard
 */
class MdeNavigationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (! $device) {
            return response()->json(['message' => 'Gerät nicht registriert.'], 401);
        }

        $employeeUlid = $request->query('employee_ulid');
        if (! $employeeUlid) {
            return response()->json(['message' => 'employee_ulid fehlt.'], 422);
        }

        $employee = Employee::where('ulid', $employeeUlid)
            ->where('tenant_id', $device->tenant_id)
            ->first();

        if (! $employee) {
            return response()->json(['message' => 'Mitarbeiter nicht gefunden.'], 404);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($device->tenant_id);

        $permissions = $employee->user
            ? $employee->user->getAllPermissions()->pluck('name')->toArray()
            : [];

        $nav   = $this->buildNavigation($permissions, $device->station);
        $tiles = $this->buildTiles($permissions);

        return response()->json([
            'navigation' => $nav,
            'tiles'      => $tiles,
        ]);
    }

    // ─── Navigation ───────────────────────────────────────────────────────────

    private function buildNavigation(array $permissions, $station): array
    {
        $nav = [
            [
                'id'    => 'home',
                'label' => 'Startseite',
                'icon'  => 'home',
                'route' => 'home',
            ],
        ];

        if ($this->hasAny($permissions, ['employee.bistro'])) {
            $nav[] = [
                'id'       => 'bistro',
                'label'    => 'Bistro',
                'icon'     => 'restaurant',
                'children' => [
                    ['id' => 'bistro.orders',   'label' => 'Bestellungen', 'route' => 'bistro/orders'],
                    ['id' => 'bistro.daily',    'label' => 'Tagesabschluss', 'route' => 'bistro/daily'],
                    ['id' => 'bistro.delivery', 'label' => 'Wareneingang', 'route' => 'bistro/delivery'],
                ],
            ];
        }

        if ($this->hasAny($permissions, ['employee.shop'])) {
            $nav[] = [
                'id'       => 'shop',
                'label'    => 'Shop',
                'icon'     => 'storefront',
                'children' => [
                    ['id' => 'shop.cashier',  'label' => 'Kassenabschluss', 'route' => 'shop/cashier'],
                    ['id' => 'shop.delivery', 'label' => 'Wareneingang', 'route' => 'shop/delivery'],
                    ['id' => 'shop.inventory','label' => 'Inventur', 'route' => 'shop/inventory'],
                ],
            ];
        }

        if ($this->hasAny($permissions, ['employee.station', 'partner.stations'])) {
            $nav[] = [
                'id'       => 'station',
                'label'    => 'Tankstelle',
                'icon'     => 'local_gas_station',
                'children' => [
                    ['id' => 'station.shift',    'label' => 'Schichtprotokoll', 'route' => 'station/shift'],
                    ['id' => 'station.tank',     'label' => 'Tankkontrolle', 'route' => 'station/tank'],
                    ['id' => 'station.incident', 'label' => 'Störung melden', 'route' => 'station/incident'],
                ],
            ];
        }

        if ($this->hasAny($permissions, ['partner.employees', 'partner.keys'])) {
            $nav[] = [
                'id'       => 'management',
                'label'    => 'Verwaltung',
                'icon'     => 'manage_accounts',
                'children' => [
                    $this->hasAny($permissions, ['partner.employees'])
                        ? ['id' => 'mgmt.employees', 'label' => 'Mitarbeiter', 'route' => 'management/employees']
                        : null,
                    $this->hasAny($permissions, ['partner.keys'])
                        ? ['id' => 'mgmt.keys', 'label' => 'Schlüsselübergabe', 'route' => 'management/keys']
                        : null,
                ],
            ];
            // null-Einträge entfernen
            $nav[count($nav) - 1]['children'] = array_filter(
                $nav[count($nav) - 1]['children']
            );
        }

        $nav[] = [
            'id'    => 'misc',
            'label' => 'Sonstiges',
            'icon'  => 'more_horiz',
            'children' => [
                ['id' => 'misc.settings', 'label' => 'Einstellungen', 'route' => 'settings'],
                ['id' => 'misc.logout',   'label' => 'Abmelden',      'route' => 'logout'],
            ],
        ];

        return $nav;
    }

    // ─── Kacheln (Dashboard) ─────────────────────────────────────────────────

    private function buildTiles(array $permissions): array
    {
        $tiles = [
            ['id' => 'shift_start', 'label' => 'Schicht starten', 'icon' => 'play_circle', 'color' => 'primary', 'route' => 'station/shift'],
        ];

        if ($this->hasAny($permissions, ['partner.keys'])) {
            $tiles[] = ['id' => 'key_handover', 'label' => 'Schlüssel', 'icon' => 'key', 'color' => 'secondary', 'route' => 'management/keys'];
        }

        if ($this->hasAny($permissions, ['employee.station', 'partner.stations'])) {
            $tiles[] = ['id' => 'tank_check', 'label' => 'Tankkontrolle', 'icon' => 'local_gas_station', 'color' => 'blue', 'route' => 'station/tank'];
            $tiles[] = ['id' => 'incident',   'label' => 'Störung melden', 'icon' => 'warning', 'color' => 'error', 'route' => 'station/incident'];
        }

        if ($this->hasAny($permissions, ['employee.shop'])) {
            $tiles[] = ['id' => 'cashier', 'label' => 'Kassenabschluss', 'icon' => 'point_of_sale', 'color' => 'green', 'route' => 'shop/cashier'];
        }

        if ($this->hasAny($permissions, ['employee.bistro'])) {
            $tiles[] = ['id' => 'bistro_orders', 'label' => 'Bestellungen', 'icon' => 'restaurant', 'color' => 'orange', 'route' => 'bistro/orders'];
        }

        return $tiles;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function hasAny(array $userPermissions, array $prefixes): bool
    {
        foreach ($userPermissions as $p) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($p, $prefix)) return true;
            }
        }
        return false;
    }

    private function resolveDevice(Request $request): ?MdeDevice
    {
        $tokenName = $request->user()?->tokens()->latest()->value('name');
        if (! $tokenName) return null;
        return MdeDevice::where('token_name', $tokenName)->with('station')->first();
    }
}
