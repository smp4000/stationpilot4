<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->json('custom_placeholders')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropColumn('custom_placeholders');
        });
    }
};
