<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->demoTenant = Tenant::where('slug', 'demo-tankstellen')->first();
});

// ── Partner Owner ─────────────────────────────────────────────────────────

test('partner owner hat alle partner permissions', function () {
    $partner = User::where('email', 'partner@stationpilot.de')->first();

    app(PermissionRegistrar::class)
        ->setPermissionsTeamId($this->demoTenant->id);

    foreach (RolesAndPermissionsSeeder::getPartnerPermissions() as $perm) {
        expect($partner->can($perm))
            ->toBeTrue("Partner Owner kann nicht: {$perm}");
    }
});

test('partner owner kann billing verwalten', function () {
    $partner = User::where('email', 'partner@stationpilot.de')->first();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->demoTenant->id);

    expect($partner->can('partner.billing.manage'))->toBeTrue()
        ->and($partner->can('partner.billing.view'))->toBeTrue();
});

// ── Station Manager ───────────────────────────────────────────────────────

test('station manager kann mitarbeiter einladen', function () {
    $stationMgr = User::where('email', 'stationsleiter@stationpilot.de')->first();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->demoTenant->id);

    expect($stationMgr->can('partner.employees.invite'))->toBeTrue()
        ->and($stationMgr->can('partner.dashboard.view'))->toBeTrue();
});

test('station manager kann KEINE stationen erstellen', function () {
    $stationMgr = User::where('email', 'stationsleiter@stationpilot.de')->first();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->demoTenant->id);

    expect($stationMgr->can('partner.stations.create'))->toBeFalse()
        ->and($stationMgr->can('partner.stations.delete'))->toBeFalse()
        ->and($stationMgr->can('partner.billing.manage'))->toBeFalse();
});

// ── Employee ──────────────────────────────────────────────────────────────

test('mitarbeiter hat minimale permissions', function () {
    $employee = User::where('email', 'mitarbeiter@stationpilot.de')->first();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->demoTenant->id);

    expect($employee->can('partner.dashboard.view'))->toBeTrue()
        ->and($employee->can('partner.stations.view'))->toBeTrue();
});

test('mitarbeiter kann keine stationen oder mitarbeiter verwalten', function () {
    $employee = User::where('email', 'mitarbeiter@stationpilot.de')->first();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->demoTenant->id);

    expect($employee->can('partner.stations.create'))->toBeFalse()
        ->and($employee->can('partner.employees.create'))->toBeFalse()
        ->and($employee->can('partner.billing.view'))->toBeFalse();
});

// ── Steuerberater ─────────────────────────────────────────────────────────

test('steuerberater kann berichte exportieren', function () {
    $taxAdvisor = User::where('email', 'steuerberater@stationpilot.de')->first();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->demoTenant->id);

    expect($taxAdvisor->can('partner.reports.view'))->toBeTrue()
        ->and($taxAdvisor->can('partner.reports.export'))->toBeTrue();
});

test('steuerberater kann keine stationen oder mitarbeiter sehen', function () {
    $taxAdvisor = User::where('email', 'steuerberater@stationpilot.de')->first();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->demoTenant->id);

    expect($taxAdvisor->can('partner.stations.list'))->toBeFalse()
        ->and($taxAdvisor->can('partner.employees.list'))->toBeFalse()
        ->and($taxAdvisor->can('partner.billing.manage'))->toBeFalse();
});

// ── Multi-Tenancy Isolation ───────────────────────────────────────────────

test('partner sieht keine permissions des anderen mandanten', function () {
    $partner1 = User::where('email', 'partner@stationpilot.de')->first();
    $tenant2  = Tenant::where('slug', 'schmidt-tankstellen')->first();

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant2->id);

    expect($partner1->can('partner.stations.edit'))->toBeFalse()
        ->and($partner1->can('partner.billing.manage'))->toBeFalse();
});

test('admin permissions funktionieren nicht im partner kontext', function () {
    $partner = User::where('email', 'partner@stationpilot.de')->first();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->demoTenant->id);

    expect($partner->can('admin.tenants.delete'))->toBeFalse()
        ->and($partner->can('admin.users.edit'))->toBeFalse();
});
