<?php

namespace Database\Seeders;

use App\Models\FuelType;
use Illuminate\Database\Seeder;

class FuelTypeSeeder extends Seeder
{
    public function run(): void
    {
        $fuels = [
            // ── Standard ────────────────────────────────────────────────
            ['sort_order' =>  1, 'category' => 'standard',   'brand' => null,            'color' => '#16a34a', 'slug' => 'super_e5',                'name' => 'Super (E5)',                    'description' => 'Ottokraftstoff mit mind. 95 Oktan (ROZ), max. 5 % Ethanol'],
            ['sort_order' =>  2, 'category' => 'standard',   'brand' => null,            'color' => '#15803d', 'slug' => 'e10',                     'name' => 'Super E10',                     'description' => 'Ottokraftstoff mit mind. 95 Oktan, bis zu 10 % Ethanol'],
            ['sort_order' =>  3, 'category' => 'standard',   'brand' => null,            'color' => '#b45309', 'slug' => 'diesel',                  'name' => 'Diesel',                        'description' => 'Standard-Dieselkraftstoff nach DIN EN 590'],
            ['sort_order' =>  4, 'category' => 'standard',   'brand' => null,            'color' => '#d97706', 'slug' => 'super_plus',              'name' => 'Super Plus (98)',                'description' => 'Ottokraftstoff mit mind. 98 Oktan (ROZ)'],
            ['sort_order' =>  5, 'category' => 'standard',   'brand' => null,            'color' => '#0ea5e9', 'slug' => 'adblue',                  'name' => 'AdBlue',                        'description' => 'Harnstofflösung zur Abgasnachbehandlung (SCR) für Dieselfahrzeuge'],

            // ── Alternativ & Eco ─────────────────────────────────────────
            ['sort_order' => 20, 'category' => 'alternativ', 'brand' => null,            'color' => '#7c3aed', 'slug' => 'lpg',                     'name' => 'LPG / Autogas',                 'description' => 'Flüssiggas (Propan/Butan-Gemisch) als Kraftstoff'],
            ['sort_order' => 21, 'category' => 'alternativ', 'brand' => null,            'color' => '#2563eb', 'slug' => 'cng',                     'name' => 'Erdgas (CNG)',                  'description' => 'Komprimiertes Erdgas, deutlich CO₂-ärmer als Benzin'],
            ['sort_order' => 22, 'category' => 'alternativ', 'brand' => null,            'color' => '#1d4ed8', 'slug' => 'lng',                     'name' => 'LNG (Flüssigerdgas)',           'description' => 'Verflüssigtes Erdgas, vor allem für LKW geeignet'],
            ['sort_order' => 23, 'category' => 'alternativ', 'brand' => null,            'color' => '#0891b2', 'slug' => 'h2',                      'name' => 'Wasserstoff (H2)',              'description' => 'Wasserstoff für Brennstoffzellenfahrzeuge'],
            ['sort_order' => 24, 'category' => 'alternativ', 'brand' => null,            'color' => '#059669', 'slug' => 'e85',                     'name' => 'E85 (Ethanol)',                 'description' => 'Kraftstoffgemisch mit 85 % Bioethanol für Flex-Fuel-Fahrzeuge'],
            ['sort_order' => 25, 'category' => 'alternativ', 'brand' => null,            'color' => '#0d9488', 'slug' => 'hvo100',                  'name' => 'HVO100 (paraffinischer Diesel)', 'description' => 'Hydrotreated Vegetable Oil – erneuerbarer Diesel aus Pflanzenölen/Abfällen, bis zu 90 % CO₂-Einsparung'],
            ['sort_order' => 26, 'category' => 'alternativ', 'brand' => null,            'color' => '#166534', 'slug' => 'synthetischer_diesel',     'name' => 'Synthetischer Diesel (XtL)',    'description' => 'Synthetisch hergestellter Kraftstoff (z. B. aus Erdgas oder Biomasse)'],

            // ── Elektro ──────────────────────────────────────────────────
            ['sort_order' => 40, 'category' => 'elektro',    'brand' => null,            'color' => '#f59e0b', 'slug' => 'elektro_ac',              'name' => 'Elektro-Laden (AC)',             'description' => 'Wechselstrom-Laden, Typ 2, bis ca. 22 kW'],
            ['sort_order' => 41, 'category' => 'elektro',    'brand' => null,            'color' => '#d97706', 'slug' => 'elektro_dc',              'name' => 'Elektro-Schnellladen (DC)',      'description' => 'Gleichstrom-Schnelladen, CCS/CHAdeMO, 50–350 kW'],

            // ── Premium / Marken ─────────────────────────────────────────
            // Aral (BP-Gruppe)
            ['sort_order' => 60, 'category' => 'premium',    'brand' => 'Aral',          'color' => '#1e3a5f', 'slug' => 'aral_ultimate_102',       'name' => 'Aral Ultimate 102',             'description' => 'Premium-Benzin mit 102 Oktan, für Hochleistungsmotoren'],
            ['sort_order' => 61, 'category' => 'premium',    'brand' => 'Aral',          'color' => '#1e3a5f', 'slug' => 'aral_ultimate_diesel',    'name' => 'Aral Ultimate Diesel',          'description' => 'Premium-Diesel mit Reinigungsadditiven für weniger Verschleiß'],

            // Shell
            ['sort_order' => 70, 'category' => 'premium',    'brand' => 'Shell',         'color' => '#dc2626', 'slug' => 'shell_vpower_100',        'name' => 'Shell V-Power 100',             'description' => 'Premium-Benzin mit 100 Oktan und Reinigungsformel'],
            ['sort_order' => 71, 'category' => 'premium',    'brand' => 'Shell',         'color' => '#dc2626', 'slug' => 'shell_vpower_racing',     'name' => 'Shell V-Power Racing',          'description' => 'Motorsport-Kraftstoff mit über 99 Oktan'],
            ['sort_order' => 72, 'category' => 'premium',    'brand' => 'Shell',         'color' => '#dc2626', 'slug' => 'shell_vpower_diesel',     'name' => 'Shell V-Power Diesel',          'description' => 'Premium-Diesel mit Nitrogen-Reinigungstechnologie'],

            // BP
            ['sort_order' => 80, 'category' => 'premium',    'brand' => 'BP',            'color' => '#16a34a', 'slug' => 'bp_ultimate_98',          'name' => 'BP Ultimate 98',                'description' => 'Premium-Benzin mit 98 Oktan und Reinigungsadditiven'],
            ['sort_order' => 81, 'category' => 'premium',    'brand' => 'BP',            'color' => '#16a34a', 'slug' => 'bp_ultimate_diesel',      'name' => 'BP Ultimate Diesel',            'description' => 'Premium-Diesel mit Reinigungsadditiven'],

            // Esso / ExxonMobil
            ['sort_order' => 90, 'category' => 'premium',    'brand' => 'Esso',          'color' => '#b91c1c', 'slug' => 'esso_synergy_100',        'name' => 'Esso Synergy Supreme+ 100',     'description' => 'Premium-Benzin mit 100 Oktan und Synergy-Additiven'],
            ['sort_order' => 91, 'category' => 'premium',    'brand' => 'Esso',          'color' => '#b91c1c', 'slug' => 'esso_synergy_diesel',     'name' => 'Esso Synergy Diesel Efficient',  'description' => 'Premium-Diesel mit kraftstoffsparenden Additiven'],

            // TotalEnergies
            ['sort_order' =>100, 'category' => 'premium',    'brand' => 'TotalEnergies', 'color' => '#ea580c', 'slug' => 'total_excellium_98',      'name' => 'TotalEnergies Excellium 98',    'description' => 'Premium-Benzin mit 98 Oktan und Reinigungspaket'],
            ['sort_order' =>101, 'category' => 'premium',    'brand' => 'TotalEnergies', 'color' => '#ea580c', 'slug' => 'total_excellium_diesel',  'name' => 'TotalEnergies Excellium Diesel', 'description' => 'Premium-Diesel mit Friction-Modifier-Technologie'],

            // OMV (Österreich / Bayern)
            ['sort_order' =>110, 'category' => 'premium',    'brand' => 'OMV',           'color' => '#4338ca', 'slug' => 'omv_maxxmotion_100',      'name' => 'OMV MaxxMotion 100+',           'description' => 'Premium-Benzin mit 100+ Oktan, vor allem in AT/BY'],
            ['sort_order' =>111, 'category' => 'premium',    'brand' => 'OMV',           'color' => '#4338ca', 'slug' => 'omv_maxxmotion_diesel',   'name' => 'OMV MaxxMotion Diesel',         'description' => 'Premium-Diesel mit Schmierfähigkeitsadditiven'],

            // Jet
            ['sort_order' =>120, 'category' => 'premium',    'brand' => 'Jet',           'color' => '#6b21a8', 'slug' => 'jet_premium',             'name' => 'Jet Premium',                   'description' => 'Premium-Kraftstoff der Jet-Stationen (ConocoPhillips)'],

            // HEM
            ['sort_order' =>130, 'category' => 'premium',    'brand' => 'HEM',           'color' => '#0f172a', 'slug' => 'hem_optimal',             'name' => 'HEM Optimal',                   'description' => 'Premium-Kraftstoff der HEM-Tankstellenkette'],

            // Agip / ENI
            ['sort_order' =>140, 'category' => 'premium',    'brand' => 'Agip/ENI',      'color' => '#7f1d1d', 'slug' => 'agip_excellium_100',      'name' => 'Agip Excellium 100',            'description' => 'Premium-Benzin mit 100 Oktan, ENI-Konzern'],
            ['sort_order' =>141, 'category' => 'premium',    'brand' => 'Agip/ENI',      'color' => '#7f1d1d', 'slug' => 'agip_excellium_diesel',   'name' => 'Agip Excellium Diesel',         'description' => 'Premium-Diesel mit Reibungsminderern'],

            // AVIA
            ['sort_order' =>150, 'category' => 'premium',    'brand' => 'AVIA',          'color' => '#1e40af', 'slug' => 'avia_xtl',                'name' => 'AVIA XTL (syn. Diesel)',        'description' => 'Synthetischer Diesel aus natürlichen Rohstoffen, paraffinisch'],

            // Westfalen
            ['sort_order' =>160, 'category' => 'premium',    'brand' => 'Westfalen',     'color' => '#374151', 'slug' => 'westfalen_super_plus',    'name' => 'Westfalen Super Plus',          'description' => 'Premium-Kraftstoff der Westfalen-Gruppe'],

            // Q8 / Kuwait Petroleum
            ['sort_order' =>170, 'category' => 'premium',    'brand' => 'Q8',            'color' => '#ca8a04', 'slug' => 'q8_hi_perform',           'name' => 'Q8 Hi-Perform 100',             'description' => 'Premium-Benzin der Q8-Stationen mit 100 Oktan'],
        ];

        foreach ($fuels as $data) {
            FuelType::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
