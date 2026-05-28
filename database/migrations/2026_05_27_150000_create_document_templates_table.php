<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('document_type');       // mitarbeiter | arbeitsvertrag | schluessel | tankstelle
            $table->string('sub_type')->nullable(); // arbeitsvertrag: unbefristet | befristet | minijob
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('body');
            $table->json('custom_placeholders')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'document_type']);
        });

        // Migrate existing contract_templates → document_templates
        if (Schema::hasTable('contract_templates')) {
            DB::table('contract_templates')->orderBy('id')->each(function ($row) {
                DB::table('document_templates')->insert([
                    'tenant_id'           => $row->tenant_id,
                    'document_type'       => 'arbeitsvertrag',
                    'sub_type'            => $row->contract_type,
                    'name'                => $row->name,
                    'body'                => $row->body,
                    'custom_placeholders' => $row->custom_placeholders,
                    'is_active'           => $row->is_active ?? 1,
                    'is_default'          => $row->is_default ?? 0,
                    'created_at'          => $row->created_at,
                    'updated_at'          => $row->updated_at,
                ]);
            });

            Schema::dropIfExists('contract_templates');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
