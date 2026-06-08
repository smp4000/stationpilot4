<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trennt Rollen nach Einsatzbereich:
 *   scope = 'web'     → steuert das Filament /app-Panel (partner.* Permissions)
 *   scope = 'gopilot' → steuert die GoPilot Android-App (employee.* Permissions)
 *
 * Bestehende Rollen werden zu 'web' (Standardwert), GoPilot-Rollen werden
 * vom RolesAndPermissionsSeeder bzw. dem Command roles:sync-tenants gesetzt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('scope', 20)->default('web')->after('guard_name')->index();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['scope']);
            $table->dropColumn('scope');
        });
    }
};
