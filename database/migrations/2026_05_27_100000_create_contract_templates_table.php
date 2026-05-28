<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('contract_type'); // unbefristet | befristet | minijob
            $table->string('name');
            $table->longText('body'); // HTML with {{placeholder}} tokens
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'contract_type']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_templates');
    }
};
