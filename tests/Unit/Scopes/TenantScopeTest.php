<?php
use App\Scopes\TenantScope;
use App\Models\Tenant;
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
    $this->tenant = Tenant::where('slug', 'demo-tankstellen')->first();
});

afterEach(fn() => Schema::dropIfExists('test_items'));

test('scope ist eine Illuminate Scope Implementierung', function () {
    $scope = new TenantScope();
    expect($scope)->toBeInstanceOf(\Illuminate\Database\Eloquent\Scope::class);
});

test('scope fügt where clause hinzu wenn tenant_id in session', function () {
    session(['tenant_id' => $this->tenant->id]);

    $query = TestItem::query();
    $sql   = $query->toSql();

    expect($sql)->toContain('tenant_id');
});

test('scope fügt KEINE where clause hinzu ohne session', function () {
    session()->forget('tenant_id');

    $query = TestItem::query();
    $sql   = $query->toSql();

    expect($sql)->not->toContain('where');
});

test('count gibt 0 zurück wenn keine items für mandanten', function () {
    session(['tenant_id' => $this->tenant->id]);

    expect(TestItem::count())->toBe(0);
});
