<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->boolean('is_24h')->default(false)->after('opening_hours');
            $table->date('opening_date_ok')->nullable()->after('is_24h');
            $table->date('opening_date_dk')->nullable()->after('opening_date_ok');
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn(['is_24h', 'opening_date_ok', 'opening_date_dk']);
        });
    }
};
