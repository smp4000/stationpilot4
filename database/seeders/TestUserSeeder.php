<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Erstellt alle Testkonten für die Entwicklung.
 *
 * ACHTUNG: Nur für Entwicklung und Staging.
 * Niemals auf Produktion ausführen.
 *
 * Keine Rollen-Zuweisung hier — kommt in Prompt 03 (RolesAndPermissionsSeeder).
 *
 * WithoutModelEvents wird bewusst NICHT verwendet, da HasUlid
 * den creating-Event benötigt um die ULID automatisch zu setzen.
 */
class TestUserSeeder extends Seeder
{

    public function run(): void
    {
        // ── 1. Super Admin ────────────────────────────────────────────────
        // Plattform-Betreiber. Kein Mandant. Nur /admin.
        User::updateOrCreate(
            ['email' => 'admin@stationpilot.de'],
            [
                'is_company'        => false,
                'first_name'        => 'Super',
                'last_name'         => 'Admin',
                'company_name'      => null,
                'password'          => Hash::make('password'),
                'type'              => 'super_admin',
                'tenant_id'         => null,
                'email_verified_at' => now(),
                'is_active'         => true,
                'locale'            => 'de',
            ]
        );

        // ── 2. Demo-Mandant (Tankstellenpartner als Person) ───────────────
        $partner = User::updateOrCreate(
            ['email' => 'partner@stationpilot.de'],
            [
                'is_company'        => false,
                'first_name'        => 'Max',
                'last_name'         => 'Mustermann',
                'company_name'      => null,
                'password'          => Hash::make('password'),
                'type'              => 'partner',
                'tenant_id'         => null, // wird unten gesetzt
                'email_verified_at' => now(),
                'is_active'         => true,
                'locale'            => 'de',
            ]
        );

        $demoTenant = Tenant::updateOrCreate(
            ['slug' => 'demo-tankstellen'],
            [
                'owner_id'            => $partner->id,
                'name'                => 'Demo Tankstellen',
                'billing_email'       => 'partner@stationpilot.de',
                'billing_address'     => [
                    'street'  => 'Musterstraße 1',
                    'zip'     => '36100',
                    'city'    => 'Petersberg',
                    'country' => 'DE',
                ],
                'tax_id'              => null,
                'ust_id'              => null,
                'subscription_status' => 'trial',
                'trial_ends_at'       => now()->addDays(14),
                'locale'              => 'de',
                'timezone'            => 'Europe/Berlin',
                'is_active'           => true,
                'settings'            => [
                    'currency'    => 'EUR',
                    'date_format' => 'd.m.Y',
                    'time_format' => 'H:i',
                ],
            ]
        );

        // tenant_id beim Partner setzen
        $partner->updateQuietly(['tenant_id' => $demoTenant->id]);

        // ── 3. Partner als Firma ──────────────────────────────────────────
        // Zeigt die Firmen-Naming-Strategie
        $firmaPartner = User::updateOrCreate(
            ['email' => 'firma@stationpilot.de'],
            [
                'is_company'        => true,
                'first_name'        => null,
                'last_name'         => null,
                'company_name'      => 'Mustermann Tankstellen GmbH',
                'password'          => Hash::make('password'),
                'type'              => 'partner',
                'tenant_id'         => null,
                'email_verified_at' => now(),
                'is_active'         => true,
                'locale'            => 'de',
            ]
        );

        $firmaTenant = Tenant::updateOrCreate(
            ['slug' => 'mustermann-tankstellen-gmbh'],
            [
                'owner_id'            => $firmaPartner->id,
                'name'                => 'Mustermann Tankstellen GmbH',
                'billing_email'       => 'firma@stationpilot.de',
                'billing_address'     => [
                    'street'  => 'Industriestraße 42',
                    'zip'     => '60311',
                    'city'    => 'Frankfurt am Main',
                    'country' => 'DE',
                ],
                'subscription_status' => 'active',
                'trial_ends_at'       => null,
                'locale'              => 'de',
                'timezone'            => 'Europe/Berlin',
                'is_active'           => true,
            ]
        );

        $firmaPartner->updateQuietly(['tenant_id' => $firmaTenant->id]);

        // ── 4. Stationsleiter (mit NFC scan_code) ────────────────────────
        // scan_code = NFC-Tag-Wert, den die Android-App liest
        User::updateOrCreate(
            ['email' => 'stationsleiter@stationpilot.de'],
            [
                'is_company'        => false,
                'first_name'        => 'Lara Sophie',
                'last_name'         => 'Mustermann',
                'company_name'      => null,
                'password'          => Hash::make('password'),
                'type'              => 'employee',
                'tenant_id'         => $demoTenant->id,
                'email_verified_at' => now(),
                'is_active'         => true,
                'locale'            => 'de',
                // NFC-Tag: In Android-App eingelesen, hier als Testwert
                'scan_code'         => 'DEMO-SL-001',
                'pin_hash'          => Hash::make('1234'),
            ]
        );

        // ── 5. Standard-Mitarbeiter ───────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'mitarbeiter@stationpilot.de'],
            [
                'is_company'        => false,
                'first_name'        => 'Erika',
                'last_name'         => 'Musterfrau',
                'company_name'      => null,
                'password'          => Hash::make('password'),
                'type'              => 'employee',
                'tenant_id'         => $demoTenant->id,
                'email_verified_at' => now(),
                'is_active'         => true,
                'locale'            => 'de',
                'scan_code'         => 'DEMO-MA-001',
                'pin_hash'          => Hash::make('4321'),
            ]
        );

