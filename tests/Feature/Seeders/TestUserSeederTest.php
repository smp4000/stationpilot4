<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\TestUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Seeder ausführen vor jedem Test ──────────────────────────────────────
beforeEach(function () {
    $this->seed(TestUserSeeder::class);
});

// ── Super Admin ───────────────────────────────────────────────────────────
test('super admin wird erstellt', function () {
    $user = User::where('email', 'admin@stationpilot.de')->first();

    expect($user)->not->toBeNull()
        ->and($user->type)->toBe('super_admin')
        ->and($user->tenant_id)->toBeNull()
        ->and($user->is_active)->toBeTrue()
        ->and($user->is_company)->toBeFalse();
});

test('super admin name ist korrekt', function () {
    $user = User::where('email', 'admin@stationpilot.de')->first();

    expect($user->name)->toBe('Super Admin');
});

test('super admin hat ulid gesetzt bekommen', function () {
    $user = User::where('email', 'admin@stationpilot.de')->first();

    expect($user->ulid)->not->toBeNull()
        ->and(strlen($user->ulid))->toBe(26);
});

// ── Partner (Person) ──────────────────────────────────────────────────────
test('partner wird erstellt', function () {
    $user = User::where('email', 'partner@stationpilot.de')->first();

    expect($user)->not->toBeNull()
        ->and($user->type)->toBe('partner')
        ->and($user->is_company)->toBeFalse()
        ->and($user->first_name)->toBe('Max')
        ->and($user->last_name)->toBe('Mustermann');
});

test('partner hat einen mandanten', function () {
    $user = User::where('email', 'partner@stationpilot.de')->first();

    expect($user->tenant_id)->not->toBeNull()
        ->and($user->tenant)->not->toBeNull()
        ->and($user->tenant->name)->toBe('Demo Tankstellen');
});

test('partner ist inhaber des mandanten', function () {
    $user = User::where('email', 'partner@stationpilot.de')->first();

    expect($user->tenant->owner_id)->toBe($user->id);
});

test('demo mandant hat 14 tage trial', function () {
    $tenant = Tenant::where('slug', 'demo-tankstellen')->first();

    expect($tenant->subscription_status)->toBe('trial')
        ->and($tenant->trial_ends_at)->not->toBeNull()
        ->and($tenant->isOnTrial())->toBeTrue()
        ->and($tenant->hasAccess())->toBeTrue();
});

// ── Partner (Firma) ───────────────────────────────────────────────────────
test('firmen-partner wird korrekt erstellt', function () {
    $user = User::where('email', 'firma@stationpilot.de')->first();

    expect($user)->not->toBeNull()
        ->and($user->type)->toBe('partner')
        ->and($user->is_company)->toBeTrue()
        ->and($user->company_name)->toBe('Mustermann Tankstellen GmbH')
        ->and($user->first_name)->toBeNull()
        ->and($user->last_name)->toBeNull();
});

test('firmen-partner name zeigt company_name', function () {
    $user = User::where('email', 'firma@stationpilot.de')->first();

    expect($user->name)->toBe('Mustermann Tankstellen GmbH');
});

test('firmen-mandant hat aktives abo', function () {
    $tenant = Tenant::where('slug', 'mustermann-tankstellen-gmbh')->first();

    expect($tenant->subscription_status)->toBe('active')
        ->and($tenant->hasActiveSubscription())->toBeTrue();
});

// ── Mitarbeiter ───────────────────────────────────────────────────────────
test('stationsleiter wird erstellt', function () {
    $user = User::where('email', 'stationsleiter@stationpilot.de')->first();

    expect($user)->not->toBeNull()
        ->and($user->type)->toBe('employee')
        ->and($user->first_name)->toBe('Lara Sophie')
        ->and($user->tenant->name)->toBe('Demo Tankstellen');
});

test('stationsleiter hat nfc scan_code', function () {
    $user = User::where('email', 'stationsleiter@stationpilot.de')->first();

    expect($user->scan_code)->toBe('DEMO-SL-001');
});

test('stationsleiter hat pin gesetzt', function () {
    $user = User::where('email', 'stationsleiter@stationpilot.de')->first();

    expect($user->pin_hash)->not->toBeNull();
});

