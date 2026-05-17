<?php

namespace App\Services;

use App\Models\Station;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BenzinpreisService
{
    private const BASE_URL       = 'https://www.benzinpreis.de';
    private const SEARCH_PATH    = '/stationen/plz/';
    private const USER_AGENT     = 'Stationpilot/4.0 (contact@stationpilot.de)';
    private const REQUEST_TIMEOUT = 10;

    private const BRAND_MAP = [
        'aral'      => 'Aral',
        'shell'     => 'Shell',
        'bp'        => 'BP',
        'esso'      => 'Esso',
        'total'     => 'Total',
        'jet'       => 'Jet',
        'agip'      => 'Agip',
        'eni'       => 'Agip',
        'westfalen' => 'Westfalen',
        'hem'       => 'HEM',
    ];

    /**
     * Sucht Tankstellen anhand der PLZ auf benzinpreis.de.
     *
     * @param  string  $zip  Deutsche Postleitzahl (5 Ziffern)
     * @return array<int, array{name: string, slug: string, street: string, city: string, brand: string|null}>
     */
    public function searchByZip(string $zip): array
    {
        if (! preg_match('/^\d{5}$/', $zip)) {
            return [];
        }

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(self::REQUEST_TIMEOUT)
                ->get(self::BASE_URL . self::SEARCH_PATH . $zip . '/');

            if (! $response->successful()) {
                return [];
            }

            return $this->parseStationList($response->body());
        } catch (\Throwable $e) {
            Log::warning('BenzinpreisService::searchByZip failed', ['zip' => $zip, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Lädt Detaildaten für eine Station anhand ihres Slugs.
     *
     * @return array{name: string, street: string, house_number: string, zip: string, city: string, brand: string|null}|null
     */
    public function enrichStation(Station $station): ?array
    {
        $slug = $station->benzinpreis_slug;

        if (! $slug) {
            return null;
        }

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(self::REQUEST_TIMEOUT)
                ->get(self::BASE_URL . '/station/' . $slug . '/');

            if (! $response->successful()) {
                return null;
            }

            return $this->parseStationDetail($response->body());
        } catch (\Throwable $e) {
            Log::warning('BenzinpreisService::enrichStation failed', ['slug' => $slug, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Prüft ob benzinpreis.de erreichbar ist.
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(5)
                ->get(self::BASE_URL . '/');

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Parsed die Stationsliste aus dem HTML der Suchergebnisseite.
     *
     * @return array<int, array{name: string, slug: string, street: string, city: string, brand: string|null}>
     */
    protected function parseStationList(string $html): array
    {
        if (empty($html)) {
            return [];
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">' . $html, \LIBXML_NOERROR | \LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        $stations = [];
        $seen     = [];

        // Station items: <div class="station-item"> with <a href="/station/{slug}/">
        $nodes = $xpath->query('//div[contains(@class,"station-item")]//a[contains(@href,"/station/")]');

        if ($nodes === false || $nodes->length === 0) {
            // Fallback: alle Links zu /station/
            $nodes = $xpath->query('//a[contains(@href,"/station/")]');
        }

        if ($nodes === false) {
            return [];
        }

        foreach ($nodes as $node) {
            /** @var \DOMElement $node */
            $href = $node->getAttribute('href');
            $slug = $this->extractSlugFromHref($href);

            if (! $slug || isset($seen[$slug])) {
                continue;
            }

            $seen[$slug] = true;

            $nameNode    = $xpath->query('.//span[contains(@class,"station-name")]|.//h3|.//strong', $node)->item(0);
            $addressNode = $xpath->query('.//span[contains(@class,"station-address")]|.//address|.//p[contains(@class,"address")]', $node)->item(0);

            $name    = $nameNode    ? trim($nameNode->textContent)    : trim($node->textContent);
            $address = $addressNode ? trim($addressNode->textContent) : '';

            $parsed = $this->parseAddress($address);

            $stations[] = [
                'name'   => $name,
                'slug'   => $slug,
                'street' => $parsed['street'] ?? '',
                'city'   => $parsed['city'] ?? '',
                'brand'  => $this->guessBrandFromName($name),
            ];
        }

        return $stations;
    }

    /**
     * Parsed die Detailseite einer einzelnen Station.
     *
     * @return array{name: string, street: string, house_number: string, zip: string, city: string, brand: string|null}|null
     */
    protected function parseStationDetail(string $html): ?array
    {
        if (empty($html)) {
            return null;
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">' . $html, \LIBXML_NOERROR | \LIBXML_NOWARNING);
        $xpath = new \DOMXPath($doc);

        $nameNode = $xpath->query('//*[contains(@class,"station-name")]|//h1')->item(0);
        if (! $nameNode) {
            return null;
        }

        $name = trim($nameNode->textContent);

        $addressNode = $xpath->query('//*[contains(@class,"station-address")]|//address')->item(0);
        $address     = $addressNode ? trim($addressNode->textContent) : '';

        $parsed = $this->parseAddress($address);

        return [
            'name'         => $name,
            'street'       => $parsed['street'] ?? '',
            'house_number' => $parsed['house_number'] ?? '',
            'zip'          => $parsed['zip'] ?? '',
            'city'         => $parsed['city'] ?? '',
            'brand'        => $this->guessBrandFromName($name),
        ];
    }

    /**
     * Parst eine Adresszeile im Format "Straße Hausnr, PLZ Stadt".
     *
     * @return array{street: string, house_number: string, zip: string, city: string}
     */
    protected function parseAddress(string $address): array
    {
        $result = ['street' => '', 'house_number' => '', 'zip' => '', 'city' => ''];

        $address = preg_replace('/\s+/', ' ', trim($address)) ?? '';

        // Format: "Musterstraße 1, 36093 Fulda" oder "Musterstraße 1\n36093 Fulda"
        $parts = preg_split('/[,\n]/', $address, 2);

        if (count($parts) >= 2) {
            // Teil 1: Straße und Hausnummer
            $streetPart = trim($parts[0]);
            // Hausnummer ist die letzte Zahl (evtl. mit Buchstabe) am Ende
            if (preg_match('/^(.+?)\s+(\d+\s*[a-zA-Z]?)$/', $streetPart, $m)) {
                $result['street']       = trim($m[1]);
                $result['house_number'] = trim($m[2]);
            } else {
                $result['street'] = $streetPart;
            }

            // Teil 2: PLZ und Stadt
            $cityPart = trim($parts[1]);
            if (preg_match('/^(\d{5})\s+(.+)$/', $cityPart, $m)) {
                $result['zip']  = $m[1];
                $result['city'] = trim($m[2]);
            }
        } elseif (preg_match('/(\d{5})\s+(\S+)/', $address, $m)) {
            $result['zip']  = $m[1];
            $result['city'] = $m[2];
        }

        return $result;
    }

    /**
     * Versucht die Marke aus dem Stationsnamen zu ermitteln.
     */
    protected function guessBrandFromName(string $name): ?string
    {
        $lower = strtolower($name);

        foreach (self::BRAND_MAP as $keyword => $brand) {
            if (str_contains($lower, $keyword)) {
                return $brand;
            }
        }

        return null;
    }

    private function extractSlugFromHref(string $href): ?string
    {
        if (preg_match('#/station/([^/]+)/#', $href, $m)) {
            return $m[1];
        }
        return null;
    }
}
