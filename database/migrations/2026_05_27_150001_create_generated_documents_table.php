<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('template_id')->nullable()->constrained('document_templates')->nullOnDelete();
            $table->string('document_type');
            $table->nullableMorphs('related'); // related_type + related_id
            $table->string('pdf_path')->nullable();
            $table->foreignId('generated_by')->constrained('users');
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();

            $table->index(['tenant_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
