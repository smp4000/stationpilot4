<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('priority')->default(1);   // 1 = erster, 2 = zweiter Kontakt
            $table->string('name', 150);
            $table->string('relationship', 80)->nullable(); // Ehepartner / Eltern / Kind / …
            $table->string('phone', 30);
            $table->string('phone_mobile', 30)->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_emergency_contacts');
    }
};
