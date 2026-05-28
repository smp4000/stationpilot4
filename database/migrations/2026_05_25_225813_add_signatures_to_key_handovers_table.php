<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('key_handovers', function (Blueprint $table) {
            // Unterschrift Mitarbeiter beim Empfang (base64 PNG)
            $table->longText('receipt_signature')->nullable()->after('employee_confirmed_at');
            // Unterschrift Mitarbeiter bei Rückgabe (base64 PNG)
            $table->longText('return_signature')->nullable()->after('returned_at');
            // Zeitstempel Rückgabe-Bestätigung durch Mitarbeiter
            $table->timestamp('employee_returned_at')->nullable()->after('return_signature');
        });
    }

    public function down(): void
    {
        Schema::table('key_handovers', function (Blueprint $table) {
            $table->dropColumn(['receipt_signature', 'return_signature', 'employee_returned_at']);
        });
    }
};
