<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DSGVO-Zugriffs-Protokoll.
     * Jeder lesende oder schreibende Zugriff auf Mitarbeiter-Stammdaten
     * und Dokumente wird hier festgehalten (Art. 5 Abs. 2 DSGVO — Rechenschaftspflicht).
     */
    public function up(): void
    {
        Schema::create('employee_access_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accessed_by')->constrained('users')->cascadeOnDelete();

            $table->string('action', 30);             // view / edit / download / delete / invite
            $table->string('resource', 50);           // stammdaten / dokument / bankdaten / …
            $table->unsignedBigInteger('resource_id')->nullable(); // z.B. document_id
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->json('changed_fields')->nullable(); // bei edit: welche Felder geändert

            $table->timestamp('accessed_at')->useCurrent();

            $table->index(['employee_id', 'accessed_at']);
            $table->index('accessed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_access_log');
    }
};
