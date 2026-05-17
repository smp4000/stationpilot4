<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Erstellt alle Rollen und Permissions.
 *
 * Architektur:
 * - GLOBAL_TEAM_ID = 0 (BIGINT): Super-Admin Rollen (kein Mandant)
 * - tenant_id als Team-ID: Mandanten-spezifische Rollen
 *
 * Super-Admin Stufen (kumulativ):
 * - Level 1: Nur schauen
 * - Level 2: Support (lesen + bearbeiten)
 * - Level 3: Vollzugriff (inkl. löschen + archivieren)
 */
class RolesAndPermissionsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * BIGINT 0 = Globales Super-Admin Team.
     * Super-Admins haben tenant_id = NULL, aber Spatie braucht einen Wert.
     * Konvention: 0 = global, niemals als echter Mandant verwendet.
     */
    public const GLOBAL_TEAM_ID = 0;

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Super-Admin Permissions (globales Team) ───────────────────────
        app(PermissionRegistrar::class)->setPermissionsTeamId(self::GLOBAL_TEAM_ID);

        $adminPerms = self::getAdminPermissions();

        foreach ($adminPerms as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Level 1 — Nur schauen (für externe Monitoring-Accounts)
        $level1 = Role::findOrCreate('super_admin_level_1', 'web');
        $level1->syncPermissions([
            'admin.dashboard.view',
            'admin.tenants.list',
            'admin.tenants.view-stats',
            'admin.system.view',
        ]);

        // Level 2 — Support (lesen + bearbeiten, kein löschen)
        $level2 = Role::findOrCreate('super_admin_level_2', 'web');
        $level2->syncPermissions([
            'admin.dashboard.view',
            'admin.tenants.list',
            'admin.tenants.view-stats',
            'admin.tenants.view',
            'admin.tenants.edit',
            'admin.system.view',
            'admin.users.list',
            'admin.users.view',
            'admin.audit-log.view',
        ]);

        // Level 3 — Vollzugriff (inkl. löschen, archivieren, sensible Logs)
        $level3 = Role::findOrCreate('super_admin_level_3', 'web');
        $level3->syncPermissions($adminPerms);

        // ── Partner Permissions global definieren ─────────────────────────
        // Rollen werden per Mandant erstellt (createTenantRoles)
        // Permissions selbst sind global (kein Team nötig für die Definition)
        app(PermissionRegistrar::class)->setPermissionsTeamId(self::GLOBAL_TEAM_ID);

        foreach (self::getPartnerPermissions() as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Ausgabe ───────────────────────────────────────────────────────
        $this->command->info('✅ Rollen und Permissions erstellt:');
        $this->command->table(
            ['Rolle', 'Permissions'],
            [
                ['super_admin_level_1', $level1->permissions->count()],
                ['super_admin_level_2', $level2->permissions->count()],
                ['super_admin_level_3', $level3->permissions->count()],
                ['Partner-Permissions', count(self::getPartnerPermissions()) . ' (global definiert)'],
            ]
        );
    }

    /**
     * Erstellt alle 5 Mandanten-Rollen für einen neuen Mandanten.
     * Wird bei Tenant-Erstellung aufgerufen.
     *
     * @param  int  $tenantId  BIGINT ID des Mandanten
     */
    public static function createTenantRoles(int $tenantId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $all = self::getPartnerPermissions();

        // Inhaber — Voller Zugriff auf alles
        $owner = Role::findOrCreate('partner_owner', 'web');
        $owner->syncPermissions($all);

        // Manager — Wie Owner, aber kein Billing
        $manager = Role::findOrCreate('partner_manager', 'web');
        $manager->syncPermissions(
            array_values(array_filter($all, fn($p) => ! str_starts_with($p, 'partner.billing')))
        );

        // Stationsleiter — Stationen + Mitarbeiter einladen + Berichte
        $stationMgr = Role::findOrCreate('station_manager', 'web');
        $stationMgr->syncPermissions([
            'partner.dashboard.view',
            'partner.stations.list',
            'partner.stations.view',
            'partner.employees.list',
            'partner.employees.view',
            'partner.employees.invite',
            'partner.reports.view',
        ]);

        // Mitarbeiter — Dashboard + Stationen ansehen (Liste + Detailansicht)
        $employee = Role::findOrCreate('employee', 'web');
        $employee->syncPermissions([
            'partner.dashboard.view',
            'partner.stations.list',
            'partner.stations.view',
        ]);

        // Steuerberater — Nur Lohnberichte lesen und exportieren
        $taxAdvisor = Role::findOrCreate('tax_advisor', 'web');
        $taxAdvisor->syncPermissions([
            'partner.dashboard.view',
            'partner.reports.view',
            'partner.reports.export',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Alle Super-Admin Permissions.
     */
    public static function getAdminPermissions(): array
    {
        return [
            // Level 1
            'admin.dashboard.view',
            'admin.tenants.list',
            'admin.tenants.view-stats',
            'admin.system.view',

            // Level 2
            'admin.tenants.view',
            'admin.tenants.edit',
            'admin.users.list',
            'admin.users.view',
            'admin.audit-log.view',

            // Level 3
            'admin.tenants.create',
            'admin.tenants.delete',
            'admin.tenants.archive',
            'admin.users.create',
            'admin.users.edit',
            'admin.users.delete',
            'admin.audit-log.view-details',
            'admin.system.edit',
        ];
    }

    /**
     * Alle Partner Permissions.
     */
    public static function getPartnerPermissions(): array
    {
        return [
            // Dashboard
            'partner.dashboard.view',

            // Stationen
            'partner.stations.list',
            'partner.stations.view',
            'partner.stations.create',
            'partner.stations.edit',
            'partner.stations.delete',

            // Personal
            'partner.employees.list',
            'partner.employees.view',
            'partner.employees.create',
            'partner.employees.edit',
            'partner.employees.invite',
            'partner.employees.approve',
            'partner.employees.terminate',

            // Billing
            'partner.billing.view',
            'partner.billing.manage',

            // Berichte
            'partner.reports.view',
            'partner.reports.export',

            // Einstellungen
            'partner.settings.view',
            'partner.settings.edit',
        ];
    }
}
