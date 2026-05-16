<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Haupt-Seeder — ruft alle Sub-Seeder in der richtigen Reihenfolge auf.
 *
 * Reihenfolge wichtig:
 * 1. TestUserSeeder    — User + Tenants (keine Rollen)
 * 2. (Prompt 03) RolesAndPermissionsSeeder — Rollen + Permissions
 * 3. (Prompt 03) RoleAssignmentSeeder      — Rollen den Testusern zuweisen
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TestUserSeeder::class,
            // Weitere Seeder kommen in späteren Prompts:
            // RolesAndPermissionsSeeder::class,
            // RoleAssignmentSeeder::class,
        ]);
    }
}
