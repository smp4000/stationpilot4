<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('station_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('gas_stations')->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('comp_station_id')->nullable(); // nullable FK to another gas_station
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('zip', 10)->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->decimal('distance_km', 5, 1)->nullable();
            $table->bigInteger('osm_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['station_id', 'distance_km']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_competitors');
    }
};
