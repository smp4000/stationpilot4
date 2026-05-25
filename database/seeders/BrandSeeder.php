<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Aral',                  'slug' => 'aral',                  'color' => '#003B95', 'sort_order' => 1],
            ['name' => 'Shell',                  'slug' => 'shell',                 'color' => '#DD1D21', 'sort_order' => 2],
            ['name' => 'TotalEnergies',          'slug' => 'totalenergies',         'color' => '#ED2939', 'sort_order' => 3],
            ['name' => 'ESSO',                   'slug' => 'esso',                  'color' => '#003087', 'sort_order' => 4],
            ['name' => 'JET',                    'slug' => 'jet',                   'color' => '#E2001A', 'sort_order' => 5],
            ['name' => 'ENI (Agip)',             'slug' => 'eni-agip',              'color' => '#FFD100', 'sort_order' => 6],
            ['name' => 'OMV',                    'slug' => 'omv',                   'color' => '#E2001A', 'sort_order' => 7],
            ['name' => 'Orlen (Star)',           'slug' => 'orlen-star',            'color' => '#D32011', 'sort_order' => 8],
            ['name' => 'Westfalen',              'slug' => 'westfalen',             'color' => '#003F87', 'sort_order' => 9],
            ['name' => 'HEM',                    'slug' => 'hem',                   'color' => '#E20025', 'sort_order' => 10],
            ['name' => 'OIL!',                   'slug' => 'oil',                   'color' => '#E2001A', 'sort_order' => 11],
            ['name' => 'Sprint',                 'slug' => 'sprint',                'color' => null,      'sort_order' => 12],
            ['name' => 'bft',                    'slug' => 'bft',                   'color' => '#005CA9', 'sort_order' => 13],
            ['name' => 'AVIA',                   'slug' => 'avia',                  'color' => '#003087', 'sort_order' => 14],
            ['name' => 'Q1',                     'slug' => 'q1',                    'color' => null,      'sort_order' => 15],
            ['name' => 'Raiffeisen',             'slug' => 'raiffeisen',            'color' => '#FFD100', 'sort_order' => 16],
            ['name' => 'Globus',                 'slug' => 'globus',                'color' => '#E2001A', 'sort_order' => 17],
            ['name' => 'classic',                'slug' => 'classic',               'color' => null,      'sort_order' => 18],
            ['name' => 'Calpam',                 'slug' => 'calpam',                'color' => null,      'sort_order' => 19],
            ['name' => 'Hoyer',                  'slug' => 'hoyer',                 'color' => null,      'sort_order' => 20],
            ['name' => 'Nordoel',                'slug' => 'nordoel',               'color' => null,      'sort_order' => 21],
            ['name' => 'Hessol',                 'slug' => 'hessol',                'color' => null,      'sort_order' => 22],
            ['name' => 'go',                     'slug' => 'go',                    'color' => null,      'sort_order' => 23],
            ['name' => 'Gulf',                   'slug' => 'gulf',                  'color' => '#F47920', 'sort_order' => 24],
            ['name' => 'SB Tankstelle',          'slug' => 'sb-tankstelle',         'color' => null,      'sort_order' => 25],
            ['name' => 'team',                   'slug' => 'team',                  'color' => null,      'sort_order' => 26],
            ['name' => 'BayWa',                  'slug' => 'baywa',                 'color' => '#009640', 'sort_order' => 27],
            ['name' => 'Roth',                   'slug' => 'roth',                  'color' => null,      'sort_order' => 28],
            ['name' => 'Supermarkt-Tankstelle',  'slug' => 'supermarkt-tankstelle', 'color' => null,      'sort_order' => 29],
            ['name' => 'Freie Tankstelle',       'slug' => 'freie-tankstelle',      'color' => null,      'sort_order' => 99],
        ];

        foreach ($brands as $data) {
            Brand::firstOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
