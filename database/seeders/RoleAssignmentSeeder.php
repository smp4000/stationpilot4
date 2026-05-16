<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\PermissionRegistrar;

/**
 * Weist den Testkonten aus TestUserSeeder ihre Rollen zu.
 * Muss NACH RolesAndPermissionsSeeder ausgeführt werden.
 */
class RoleAssignmentSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Super Admin → Level 3 (globales Team) ─────────────────���──────
        $admin = User::where('email', 'admin@stationpilot.de')->firstOrFail();

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(RolesAndPermissionsSeeder::GLOBAL_TEAM_ID);

        $admin->assignRole('super_admin_level_3');

        // ─�� Demo Mandant Rollen ───────────────────────────────────────────
        $demoTenant = Tenant::where('slug', 'demo-tankstellen')->firstOrFail();

        // Tenant-Rollen anlegen falls noch nicht vorhanden
        RolesAndPermissionsSeeder::createTenantRoles($demoTenant->id);

        // Unter Demo-Tenant Team arbeiten
        app(PermissionRegistrar::class)->setPermissionsTeamId($demoTenant->id);

        User::where('email', 'partner@stationpilot.de')->firstOrFail()
            ->assignRole('partner_owner');

        User::where('email', 'stationsleiter@stationpilot.de')->firstOrFail()
            ->assignRole('station_manager');

        User::where('email', 'mitarbeiter@stationpilot.de')->firstOrFail()
            ->assignRole('employee');

        User::where('email', 'mitarbeiter2@stationpilot.de')->firstOrFail()
            ->assignRole('employee');

        User::where('email', 'steuerberater@stationpilot.de')->firstOrFail()
            ->assignRole('tax_advisor');

        // ── Mustermann Tankstellen GmbH ───────────────────────────────────
        $firmaTenant = Tenant::where('slug', 'mustermann-tankstellen-gmbh')->firstOrFail();

        RolesAndPermissionsSeeder::createTenantRoles($firmaTenant->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($firmaTenant->id);

        User::where('email', 'firma@stationpilot.de')->firstOrFail()
            ->assignRole('partner_owner');

        // ── Schmidt Tankstellen (Isolations-Test) ─────────────────────────
        $schmidtTenant = Tenant::where('slug', 'schmidt-tankstellen')->firstOrFail();

        RolesAndPermissionsSeeder::createTenantRoles($schmidtTenant->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($schmidtTenant->id);

        User::where('email', 'partner2@stationpilot.de')->firstOrFail()
            ->assignRole('partner_owner');

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Ausgabe ───────────────────────────────────────────────────────
        $this->command->info('✅ Rollen zugewiesen:');
        $this->command->table(
            ['E-Mail', 'Rolle', 'Mandant'],
            [
                ['admin@stationpilot.de',          'super_admin_level_3', 'Global (0)'],
                ['partner@stationpilot.de',         'partner_owner',       'Demo Tankstellen'],
                ['stationsleiter@stationpilot.de',  'station_manager',     'Demo Tankstellen'],
                ['mitarbeiter@stationpilot.de',     'employee',            'Demo Tankstellen'],
                ['mitarbeiter2@stationpilot.de',    'employee',            'Demo Tankstellen'],
                ['steuerberater@stationpilot.de',   'tax_advisor',         'Demo Tankstellen'],
                ['firma@stationpilot.de',           'partner_owner',       'Mustermann GmbH'],
                ['partner2@stationpilot.de',        'partner_owner',       'Schmidt Tankstellen'],
            ]
        );
    }
}
