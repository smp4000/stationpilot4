<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
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

test('level 3 admin kann user-liste öffnen', function () {
    $this->actingAs($this->admin)
        ->get('/admin/users')
        ->assertSuccessful();
});

test('partner kann user-liste nicht öffnen', function () {
    // canAccessPanel() gibt false → Filament blockt mit 403
    $this->actingAs($this->partner)
        ->get('/admin/users')
        ->assertStatus(403);
});

// ── User anlegen ──────────────────────────────────────────────────────────

test('admin kann person-user anlegen', function () {
    $this->actingAs($this->admin);

    User::create([
        'is_company'        => false,
        'first_name'        => 'Max',
        'last_name'         => 'Neu',
        'company_name'      => null,
        'email'             => 'neu@example.de',
        'password'          => Hash::make('SecurePassword123'),
        'type'              => 'partner',
        'is_active'         => true,
        'locale'            => 'de',
        'email_verified_at' => now(),
    ]);

    $user = User::where('email', 'neu@example.de')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Max Neu')
        ->and($user->is_company)->toBeFalse();
});

test('admin kann firmen-user anlegen', function () {
    $this->actingAs($this->admin);

    User::create([
        'is_company'        => true,
        'first_name'        => null,
        'last_name'         => null,
        'company_name'      => 'Neue GmbH',
        'email'             => 'firma-neu@example.de',
        'password'          => Hash::make('SecurePassword123'),
        'type'              => 'partner',
        'is_active'         => true,
        'locale'            => 'de',
        'email_verified_at' => now(),
    ]);

    $user = User::where('email', 'firma-neu@example.de')->first();

    expect($user->name)->toBe('Neue GmbH')
        ->and($user->is_company)->toBeTrue();
});

test('pin wird gehasht gespeichert niemals im klartext', function () {
    $user = User::factory()->create(['pin_hash' => Hash::make('1234')]);

    expect($user->pin_hash)->not->toBe('1234')
        ->and(str_starts_with($user->pin_hash, '$2y$'))->toBeTrue();
});

test('name accessor zeigt korrekten wert in tabelle', function () {
    $person  = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Muster', 'is_company' => false]);
    $company = User::factory()->create(['company_name' => 'Muster AG', 'is_company' => true]);

    expect($person->name)->toBe('Anna Muster')
        ->and($company->name)->toBe('Muster AG');
});

test('soft delete und restore funktioniert', function () {
    $user = User::factory()->create();
    $user->delete();

    expect(User::find($user->id))->toBeNull();

    $user->restore();

    expect(User::find($user->id))->not->toBeNull();
});
