<?php

use App\Models\Tenant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TestUserSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)
        ->setPermissionsTeamId(RolesAndPermissionsSeeder::GLOBAL_TEAM_ID);
});

// ── Permissions existieren ────────────────────────────────────────────────

test('alle admin permissions sind in der datenbank', function () {
    foreach (RolesAndPermissionsSeeder::getAdminPermissions() as $perm) {
        expect(Permission::where('name', $perm)->exists())
            ->toBeTrue("Permission '{$perm}' fehlt");
    }
});

test('alle partner permissions sind in der datenbank', function () {
    foreach (RolesAndPermissionsSeeder::getPartnerPermissions() as $perm) {
        expect(Permission::where('name', $perm)->exists())
            ->toBeTrue("Permission '{$perm}' fehlt");
    }
});

// ── Super Admin Rollen existieren ─────────────────────────────────────────

test('alle drei super admin rollen existieren', function () {
    expect(Role::where('name', 'super_admin_level_1')->exists())->toBeTrue()
        ->and(Role::where('name', 'super_admin_level_2')->exists())->toBeTrue()
        ->and(Role::where('name', 'super_admin_level_3')->exists())->toBeTrue();
});

// ── Kumulative Permissions (Level 1 ⊂ Level 2 ⊂ Level 3) ────────────────

test('level 2 enthält alle permissions von level 1', function () {
    $level1 = Role::findByName('super_admin_level_1', 'web');
    $level2 = Role::findByName('super_admin_level_2', 'web');

    $level1Perms = $level1->permissions->pluck('name')->toArray();
    $level2Perms = $level2->permissions->pluck('name')->toArray();

    foreach ($level1Perms as $perm) {
        expect(in_array($perm, $level2Perms))
            ->toBeTrue("Level 2 fehlt Permission: {$perm}");
    }
});

test('level 3 enthält alle permissions von level 2', function () {
    $level2 = Role::findByName('super_admin_level_2', 'web');
    $level3 = Role::findByName('super_admin_level_3', 'web');

    $level2Perms = $level2->permissions->pluck('name')->toArray();
    $level3Perms = $level3->permissions->pluck('name')->toArray();

    foreach ($level2Perms as $perm) {
        expect(in_array($perm, $level3Perms))
            ->toBeTrue("Level 3 fehlt Permission: {$perm}");
    }
});

test('level 3 hat alle admin permissions', function () {
    $level3 = Role::findByName('super_admin_level_3', 'web');
    $allPerms = RolesAndPermissionsSeeder::getAdminPermissions();
    $rolePerms = $level3->permissions->pluck('name')->toArray();

    foreach ($allPerms as $perm) {
        expect(in_array($perm, $rolePerms))
            ->toBeTrue("Level 3 fehlt Permission: {$perm}");
    }
});

test('level 1 kann NICHT löschen oder archivieren', function () {
    $level1 = Role::findByName('super_admin_level_1', 'web');
    $perms  = $level1->permissions->pluck('name');

    expect($perms->contains('admin.tenants.delete'))->toBeFalse()
        ->and($perms->contains('admin.tenants.archive'))->toBeFalse()
        ->and($perms->contains('admin.users.delete'))->toBeFalse();
});

// ── createTenantRoles ───────────────────────────────────────────────���─────

test('createTenantRoles erstellt genau 5 rollen', function () {
    $this->seed(TestUserSeeder::class);
    $tenant = Tenant::where('slug', 'demo-tankstellen')->first();

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

    RolesAndPermissionsSeeder::createTenantRoles($tenant->id);

    $roles = ['partner_owner', 'partner_manager', 'station_manager', 'employee', 'tax_advisor'];

    foreach ($roles as $role) {
        expect(Role::where('name', $role)->exists())
            ->toBeTrue("Rolle '{$role}' fehlt");
    }
});

test('partner_owner hat mehr permissions als station_manager', function () {
    $this->seed(TestUserSeeder::class);
    $tenant = Tenant::where('slug', 'demo-tankstellen')->first();

    RolesAndPermissionsSeeder::createTenantRoles($tenant->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

    $ownerCount   = Role::findByName('partner_owner', 'web')->permissions->count();
    $stationCount = Role::findByName('station_manager', 'web')->permissions->count();

    expect($ownerCount)->toBeGreaterThan($stationCount);
});

test('partner_manager hat KEIN billing.manage', function () {
    $this->seed(TestUserSeeder::class);
    $tenant = Tenant::where('slug', 'demo-tankstellen')->first();

    RolesAndPermissionsSeeder::createTenantRoles($tenant->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

    $manager = Role::findByName('partner_manager', 'web');
    $perms   = $manager->permissions->pluck('name');

    expect($perms->contains('partner.billing.manage'))->toBeFalse()
        ->and($perms->contains('partner.billing.view'))->toBeFalse();
});

test('tax_advisor hat nur report permissions', function () {
    $this->seed(TestUserSeeder::class);
    $tenant = Tenant::where('slug', 'demo-tankstellen')->first();

    RolesAndPermissionsSeeder::createTenantRoles($tenant->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

    $taxAdvisor = Role::findByName('tax_advisor', 'web');
    $perms      = $taxAdvisor->permissions->pluck('name');

    expect($perms->contains('partner.reports.view'))->toBeTrue()
        ->and($perms->contains('partner.reports.export'))->toBeTrue()
        ->and($perms->contains('partner.stations.edit'))->toBeFalse()
        ->and($perms->contains('partner.employees.create'))->toBeFalse()
        ->and($perms->contains('partner.billing.manage'))->toBeFalse();
});

test('seeder ist idempotent', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $permCount = Permission::count();
    expect($permCount)->toBeGreaterThan(0);
});
