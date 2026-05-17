<?php

namespace Database\Seeders;

use App\Models\Station;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class StationSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-tankstellen')->first();

        if (! $tenant) {
            $this->command->warn('Demo-Mandant fehlt — StationSeeder übersprungen.');
            return;
        }

        session(['tenant_id' => $tenant->id]);

        Station::updateOrCreate(
            ['benzinpreis_slug' => 'demo-fulda-1'],
            [
                'tenant_id'        => $tenant->id,
                'name'             => 'Aral Tankstelle Welle Fulda',
                'brand'            => 'Aral',
                'station_number'   => '1234567',
                'street'           => 'Künzeller Straße',
                'house_number'     => '101',
                'zip'              => '36093',
                'city'             => 'Künzell',
                'country'          => 'DE',
                'lat'              => 50.55960,
                'lng'              => 9.68273,
                'opening_hours'    => array_merge(
                    Station::defaultOpeningHours(),
                    [
                        'saturday' => ['open' => '07:00', 'close' => '22:00', 'is_closed' => false],
                        'sunday'   => ['open' => '08:00', 'close' => '21:00', 'is_closed' => false],
                    ]
                ),
                'tank_count'       => 4,
                'dispenser_count'  => 8,
                'has_car_wash'     => true,
                'wash_model'       => 'Waschstraße',
                'has_bistro'       => true,
                'has_shop'         => true,
                'is_active'        => true,
            ]
        );

        Station::updateOrCreate(
            ['benzinpreis_slug' => 'demo-petersberg-2'],
            [
                'tenant_id'        => $tenant->id,
                'name'             => 'Aral Tankstelle Welle Petersberg',
                'brand'            => 'Aral',
                'station_number'   => '7654321',
                'street'           => 'Hubertusstraße',
                'house_number'     => '1',
                'zip'              => '36100',
                'city'             => 'Petersberg',
                'country'          => 'DE',
                'lat'              => 50.57810,
                'lng'              => 9.72150,
                'opening_hours'    => Station::defaultOpeningHours(),
                'tank_count'       => 3,
                'dispenser_count'  => 6,
                'has_car_wash'     => false,
                'has_bistro'       => false,
                'has_shop'         => true,
                'is_active'        => true,
            ]
        );

        session()->forget('tenant_id');

        $this->command->info('✅ 2 Teststationen angelegt (Künzell + Petersberg)');
    }
}
