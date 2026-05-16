<?php
use App\Models\Tenant;
use App\Scopes\TenantScope;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TestUserSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Models\TestItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::create('test_items', function (Blueprint $table) {
        $table->id();
        $table->char('ulid', 26)->unique();
        $table->unsignedBigInteger('tenant_id')->nullable()->index();
        $table->string('name');
        $table->timestamps();
    });

    $this->seed(TestUserSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->tenant1 = Tenant::where('slug', 'demo-tankstellen')->first();
    $this->tenant2 = Tenant::where('slug', 'schmidt-tankstellen')->first();
});

afterEach(function () {
    Schema::dropIfExists('test_items');
});

// ── TenantScope wird automatisch angewendet ───────────────────────────────

test('model mit BelongsToTenant hat TenantScope', function () {
    $scopes = (new TestItem())->getGlobalScopes();
    expect($scopes)->toHaveKey(TenantScope::class);
});

// ── Datenisolation zwischen Mandanten ────────────────────────────────────

test('mandant 1 sieht nur seine eigenen datensätze', function () {
    TestItem::withoutTenantScope()->create([
        'tenant_id' => $this->tenant1->id,
        'name'      => 'Item von Mandant 1',
    ]);
    TestItem::withoutTenantScope()->create([
        'tenant_id' => $this->tenant2->id,
        'name'      => 'Item von Mandant 2',
    ]);

    session(['tenant_id' => $this->tenant1->id]);

    $items = TestItem::all();
    expect($items)->toHaveCount(1)
        ->and($items->first()->name)->toBe('Item von Mandant 1');
});

test('mandant 2 sieht keine daten von mandant 1', function () {
    TestItem::withoutTenantScope()->create([
        'tenant_id' => $this->tenant1->id,
        'name'      => 'Geheime Daten Mandant 1',
    ]);
    TestItem::withoutTenantScope()->create([
        'tenant_id' => $this->tenant2->id,
        'name'      => 'Daten Mandant 2',
    ]);

    session(['tenant_id' => $this->tenant2->id]);

    $items = TestItem::all();
    expect($items)->toHaveCount(1)
        ->and($items->first()->name)->toBe('Daten Mandant 2')
        ->and($items->pluck('name'))->not->toContain('Geheime Daten Mandant 1');
});

test('super admin sieht ohne filter alle datensätze', function () {
    TestItem::withoutTenantScope()->create(['tenant_id' => $this->tenant1->id, 'name' => 'T1']);
    TestItem::withoutTenantScope()->create(['tenant_id' => $this->tenant2->id, 'name' => 'T2']);

    session()->forget('tenant_id');

    $items = TestItem::all();
    expect($items)->toHaveCount(2);
});

// ── tenant_id wird beim Erstellen automatisch gesetzt ────────────────────

test('tenant_id wird beim erstellen aus der session gesetzt', function () {
    session(['tenant_id' => $this->tenant1->id]);

    $item = TestItem::create(['name' => 'Automatisch zugewiesen']);
    expect($item->tenant_id)->toBe($this->tenant1->id);
});

test('manuell gesetzte tenant_id wird nicht überschrieben', function () {
    session(['tenant_id' => $this->tenant1->id]);

    $item = TestItem::create([
        'tenant_id' => $this->tenant2->id,
        'name'      => 'Explizit gesetzt',
    ]);

    expect($item->tenant_id)->toBe($this->tenant2->id);
});

// ── withoutTenantScope ────────────────────────────────────────────────────

test('withoutTenantScope zeigt alle mandanten', function () {
    TestItem::withoutTenantScope()->create(['tenant_id' => $this->tenant1->id, 'name' => 'T1']);
    TestItem::withoutTenantScope()->create(['tenant_id' => $this->tenant2->id, 'name' => 'T2']);

    session(['tenant_id' => $this->tenant1->id]);

    expect(TestItem::count())->toBe(1);
    expect(TestItem::withoutTenantScope()->count())->toBe(2);
});

// ── Beziehung tenant() ────────────────────────────────────────────────────

test('tenant() beziehung gibt den richtigen mandanten zurück', function () {
    session(['tenant_id' => $this->tenant1->id]);

    $item = TestItem::create(['name' => 'Mit Beziehung']);

    expect($item->tenant)->not->toBeNull()
        ->and($item->tenant->id)->toBe($this->tenant1->id)
        ->and($item->tenant->name)->toBe('Demo Tankstellen');
});
