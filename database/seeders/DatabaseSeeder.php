<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Haupt-Seeder — ruft alle Sub-Seeder in der richtigen Reihenfolge auf.
 *
 * Reihenfolge wichtig:
 * 1. TestUserSeeder             — User + Tenants
 * 2. RolesAndPermissionsSeeder  — Rollen + Permissions definieren
 * 3. RoleAssignmentSeeder       — Rollen den Testusern zuweisen
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TestUserSeeder::class,
            RolesAndPermissionsSeeder::class,
            RoleAssignmentSeeder::class,
            BrandSeeder::class,    // Marken müssen vor Stationen existieren
            FuelTypeSeeder::class, // Kraftstoffsorten müssen vor Stationen existieren
            StationSeeder::class,
        ]);
    }
}
