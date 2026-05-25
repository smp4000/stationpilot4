<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Scraper for benzinpreis-aktuell.de.
 * Provides low-level HTML fetching and parsing.
 * Use BenzinpreisService for higher-level operations.
 */
class BenzinpreisParser
{
    public const BP_BASE    = 'https://www.benzinpreis-aktuell.de/';
    public const SCRAPER_UA = 'Mozilla/5.0 (compatible; StationPilot/4.0)';

    // ─────────────────────────────────────────────
    // HTTP helper
    // ─────────────────────────────────────────────

    public function fetchUrl(string $url, int $timeout = 10): ?string
    {
        try {
            $response = Http::withoutVerifying()->withHeaders([
                'User-Agent'      => self::SCRAPER_UA,
                'Accept'          => 'text/html,application/xhtml+xml,application/json',
                'Accept-Language' => 'de-DE,de;q=0.9',
            ])->timeout($timeout)->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning('BenzinpreisParser: HTTP ' . $response->status() . ' for ' . $url);
            return null;
        } catch (\Throwable $e) {
            Log::warning('BenzinpreisParser: ' . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────
    // Station page
    // ─────────────────────────────────────────────

    /**
     * Fetch station page and return parsed data including mts_uuid.
     *
     * @return array{name: string, mts_uuid: ?string, prices: array}|null
     */
    public function fetchStation(string $hash, string $slug): ?array
    {
        $url  = self::BP_BASE . "preise-t{$hash}-{$slug}";
        $html = $this->fetchUrl($url);

        if (! $html) {
            return null;
        }

        return $this->parseStationPage($html);
    }

    /**
     * Parse a station HTML page.
     *
     * @return array{name: string, mts_uuid: ?string, prices: array<string, string>}
     */
    public function parseStationPage(string $html): array
    {
        $result = [
            'name'     => '',
            'mts_uuid' => null,
            'prices'   => [],
        ];

        // Extract MTS UUID used by the JSON price API
        if (preg_match('/["\']mts[_-]uuid["\']?\s*[=:]\s*["\']([a-f0-9\-]{36})["\']/', $html, $m)) {
            $result['mts_uuid'] = $m[1];
        }
        // Alternative: data attribute
        if (! $result['mts_uuid'] && preg_match('/data-mts-uuid=["\']([a-f0-9\-]{36})["\']/', $html, $m)) {
            $result['mts_uuid'] = $m[1];
        }
        // Alternative: JS variable
        if (! $result['mts_uuid'] && preg_match('/mtsUuid\s*=\s*["\']([a-f0-9\-]{36})["\']/', $html, $m)) {
            $result['mts_uuid'] = $m[1];
        }

        // Extract title / name — strip trailing SEO parts like ": Aktuelle..." or "| ..."
        if (preg_match('/<title>\s*([^|<]+)/i', $html, $tm)) {
            $raw  = trim(html_entity_decode($tm[1], ENT_QUOTES, 'UTF-8'));
            // Remove ": Aktuelle Spritpreise..." and similar SEO suffixes
            $raw  = preg_replace('/\s*[:–-]\s*(Aktuelle|Günstige|Benzin|Diesel|Sprit|Kraft).*/iu', '', $raw);
            $result['name'] = trim($raw);
        }

        // Extract prices from HTML blocks like: preis25 preis_benzin / preis_e10 / preis_diesel
        if (preg_match_all('/<div[^>]*class="[^"]*preis25[^"]*preis_(\w+)[^"]*"[^>]*>\s*<em>([\d,]+)<sup>(\d)<\/sup><\/em>/si', $html, $pm, PREG_SET_ORDER)) {
            foreach ($pm as $p) {
                $key = strtolower(trim($p[1]));
                $val = str_replace(',', '.', $p[2]) . $p[3];
                $result['prices'][$key] = $val;
            }
        }

        return $result;
    }

    // ─────────────────────────────────────────────
    // JSON price API
    // ─────────────────────────────────────────────

    /**
     * Fetch current prices via the MTS JSON API.
     *
     * @return array{e5: ?float, e10: ?float, diesel: ?float}|null
     */
    public function fetchPriceByApi(string $mtsUuid): ?array
    {
        $url  = self::BP_BASE . "api/prices/{$mtsUuid}";
        $html = $this->fetchUrl($url);

        if (! $html) {
            return null;
        }

        $data = json_decode($html, true);
        if (! is_array($data)) {
            return null;
        }

        $prices = $data['prices'] ?? $data;

        return [
            'e5'     => isset($prices['e5'])     ? (float) $prices['e5']     : null,
            'e10'    => isset($prices['e10'])     ? (float) $prices['e10']    : null,
            'diesel' => isset($prices['diesel'])  ? (float) $prices['diesel'] : null,
        ];
    }

    /**
     * Scrape prices directly from the station HTML page (fallback).
     *
     * @return array{e5: ?float, e10: ?float, diesel: ?float}|null
     */
    public function fetchPriceByHtml(string $hash, string $slug): ?array
    {
        $data = $this->fetchStation($hash, $slug);

        if (! $data || empty($data['prices'])) {
            return null;
        }

        $p = $data['prices'];

        return [
            'e5'     => isset($p['benzin']) ? (float) $p['benzin'] : (isset($p['e5'])     ? (float) $p['e5']     : null),
            'e10'    => isset($p['e10'])     ? (float) $p['e10']   : null,
            'diesel' => isset($p['diesel'])  ? (float) $p['diesel'] : null,
        ];
    }

    // ─────────────────────────────────────────────
    // City station list
    // ─────────────────────────────────────────────

    /**
     * Fetch a list of stations in a city.
     *
     * @return array<int, array{hash: string, slug: string, name: string}>
     */
    public function fetchCityStations(string $city): array
    {
        $slug = $this->cityToSlug($city);
        $url  = self::BP_BASE . "tankstellen/{$slug}";
        $html = $this->fetchUrl($url, 15);

        if (! $html) {
            return [];
        }

        $stations = [];

        if (preg_match_all(
            '/href="[^"]*preise-t([a-f0-9]+)-([^"\/]+)"/i',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $m) {
                $hash = $m[1];
                $slug = rtrim($m[2], '/');
                if (! isset($stations[$hash])) {
                    $stations[$hash] = [
                        'hash' => $hash,
                        'slug' => $slug,
                        'name' => $slug,
                    ];
                }
            }
        }

        return array_values($stations);
    }

    // ─────────────────────────────────────────────
    // Neighbor extraction (from station page HTML)
    // ─────────────────────────────────────────────

    /**
     * Extract neighboring station links from a station page.
     * Used by BenzinpreisService::discoverNeighbors() for multi-level crawling.
     *
     * @return array<string, array{slug: string, name: string}>  keyed by hash
     */
    public function extractNeighbors(string $html): array
    {
        $neighbors = [];

        // Try to capture link text (station name) alongside hash+slug
        if (preg_match_all(
            '/<a[^>]+href="[^"]*preise-t([a-f0-9]+)-([^"\/\s]+)"[^>]*>\s*([^<]*?)\s*<\/a>/i',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $m) {
                $hash = $m[1];
                $slug = rtrim($m[2], '/');
                $name = trim(html_entity_decode($m[3], ENT_QUOTES, 'UTF-8'));

                if ($hash && $slug && ! isset($neighbors[$hash])) {
                    $neighbors[$hash] = ['slug' => $slug, 'name' => $name];
                }
            }
        }

        // Fallback: plain href without link text
        if (empty($neighbors) && preg_match_all(
            '/href="[^"]*preise-t([a-f0-9]+)-([^"\/\s]+)/i',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $m) {
                $hash = $m[1];
                $slug = rtrim($m[2], '/');
                if ($hash && $slug && ! isset($neighbors[$hash])) {
                    $neighbors[$hash] = ['slug' => $slug, 'name' => ''];
                }
            }
        }

        return $neighbors;
    }

    // ─────────────────────────────────────────────
    // Slug helper
    // ─────────────────────────────────────────────

    public function cityToSlug(string $city): string
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
