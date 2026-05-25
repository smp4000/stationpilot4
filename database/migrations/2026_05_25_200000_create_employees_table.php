<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->nullable()->constrained('gas_stations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // ── Persönliche Daten ──────────────────────────────────────────
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('birth_name', 100)->nullable();             // Geburtsname
            $table->text('date_of_birth')->nullable();                 // verschlüsselt
            $table->string('place_of_birth', 100)->nullable();        // verschlüsselt
            $table->string('country_of_birth', 100)->nullable();      // verschlüsselt
            $table->string('nationality', 100)->nullable();           // verschlüsselt
            $table->string('gender', 10)->nullable();                 // m / w / d
            $table->string('marital_status', 30)->nullable();
            // Schwerbehinderung
            $table->boolean('severely_disabled')->default(false);
            $table->tinyInteger('disability_degree')->nullable();     // GdB 0–100

            // ── Anschrift ──────────────────────────────────────────────────
            $table->string('street', 150)->nullable();
            $table->string('house_number', 20)->nullable();
            $table->string('zip', 10)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->default('Deutschland');

            // ── Kontakt ────────────────────────────────────────────────────
            $table->string('phone_private', 30)->nullable();
            $table->string('phone_mobile', 30)->nullable();
            $table->string('email', 150)->nullable();

            // ── Steuer (verschlüsselt) ─────────────────────────────────────
            $table->text('tax_id')->nullable();                       // Steuer-ID 11-stellig
            $table->tinyInteger('tax_class')->nullable();             // Steuerklasse I–VI
            $table->decimal('tax_child_allowance', 4, 1)->nullable(); // Kinderfreibeträge
            $table->string('church_tax', 10)->nullable();             // ev/rk/keine/…
            $table->decimal('tax_factor', 4, 3)->nullable();          // Faktor bei Kl. IV

            // ── Sozialversicherung (verschlüsselt) ─────────────────────────
            $table->text('social_security_number')->nullable();       // SVNR 12-stellig
            $table->text('health_insurance_name')->nullable();        // Krankenkasse
            $table->string('health_insurance_type', 30)->nullable();  // gesetzl./freiwillig/privat/befreit
            $table->boolean('pension_insurance')->default(true);      // RV pflichtversichert
            $table->boolean('unemployment_insurance')->default(true); // ALV pflichtversichert

            // ── Beschäftigung ──────────────────────────────────────────────
            $table->date('employment_start')->nullable();
            $table->date('employment_end')->nullable();
            $table->string('employment_type', 30)->nullable();        // Vollzeit/Teilzeit/Minijob/…
            $table->string('employee_status', 30)->nullable();        // Arbeiter/Angestellter/Azubi/…
            $table->string('job_title', 100)->nullable();             // Berufsbezeichnung
            $table->decimal('weekly_hours', 5, 2)->nullable();
            $table->tinyInteger('vacation_days')->nullable();         // Urlaubstage/Jahr
            $table->string('cost_center', 50)->nullable();            // Kostenstelle

            // ── Vergütung (verschlüsselt) ──────────────────────────────────
            $table->string('wage_type', 20)->nullable();              // stundenlohn/gehalt/minijob
            $table->text('wage_amount')->nullable();                  // € verschlüsselt
            $table->string('payment_interval', 20)->nullable();       // monatlich/wöchentlich

            // ── Bankverbindung (verschlüsselt) ─────────────────────────────
            $table->text('iban')->nullable();
            $table->text('bic')->nullable();
            $table->string('account_holder', 150)->nullable();
            $table->string('bank_name', 150)->nullable();

            // ── Ausbildung ─────────────────────────────────────────────────
            $table->string('education_level', 50)->nullable();        // Schulabschluss
            $table->string('vocational_training', 50)->nullable();    // Berufsausbildung
            $table->string('vocational_title', 100)->nullable();      // Berufsbezeichnung Ausb.

            // ── Führerschein ───────────────────────────────────────────────
            $table->boolean('has_driving_license')->default(false);
            $table->json('driving_license_classes')->nullable();      // ['B','BE','C']
            $table->string('driving_license_number', 50)->nullable();
            $table->date('driving_license_issued')->nullable();
            $table->date('driving_license_expires')->nullable();

            // ── Arbeitsgenehmigung ─────────────────────────────────────────
            $table->string('residence_permit_type', 80)->nullable();
            $table->date('residence_permit_expires')->nullable();
            $table->boolean('work_permit_granted')->default(false);
            $table->date('work_permit_expires')->nullable();

            // ── System / Zugang ────────────────────────────────────────────
            $table->string('mde_pin', 255)->nullable();               // bcrypt-gehashte PIN
            $table->string('invitation_token', 64)->nullable()->unique();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('invitation_expires_at')->nullable();
            $table->enum('status', ['neu', 'eingeladen', 'aktiv', 'inaktiv'])->default('neu');

            // ── DSGVO ──────────────────────────────────────────────────────
            $table->timestamp('data_verified_at')->nullable();        // Daten vom MA bestätigt
            $table->date('retention_delete_after')->nullable();       // Löschfrist
            $table->timestamp('anonymized_at')->nullable();           // Anonymisiert am

            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('station_id');
            $table->index('employment_start');
            $table->index('invitation_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
