<?php

namespace App\Services;

use App\Models\Station;
use App\Models\StationFuelPrice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BenzinpreisService
{
    private const BASE_URL   = 'https://www.benzinpreis-aktuell.de/';
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    public function __construct(private ?BenzinpreisParser $parser = null)
    {
        $this->parser ??= new BenzinpreisParser();
    }

    /**
     * Fetch current prices for a station via BenzinpreisParser and persist them.
     * Returns the new StationFuelPrice record, or null if no prices could be retrieved.
     */
    public function fetchAndStore(Station $station): ?StationFuelPrice
    {
        $prices = null;

        if ($station->benzinpreis_hash) {
            // Try JSON API first (fastest) — needs mts_uuid from station page
            $stationData = $this->parser->fetchStation(
                $station->benzinpreis_hash,
                $station->benzinpreis_slug ?? ''
            );

            if ($stationData && $stationData['mts_uuid']) {
                $apiPrices = $this->parser->fetchPriceByApi($stationData['mts_uuid']);
                if ($apiPrices) {
                    $prices = [
                        'e5'     => $apiPrices['e5'],
                        'e10'    => $apiPrices['e10'],
                        'diesel' => $apiPrices['diesel'],
                        'source' => 'api',
                    ];
                }
            }

            // Fallback: scrape HTML prices from already-fetched station data
            if (! $prices && $stationData && ! empty($stationData['prices'])) {
                $p      = $stationData['prices'];
                $prices = [
                    'e5'     => isset($p['benzin']) ? (float) $p['benzin'] : (isset($p['e5'])     ? (float) $p['e5']     : null),
                    'e10'    => isset($p['e10'])     ? (float) $p['e10']   : null,
                    'diesel' => isset($p['diesel'])  ? (float) $p['diesel'] : null,
                    'source' => 'scraper',
                ];
            }
        }

        if (! $prices || (! $prices['e5'] && ! $prices['diesel'])) {
            return null;
        }

        // ── Price-Change Detection ─────────────────────────────────────────
        // Only persist a new record when at least one price actually changed.
        $lastPrice = StationFuelPrice::where('station_id', $station->id)
            ->latest('recorded_at')
            ->first();

        if ($lastPrice) {
            $hasChanged = false;
            foreach (['e5', 'e10', 'diesel'] as $fuel) {
                $oldVal = round((float) ($lastPrice->{$fuel} ?? 0), 3);
                $newVal = round((float) ($prices[$fuel] ?? 0), 3);
                if ($newVal > 0 && abs($oldVal - $newVal) > 0.0001) {
                    $hasChanged = true;
                    break;
                }
            }

            if (! $hasChanged) {
                // Touch timestamp so we know we checked, but skip the insert
                $station->updateQuietly(['prices_updated_at' => now()]);
                return null; // null = "no change" (not an error)
            }
        }

        $record = StationFuelPrice::create([
            'station_id'  => $station->id,
            'e5'          => $prices['e5'] ?? null,
            'e10'         => $prices['e10'] ?? null,
            'diesel'      => $prices['diesel'] ?? null,
            'lpg'         => $prices['lpg'] ?? null,
            'source'      => $prices['source'],
            'recorded_at' => now(),
        ]);

        // Keep station's current prices in sync
        $station->update([
            'price_super'        => $prices['e5']     ?? $station->price_super,
            'price_e10'          => $prices['e10']    ?? $station->price_e10,
            'price_diesel'       => $prices['diesel'] ?? $station->price_diesel,
            'prices_updated_at'  => now(),
        ]);

        return $record;
    }

    /**
     * Sucht Tankstellen im Umkreis einer PLZ.
     * Radius via Setting konfigurierbar (Standard: 20 km).
     *
     * @return array<int, array{hash: string, slug: string, name: string, street: string, city: string, price: ?string, fuel_type: string}>
     */
    public function searchByPlz(string $plz, int $radius = 20): array
    {
        if (strlen($plz) !== 5 || ! ctype_digit($plz)) {
            return [];
        }

        return Cache::remember("bp_plz{$radius}_{$plz}", now()->addMinutes(15), function () use ($plz, $radius) {
            $place = $this->resolvePlace($plz);
            if (! $place) {
                return [];
            }

            $candidates = [$place['city'], ...$place['fallbacks']];
            $result = null;

            foreach ($candidates as $city) {
                $slug   = $this->cityToSlug($city);
                $result = $this->fetchPlzSearchPage($plz, $slug, $radius);
                if ($result && ! empty($result['stations'])) {
                    break;
                }
                $result = null;
            }

            if (! $result) {
                return [];
            }

            $stations = array_values($result['stations']);
            usort($stations, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

            return $stations;
        });
    }

    /**
     * Laedt die Detailseite einer Tankstelle und gibt alle Daten zurueck.
     *
     * @return array{name: string, brand: string, street: string, house_number: string, zip: string, city: string, lat: ?float, lng: ?float, opening_hours: array, prices: array}|null
     */
    public function fetchStationDetails(string $hash, string $slug): ?array
    {
        return Cache::remember("bp_station_{$hash}", now()->addMinutes(30), function () use ($hash, $slug) {
            $url = self::BASE_URL . "preise-t{$hash}-{$slug}";

            try {
                $response = Http::withoutVerifying()->timeout(12)
                    ->withUserAgent(self::USER_AGENT)
                    ->withHeaders(['Accept-Language' => 'de-DE,de;q=0.9'])
                    ->get($url);

                if (! $response->successful()) {
                    Log::warning("BenzinpreisService: HTTP {$response->status()} fuer {$url}");
                    return null;
                }

                return $this->parseStationPage($response->body());
            } catch (\Exception $e) {
                Log::error("BenzinpreisService: Fehler beim Laden von {$url}: {$e->getMessage()}");
                return null;
            }
        });
    }

    /**
     * Crawl outward from a station's benzinpreis page to discover nearby competitor stations.
     * Level 0 = own page; Level 1 = neighbors of own page (~10km); Level 2 = their neighbors (~20km).
     *
     * @param  int  $levels    Number of crawl levels (1 = ~10km, 2 = ~20km)
     * @param  int  $radiusKm  Hard cut-off: discard stations beyond this distance
     * @return array<int, array{hash:string,slug:string,name:string,brand:string,street:string,city:string,zip:string,lat:?float,lng:?float,distance_km:?float}>
     */
    public function discoverNeighbors(Station $station, int $levels = 1, int $radiusKm = 15): array
    {
        if (! $station->benzinpreis_hash || ! $station->benzinpreis_slug) {
            return [];
        }

        $ownLat   = (float) ($station->latitude ?? 0);
        $ownLng   = (float) ($station->longitude ?? 0);
        $seedHash = $station->benzinpreis_hash;
        $seedSlug = $station->benzinpreis_slug;

        /** @var array<string, string>  hash => slug for stations yet to crawl */
        $toCrawl  = [$seedHash => $seedSlug];
        /** @var array<string, bool>    hash => true for already-crawled pages */
        $crawled  = [];
        /** @var array<string, array{slug:string,name:string}>  all discovered hashes */
        $found    = [];

        for ($level = 0; $level <= $levels; $level++) {
            foreach ($toCrawl as $hash => $slug) {
                if (isset($crawled[$hash])) continue;
                $crawled[$hash] = true;

                $url  = self::BASE_URL . "preise-t{$hash}-{$slug}";
                $html = $this->parser->fetchUrl($url, 12);
                if (! $html) { usleep(300_000); continue; }

                foreach ($this->parser->extractNeighbors($html) as $nHash => $nData) {
                    if (! isset($found[$nHash])) {
                        $found[$nHash] = $nData;
                    }
                }

                usleep(400_000); // ~0.4s between requests
            }

            // Prepare next level: all not-yet-crawled discovered stations
            if ($level < $levels) {
                $toCrawl = [];
                foreach ($found as $hash => $data) {
                    if (! isset($crawled[$hash])) {
                        $toCrawl[$hash] = $data['slug'];
                    }
                }
            }
        }

        // Remove own station from results
        unset($found[$seedHash]);

        // Fetch details + apply distance filter
        $result = [];

        foreach ($found as $hash => $info) {
            $slug    = $info['slug'];
            $details = $this->fetchStationDetails($hash, $slug);

            $street = trim(($details['street'] ?? '') . ' ' . ($details['house_number'] ?? ''));
            $city   = $details['city'] ?? '';
            $zip    = $details['zip'] ?? '';

            // Prefer Nominatim geocoding over benzinpreis meta-coords (BP lat/lng are often wrong)
            $lat = null;
            $lng = null;

            if ($street && $city) {
                $geoCoords = $this->geocodeAddress($street, $city, $zip);
                if ($geoCoords) {
                    [$lat, $lng] = $geoCoords;
                    usleep(1_100_000); // Nominatim rate-limit: max 1 req/s
                }
            }

            // Fallback: use benzinpreis meta-coords
            if (! $lat && ! $lng) {
                $lat = $details['lat'] ?? null;
                $lng = $details['lng'] ?? null;
            }

            $distKm = null;

            if ($lat && $lng && $ownLat && $ownLng) {
                $dLat   = deg2rad($lat - $ownLat);
                $dLng   = deg2rad($lng - $ownLng);
                $a      = sin($dLat / 2) ** 2 + cos(deg2rad($ownLat)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;
                $distKm = round(6371 * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);

                if ($distKm > $radiusKm) {
                    usleep(200_000);
                    continue;
                }
            }

            $result[] = [
                'hash'        => $hash,
                'slug'        => $slug,
                'name'        => $details['name'] ?? ($info['name'] ?: $slug),
                'brand'       => $details['brand'] ?? '',
                'street'      => $street,
                'city'        => $city,
                'zip'         => $zip,
                'lat'         => $lat,
                'lng'         => $lng,
                'distance_km' => $distKm,
            ];

            usleep(400_000);
        }

        usort($result, fn ($a, $b) => ($a['distance_km'] ?? 999) <=> ($b['distance_km'] ?? 999));

        return $result;
    }

    private function resolvePlace(string $plz): ?array
    {
        try {
            $response = Http::withoutVerifying()->timeout(5)
                ->withUserAgent(self::USER_AGENT)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'postalcode'    => $plz,
                    'country'       => 'de',
                    'format'        => 'json',
                    'limit'         => 1,
                    'addressdetails'=> 1,
                ]);

            if (! $response->successful() || empty($response->json())) {
                return null;
            }

            $address = $response->json()[0]['address'] ?? [];
            $city    = $address['city'] ?? $address['town'] ?? $address['village'] ?? null;

            $fallbacks = [];
            if (! empty($address['municipality']) && $address['municipality'] !== $city) {
                $fallbacks[] = $address['municipality'];
            }
            $county = $address['county'] ?? '';
            if ($county && preg_match('/^(?:Landkreis|Kreis)\s+(.+)$/i', $county, $cm)) {
                $countyCity = trim($cm[1]);
                if ($countyCity !== $city && ! in_array($countyCity, $fallbacks)) {
                    $fallbacks[] = $countyCity;
                }
            }

            if ($city) {
                return ['city' => $city, 'fallbacks' => $fallbacks];
            }

            // Fallback: display_name parsen
            $displayName = $response->json()[0]['display_name'] ?? '';
            foreach (explode(',', $displayName) as $part) {
                $part = trim($part);
                if ($part && ! is_numeric($part) && strlen($part) > 2 && $part !== 'Deutschland') {
                    return ['city' => $part, 'fallbacks' => $fallbacks];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("BenzinpreisService: Nominatim-Fehler: {$e->getMessage()}");
            return null;
        }
    }

    private function fetchPlzSearchPage(string $plz, string $citySlug, int $radius): ?array
    {
        $url = self::BASE_URL . "{$plz}-{$citySlug}-aktuelle-benzinpreise?umkreis={$radius}";

        try {
            $response = Http::withoutVerifying()->timeout(15)
                ->withUserAgent(self::USER_AGENT)
                ->withHeaders(['Accept-Language' => 'de-DE,de;q=0.9'])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $html = $response->body();

            if (preg_match('/<title>[^<]*(?:nicht gefunden|404|401)/i', $html)) {
                return null;
            }

            return $this->parseStationBlocks($html);
        } catch (\Exception $e) {
            Log::error("BenzinpreisService: PLZ-Suche Fehler fuer {$plz}-{$citySlug}: {$e->getMessage()}");
            return null;
        }
    }

    private function parseStationBlocks(string $html): array
    {
        $stations = [];

        if (preg_match_all(
            '/<div id="station-t([a-f0-9]+)-([^"]+)"[^>]*>(.+?)(?=<div id="station-t|<div class="sxi"|$)/s',
            $html,
            $blocks,
            PREG_SET_ORDER
        )) {
            foreach ($blocks as $block) {
                $hash     = $block[1];
                $slug     = rtrim($block[2], '/');
                $blockHtml = $block[3];

                if (isset($stations[$hash])) {
                    continue;
                }

                if (! preg_match('/<strong class="isstrong">([^<]+)<\/strong><br>\s*([^<]+)/s', $blockHtml, $nm)) {
                    continue;
                }

                $name   = trim(html_entity_decode($nm[1], ENT_QUOTES, 'UTF-8'));
                $street = trim(html_entity_decode($nm[2], ENT_QUOTES, 'UTF-8'));

                if (! $name || ! preg_match('/Tankstelle/i', $name)) {
                    continue;
                }

                $city = '';
                if (preg_match('/Tankstelle\s+(.+)$/i', $name, $cm)) {
                    $city = trim($cm[1]);
                }

                $price    = null;
                $fuelType = '';
                if (preg_match('/<em\s+title="([^"]*)">([\d.]+)<\/em><sup>(\d)<\/sup>/i', $blockHtml, $pm)) {
                    $price = $pm[2] . $pm[3];
                    if (preg_match('/(E10|Diesel|Super\s*E5|Super\s*Plus|Super)\s*-?\s*Preis/i', $pm[1], $ft)) {
                        $fuelType = trim($ft[1]);
                    }
                }

                $stations[$hash] = [
                    'hash'      => $hash,
                    'slug'      => $slug,
                    'name'      => $name,
                    'street'    => $street,
                    'city'      => $city,
                    'price'     => $price,
                    'fuel_type' => $fuelType,
                ];
            }
        }

        return ['stations' => $stations];
    }

    private function parseStationPage(string $html): ?array
    {
        $result = [
            'name'          => '',
            'brand'         => '',
            'street'        => '',
            'house_number'  => '',
            'zip'           => '',
            'city'          => '',
            'lat'           => null,
            'lng'           => null,
            'opening_hours' => [],
            'prices'        => [],
        ];

        if (preg_match('/Spritpreise\s+(\S+)\s+in\s+(\S+)/i', $html, $tm)) {
            $result['brand'] = strip_tags(trim($tm[1]));
            $result['city']  = rtrim(strip_tags(trim($tm[2])), ':.,;');
        }

        $streetSuffix = 'stra(?:ß|ss)e|str\.|weg|platz|gasse|berg|ring|allee|damm|chaussee|hof|steig';

        if (preg_match('/([A-ZÄÖÜa-zäöüß][^<>]{0,80}?(?:' . $streetSuffix . '))\s+(\d[\d\s\-\/a-z]*?)\s*<br>\s*(\d{5})\s+([A-ZÄÖÜa-zäöüß][^<>]{0,60}?)\s*</iu', $html, $am)) {
            $result['street']       = trim($am[1]);
            $result['house_number'] = trim($am[2]);
            $result['zip']          = trim($am[3]);
            $result['city']         = trim($am[4]);
        } elseif (preg_match('/([A-ZÄÖÜa-zäöüß][^<>]{0,80}?(?:' . $streetSuffix . '))\s*<br>\s*(\d{5})\s+([A-ZÄÖÜa-zäöüß][^<>]{0,60}?)\s*</iu', $html, $am)) {
            $result['street'] = trim($am[1]);
            $result['zip']    = trim($am[2]);
            $result['city']   = trim($am[3]);
        }

        if (! $result['street'] && preg_match('/Wo finde ich die Tankstelle\?\s*<\/h2>\s*<p[^>]*>\s*(.+?)\s+(\d[\d\s\-\/a-z]*?)\s*<br>\s*(\d{5})\s+([^<]+)/iu', $html, $am)) {
            $result['street']       = trim($am[1]);
            $result['house_number'] = trim($am[2]);
            $result['zip']          = trim($am[3]);
            $result['city']         = trim($am[4]);
        }

        if (! $result['street'] && preg_match('/Wo finde ich die Tankstelle\?\s*<\/h2>\s*<p[^>]*>\s*(.+?)\s*<br>\s*(\d{5})\s+([^<]+)/iu', $html, $am)) {
            $result['street'] = trim($am[1]);
            $result['zip']    = trim($am[2]);
            $result['city']   = trim($am[3]);
        }

        if (! $result['street'] && preg_match('/daddr=([^&"\']+)/i', $html, $gm)) {
            $parts = array_values(array_filter(
                array_map(fn ($p) => urldecode(trim($p)), explode('+', $gm[1])),
                fn ($p) => $p !== ''
            ));
            foreach ($parts as $i => $part) {
                if (preg_match('/^\d{5}$/', $part)) {
                    $streetStr = implode(' ', array_slice($parts, 0, $i));
                    if (preg_match('/(?:Tankstelle\s+\S+\s+)(.+)$/i', $streetStr, $sp)) {
                        $streetStr = $sp[1];
                    }
                    $result['street'] = trim($streetStr);
                    $result['zip']    = $part;
                    $result['city']   = trim(implode(' ', array_slice($parts, $i + 1)));
                    break;
                }
            }
            if ($result['street'] && preg_match('/^(.+?)\s+(\d[\d\s\-\/a-z]*)$/iu', $result['street'], $hn)) {
                $result['street']       = trim($hn[1]);
                $result['house_number'] = trim($hn[2]);
            }
        }

        if (preg_match('/property="place:location:latitude"\s+content="([\d.]+)"/i', $html, $latM)) {
            $result['lat'] = round((float) $latM[1], 8);
        }
        if (preg_match('/property="place:location:longitude"\s+content="([\d.]+)"/i', $html, $lngM)) {
            $result['lng'] = round((float) $lngM[1], 8);
        }

        $dayMap = [
            'Montag' => 'monday', 'Dienstag' => 'tuesday', 'Mittwoch' => 'wednesday',
            'Donnerstag' => 'thursday', 'Freitag' => 'friday',
            'Samstag' => 'saturday', 'Sonntag' => 'sunday',
        ];

        if (preg_match_all('/<p\s+class="e-otimes[^"]*">\s*<em>([^<]+)<\/em>\s*<span>([^<]+)<\/span>/i', $html, $hm, PREG_SET_ORDER)) {
            foreach ($hm as $h) {
                $dayEn = $dayMap[trim($h[2])] ?? null;
                if ($dayEn && preg_match('/(\d{2}:\d{2})\s*bis\s*(\d{2}:\d{2})/', trim($h[1]), $tp)) {
                    $result['opening_hours'][$dayEn] = ['open' => $tp[1], 'close' => $tp[2]];
                }
            }
        }

        if (preg_match('/<title>\s*([^|<]+)/i', $html, $titleM)) {
            $fullTitle = trim($titleM[1]);
            if (preg_match('/^(\S+)\s+(?:Tankstelle|Preise)\s+(\S+)/i', $fullTitle, $ntm)) {
                $result['name'] = rtrim(trim($ntm[1]), ':.,') . ' ' . rtrim(trim($ntm[2]), ':.,');
            }
        }

        if (! $result['name']) {
            $result['name'] = trim(($result['brand'] ?: '') . ' ' . ($result['city'] ?: ''));
        }

        if (preg_match_all('/<div[^>]*class="[^"]*preis25[^"]*preis_(\w+)[^"]*"[^>]*>\s*<em>([\d,]+)<sup>(\d)<\/sup><\/em>/si', $html, $pm, PREG_SET_ORDER)) {
            foreach ($pm as $p) {
                $fuelName = match (strtolower(trim($p[1]))) {
                    'benzin' => 'Super',
                    'e10'    => 'E10',
                    'diesel' => 'Diesel',
                    default  => ucfirst(strtolower($p[1])),
                };
                $result['prices'][$fuelName] = str_replace(',', '.', $p[2]) . $p[3];
            }
        }

        $result['city'] = rtrim($result['city'], ':.,;');

        if ($result['name'] && $result['street']) {
            $result['name'] .= ' · ' . $result['street'];
            if ($result['house_number']) {
                $result['name'] .= ' ' . $result['house_number'];
            }
        }

        return $result;
    }

    /**
     * Geocode a street address via Nominatim.
     * Returns [lat, lng] or null on failure.
     *
     * @return array{float, float}|null
     */
    public function geocodeAddress(string $street, string $city, string $zip = ''): ?array
    {
        $query = trim($street . ', ' . ($zip ? $zip . ' ' : '') . $city . ', Deutschland');

        try {
            $response = Http::withoutVerifying()->timeout(5)
                ->withUserAgent(self::USER_AGENT)
                ->withHeaders(['Accept-Language' => 'de-DE,de;q=0.9'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q'            => $query,
                    'format'       => 'json',
                    'limit'        => 1,
                    'countrycodes' => 'de',
                ]);

            $results = $response->json() ?? [];

            if (! empty($results[0]['lat'])) {
                return [round((float) $results[0]['lat'], 8), round((float) $results[0]['lon'], 8)];
            }
        } catch (\Exception $e) {
            Log::warning("BenzinpreisService: Geocoding-Fehler für '{$query}': " . $e->getMessage());
        }

        return null;
    }

    private function cityToSlug(string $city): string
    {
        $slug = mb_strtolower(trim($city));
        $slug = str_replace(
            ['ä', 'ö', 'ü', 'ß', 'é', 'è', 'ê', 'á', 'à'],
            ['ae', 'oe', 'ue', 'ss', 'e', 'e', 'e', 'a', 'a'],
            $slug
        );
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim($slug, '-');
    }
}
