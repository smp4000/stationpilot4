<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_contracts', function (Blueprint $table) {
            $table->boolean('is_uploaded')->default(false)->after('pdf_path');
            $table->string('original_filename')->nullable()->after('is_uploaded');
        });
    }

    public function down(): void
    {
        Schema::table('employee_contracts', function (Blueprint $table) {
            $table->dropColumn(['is_uploaded', 'original_filename']);
        });
    }
};
