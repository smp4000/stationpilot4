<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Note: The old `competitors` JSON column on gas_stations is intentionally
        // left in place for safety. Stop reading from it in application code.

        $stations = DB::table('gas_stations')
            ->whereNotNull('competitors')
            ->where('competitors', '!=', '[]')
            ->where('competitors', '!=', '')
            ->get(['id', 'tenant_id', 'competitors']);

        foreach ($stations as $station) {
            $list = json_decode($station->competitors, true);
            if (!is_array($list)) continue;
            foreach ($list as $c) {
                if (($c['_del'] ?? '0') === '1') continue;
                if (empty($c['name'])) continue;
                DB::table('station_competitors')->insert([
                    'station_id'  => $station->id,
                    'tenant_id'   => $station->tenant_id,
                    'name'        => $c['name'],
                    'brand'       => $c['brand']       ?? null,
                    'street'      => $c['street']      ?? null,
                    'city'        => $c['city']        ?? null,
                    'distance_km' => isset($c['distance_km']) ? (float)$c['distance_km'] : null,
                    'lat'         => isset($c['lat'])  && $c['lat']  ? (float)$c['lat']  : null,
                    'lng'         => isset($c['lng'])  && $c['lng']  ? (float)$c['lng']  : null,
                    'notes'       => $c['notes']       ?? null,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('station_competitors')->truncate();
    }
};
