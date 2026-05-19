<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sucht Tankstellen (amenity=fuel) über die OpenStreetMap Overpass API.
 * Liefert exakte OSM-Koordinaten des Gebäude-Mittelpunkts.
 */
class OverpassService
{
    private const OVERPASS_ENDPOINT = 'https://overpass-api.de/api/interpreter';
    private const NOMINATIM_ENDPOINT = 'https://nominatim.openstreetmap.org/search';
    private const USER_AGENT = 'Stationpilot/4.0 (contact@stationpilot.de)';
    private const TIMEOUT = 15;

    /**
     * Sucht Tankstellen im Umkreis einer PLZ.
     * Strategie 1: PLZ-Boundary-Suche via Overpass
     * Strategie 2: Nominatim-Mittelpunkt + Radius-Suche (Fallback)
     *
     * @return array<int, array{osm_id: int, name: string, brand: string|null, lat: float, lng: float, street: string, house_number: string, zip: string, city: string}>
     */
    public function searchFuelStationsByZip(string $zip, int $radius = 8000): array
    {
        if (! preg_match('/^\d{5}$/', $zip)) {
            return [];
        }

        // Strategie 1: PLZ-Boundary-Suche
        $results = $this->searchByPlzBoundary($zip);

        // Strategie 2: Fallback via Nominatim-Mittelpunkt + Radius
        if (empty($results)) {
            $center = $this->resolvePlzCenter($zip);
            if ($center) {
                $results = $this->searchByRadius($center['lat'], $center['lng'], $radius);
            }
        }

        usort($results, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $results;
    }

    // ─────────────────────────────────────────────
    // Private Methoden
    // ─────────────────────────────────────────────

    private function searchByPlzBoundary(string $zip): array
    {
        $query = <<<OVERPASS
[out:json][timeout:15];
area["postal_code"="{$zip}"]["boundary"="postal_code"];
(
  node["amenity"="fuel"](area);
  way["amenity"="fuel"](area);
);
out center;
OVERPASS;

        return $this->runQuery($query, "PLZ:{$zip}");
    }

    private function searchByRadius(float $lat, float $lng, int $radius): array
    {
        $query = <<<OVERPASS
[out:json][timeout:15];
(
  node["amenity"="fuel"](around:{$radius},{$lat},{$lng});
  way["amenity"="fuel"](around:{$radius},{$lat},{$lng});
);
out center;
OVERPASS;

        return $this->runQuery($query, "radius:{$radius}m@{$lat},{$lng}");
    }

    private function runQuery(string $query, string $context): array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(self::TIMEOUT)
                ->post(self::OVERPASS_ENDPOINT, ['data' => $query]);

            if (! $response->successful()) {
                Log::warning('OverpassService: HTTP-Fehler', ['status' => $response->status(), 'context' => $context]);
                return [];
            }

            return $this->parseElements($response->json('elements', []));
        } catch (\Throwable $e) {
            Log::warning('OverpassService: Anfrage fehlgeschlagen', ['context' => $context, 'error' => $e->getMessage()]);
            return [];
        }
    }

    private function resolvePlzCenter(string $zip): ?array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(5)
                ->get(self::NOMINATIM_ENDPOINT, [
                    'postalcode' => $zip,
                    'country'    => 'de',
                    'format'     => 'json',
                    'limit'      => 1,
                ]);

            if ($response->successful() && ! empty($response->json())) {
                return [
                    'lat' => (float) $response->json()[0]['lat'],
                    'lng' => (float) $response->json()[0]['lon'],
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('OverpassService: Nominatim-Fehler', ['zip' => $zip, 'error' => $e->getMessage()]);
        }

        return null;
    }

    private function parseElements(array $elements): array
    {
        $stations = [];

        foreach ($elements as $el) {
            $tags = $el['tags'] ?? [];

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
                'zip'          => $tags['addr:postcode'] ?? '',
                'city'         => $tags['addr:city'] ?? $tags['addr:suburb'] ?? '',
            ];
        }

        return $stations;
    }
}
