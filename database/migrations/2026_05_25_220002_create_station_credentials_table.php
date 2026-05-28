<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('station_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->constrained('gas_stations')->cascadeOnDelete();
            $table->string('type'); // kasse, ec_cash, terminal, alarm, tresor, sonstiges
            $table->string('label');
            $table->string('username')->nullable();
            $table->text('credential_value')->nullable(); // encrypted
            $table->string('pin_value')->nullable(); // encrypted
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('station_credentials'); }
};
