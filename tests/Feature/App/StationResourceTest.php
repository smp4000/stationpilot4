<?php

use App\Models\AuditLog;
use App\Models\Station;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->partner  = User::where('email', 'partner@stationpilot.de')->first();
    $this->employee = User::where('email', 'mitarbeiter@stationpilot.de')->first();
    $this->tenant   = Tenant::where('slug', 'demo-tankstellen')->first();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
});

// ── Zugriffskontrolle ─────────────────────────────────────────────────────

test('partner kann station-liste öffnen', function () {
    $this->actingAs($this->partner);
    session(['tenant_id' => $this->tenant->id]);

    $this->get('/app/stations')->assertSuccessful();
});

test('mitarbeiter kann station-liste öffnen', function () {
    $this->actingAs($this->employee);
    session(['tenant_id' => $this->tenant->id]);

    $this->get('/app/stations')->assertSuccessful();
});

// ── Seeder ────────────────────────────────────────────────────────────────

test('seeder erstellt 2 teststationen', function () {
    session(['tenant_id' => $this->tenant->id]);

    expect(Station::count())->toBe(2);
});

test('seeder-stationen haben gültige koordinaten', function () {
    session(['tenant_id' => $this->tenant->id]);

    Station::all()->each(fn($s) => expect($s->hasCoordinates())->toBeTrue());
});

// ── DSGVO Encryption ──────────────────────────────────────────────────────

test('station mit iban speichert verschlüsselt in der db', function () {
    session(['tenant_id' => $this->tenant->id]);

    $station = Station::factory()->create([
        'tenant_id' => $this->tenant->id,
        'iban'      => 'DE89370400440532013000',
    ]);

    $raw = DB::table('stations')->where('id', $station->id)->value('iban');

    expect($raw)->not->toContain('DE89');
});

// ── Multi-Tenancy Isolation ───────────────────────────────────────────────

test('partner sieht keine stationen des anderen mandanten', function () {
    $tenant2 = Tenant::where('slug', 'schmidt-tankstellen')->first();

    session(['tenant_id' => $tenant2->id]);
    Station::factory()->create(['tenant_id' => $tenant2->id, 'name' => 'Fremde Station']);

    session(['tenant_id' => $this->tenant->id]);

    expect(Station::where('name', 'Fremde Station')->count())->toBe(0);
});

// ── Audit-Log ─────────────────────────────────────────────────────────────

test('station anlegen schreibt audit-log mit action created', function () {
    $this->actingAs($this->partner);
    session(['tenant_id' => $this->tenant->id]);

    Station::factory()->create(['tenant_id' => $this->tenant->id]);

    expect(
        AuditLog::where('action', 'created')
            ->where('auditable_type', Station::class)
            ->count()
    )->toBeGreaterThan(0);
});
