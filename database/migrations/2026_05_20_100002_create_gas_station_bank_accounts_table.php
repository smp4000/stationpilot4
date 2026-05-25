<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gas_station_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gas_station_id')
                ->constrained('gas_stations')
                ->cascadeOnDelete();
            // Verschlüsselte Felder (Filament / Eloquent encrypted cast)
            $table->text('iban')->comment('IBAN (verschlüsselt)');
            $table->string('bank_name')->nullable()->comment('Bankname');
            $table->text('bic')->nullable()->comment('BIC/SWIFT (verschlüsselt)');
            $table->string('account_holder')->nullable()->comment('Kontoinhaber');
            $table->string('description')->nullable()->comment('Beschreibung / Verwendungszweck');
            $table->string('account_type', 60)->nullable()
                ->comment('Kontotyp: geschaeftskonto | agenturkonto | lottokonto | shop | waschanlage');
            $table->timestamps();

            $table->index('gas_station_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gas_station_bank_accounts');
    }
};
