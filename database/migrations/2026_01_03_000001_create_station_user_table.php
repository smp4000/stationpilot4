<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('station_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('station_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role_at_station', ['manager', 'cashier', 'cleaner', 'apprentice'])
                ->default('cashier');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            $table->foreign('station_id')->references('id')->on('stations')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['station_id', 'user_id']);
            $table->index(['station_id', 'removed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_user');
    }
};
