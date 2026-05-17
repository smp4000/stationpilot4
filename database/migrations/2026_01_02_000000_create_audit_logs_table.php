<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DSGVO-konformes Audit-Log.
     * Kein updated_at — Logs sind unveränderlich.
     * old_values + new_values werden in P06 mit encrypted Cast gesichert.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index()
                ->comment('NULL bei Super-Admin-Aktionen');
            $table->unsignedBigInteger('user_id')->nullable()->index()
                ->comment('Handelnder User');
            $table->string('user_type', 20)->nullable()
                ->comment('super_admin, partner, employee');
            $table->string('action', 100)->index()
                ->comment('login, logout, login_failed, created, updated, deleted');
            $table->string('auditable_type')->nullable()
                ->comment('Vollständiger Model-Klassenname');
            $table->unsignedBigInteger('auditable_id')->nullable()
                ->comment('ID des betroffenen Datensatzes');
            $table->text('old_values')->nullable()
                ->comment('Alte Werte (wird in P06 encrypted)');
            $table->text('new_values')->nullable()
                ->comment('Neue Werte (wird in P06 encrypted)');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('reason')->nullable()
                ->comment('Begründung bei kritischen Aktionen');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
