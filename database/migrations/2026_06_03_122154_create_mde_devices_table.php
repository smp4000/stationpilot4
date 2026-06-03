<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mde_devices', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('station_id')->constrained('gas_stations')->cascadeOnDelete();

            $table->string('device_name')->comment('z.B. "Kasse 1", "Lager MDE"');
            $table->string('device_model')->nullable()->comment('z.B. "Zebra TC52", "Netum Q700"');
            $table->string('android_id', 64)->nullable()->unique()->comment('Android Device ID');
            $table->string('token_name', 100)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->string('app_version', 20)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mde_devices');
    }
};
