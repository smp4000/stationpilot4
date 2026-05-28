<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keys', function (Blueprint $table) {
            // Art des Schlüssels/Zugangsmediums
            $table->string('type')->default('schluessel')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('keys', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
