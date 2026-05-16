<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Naming: Person ────────────────────────────────────────────────────────

test('name accessor gibt vor- und nachname zurück für person', function () {
    $user = User::factory()->make([
        'is_company'   => false,
        'first_name'   => 'Christian',
        'last_name'    => 'Welle',
        'company_name' => null,
    ]);

    expect($user->name)->toBe('Christian Welle');
});

test('name accessor funktioniert ohne vorname', function () {
    $user = User::factory()->make([
        'is_company'   => false,
        'first_name'   => null,
        'last_name'    => 'Welle',
        'company_name' => null,
    ]);

    expect($user->name)->toBe('Welle');
});

// ── Naming: Firma ─────────────────────────────────────────────────────────

test('name accessor gibt company_name zurück für firma', function () {
    $user = User::factory()->make([
        'is_company'   => true,
        'first_name'   => null,
        'last_name'    => null,
        'company_name' => 'Welle Tankstellen GmbH',
    ]);

    expect($user->name)->toBe('Welle Tankstellen GmbH');
});

test('company_name hat vorrang wenn is_company gesetzt', function () {
    $user = User::factory()->make([
        'is_company'   => true,
        'first_name'   => 'Christian',
        'last_name'    => 'Welle',
        'company_name' => 'Welle Tankstellen GmbH',
    ]);

    expect($user->name)->toBe('Welle Tankstellen GmbH');
});

test('formelle anrede für firma korrekt', function () {
    $user = User::factory()->make([
        'is_company'   => true,
        'company_name' => 'Welle Tankstellen GmbH',
    ]);

    expect($user->formal_greeting)->toBe('Sehr geehrte Damen und Herren');
});

test('formelle anrede für person korrekt', function () {
    $user = User::factory()->make([
        'is_company' => false,
        'first_name' => 'Christian',
        'last_name'  => 'Welle',
    ]);

    expect($user->formal_greeting)->toBe('Guten Tag Christian Welle');
});

// ── Typ-Prüfer ────────────────────────────────────────────────────────────

test('isSuperAdmin gibt true zurück für super_admin', function () {
    $user = User::factory()->make(['type' => 'super_admin']);

    expect($user->isSuperAdmin())->toBeTrue()
        ->and($user->isPartner())->toBeFalse()
        ->and($user->isEmployee())->toBeFalse();
});

test('isPartner gibt true zurück für partner', function () {
    $user = User::factory()->make(['type' => 'partner']);

    expect($user->isPartner())->toBeTrue()
        ->and($user->isSuperAdmin())->toBeFalse();
});

test('isEmployee gibt true zurück für employee', function () {
    $user = User::factory()->make(['type' => 'employee']);

    expect($user->isEmployee())->toBeTrue();
});

test('isTaxAdvisor gibt true zurück für tax_advisor', function () {
    $user = User::factory()->make(['type' => 'tax_advisor']);

    expect($user->isTaxAdvisor())->toBeTrue();
});

// ── Panel-Zugriff ─────────────────────────────────────────────────────────

test('super_admin darf admin panel öffnen', function () {
    $user = User::factory()->make([
        'type'      => 'super_admin',
        'is_active' => true,
    ]);

    $panel = Mockery::mock(\Filament\Panel::class);
    $panel->shouldReceive('getId')->andReturn('admin');

    expect($user->canAccessPanel($panel))->toBeTrue();
});

test('super_admin darf app panel nicht öffnen', function () {
    $user = User::factory()->make([
        'type'      => 'super_admin',
        'is_active' => true,
    ]);

    $panel = Mockery::mock(\Filament\Panel::class);
    $panel->shouldReceive('getId')->andReturn('app');

    expect($user->canAccessPanel($panel))->toBeFalse();
});

test('partner darf app panel öffnen', function () {
    $user = User::factory()->make([
        'type'      => 'partner',
        'is_active' => true,
    ]);

    $panel = Mockery::mock(\Filament\Panel::class);
    $panel->shouldReceive('getId')->andReturn('app');

    expect($user->canAccessPanel($panel))->toBeTrue();
});

test('partner darf admin panel nicht öffnen', function () {
    $user = User::factory()->make([
        'type'      => 'partner',
        'is_active' => true,
    ]);

    $panel = Mockery::mock(\Filament\Panel::class);
    $panel->shouldReceive('getId')->andReturn('admin');

    expect($user->canAccessPanel($panel))->toBeFalse();
});

test('deaktivierter user darf kein panel öffnen', function () {
    $user = User::factory()->make([
        'type'      => 'partner',
        'is_active' => false,
    ]);

    $adminPanel = Mockery::mock(\Filament\Panel::class);
    $adminPanel->shouldReceive('getId')->andReturn('admin');

    $appPanel = Mockery::mock(\Filament\Panel::class);
    $appPanel->shouldReceive('getId')->andReturn('app');

    expect($user->canAccessPanel($adminPanel))->toBeFalse()
        ->and($user->canAccessPanel($appPanel))->toBeFalse();
});

// ── hasAccess ─────────────────────────────────────────────────────────────

test('super_admin hat zugang wenn aktiv', function () {
    $user = User::factory()->make([
        'type'      => 'super_admin',
        'is_active' => true,
        'tenant_id' => null,
    ]);

    expect($user->hasAccess())->toBeTrue();
});

test('super_admin hat keinen zugang wenn deaktiviert', function () {
    $user = User::factory()->make([
        'type'      => 'super_admin',
        'is_active' => false,
    ]);

    expect($user->hasAccess())->toBeFalse();
});

// ── SoftDelete & scan_code ────────────────────────────────────────────────

test('user kann soft-deleted werden', function () {
    $user = User::factory()->create();
    $user->delete();

    expect(User::find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id))->not->toBeNull();
});

test('scan_code ist unique', function () {
    User::factory()->create(['scan_code' => 'ABC123']);

    expect(fn() => User::factory()->create(['scan_code' => 'ABC123']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
