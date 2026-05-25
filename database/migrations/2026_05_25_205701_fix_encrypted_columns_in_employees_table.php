<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Diese Felder werden verschlüsselt gespeichert → müssen text statt varchar sein
            $table->text('place_of_birth')->nullable()->change();
            $table->text('country_of_birth')->nullable()->change();
            $table->text('nationality')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('place_of_birth', 100)->nullable()->change();
            $table->string('country_of_birth', 100)->nullable()->change();
            $table->string('nationality', 100)->nullable()->change();
        });
    }
};
