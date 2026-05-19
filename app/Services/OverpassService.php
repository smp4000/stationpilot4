<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sucht Tankstellen über die OpenStreetMap Overpass API.
 * Keine API-Key nötig, kostenlos, zuverlässig.
 */
class OverpassService
{
    private const ENDPOINT   = 'https://overpass-api.de/api/interpreter';
    private const USER_AGENT = 'Stationpilot/4.0 (contact@stationpilot.de)';
    private const TIMEOUT    = 15;

    /**
     * Sucht alle Tankstellen (amenity=fuel) in einem PLZ-Gebiet.
     *
     * @return array<int, array{
     *   osm_id: int,
     *   name: string,
     *   brand: string|null,
     *   lat: float,
     *   lng: float,
     *   street: string,
     *   house_number: string,
     *   zip: string,
     *   city: string,
     * }>
     */
    public function searchFuelStationsByZip(string $zip): array
    {
        if (! preg_match('/^\d{5}$/', $zip)) {
            return [];
        }

        // Overpass QL: Alle Fuel-Nodes im PLZ-Gebiet
        $query = <<<OVERPASS
[out:json][timeout:15];
area["postal_code"="{$zip}"]["boundary"="postal_code"];
(
  node["amenity"="fuel"](area);
  way["amenity"="fuel"](area);
);
out center;
OVERPASS;

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(self::TIMEOUT)
                ->post(self::ENDPOINT, ['data' => $query]);

            if (! $response->successful()) {
                Log::warning('OverpassService: HTTP-Fehler', ['status' => $response->status(), 'zip' => $zip]);
                return [];
            }

            return $this->parseElements($response->json('elements', []), $zip);
        } catch (\Throwable $e) {
            Log::warning('OverpassService: Anfrage fehlgeschlagen', ['zip' => $zip, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * @param  array<mixed>  $elements
     * @return array<int, array{osm_id: int, name: string, brand: string|null, lat: float, lng: float, street: string, house_number: string, zip: string, city: string}>
     */
    private function parseElements(array $elements, string $fallbackZip): array
    {
        $stations = [];

        foreach ($elements as $el) {
            $tags = $el['tags'] ?? [];

            // Koordinaten: node hat lat/lon, way hat center
            $lat = (float) ($el['lat'] ?? $el['center']['lat'] ?? 0);
            $lng = (float) ($el['lon'] ?? $el['center']['lon'] ?? 0);

            if (! $lat || ! $lng) {
                continue;
            }

            $name = $tags['name'] ?? $tags['brand'] ?? 'Unbekannte Tankstelle';

            $stations[] = [
                'osm_id'       => (int) ($el['id'] ?? 0),
                'name'         => $name,
                'brand'        => $tags['brand'] ?? null,
                'lat'          => round($lat, 8),
                'lng'          => round($lng, 8),
                'street'       => $tags['addr:street'] ?? '',
                'house_number' => $tags['addr:housenumber'] ?? '',
                'zip'          => $tags['addr:postcode'] ?? $fallbackZip,
                'city'         => $tags['addr:city'] ?? $tags['addr:suburb'] ?? '',
            ];
        }

        // Sortierung: named stations first, then alphabetical
        usort($stations, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $stations;
    }
}
