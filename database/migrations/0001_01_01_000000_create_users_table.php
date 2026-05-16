<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Erstellt: tenants, users, password_reset_tokens, sessions
     *
     * ID-Strategie:
     * - id: BIGINT auto-increment (interne DB-Performance)
     * - ulid: char(26) unique (öffentliche URLs, API, QR-Codes)
     *
     * Naming-Strategie auf users:
     * - Firma: company_name required, is_company = true
     * - Person: first_name + last_name, is_company = false
     * - Accessor getNameAttribute() gibt immer den korrekten Namen zurück
     */
    public function up(): void
    {
        // tenants zuerst (users hat FK auf tenants)
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique()->comment('Öffentliche ID für URLs/API');
            // owner_id kommt per ALTER nach users-Tabelle (zirkuläre FK)
            $table->string('name')->comment('Firmenname des Mandanten');
            $table->string('slug')->unique();
            $table->string('billing_email')->comment('E-Mail für Rechnungen');
            $table->json('billing_address')->nullable()
                ->comment('{ street, zip, city, country }');
            $table->string('tax_id', 50)->nullable()->comment('Steuernummer');
            $table->string('ust_id', 20)->nullable()->comment('Umsatzsteuer-ID');
            $table->string('phone', 50)->nullable();
            $table->string('logo')->nullable();
            $table->enum('subscription_status', [
                'trial',
                'active',
                'past_due',
                'read_only',
                'cancelled',
                'archived',
            ])->default('trial');
            $table->dateTime('trial_ends_at')->nullable();
            $table->string('billing_driver', 20)->default('manual_sepa')
                ->comment('manual_sepa | stripe');
            $table->string('locale', 5)->default('de');
            $table->string('timezone', 50)->default('Europe/Berlin');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('subscription_status');
            $table->index('is_active');
        });

        // users-Tabelle
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique()->comment('Öffentliche ID');
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->nullOnDelete()
                ->comment('NULL = Super-Admin');

            // --- Naming-Strategie ---
            // Firma: company_name gesetzt, is_company = true
            // Person: first_name + last_name, is_company = false
            $table->boolean('is_company')->default(false);
            $table->string('first_name')->nullable()->comment('Vorname (Person)');
            $table->string('last_name')->nullable()->comment('Nachname (Person)');
            $table->string('company_name')->nullable()->comment('Firmenname (Firma)');

            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable()
                ->comment('Nullable: Magic-Link-User haben kein Passwort');
            $table->enum('type', ['super_admin', 'partner', 'employee', 'tax_advisor'])
                ->default('employee');
            $table->string('phone', 50)->nullable();
            $table->string('locale', 5)->default('de');

            // 2FA (beide verschlüsselt)
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            // Login-Tracking
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();

            // Android-App Auth
            $table->string('pin_hash')->nullable()
                ->comment('PIN für Android-App Login (bcrypt)');
            $table->string('scan_code', 50)->nullable()->unique()
                ->comment('NFC/Barcode Login-Code');

            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('type');
            $table->index('is_active');
        });

        // Zirkuläre FK: tenants.owner_id → users.id (nach users anlegen)
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('owner_id')
                ->nullable()
                ->after('ulid')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Inhaber des Mandanten');
        });

        // Standard Laravel Tabellen
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn('owner_id');
        });

        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');
    }
};
