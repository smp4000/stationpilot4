<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // "Super (E5)", "Aral Ultimate 102"
            $table->string('slug')->unique();                    // "super_e5", "aral_ultimate_102"
            $table->string('category', 30)->default('standard'); // standard | premium | alternativ | elektro
            $table->string('brand')->nullable();                 // "Aral", "Shell" — null = markenunabhängig
            $table->string('color', 20)->nullable();             // Hex-Farbe für Badges
            $table->text('description')->nullable();             // Kurzbeschreibung
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_types');
    }
};
