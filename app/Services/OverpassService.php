<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sucht Tankstellen (amenity=fuel) über die OpenStreetMap Overpass API.
 * Liefert exakte OSM-Koordinaten und reichert fehlende Adressen via Nominatim an.
 */
class OverpassService
{
    private const OVERPASS_ENDPOINT  = 'https://overpass-api.de/api/interpreter';
    private const NOMINATIM_ENDPOINT = 'https://nominatim.openstreetmap.org/search';
    private const NOMINATIM_LOOKUP   = 'https://nominatim.openstreetmap.org/lookup';
    private const USER_AGENT         = 'Stationpilot/4.0 (contact@stationpilot.de)';
    private const TIMEOUT            = 15;

    /**
     * Sucht Tankstellen im Umkreis einer PLZ.
     * Strategie: PLZ-Mittelpunkt (Nominatim) + Radius-Suche (20 km).
     * Zusätzlich PLZ-Boundary-Suche — beide Ergebnisse werden zusammengeführt.
     * Anschließend werden fehlende Adressen via Nominatim Lookup angereichert.
     *
     * @return array<int, array{osm_id: int, osm_type: string, name: string, brand: string|null, lat: float, lng: float, street: string, house_number: string, zip: string, city: string}>
     */
    public function searchFuelStationsByZip(string $zip, int $radius = 15000): array
    {
        if (! preg_match('/^\d{5}$/', $zip)) {
            return [];
        }

        // PLZ-Mittelpunkt via Nominatim ermitteln, dann Radius-Suche via Overpass.
        // Die PLZ-Boundary-Suche via Overpass wird nicht mehr verwendet,
        // da sie häufig in 504-Timeouts läuft.
        $center = $this->resolvePlzCenter($zip);

        if (! $center) {
            Log::warning('OverpassService: PLZ-Mittelpunkt nicht gefunden', ['zip' => $zip]);
            return [];
        }

        $results = $this->searchByRadius($center['lat'], $center['lng'], $radius);

        if (empty($results)) {
            return [];
        }

        // Fehlende Adressen via Nominatim Lookup anreichern
        $enriched = $this->enrichAddresses(array_values(
            collect($results)->keyBy('osm_id')->toArray()
        ));

        usort($enriched, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $enriched;
    }

    /**
     * Sucht Tankstellen direkt per Koordinaten + Radius (öffentlich).
     */
    public function searchNearby(float $lat, float $lng, int $radius = 400): array
    {
        return $this->searchByRadius($lat, $lng, $radius);
    }

    // ─────────────────────────────────────────────
    // Private Methoden
    // ─────────────────────────────────────────────

    private function searchByRadius(float $lat, float $lng, int $radius): array
    {
        $latF = number_format($lat, 8, '.', '');
        $lngF = number_format($lng, 8, '.', '');

        $query = <<<OVERPASS
[out:json][timeout:15];
(
  node["amenity"="fuel"](around:{$radius},{$latF},{$lngF});
  way["amenity"="fuel"](around:{$radius},{$latF},{$lngF});
);
out center;
OVERPASS;

        return $this->runQuery($query, "radius:{$radius}m@{$latF},{$lngF}");
    }

    private function runQuery(string $query, string $context): array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(self::TIMEOUT)
                ->asForm()
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
                'osm_type'     => $el['type'] ?? 'node',
                'name'         => $name,
                'brand'        => $tags['brand'] ?? null,
                'lat'          => round($lat, 8),
                'lng'          => round($lng, 8),
                'street'       => $tags['addr:street'] ?? '',
                'house_number' => $tags['addr:housenumber'] ?? '',
                'zip'          => $tags['addr:postcode'] ?? '',
                'city'         => $tags['addr:city'] ?? $tags['addr:suburb'] ?? '',
                'opening_hours'=> $tags['opening_hours'] ?? null,
            ];
        }

        return $stations;
    }

    /**
     * Reichert Stationen ohne Adresse via Nominatim Lookup an.
     * Nominatim akzeptiert mehrere IDs in einem Request: ?osm_ids=N123,W456
     */
    private function enrichAddresses(array $stations): array
    {
        $missing = array_filter($stations, fn ($s) => empty($s['street']) && empty($s['city']));

        if (empty($missing)) {
            return $stations;
        }

        // OSM-IDs formatieren: N für node, W für way, R für relation
        $typeMap = ['node' => 'N', 'way' => 'W', 'relation' => 'R'];
        $ids = collect($missing)
            ->map(fn ($s) => ($typeMap[$s['osm_type']] ?? 'N') . $s['osm_id'])
            ->implode(',');

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(10)
                ->get(self::NOMINATIM_LOOKUP, [
                    'osm_ids'          => $ids,
                    'format'           => 'json',
                    'addressdetails'   => 1,
                ]);

            if (! $response->successful()) {
                return $stations;
            }

            // Lookup-Ergebnisse indexiert nach osm_id
            $lookup = collect($response->json())->keyBy('osm_id');
        } catch (\Throwable $e) {
            Log::warning('OverpassService: Nominatim-Lookup fehlgeschlagen', ['error' => $e->getMessage()]);
            return $stations;
        }

        // Adressen einsetzen
        return array_map(function ($s) use ($lookup) {
            if (! empty($s['street']) || ! empty($s['city'])) {
                return $s; // bereits vollständig
            }

            $info = $lookup->get($s['osm_id']);
            if (! $info) {
                return $s;
            }

            $addr = $info['address'] ?? [];

            $s['street']       = $addr['road'] ?? $addr['pedestrian'] ?? $addr['footway'] ?? $s['street'];
            $s['house_number'] = $addr['house_number'] ?? $s['house_number'];
            $s['zip']          = $addr['postcode'] ?? $s['zip'];
            $s['city']         = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? $addr['suburb'] ?? $s['city'];

            return $s;
        }, $stations);
    }
}
