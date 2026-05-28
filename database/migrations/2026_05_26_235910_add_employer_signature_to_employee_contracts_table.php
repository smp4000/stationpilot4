<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_contracts', function (Blueprint $table) {
            $table->text('employer_signature')->nullable()->after('employee_signature');
            $table->timestamp('employer_signed_at')->nullable()->after('employer_signature');
            $table->foreignId('employer_signed_by')->nullable()->constrained('users')->nullOnDelete()->after('employer_signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('employee_contracts', function (Blueprint $table) {
            $table->dropForeign(['employer_signed_by']);
            $table->dropColumn(['employer_signature', 'employer_signed_at', 'employer_signed_by']);
        });
    }
};
