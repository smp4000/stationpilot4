<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('key_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('key_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('handed_out_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('handed_out_at');
            $table->foreignId('returned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('employee_confirmed_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('key_handovers'); }
};
