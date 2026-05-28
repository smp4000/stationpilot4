<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credential_station', function (Blueprint $table) {
            $table->foreignId('station_credential_id')->constrained('station_credentials')->cascadeOnDelete();
            $table->foreignId('station_id')->constrained('gas_stations')->cascadeOnDelete();
            $table->primary(['station_credential_id', 'station_id']);
        });

        // Vorhandene station_id-Werte in die Pivot-Tabelle übertragen
        \DB::table('station_credentials')
            ->whereNotNull('station_id')
            ->orderBy('id')
            ->each(function ($row) {
                \DB::table('credential_station')->insertOrIgnore([
                    'station_credential_id' => $row->id,
                    'station_id'            => $row->station_id,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_station');
    }
};