test('mitarbeiter wird erstellt', function () {
    $user = User::where('email', 'mitarbeiter@stationpilot.de')->first();

    expect($user)->not->toBeNull()
        ->and($user->type)->toBe('employee')
        ->and($user->scan_code)->toBe('DEMO-MA-001')
        ->and($user->tenant->name)->toBe('Demo Tankstellen');
});

test('zweiter mitarbeiter hat keinen nfc scan_code', function () {
    $user = User::where('email', 'mitarbeiter2@stationpilot.de')->first();

    expect($user->scan_code)->toBeNull()
        ->and($user->pin_hash)->not->toBeNull();
});

// ── Steuerberater ─────────────────────────────────────────────────────────
test('steuerberater wird erstellt', function () {
    $user = User::where('email', 'steuerberater@stationpilot.de')->first();

    expect($user)->not->toBeNull()
        ->and($user->type)->toBe('tax_advisor')
        ->and($user->is_company)->toBeTrue()
        ->and($user->company_name)->toBe('Steuerberatung Muster & Partner')
        ->and($user->tenant->name)->toBe('Demo Tankstellen');
});

// ── Multi-Tenancy Isolation ───────────────────────────────────────────────
test('zweiter partner hat eigenen mandanten', function () {
    $partner1 = User::where('email', 'partner@stationpilot.de')->first();
    $partner2 = User::where('email', 'partner2@stationpilot.de')->first();

    expect($partner2->tenant_id)->not->toBeNull()
        ->and($partner2->tenant_id)->not->toBe($partner1->tenant_id)
        ->and($partner2->tenant->name)->toBe('Schmidt Tankstellen');
});

test('mitarbeiter des demo-mandanten sind nicht im schmidt-mandanten', function () {
    $schmidtTenant = Tenant::where('slug', 'schmidt-tankstellen')->first();

    $demoEmployees = User::where('email', 'mitarbeiter@stationpilot.de')
        ->orWhere('email', 'mitarbeiter2@stationpilot.de')
        ->get();

    foreach ($demoEmployees as $employee) {
        expect($employee->tenant_id)->not->toBe($schmidtTenant->id);
    }
});

test('es gibt genau 3 mandanten nach dem seeder', function () {
    // Demo Tankstellen + Mustermann GmbH + Schmidt Tankstellen
    expect(Tenant::count())->toBe(3);
});

test('es gibt genau 8 user nach dem seeder', function () {
    expect(User::count())->toBe(8);
});

// ── Idempotenz ────────────────────────────────────────────────────────────
test('seeder kann mehrmals ausgeführt werden ohne fehler', function () {
    $this->seed(TestUserSeeder::class);

    expect(User::count())->toBe(8)
        ->and(Tenant::count())->toBe(3);
});

// ── Passwörter ────────────────────────────────────────────────────────────
test('alle passwörter sind gehasht nicht im klartext', function () {
    User::all()->each(function ($user) {
        if ($user->password) {
            expect($user->password)->not->toBe('password')
                ->and(str_starts_with($user->password, '$2y$'))->toBeTrue();
        }
    });
});

// ── Panel-Zugriff Übersicht ───────────────────────────────────────────────
test('nur super admin darf admin panel öffnen', function () {
    $adminPanel = Mockery::mock(\Filament\Panel::class);
    $adminPanel->shouldReceive('getId')->andReturn('admin');

    User::all()->each(function ($user) use ($adminPanel) {
        $canAccess = $user->canAccessPanel($adminPanel);

        if ($user->type === 'super_admin') {
            expect($canAccess)->toBeTrue("Super Admin soll /admin öffnen dürfen");
        } else {
            expect($canAccess)->toBeFalse("{$user->email} soll /admin NICHT öffnen dürfen");
        }
    });
});

test('alle nicht-admin user dürfen app panel öffnen', function () {
    $appPanel = Mockery::mock(\Filament\Panel::class);
    $appPanel->shouldReceive('getId')->andReturn('app');

    User::all()->each(function ($user) use ($appPanel) {
        $canAccess = $user->canAccessPanel($appPanel);

        if ($user->type === 'super_admin') {
            expect($canAccess)->toBeFalse("Super Admin soll /app NICHT öffnen dürfen");
        } else {
            expect($canAccess)->toBeTrue("{$user->email} soll /app öffnen dürfen");
        }
    });
});
