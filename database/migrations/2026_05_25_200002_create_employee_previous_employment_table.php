<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_previous_employment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            $table->string('employer_name', 200)->nullable();
            $table->date('employed_from')->nullable();
            $table->date('employed_until')->nullable();

            // Für Lohnsteuer laufendes Jahr (verschlüsselt)
            $table->text('gross_wages_ytd')->nullable();       // Bruttoarbeitslohn lfd. Jahr
            $table->text('income_tax_ytd')->nullable();        // Einbeh. Lohnsteuer lfd. Jahr
            $table->text('solidarity_tax_ytd')->nullable();    // Einbeh. Solidaritätszuschlag

            $table->timestamps();

            $table->unique('employee_id'); // genau 1 Eintrag pro MA
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_previous_employment');
    }
};
