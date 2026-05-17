<?php

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->service = app(AuditService::class);
});

// ── Sanitize ──────────────────────────────────────────────────────────────

test('sanitize entfernt password felder', function () {
    $result = $this->service->sanitize(['email' => 'x@y.de', 'password' => 'secret123']);

    expect($result)->not->toHaveKey('password')
        ->and($result['email'])->toBe('x@y.de');
});

test('sanitize entfernt pin_hash', function () {
    $result = $this->service->sanitize(['pin_hash' => 'geheimerpin', 'name' => 'Max']);

    expect($result)->not->toHaveKey('pin_hash')
        ->and($result)->toHaveKey('name');
});

test('sanitize entfernt two_factor_secret', function () {
    $result = $this->service->sanitize(['two_factor_secret' => 'TOTP_SECRET', 'email' => 'x@y.de']);

    expect($result)->not->toHaveKey('two_factor_secret');
});

test('sanitize maskiert iban mit sternchen', function () {
    $result = $this->service->sanitize(['iban' => 'DE89370400440532013000']);

    expect($result['iban'])->toBe('***');
});

test('sanitize maskiert tax_id mit sternchen', function () {
    $result = $this->service->sanitize(['tax_id' => '12/345/67890']);

    expect($result['tax_id'])->toBe('***');
});

test('sanitize lässt normale felder unverändert durch', function () {
    $result = $this->service->sanitize(['name' => 'Max', 'email' => 'max@example.de', 'locale' => 'de']);

    expect($result)->toBe(['name' => 'Max', 'email' => 'max@example.de', 'locale' => 'de']);
});

// ── Safe ──────────────────────────────────────────────────────────────────

test('safe fängt exceptions und wirft nicht weiter', function () {
    expect(fn() => $this->service->safe(fn() => throw new \RuntimeException('boom')))
        ->not->toThrow(\RuntimeException::class);
});

// ── logCreate ─────────────────────────────────────────────────────────────

test('logCreate erstellt audit log mit action created', function () {
    $user   = User::factory()->create();
    $tenant = Tenant::factory()->create();

    $before = AuditLog::count();

    $this->service->logCreate($tenant);

    expect(AuditLog::count())->toBe($before + 1);

    $log = AuditLog::latest()->first();
    expect($log->action)->toBe('created')
        ->and($log->auditable_type)->toBe(Tenant::class);
});

test('logCreate speichert new_values als entschlüsselbares array', function () {
    $tenant = Tenant::factory()->create();

    $this->service->logCreate($tenant);

    $log = AuditLog::where('action', 'created')
        ->where('auditable_type', Tenant::class)
        ->latest()->first();

    expect($log->new_values)->toBeArray()
        ->and($log->new_values)->toHaveKey('name');
});

// ── logUpdate ─────────────────────────────────────────────────────────────

test('logUpdate speichert old und new values getrennt', function () {
    $tenant = Tenant::factory()->create(['name' => 'Alt GmbH']);

    $originals = $tenant->getOriginal();
    $tenant->name = 'Neu GmbH';
    $tenant->saveQuietly();

    $this->service->logUpdate($tenant, $originals);

    $log = AuditLog::where('action', 'updated')->latest()->first();

    expect($log->old_values)->toHaveKey('name')
        ->and($log->old_values['name'])->toBe('Alt GmbH')
        ->and($log->new_values['name'])->toBe('Neu GmbH');
});

test('logUpdate erstellt keinen eintrag wenn nur updated_at geändert', function () {
    $tenant = Tenant::factory()->create();

    $originals = $tenant->getOriginal();

    // Simuliere touch(): nur updated_at in getChanges()
    $tenant->updated_at = now()->addSecond();
    $tenant->syncChanges(['updated_at' => $tenant->updated_at]);

    $before = AuditLog::count();

    $this->service->logUpdate($tenant, $originals);

    expect(AuditLog::count())->toBe($before);
});

// ── logDelete ─────────────────────────────────────────────────────────────

test('logDelete speichert old_values des gelöschten models', function () {
    $tenant = Tenant::factory()->create(['name' => 'Zu löschen GmbH']);

    $before = AuditLog::count();

    $this->service->logDelete($tenant);

    expect(AuditLog::count())->toBe($before + 1);

    $log = AuditLog::where('action', 'deleted')->latest()->first();
    expect($log->old_values)->toHaveKey('name')
        ->and($log->old_values['name'])->toBe('Zu löschen GmbH');
});

// ── logFailedLogin ────────────────────────────────────────────────────────

test('logFailedLogin speichert email aber niemals passwort', function () {
    $before = AuditLog::count();

    $this->service->logFailedLogin('hacker@example.com');

    expect(AuditLog::count())->toBe($before + 1);

    $log = AuditLog::where('action', 'login_failed')->latest()->first();

    expect($log->new_values['email'])->toBe('hacker@example.com')
        ->and($log->new_values)->not->toHaveKey('password');
});
