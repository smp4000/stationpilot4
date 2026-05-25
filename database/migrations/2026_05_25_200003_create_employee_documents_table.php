<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();

            // Klassifikation
            $table->string('category', 50);           // vertrag/ausweis/gesundheit/…
            $table->string('document_type', 80);      // Arbeitsvertrag / Gesundheitszeugnis / …
            $table->string('title', 200);             // Anzeigetitel

            // Datei (DSGVO: Pfad verschlüsselt, kein direkter Zugriff)
            $table->text('file_path');                // verschlüsselter Speicherpfad
            $table->string('file_hash', 64)->nullable(); // SHA-256 für Integritätsprüfung
            $table->string('mime_type', 50)->nullable();
            $table->unsignedInteger('file_size')->nullable(); // Bytes

            // Gültigkeit
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('expiry_notified')->default(false);

            $table->text('notes')->nullable();

            // DSGVO
            $table->timestamp('last_accessed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['employee_id', 'category']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
