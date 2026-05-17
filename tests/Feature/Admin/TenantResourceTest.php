<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->admin   = User::where('email', 'admin@stationpilot.de')->first();
    $this->partner = User::where('email', 'partner@stationpilot.de')->first();

    app(PermissionRegistrar::class)
        ->setPermissionsTeamId(RolesAndPermissionsSeeder::GLOBAL_TEAM_ID);
});

// ── Zugriffskontrolle ─────────────────────────────────────────────────────

test('level 3 admin kann tenant-liste öffnen', function () {
    $this->actingAs($this->admin)
        ->get('/admin/tenants')
        ->assertSuccessful();
});

test('partner kann admin panel nicht öffnen', function () {
    // canAccessPanel() gibt false → Filament blockt mit 403
    $this->actingAs($this->partner)
        ->get('/admin/tenants')
        ->assertStatus(403);
});

// ── Tenant anlegen ────────────────────────────────────────────────────────

test('level 3 admin kann neuen tenant anlegen', function () {
    $this->actingAs($this->admin);

    Tenant::create([
        'name'                => 'Test Tankstellen GmbH',
        'slug'                => 'test-tankstellen-gmbh',
        'billing_email'       => 'test@example.de',
        'subscription_status' => 'trial',
        'trial_ends_at'       => now()->addDays(14),
        'locale'              => 'de',
        'timezone'            => 'Europe/Berlin',
        'is_active'           => true,
        'billing_driver'      => 'manual_sepa',
    ]);

    expect(Tenant::withTrashed()->where('slug', 'test-tankstellen-gmbh')->exists())->toBeTrue();
});

test('tenant anlegen erstellt automatisch tenant-rollen', function () {
    $this->actingAs($this->admin);

    $tenant = Tenant::factory()->create();

    RolesAndPermissionsSeeder::createTenantRoles($tenant->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

    $roles = ['partner_owner', 'partner_manager', 'station_manager', 'employee', 'tax_advisor'];

    foreach ($roles as $role) {
        expect(Role::where('name', $role)->exists())
            ->toBeTrue("Rolle '{$role}' wurde nicht erstellt");
    }
});

test('level 3 admin kann tenant bearbeiten', function () {
    $tenant = Tenant::factory()->create();

    $this->actingAs($this->admin);

    $tenant->update(['name' => 'Geänderter Name']);

    expect($tenant->fresh()->name)->toBe('Geänderter Name');
});

test('level 3 admin kann tenant löschen (soft delete)', function () {
    $tenant = Tenant::factory()->create();

    $this->actingAs($this->admin);

    $tenant->delete();

    expect(Tenant::find($tenant->id))->toBeNull()
        ->and(Tenant::withTrashed()->find($tenant->id))->not->toBeNull();
});

test('slug wird automatisch aus name generiert', function () {
    $slug = Tenant::generateSlug('Neue Tankstelle GmbH');

    expect($slug)->toBe('neue-tankstelle-gmbh');
});

test('slug-kollision wird automatisch aufgelöst', function () {
    Tenant::factory()->create(['slug' => 'test-station']);

    $slug = Tenant::generateSlug('Test Station');

    expect($slug)->toBe('test-station-1');
});
