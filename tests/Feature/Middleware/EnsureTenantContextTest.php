<?php
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

// ── Super Admin kann app panel nicht zugreifen ───────────────────────────
// canAccessPanel() gibt false zurück → Filament blockt mit 403, bevor
// EnsureTenantContext läuft. Super-Admins gehören ausschließlich ins /admin Panel.

test('super admin kann das app panel nicht zugreifen', function () {
    $admin = User::where('email', 'admin@stationpilot.de')->first();

    $this->actingAs($admin)
        ->get('/app')
        ->assertStatus(403);
});

// ── Partner hat korrekten Tenant-Kontext ─────────────────────────────────

test('partner session erhält tenant_id nach login', function () {
    $partner = User::where('email', 'partner@stationpilot.de')->first();

    $this->actingAs($partner)
        ->get('/app');

    expect(session('tenant_id'))->toBe($partner->tenant_id);
});

test('session wird repariert wenn tenant_id nicht stimmt', function () {
    $partner = User::where('email', 'partner@stationpilot.de')->first();

    session(['tenant_id' => 99999]);

    $this->actingAs($partner)
        ->get('/app');

    expect(session('tenant_id'))->toBe($partner->tenant_id);
});

// ── User ohne Mandant bekommt 403 ────────────────────────────────────────

test('user ohne tenant_id bekommt 403', function () {
    $user = User::factory()->create([
        'type'      => 'employee',
        'tenant_id' => null,
    ]);

    $this->actingAs($user)
        ->get('/app')
        ->assertStatus(403);
});
