<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->boolean('requires_signature')->default(false)->after('is_default');
        });

        Schema::table('generated_documents', function (Blueprint $table) {
            $table->string('sign_token', 64)->nullable()->unique()->after('generated_at');
            $table->timestamp('signed_at')->nullable()->after('sign_token');
            $table->text('signature')->nullable()->after('signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn('requires_signature');
        });

        Schema::table('generated_documents', function (Blueprint $table) {
            $table->dropColumn(['sign_token', 'signed_at', 'signature']);
        });
    }
};
