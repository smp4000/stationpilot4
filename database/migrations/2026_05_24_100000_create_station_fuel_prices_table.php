<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('station_fuel_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('gas_stations')->cascadeOnDelete();
            $table->decimal('e5', 5, 3)->nullable()->comment('Super E5 €/L');
            $table->decimal('e10', 5, 3)->nullable()->comment('Super E10 €/L');
            $table->decimal('diesel', 5, 3)->nullable()->comment('Diesel €/L');
            $table->decimal('lpg', 5, 3)->nullable()->comment('LPG €/L');
            $table->string('source', 20)->default('manual')->comment('manual|scraper|api|import');
            $table->timestamp('recorded_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['station_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_fuel_prices');
    }
};
