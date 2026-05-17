<?php

use App\Filament\Admin\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->admin   = User::where('email', 'admin@stationpilot.de')->first();
    $this->partner = User::where('email', 'partner@stationpilot.de')->first();

    app(PermissionRegistrar::class)
        ->setPermissionsTeamId(RolesAndPermissionsSeeder::GLOBAL_TEAM_ID);
});

// ── Read-Only: kein Create / Edit / Delete ────────────────────────────────

test('audit log resource ist read-only — kein create möglich', function () {
    expect(AuditLogResource::canCreate())->toBeFalse();
});

test('audit log resource ist read-only — kein edit möglich', function () {
    $log = AuditLog::create(['action' => 'login']);

    expect(AuditLogResource::canEdit($log))->toBeFalse();
});

test('audit log resource ist read-only — kein delete möglich', function () {
    $log = AuditLog::create(['action' => 'login']);

    expect(AuditLogResource::canDelete($log))->toBeFalse();
});

// ── Zugriffskontrolle ─────────────────────────────────────────────────────

test('level 3 admin kann audit-log-liste öffnen', function () {
    $this->actingAs($this->admin)
        ->get('/admin/audit-logs')
        ->assertSuccessful();
});

test('partner kann audit-logs nicht öffnen', function () {
    // canAccessPanel() gibt false → Filament blockt mit 403
    $this->actingAs($this->partner)
        ->get('/admin/audit-logs')
        ->assertStatus(403);
});

// ── Audit-Log Model ───────────────────────────────────────────────────────

test('audit_logs tabelle existiert', function () {
    expect(Schema::hasTable('audit_logs'))->toBeTrue();
});

test('auditable_type_short gibt kurzen klassennamen zurück', function () {
    $log = new AuditLog(['auditable_type' => 'App\\Models\\Tenant']);

    expect($log->auditable_type_short)->toBe('Tenant');
});

test('auditable_type_short gibt strich zurück wenn null', function () {
    $log = new AuditLog(['auditable_type' => null]);

    expect($log->auditable_type_short)->toBe('—');
});
