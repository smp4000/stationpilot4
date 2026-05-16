<?php
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

// ── Aktiver Trial → normaler Zugriff ─────────────────────────────────────

test('partner mit aktivem trial hat normalen zugriff', function () {
    $partner = User::where('email', 'partner@stationpilot.de')->first();
    $tenant  = $partner->tenant;

    expect($tenant->isOnTrial())->toBeTrue();

    $this->actingAs($partner)
        ->get('/app')
        ->assertSuccessful();
});

// ── Archiviert → Logout ───────────────────────────────────────────────────

test('partner mit archiviertem mandanten wird ausgeloggt', function () {
    $partner = User::where('email', 'partner@stationpilot.de')->first();
    $partner->tenant->update([
        'subscription_status' => 'archived',
        'is_active'           => false,
    ]);

    $this->actingAs($partner)
        ->get('/app')
        ->assertRedirect('/app/login');

    $this->assertGuest();
});

// ── Deaktivierter Mandant → Logout ───────────────────────────────────────

test('partner mit deaktiviertem mandanten wird ausgeloggt', function () {
    $partner = User::where('email', 'partner@stationpilot.de')->first();
    $partner->tenant->update(['is_active' => false]);

    $this->actingAs($partner)
        ->get('/app')
        ->assertRedirect('/app/login');

    $this->assertGuest();
});

// ── Abgelaufener Trial → Warnung in Session ───────────────────────────────

test('abgelaufener trial setzt warnung in session', function () {
    $partner = User::where('email', 'partner@stationpilot.de')->first();
    $partner->tenant->update([
        'subscription_status' => 'trial',
        'trial_ends_at'       => now()->subDay(),
    ]);

    $this->actingAs($partner)
        ->get('/app');

    expect(session('tenant_warning'))->toBe('trial_expired');
});

// ── Past Due → Warnung in Session ────────────────────────────────────────

test('past_due setzt zahlungswarnung in session', function () {
    $partner = User::where('email', 'partner@stationpilot.de')->first();
    $partner->tenant->update(['subscription_status' => 'past_due']);

    $this->actingAs($partner)
        ->get('/app');

    expect(session('tenant_warning'))->toBe('past_due');
});

// ── Read-Only → Warnung in Session ───────────────────────────────────────

test('read_only setzt readonly warnung in session', function () {
    $partner = User::where('email', 'partner@stationpilot.de')->first();
    $partner->tenant->update(['subscription_status' => 'read_only']);

    $this->actingAs($partner)
        ->get('/app');

    expect(session('tenant_warning'))->toBe('read_only');
});

// ── Super Admin ist ausgenommen ───────────────────────────────────────────
// canAccessPanel() gibt false zurück → Filament blockt mit 403.
// CheckTenantStatus löst keinen Logout aus — Super-Admin bleibt eingeloggt.

test('super admin wird von check tenant status nicht ausgeloggt', function () {
    $admin = User::where('email', 'admin@stationpilot.de')->first();

    $this->actingAs($admin)
        ->get('/app')
        ->assertStatus(403);

    // Kein Logout durch CheckTenantStatus
    $this->assertAuthenticated();
});

// ── Aktives Abo → keine Warnung ──────────────────────────────────────────

test('aktives abo setzt keine warnung', function () {
    $partner = User::where('email', 'firma@stationpilot.de')->first();

    expect($partner->tenant->hasActiveSubscription())->toBeTrue();

    $this->actingAs($partner)
        ->get('/app');

    expect(session('tenant_warning'))->toBeNull();
});
