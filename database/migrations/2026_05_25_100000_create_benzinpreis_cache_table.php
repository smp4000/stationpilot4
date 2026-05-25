<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benzinpreis_cache', function (Blueprint $table) {
            $table->string('hash', 32)->primary();
            $table->string('slug', 255);
            $table->string('mts_uuid', 36)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('brand', 100)->nullable();
            $table->decimal('e5', 6, 3)->nullable();
            $table->decimal('e10', 6, 3)->nullable();
            $table->decimal('diesel', 6, 3)->nullable();
            $table->datetime('fetched_at');
            $table->datetime('last_changed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benzinpreis_cache');
    }
};