        // ── 6. Zweiter Mitarbeiter (andere PIN, kein NFC) ─────────────────
        User::updateOrCreate(
            ['email' => 'mitarbeiter2@stationpilot.de'],
            [
                'is_company'        => false,
                'first_name'        => 'Hans',
                'last_name'         => 'Müller',
                'company_name'      => null,
                'password'          => Hash::make('password'),
                'type'              => 'employee',
                'tenant_id'         => $demoTenant->id,
                'email_verified_at' => now(),
                'is_active'         => true,
                'locale'            => 'de',
                'scan_code'         => null, // noch kein NFC-Tag
                'pin_hash'          => Hash::make('9999'),
            ]
        );

        // ── 7. Steuerberater ──────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'steuerberater@stationpilot.de'],
            [
                'is_company'        => true,
                'first_name'        => null,
                'last_name'         => null,
                'company_name'      => 'Steuerberatung Muster & Partner',
                'password'          => Hash::make('password'),
                'type'              => 'tax_advisor',
                'tenant_id'         => $demoTenant->id,
                'email_verified_at' => now(),
                'is_active'         => true,
                'locale'            => 'de',
            ]
        );

        // ── 8. Zweiter Partner (Isolations-Test Multi-Tenancy) ────────────
        // Dieser Mandant darf NIEMALS Daten vom ersten Mandanten sehen
        $partner2 = User::updateOrCreate(
            ['email' => 'partner2@stationpilot.de'],
            [
                'is_company'        => false,
                'first_name'        => 'Peter',
                'last_name'         => 'Schmidt',
                'company_name'      => null,
                'password'          => Hash::make('password'),
                'type'              => 'partner',
                'tenant_id'         => null,
                'email_verified_at' => now(),
                'is_active'         => true,
                'locale'            => 'de',
            ]
        );

        $tenant2 = Tenant::updateOrCreate(
            ['slug' => 'schmidt-tankstellen'],
            [
                'owner_id'            => $partner2->id,
                'name'                => 'Schmidt Tankstellen',
                'billing_email'       => 'partner2@stationpilot.de',
                'billing_address'     => [
                    'street'  => 'Teststraße 99',
                    'zip'     => '80331',
                    'city'    => 'München',
                    'country' => 'DE',
                ],
                'subscription_status' => 'trial',
                'trial_ends_at'       => now()->addDays(7),
                'locale'              => 'de',
                'timezone'            => 'Europe/Berlin',
                'is_active'           => true,
            ]
        );

        $partner2->updateQuietly(['tenant_id' => $tenant2->id]);

        // ── Ausgabe ───────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('✅ Testkonten erstellt (Passwort überall: password)');
        $this->command->newLine();
        $this->command->table(
            ['Typ', 'E-Mail', 'Mandant', 'Panel'],
            [
                ['super_admin',   'admin@stationpilot.de',           '—',                   '/admin'],
                ['partner',       'partner@stationpilot.de',          'Demo Tankstellen',    '/app'],
                ['partner Firma', 'firma@stationpilot.de',            'Mustermann GmbH',     '/app'],
                ['stationsleiter','stationsleiter@stationpilot.de',   'Demo Tankstellen',    '/app + App'],
                ['mitarbeiter',   'mitarbeiter@stationpilot.de',      'Demo Tankstellen',    '/app + App'],
                ['mitarbeiter',   'mitarbeiter2@stationpilot.de',     'Demo Tankstellen',    '/app + App'],
                ['tax_advisor',   'steuerberater@stationpilot.de',    'Demo Tankstellen',    '/app'],
                ['partner',       'partner2@stationpilot.de',         'Schmidt Tankstellen', '/app'],
            ]
        );
        $this->command->newLine();
        $this->command->line('NFC scan_codes für Android-App Tests:');
        $this->command->line('  Stationsleiter: DEMO-SL-001');
        $this->command->line('  Mitarbeiter:    DEMO-MA-001');
        $this->command->newLine();
    }
}
