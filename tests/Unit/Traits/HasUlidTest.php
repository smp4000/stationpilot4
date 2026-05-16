<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── HasUlid wird via User-Model getestet ──────────────────────────────────

test('ulid wird beim erstellen automatisch generiert', function () {
    $user = User::factory()->create();

    expect($user->ulid)->not->toBeNull();
});

test('ulid ist genau 26 zeichen lang', function () {
    $user = User::factory()->create();

    expect(strlen($user->ulid))->toBe(26);
});

test('ulid enthält keine bindestriche', function () {
    $user = User::factory()->create();

    expect($user->ulid)->not->toContain('-');
});

test('ulid ist eindeutig zwischen zwei usern', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    expect($user1->ulid)->not->toBe($user2->ulid);
});

test('route key name ist ulid', function () {
    $user = User::factory()->make();

    expect($user->getRouteKeyName())->toBe('ulid');
});

test('findByUlid gibt den richtigen user zurück', function () {
    $user = User::factory()->create();

    $found = User::findByUlid($user->ulid);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($user->id);
});

test('findByUlid gibt null zurück wenn ulid nicht existiert', function () {
    $result = User::findByUlid('NICHTVORHANDEN00000000000');

    expect($result)->toBeNull();
});

test('findByUlidOrFail wirft exception wenn nicht gefunden', function () {
    User::findByUlidOrFail('NICHTVORHANDEN00000000000');
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('manuell gesetztes ulid wird nicht überschrieben', function () {
    $user = User::factory()->make(['ulid' => '01ABCDEFGHJKMNPQRSTVWXYZ12']);
    $user->save();

    expect($user->ulid)->toBe('01ABCDEFGHJKMNPQRSTVWXYZ12');
});
