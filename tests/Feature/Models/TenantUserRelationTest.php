<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tenant hat einen owner', function () {
    $owner = User::factory()->create(['type' => 'partner']);
    $tenant = Tenant::factory()->create(['owner_id' => $owner->id]);

    expect($tenant->owner)->not->toBeNull()
        ->and($tenant->owner->id)->toBe($owner->id);
});

test('tenant hat viele users', function () {
    $tenant = Tenant::factory()->create();

    User::factory()->count(3)->create([
        'tenant_id' => $tenant->id,
        'type'      => 'employee',
    ]);

    expect($tenant->users)->toHaveCount(3);
});

test('user gehört zu einem tenant', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    expect($user->tenant)->not->toBeNull()
        ->and($user->tenant->id)->toBe($tenant->id);
});

test('super_admin hat keinen tenant', function () {
    $user = User::factory()->create([
        'type'      => 'super_admin',
        'tenant_id' => null,
    ]);

    expect($user->tenant)->toBeNull();
});

test('partner hat zugang wenn tenant trial aktiv', function () {
    $tenant = Tenant::factory()->create([
        'subscription_status' => 'trial',
        'trial_ends_at'       => now()->addDays(7),
        'is_active'           => true,
    ]);

    $partner = User::factory()->create([
        'type'      => 'partner',
        'tenant_id' => $tenant->id,
        'is_active' => true,
    ]);

    expect($partner->hasAccess())->toBeTrue();
});

test('partner hat keinen zugang wenn tenant deaktiviert', function () {
    $tenant = Tenant::factory()->create([
        'subscription_status' => 'active',
        'is_active'           => false,
    ]);

    $partner = User::factory()->create([
        'type'      => 'partner',
        'tenant_id' => $tenant->id,
        'is_active' => true,
    ]);

    expect($partner->hasAccess())->toBeFalse();
});

test('ownedTenant gibt den mandanten zurück der dem user gehört', function () {
    $owner = User::factory()->create(['type' => 'partner']);
    $tenant = Tenant::factory()->create(['owner_id' => $owner->id]);

    expect($owner->ownedTenant)->not->toBeNull()
        ->and($owner->ownedTenant->id)->toBe($tenant->id);
});
