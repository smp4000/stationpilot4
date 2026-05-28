<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('station_credentials', function (Blueprint $table) {
            // Mitarbeiter ist jetzt die primäre Zuordnung
            $table->foreignId('employee_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained()
                ->nullOnDelete();

            // Tankstelle wird optional (Kontext: an welcher Station werden die Credentials genutzt)
            $table->foreignId('station_id')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('station_credentials', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};
