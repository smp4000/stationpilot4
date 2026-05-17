<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use App\Listeners\LogFailedLogin;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

// ── Login ─────────────────────────────────────────────────────────────────

test('erfolglogin erstellt audit log mit action login', function () {
    $user   = User::factory()->create(['tenant_id' => null]);
    $before = AuditLog::where('action', 'login')->count();

    event(new Login('web', $user, false));

    expect(AuditLog::where('action', 'login')->count())->toBe($before + 1);
});

test('erfolglogin setzt session tenant_id für partner', function () {
    $tenant = \App\Models\Tenant::factory()->create();
    $user   = User::factory()->create(['type' => 'partner', 'tenant_id' => $tenant->id]);

    event(new Login('web', $user, false));

    expect(session('tenant_id'))->toBe($tenant->id);
});

test('erfolglogin aktualisiert last_login_at', function () {
    $user = User::factory()->create(['last_login_at' => null]);

    event(new Login('web', $user, false));

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

test('erfolglogin aktualisiert last_login_ip', function () {
    $user = User::factory()->create(['last_login_ip' => null]);

    event(new Login('web', $user, false));

    expect($user->fresh()->last_login_ip)->not->toBeNull();
});

// ── Logout ────────────────────────────────────────────────────────────────

test('logout erstellt audit log mit action logout', function () {
    $user   = User::factory()->create(['tenant_id' => null]);
    $before = AuditLog::where('action', 'logout')->count();

    event(new Logout('web', $user));

    expect(AuditLog::where('action', 'logout')->count())->toBe($before + 1);
});

// ── Failed Login ──────────────────────────────────────────────────────────

test('fehllogin erstellt audit log mit action login_failed', function () {
    $before = AuditLog::where('action', 'login_failed')->count();

    event(new Failed('web', null, ['email' => 'falsch@example.de', 'password' => 'geheim']));

    expect(AuditLog::where('action', 'login_failed')->count())->toBe($before + 1);
});

test('fehllogin speichert niemals das passwort im audit log', function () {
    event(new Failed('web', null, ['email' => 'x@y.de', 'password' => 'supergeheim']));

    $log = AuditLog::where('action', 'login_failed')->latest()->first();

    expect($log->new_values)->not->toHaveKey('password')
        ->and($log->new_values['email'])->toBe('x@y.de');
});
