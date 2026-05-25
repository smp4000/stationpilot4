<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\FuelType;
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

        $aral = Brand::where('slug', 'aral')->first();

        // Standard-Kraftstoffe für Demo-Stationen
        $standardFuels = FuelType::whereIn('slug', ['super_e5', 'e10', 'diesel', 'adblue', 'aral_ultimate_102', 'aral_ultimate_diesel'])
            ->pluck('slug')
            ->toArray();

        Station::updateOrCreate(
            ['benzinpreis_slug' => 'demo-fulda-1'],
            [
                'tenant_id'        => $tenant->id,
                'name'             => 'Aral Tankstelle Welle Fulda',
                'brand_id'         => $aral?->id,
                'station_number'   => '1234567',
                'street'           => 'Schlitzer Straße',
                'house_number'     => '105',
                'zip'              => '36039',
                'city'             => 'Fulda',
                'state'            => 'HE',
                'country'          => 'DE',
                'latitude'         => 50.56522,
                'longitude'        => 9.65700,
                'phone'            => '066151681',
                'email'            => 'sv.welle@aral-welle.de',
                'contact_first_name' => 'Christian',
                'contact_last_name'  => 'Welle',
                'opening_hours'    => array_merge(
                    Station::defaultOpeningHours(),
                    [
                        'saturday' => ['open' => '07:00', 'close' => '22:00', 'is_closed' => false],
                        'sunday'   => ['open' => '08:00', 'close' => '21:00', 'is_closed' => false],
                    ]
                ),
                'num_pumps'        => 8,
                'has_car_wash'     => true,
                'car_wash_details' => [
                    'brand'              => 'WasTec',
                    'type'               => 'portal',
                    'drive_through'      => true,
                    'underbody_wash'     => false,
                    'height'             => 2.4,
                    'width'              => 2.3,
                    'has_ticket_system'  => true,
                    'easy_carwash'       => true,
                ],
                'has_shop'         => true,
                'shop_type'        => 'REWE To Go',
                'shop_size'        => 'G2',
                'shop_partner'     => 'News To Go',
                'shop_operation_number' => '0FE/1',
                'assortment_level' => 'Primum',
                'fuel_types'       => $standardFuels,
                'additional_businesses' => ['hermes', 'amazon_locker', 'lotto', 'toto', 'backshop', 'cafe'],
                'services'         => ['air', 'vacuum', 'water', 'tire_check'],
                'ownership_type'   => 'DOFO',
                'sales_channel'    => 'eigengeschaeft',
                'district'         => '11',
                'district_description' => 'TD 11 - DODO Nord',
                'region'           => '06',
                'region_manager'   => 'Johannes Diemert',
                'station_number_fuel' => '0F671',
                'station_number_shop' => '170320196',
                'is_active'        => true,
            ]
        );

        Station::updateOrCreate(
            ['benzinpreis_slug' => 'demo-petersberg-2'],
            [
                'tenant_id'        => $tenant->id,
                'name'             => 'ARAL Petersberg',
                'brand_id'         => $aral?->id,
                'station_number'   => '7654321',
                'street'           => 'Petersberger Straße',
                'house_number'     => '101',
                'zip'              => '36100',
                'city'             => 'Petersberg',
                'state'            => 'HE',
                'country'          => 'DE',
                'latitude'         => 50.57810,
                'longitude'        => 9.72150,
                'opening_hours'    => Station::defaultOpeningHours(),
                'num_pumps'        => 6,
                'has_car_wash'     => false,
                'has_shop'         => true,
                'shop_type'        => 'Convenience',
                'fuel_types'       => ['super_e5', 'e10', 'diesel', 'adblue'],
                'additional_businesses' => ['lotto'],
                'services'         => ['air', 'vacuum'],
                'ownership_type'   => 'DOFO',
                'is_active'        => true,
            ]
        );

        session()->forget('tenant_id');

        $this->command->info('✅ 2 Teststationen angelegt (Fulda + Petersberg)');
    }
}
