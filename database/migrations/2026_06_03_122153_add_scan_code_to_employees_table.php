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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('scan_code', 50)->nullable()->unique()
                ->comment('NFC-UID / Barcode / QR-Code für MDE-Login')
                ->after('mde_pin');

            $table->string('nfc_uid', 50)->nullable()->unique()
                ->comment('Physische NFC-Karten-UID (Hex)')
                ->after('scan_code');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['scan_code', 'nfc_uid']);
        });
    }
};
