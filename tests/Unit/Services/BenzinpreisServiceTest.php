<?php

use App\Models\Station;
use App\Models\Tenant;
use App\Services\BenzinpreisService;
use Database\Seeders\TestUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TestUserSeeder::class);
    $this->tenant  = Tenant::where('slug', 'demo-tankstellen')->first();
    $this->service = app(BenzinpreisService::class);

    session(['tenant_id' => $this->tenant->id]);
});

// ── searchByZip ───────────────────────────────────────────────────────────

test('searchByZip gibt leeres array bei ungültiger plz zurück', function () {
    Http::fake();

    expect($this->service->searchByZip('abc'))->toBe([])
        ->and($this->service->searchByZip('1234'))->toBe([])
        ->and($this->service->searchByZip(''))->toBe([]);

    Http::assertNothingSent();
});

test('searchByZip gibt leeres array bei http fehler zurück', function () {
    Http::fake(['*' => Http::response('', 500)]);

    expect($this->service->searchByZip('36093'))->toBe([]);
});

test('searchByZip parsed html und gibt stationen zurück', function () {
    $html = <<<'HTML'
    <html><body>
        <div class="station-item">
            <a href="/station/aral-tankstelle-fulda/">
                <span class="station-name">Aral Tankstelle Fulda</span>
                <span class="station-address">Musterstraße 1, 36093 Fulda</span>
            </a>
        </div>
        <div class="station-item">
            <a href="/station/shell-fulda-mitte/">
                <span class="station-name">Shell Fulda Mitte</span>
                <span class="station-address">Hauptstraße 5, 36037 Fulda</span>
            </a>
        </div>
    </body></html>
    HTML;

    Http::fake(['*' => Http::response($html, 200)]);

    $results = $this->service->searchByZip('36093');

    expect($results)->toHaveCount(2)
        ->and($results[0]['slug'])->toBe('aral-tankstelle-fulda')
        ->and($results[0]['name'])->toBe('Aral Tankstelle Fulda')
        ->and($results[0]['brand'])->toBe('Aral')
        ->and($results[1]['slug'])->toBe('shell-fulda-mitte')
        ->and($results[1]['brand'])->toBe('Shell');
});

test('searchByZip dedupliziert stationen mit gleichem slug', function () {
    $html = <<<'HTML'
    <html><body>
        <a href="/station/aral-fulda/"><span class="station-name">Aral Fulda</span></a>
        <a href="/station/aral-fulda/"><span class="station-name">Aral Fulda Duplikat</span></a>
    </body></html>
    HTML;

    Http::fake(['*' => Http::response($html, 200)]);

    $results = $this->service->searchByZip('36093');

    expect($results)->toHaveCount(1);
});

// ── enrichStation ─────────────────────────────────────────────────────────

test('enrichStation gibt null zurück wenn kein slug gesetzt', function () {
    Http::fake();

    $station = Station::factory()->create([
        'tenant_id'       => $this->tenant->id,
        'benzinpreis_slug' => null,
    ]);

    expect($this->service->enrichStation($station))->toBeNull();

    Http::assertNothingSent();
});

test('enrichStation parsed detailseite korrekt', function () {
    $html = <<<'HTML'
    <html><body>
        <h1 class="station-name">Aral Tankstelle Künzell</h1>
        <address class="station-address">Künzeller Straße 101, 36093 Künzell</address>
    </body></html>
    HTML;

    Http::fake(['*' => Http::response($html, 200)]);

    $station = Station::factory()->create([
        'tenant_id'       => $this->tenant->id,
        'benzinpreis_slug' => 'aral-kuenzell-101',
    ]);

    $result = $this->service->enrichStation($station);

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('Aral Tankstelle Künzell')
        ->and($result['street'])->toBe('Künzeller Straße')
        ->and($result['house_number'])->toBe('101')
        ->and($result['zip'])->toBe('36093')
        ->and($result['city'])->toBe('Künzell')
        ->and($result['brand'])->toBe('Aral');
});

// ── guessBrandFromName ────────────────────────────────────────────────────

test('guessBrandFromName erkennt bekannte marken', function () {
    $method = new \ReflectionMethod(BenzinpreisService::class, 'guessBrandFromName');

    expect($method->invoke($this->service, 'Aral Tankstelle Fulda'))->toBe('Aral')
        ->and($method->invoke($this->service, 'Shell Station Hamburg'))->toBe('Shell')
        ->and($method->invoke($this->service, 'JET Tankstelle'))->toBe('Jet')
        ->and($method->invoke($this->service, 'Unbekannte Freie Station'))->toBeNull();
});

// ── parseAddress ──────────────────────────────────────────────────────────

test('parseAddress parst deutsche adressformate korrekt', function () {
    $method = new \ReflectionMethod(BenzinpreisService::class, 'parseAddress');

    $result = $method->invoke($this->service, 'Musterstraße 1, 36093 Fulda');

    expect($result['street'])->toBe('Musterstraße')
        ->and($result['house_number'])->toBe('1')
        ->and($result['zip'])->toBe('36093')
        ->and($result['city'])->toBe('Fulda');
});

// ── isHealthy ─────────────────────────────────────────────────────────────

test('isHealthy gibt true zurück wenn benzinpreis.de antwortet', function () {
    Http::fake(['*' => Http::response('<html></html>', 200)]);

    expect($this->service->isHealthy())->toBeTrue();
});

test('isHealthy gibt false zurück wenn benzinpreis.de nicht erreichbar', function () {
    Http::fake(['*' => Http::response('', 503)]);

    expect($this->service->isHealthy())->toBeFalse();
});
