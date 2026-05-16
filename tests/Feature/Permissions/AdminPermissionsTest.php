<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

// ── Super Admin Level 3 ───────────────────────────────────────────────────

test('super admin level 3 hat alle admin permissions', function () {
    $admin = User::where('email', 'admin@stationpilot.de')->first();

    app(PermissionRegistrar::class)
        ->setPermissionsTeamId(RolesAndPermissionsSeeder::GLOBAL_TEAM_ID);

    foreach (RolesAndPermissionsSeeder::getAdminPermissions() as $perm) {
        expect($admin->can($perm))
            ->toBeTrue("Super Admin kann nicht: {$perm}");
    }
});

test('super admin kann mandanten löschen', function () {
    $admin = User::where('email', 'admin@stationpilot.de')->first();

    app(PermissionRegistrar::class)
        ->setPermissionsTeamId(RolesAndPermissionsSeeder::GLOBAL_TEAM_ID);

    expect($admin->can('admin.tenants.delete'))->toBeTrue()
        ->and($admin->can('admin.tenants.archive'))->toBeTrue();
});

test('super admin kann sensible audit logs sehen', function () {
    $admin = User::where('email', 'admin@stationpilot.de')->first();

    app(PermissionRegistrar::class)
        ->setPermissionsTeamId(RolesAndPermissionsSeeder::GLOBAL_TEAM_ID);

    expect($admin->can('admin.audit-log.view-details'))->toBeTrue();
});

test('super admin hat keine partner permissions', function () {
    $admin = User::where('email', 'admin@stationpilot.de')->first();

    app(PermissionRegistrar::class)
        ->setPermissionsTeamId(RolesAndPermissionsSeeder::GLOBAL_TEAM_ID);

    expect($admin->can('partner.stations.edit'))->toBeFalse()
        ->and($admin->can('partner.employees.create'))->toBeFalse()
        ->and($admin->can('partner.billing.manage'))->toBeFalse();
});
