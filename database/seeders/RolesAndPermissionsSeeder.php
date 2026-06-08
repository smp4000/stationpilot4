<?php

namespace Database\Seeders;

use App\Support\RolePermissions;
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
     * Erstellt alle Standard-Rollen für einen Mandanten – getrennt nach Bereich:
     *   - Web-Rollen   (scope "web")     steuern das Filament /app-Panel    (partner.*)
     *   - GoPilot-Rollen (scope "gopilot") steuern die GoPilot Android-App  (employee.*)
     *
     * Idempotent: kann jederzeit erneut aufgerufen werden (z.B. roles:sync-tenants),
     * um bestehende Mandanten auf den aktuellen Stand zu bringen.
     *
     * @param  int  $tenantId  BIGINT ID des Mandanten
     */
    public static function createTenantRoles(int $tenantId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Web-Rollen (partner.*) ───────────────────────────────────────────
        foreach (RolePermissions::webStandardRoles() as $name => $perms) {
            $role = Role::findOrCreate($name, 'web');
            $role->scope = RolePermissions::SCOPE_WEB;
            $role->save();
            $role->syncPermissions($perms);
        }

        // ── GoPilot-Rollen (employee.*) ──────────────────────────────────────
        foreach (RolePermissions::gopilotStandardRoles() as $name => $perms) {
            $role = Role::findOrCreate($name, 'web');
            $role->scope = RolePermissions::SCOPE_GOPILOT;
            $role->save();
            $role->syncPermissions($perms);
        }

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
     * Alle Mandanten-Permissions (Web + GoPilot) aus dem zentralen Katalog.
     * Wird in run() global registriert. Die Aufteilung nach Bereich erfolgt
     * über App\Support\RolePermissions::webPermissions() / gopilotPermissions().
     */
    public static function getPartnerPermissions(): array
    {
        return RolePermissions::all();
    }
}
