<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;

/**
 * Bringt die Standard-Rollen (Web + GoPilot) aller – oder eines – Mandanten
 * auf den aktuellen Stand. Idempotent.
 *
 *   php artisan roles:sync-tenants            # alle Mandanten
 *   php artisan roles:sync-tenants --tenant=5 # nur Mandant 5
 */
class SyncTenantRoles extends Command
{
    protected $signature = 'roles:sync-tenants {--tenant= : Nur diesen Mandanten (ID) synchronisieren}';

    protected $description = 'Erstellt/aktualisiert Web- und GoPilot-Standardrollen je Mandant';

    public function handle(): int
    {
        $query = Tenant::query();

        if ($tenantId = $this->option('tenant')) {
            $query->whereKey($tenantId);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('Keine Mandanten gefunden.');
            return self::SUCCESS;
        }

        $this->info("Synchronisiere Rollen für {$tenants->count()} Mandant(en) …");

        foreach ($tenants as $tenant) {
            RolesAndPermissionsSeeder::createTenantRoles($tenant->id);
            $this->line("  ✅ Mandant #{$tenant->id} — {$tenant->name}");
        }

        $this->info('Fertig.');

        return self::SUCCESS;
    }
}
