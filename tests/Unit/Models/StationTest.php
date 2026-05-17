<?php

use App\Models\Station;
use App\Models\Tenant;
use Database\Seeders\TestUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TestUserSeeder::class);
    $this->tenant = Tenant::where('slug', 'demo-tankstellen')->first();
    session(['tenant_id' => $this->tenant->id]);
});

// ── ULID & Multi-Tenancy ──────────────────────────────────────────────────

test('station wird mit ulid erstellt', function () {
    $station = Station::factory()->create(['tenant_id' => $this->tenant->id]);

    expect($station->ulid)->not->toBeNull()
        ->and(strlen($station->ulid))->toBe(26);
});

test('tenant_id wird automatisch aus session gesetzt', function () {
    $station = Station::create(['name' => 'Auto-Test', 'brand' => 'Aral']);

    expect($station->tenant_id)->toBe($this->tenant->id);
});

test('mandant a sieht keine stationen von mandant b', function () {
    $tenant2 = Tenant::where('slug', 'schmidt-tankstellen')->first();

    Station::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Station A']);
    Station::factory()->create(['tenant_id' => $tenant2->id, 'name' => 'Station B']);

    session(['tenant_id' => $this->tenant->id]);

    expect(Station::count())->toBe(1)
        ->and(Station::first()->name)->toBe('Station A');
});

// ── DSGVO: Encryption ─────────────────────────────────────────────────────

test('iban wird verschlüsselt in der datenbank gespeichert', function () {
    $station = Station::factory()->create([
        'tenant_id' => $this->tenant->id,
        'iban'      => 'DE89370400440532013000',
    ]);

    expect($station->iban)->toBe('DE89370400440532013000');

    $raw = DB::table('stations')->where('id', $station->id)->value('iban');

    expect($raw)->not->toContain('DE89');
});

test('bic wird verschlüsselt gespeichert', function () {
    $station = Station::factory()->create([
        'tenant_id' => $this->tenant->id,
        'bic'       => 'COBADEFFXXX',
    ]);

    $raw = DB::table('stations')->where('id', $station->id)->value('bic');

    expect($station->bic)->toBe('COBADEFFXXX')
        ->and($raw)->not->toBe('COBADEFFXXX');
});

// ── Accessors ─────────────────────────────────────────────────────────────

test('full_address gibt korrekte adresse zurück', function () {
    $station = Station::factory()->create([
        'tenant_id'    => $this->tenant->id,
        'street'       => 'Musterstraße',
        'house_number' => '1',
        'zip'          => '36100',
        'city'         => 'Petersberg',
    ]);

    expect($station->full_address)->toBe('Musterstraße 1, 36100 Petersberg');
});

test('hasCoordinates gibt true zurück wenn lat und lng gesetzt', function () {
    $station = Station::factory()->create([
        'tenant_id' => $this->tenant->id,
        'lat'       => 50.5596,
        'lng'       => 9.6827,
    ]);

    expect($station->hasCoordinates())->toBeTrue();
});

test('hasCoordinates gibt false zurück wenn keine koordinaten', function () {
    $station = Station::factory()->create([
        'tenant_id' => $this->tenant->id,
        'lat'       => null,
        'lng'       => null,
    ]);

    expect($station->hasCoordinates())->toBeFalse();
});

// ── Öffnungszeiten ────────────────────────────────────────────────────────

test('defaultOpeningHours enthält alle 7 wochentage', function () {
    $hours = Station::defaultOpeningHours();

    expect($hours)->toHaveKeys(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
});

test('opening_hours werden als array gespeichert und geladen', function () {
    $station = Station::factory()->create([
        'tenant_id'     => $this->tenant->id,
        'opening_hours' => Station::defaultOpeningHours(),
    ]);

    expect($station->fresh()->opening_hours)->toBeArray()
        ->and($station->fresh()->opening_hours['monday']['open'])->toBe('06:00');
});

// ── Soft Delete ───────────────────────────────────────────────────────────

test('station kann soft-deleted werden', function () {
    $station = Station::factory()->create(['tenant_id' => $this->tenant->id]);
    $station->delete();

    expect(Station::find($station->id))->toBeNull()
        ->and(Station::withTrashed()->find($station->id))->not->toBeNull();
});

// ── withoutTenantScope ────────────────────────────────────────────────────

test('withoutTenantScope zeigt alle stationen mandantenübergreifend', function () {
    $tenant2 = Tenant::where('slug', 'schmidt-tankstellen')->first();

    Station::factory()->create(['tenant_id' => $this->tenant->id]);
    Station::factory()->create(['tenant_id' => $tenant2->id]);

    expect(Station::count())->toBe(1)
        ->and(Station::withoutTenantScope()->count())->toBe(2);
});
