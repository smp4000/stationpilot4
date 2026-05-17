<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->unsignedBigInteger('tenant_id')->index();

            // Stammdaten
            $table->string('name');
            $table->string('brand', 50)->nullable();
            $table->string('station_number', 50)->nullable();

            // Adresse
            $table->string('street')->nullable();
            $table->string('house_number', 20)->nullable();
            $table->string('zip', 10)->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->default('DE');

            // Geodaten
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();

            // Öffnungszeiten
            $table->json('opening_hours')->nullable();

            // Station-Details
            $table->unsignedSmallInteger('tank_count')->nullable();
            $table->unsignedSmallInteger('dispenser_count')->nullable();
            $table->boolean('has_car_wash')->default(false);
            $table->string('wash_model')->nullable();
            $table->boolean('has_bistro')->default(false);
            $table->boolean('has_shop')->default(false);
            $table->json('meta')->nullable();

            // Bankverbindung (DSGVO verschlüsselt)
            $table->string('bank_name')->nullable();
            $table->text('iban')->nullable()->comment('AES-256-CBC encrypted');
            $table->text('bic')->nullable()->comment('AES-256-CBC encrypted');
            $table->string('account_holder')->nullable();

            // BenzinpreisService (P08)
            $table->string('benzinpreis_slug')->nullable()->unique();
            $table->string('benzinpreis_hash')->nullable();
            $table->timestamp('enriched_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'is_active']);
            $table->index('zip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
