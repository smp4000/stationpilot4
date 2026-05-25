<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_station', function (Blueprint $table) {
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->constrained('gas_stations')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->primary(['employee_id', 'station_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_station');
    }
};
