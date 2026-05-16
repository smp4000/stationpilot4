<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Grundstruktur ─────────────────────────────────────────────────────────

test('tenant wird mit ulid erstellt', function () {
    $tenant = Tenant::factory()->create();

    expect($tenant->ulid)->not->toBeNull()
        ->and(strlen($tenant->ulid))->toBe(26);
});

test('tenant hat id als bigint primary key', function () {
    $tenant = Tenant::factory()->create();

    expect($tenant->id)->toBeInt();
});

// ── Slug-Generierung ──────────────────────────────────────────────────────

test('generateSlug erstellt slug aus namen', function () {
    $slug = Tenant::generateSlug('Welle Tankstellen GmbH');

    expect($slug)->toBe('welle-tankstellen-gmbh');
});

test('generateSlug macht slug eindeutig bei kollision', function () {
    Tenant::factory()->create(['slug' => 'welle-tankstellen']);

    $slug = Tenant::generateSlug('Welle Tankstellen');

    expect($slug)->toBe('welle-tankstellen-1');
});

test('generateSlug zählt weiter bei mehreren kollisionen', function () {
    Tenant::factory()->create(['slug' => 'aral-station']);
    Tenant::factory()->create(['slug' => 'aral-station-1']);

    $slug = Tenant::generateSlug('Aral Station');

    expect($slug)->toBe('aral-station-2');
});

// ── Abo-Status ────────────────────────────────────────────────────────────

test('isOnTrial ist true wenn trial aktiv', function () {
    $tenant = Tenant::factory()->create([
        'subscription_status' => 'trial',
        'trial_ends_at'       => now()->addDays(7),
    ]);

    expect($tenant->isOnTrial())->toBeTrue();
});

test('isOnTrial ist false wenn trial abgelaufen', function () {
    $tenant = Tenant::factory()->create([
        'subscription_status' => 'trial',
        'trial_ends_at'       => now()->subDay(),
    ]);

    expect($tenant->isOnTrial())->toBeFalse();
});

test('isTrialExpired ist true wenn trial abgelaufen', function () {
    $tenant = Tenant::factory()->create([
        'subscription_status' => 'trial',
        'trial_ends_at'       => now()->subDay(),
    ]);

    expect($tenant->isTrialExpired())->toBeTrue();
});

test('hasActiveSubscription ist true bei aktivem abo', function () {
    $tenant = Tenant::factory()->create([
        'subscription_status' => 'active',
    ]);

    expect($tenant->hasActiveSubscription())->toBeTrue();
});

test('hasAccess ist true bei laufendem trial', function () {
    $tenant = Tenant::factory()->create([
        'subscription_status' => 'trial',
        'trial_ends_at'       => now()->addDays(7),
        'is_active'           => true,
    ]);

    expect($tenant->hasAccess())->toBeTrue();
});

test('hasAccess ist true bei aktivem abo', function () {
    $tenant = Tenant::factory()->create([
        'subscription_status' => 'active',
        'is_active'           => true,
    ]);

    expect($tenant->hasAccess())->toBeTrue();
});

test('hasAccess ist false bei abgelaufenem trial', function () {
    $tenant = Tenant::factory()->create([
        'subscription_status' => 'trial',
        'trial_ends_at'       => now()->subDay(),
        'is_active'           => true,
    ]);

    expect($tenant->hasAccess())->toBeFalse();
});

test('hasAccess ist false wenn deaktiviert', function () {
    $tenant = Tenant::factory()->create([
        'subscription_status' => 'active',
        'is_active'           => false,
    ]);

    expect($tenant->hasAccess())->toBeFalse();
});

test('hasAccess ist false bei read_only', function () {
    $tenant = Tenant::factory()->create([
        'subscription_status' => 'read_only',
        'is_active'           => true,
    ]);

    expect($tenant->hasAccess())->toBeFalse();
});

test('isPastDue ist true bei überfälliger zahlung', function () {
    $tenant = Tenant::factory()->create([
        'subscription_status' => 'past_due',
    ]);

    expect($tenant->isPastDue())->toBeTrue();
});

test('isArchived ist true bei archivierten mandanten', function () {
    $tenant = Tenant::factory()->create([
        'subscription_status' => 'archived',
    ]);

    expect($tenant->isArchived())->toBeTrue();
});

// ── SoftDelete ────────────────────────────────────────────────────────────

test('tenant kann soft-deleted werden', function () {
    $tenant = Tenant::factory()->create();
    $tenant->delete();

    expect(Tenant::find($tenant->id))->toBeNull()
        ->and(Tenant::withTrashed()->find($tenant->id))->not->toBeNull();
});
