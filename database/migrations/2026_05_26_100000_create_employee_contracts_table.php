<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('contract_type', ['unbefristet', 'befristet', 'minijob']);
            $table->enum('status', ['draft', 'sent', 'employee_signed', 'completed', 'cancelled'])->default('draft');

            // Arbeitgeberdaten (Snapshot zum Erstellungszeitpunkt)
            $table->string('employer_name');
            $table->string('employer_company');
            $table->string('employer_street');
            $table->string('employer_zip');
            $table->string('employer_city');
            $table->string('signing_location')->default('');

            // Alle Vertragsdaten als JSON
            $table->json('contract_data');

            // PDF-Dateipfade
            $table->string('pdf_path')->nullable();

            // Digitale Unterschrift Mitarbeiter
            $table->string('employee_sign_token')->nullable()->unique();
            $table->timestamp('employee_signed_at')->nullable();
            $table->text('employee_signature')->nullable();
            $table->timestamp('sent_to_employee_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_contracts');
    }
};
