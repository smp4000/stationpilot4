<?php

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

// ── CRUD-Events ───────────────────────────────────────────────────────────

test('created event erstellt automatisch audit log', function () {
    $before = AuditLog::where('action', 'created')->where('auditable_type', Tenant::class)->count();

    Tenant::factory()->create();

    expect(AuditLog::where('action', 'created')->where('auditable_type', Tenant::class)->count())
        ->toBe($before + 1);
});

test('updated event erstellt audit log mit old und new values', function () {
    $tenant = Tenant::factory()->create(['name' => 'Alt GmbH']);
    $before = AuditLog::where('action', 'updated')->count();

    $tenant->update(['name' => 'Neu GmbH']);

    $log = AuditLog::where('action', 'updated')->latest()->first();

    expect(AuditLog::where('action', 'updated')->count())->toBe($before + 1)
        ->and($log->old_values['name'])->toBe('Alt GmbH')
        ->and($log->new_values['name'])->toBe('Neu GmbH');
});

test('deleted event erstellt audit log mit action deleted', function () {
    $tenant = Tenant::factory()->create();
    $before = AuditLog::where('action', 'deleted')->count();

    $tenant->delete();

    expect(AuditLog::where('action', 'deleted')->count())->toBe($before + 1);
});

// ── auditExclude ──────────────────────────────────────────────────────────

test('felder in auditExclude werden nicht in audit log gespeichert', function () {
    // User hat last_login_at in auditExclude; update mit email + last_login_at
    // → nur email darf im audit log erscheinen
    $user = User::factory()->create(['email' => 'alt@example.de']);
    $before = AuditLog::where('action', 'updated')->count();

    $user->update([
        'email'         => 'neu@example.de',
        'last_login_at' => now(),
    ]);

    expect(AuditLog::where('action', 'updated')->count())->toBe($before + 1);

    $log = AuditLog::where('action', 'updated')->latest()->first();

    expect($log->new_values)->toHaveKey('email')
        ->and($log->new_values)->not->toHaveKey('last_login_at');
});

// ── updateQuietly ─────────────────────────────────────────────────────────

test('updateQuietly triggert keinen audit log', function () {
    $user   = User::factory()->create();
    $before = AuditLog::count();

    $user->updateQuietly(['last_login_at' => now(), 'last_login_ip' => '127.0.0.1']);

    expect(AuditLog::count())->toBe($before);
});

// ── Hilfsmethoden ─────────────────────────────────────────────────────────

test('getAuditExclude gibt konfiguriertes array zurück', function () {
    $user   = User::factory()->make();
    $tenant = Tenant::factory()->make();

    expect($user->getAuditExclude())->toContain('last_login_at')
        ->and($tenant->getAuditExclude())->toContain('updated_at');
});

test('auditKey gibt string-id des models zurück', function () {
    $tenant = Tenant::factory()->create();

    expect($tenant->auditKey())->toBe((string) $tenant->id);
});
