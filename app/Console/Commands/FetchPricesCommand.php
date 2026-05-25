<?php

namespace App\Console\Commands;

use App\Models\BenzinpreisCache;
use App\Models\Station;
use App\Models\StationCompetitor;
use App\Models\StationFuelPrice;
use App\Services\BenzinpreisParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchPricesCommand extends Command
{
    protected $signature = 'stations:fetch-prices
                            {--station=  : Nur eine eigene Station (ULID/ID)}
                            {--force     : Auch speichern wenn Preis unverändert}
                            {--dry-run   : Preise abrufen aber nicht in DB schreiben}
                            {--no-cache  : Cache überspringen, immer neu abrufen}';

    protected $description = 'Kraftstoffpreise für alle Stationen + Wettbewerber abrufen (dedupliziert via benzinpreis_cache).';

    public function handle(BenzinpreisParser $parser): int
    {
        $dryRun  = (bool) $this->option('dry-run');
        $force   = (bool) $this->option('force');
        $noCache = (bool) $this->option('no-cache');

        // ── 1. Alle einzigartigen Hashes sammeln ────────────────────────────
        // hash => ['slug', 'own_station_ids' => []]
        $hashMap = [];

        $ownQuery = Station::query()
            ->where('is_active', true)
            ->whereNotNull('benzinpreis_hash')
            ->where('benzinpreis_hash', '!=', '');

        if ($stationOpt = $this->option('station')) {
            $ownQuery->where('id', $stationOpt);
        }

        foreach ($ownQuery->get(['id', 'name', 'benzinpreis_hash', 'benzinpreis_slug']) as $station) {
            $hash = $station->benzinpreis_hash;
            if (! isset($hashMap[$hash])) {
                $hashMap[$hash] = [
                    'slug'             => $station->benzinpreis_slug ?? '',
                    'own_station_ids'  => [],
                ];
            }
            $hashMap[$hash]['own_station_ids'][] = $station->id;
        }

        // Competitor hashes (alle Tenants, dedupliziert)
        if (! $this->option('station')) {
            foreach (StationCompetitor::whereNotNull('benzinpreis_hash')
                         ->where('benzinpreis_hash', '!=', '')
                         ->select('benzinpreis_hash', 'benzinpreis_slug')
                         ->distinct()
                         ->get() as $comp) {
                $hash = $comp->benzinpreis_hash;
                if (! isset($hashMap[$hash])) {
                    $hashMap[$hash] = ['slug' => $comp->benzinpreis_slug ?? '', 'own_station_ids' => []];
                }
            }
        }

        if (empty($hashMap)) {
            $this->warn('Keine Stationen mit benzinpreis_hash gefunden.');
            return Command::FAILURE;
        }

        $total = count($hashMap);
        $ownCount  = count(array_filter($hashMap, fn ($v) => ! empty($v['own_station_ids'])));
        $compCount = $total - $ownCount;

        $this->info("Verarbeite {$total} einzigartige Hashes ({$ownCount} eigene, {$compCount} Wettbewerber-only)...");
        $this->newLine();

        // ── 2. Jeden Hash einmal abrufen ────────────────────────────────────
        $fetched   = 0;
        $skipped   = 0;
        $failed    = 0;
        $changed   = 0;
        $unchanged = 0;
        $rows      = [];
        $start     = microtime(true);
        $now       = now();

        foreach ($hashMap as $hash => $info) {
            $slug = $info['slug'];

            // Cache check (frisch genug wenn < 3h alt und kein --no-cache)
            if (! $noCache && ! $force) {
                $cached = BenzinpreisCache::find($hash);
                if ($cached && $cached->fetched_at && $cached->fetched_at->diffInMinutes(now()) < 180) {
                    $skipped++;
                    $this->writeCacheRow($rows, $hash, $cached, 'cache');
                    $this->syncOwnStations($info['own_station_ids'], $cached, $force, $dryRun, $changed, $unchanged, $now);
                    continue;
                }
            }

            // Stationsseite laden
            $stationData = $parser->fetchStation($hash, $slug);

            if (! $stationData) {
                $failed++;
                $rows[] = [$hash, '—', '—', '—', '❌ nicht erreichbar'];
                Log::warning("FetchPricesCommand: Seite nicht erreichbar hash={$hash}");
                usleep(500_000);
                continue;
            }

            // Preis ermitteln: API → HTML-Fallback
            $prices = null;
            $source = null;

            if ($stationData['mts_uuid']) {
                $api = $parser->fetchPriceByApi($stationData['mts_uuid']);
                if ($api && ($api['e5'] || $api['diesel'])) {
                    $prices = $api;
                    $source = 'api';
                }
                usleep(200_000);
            }

            if (! $prices && ! empty($stationData['prices'])) {
                $p      = $stationData['prices'];
                $prices = [
                    'e5'     => isset($p['benzin']) ? (float) $p['benzin'] : (isset($p['e5'])    ? (float) $p['e5']    : null),
                    'e10'    => isset($p['e10'])    ? (float) $p['e10']   : null,
                    'diesel' => isset($p['diesel']) ? (float) $p['diesel'] : null,
                ];
                $source = 'scraper';
            }

            if (! $prices || (! ($prices['e5'] ?? null) && ! ($prices['diesel'] ?? null))) {
                $failed++;
                $rows[] = [$hash, '—', '—', '—', '❌ kein Preis'];
                usleep(500_000);
                continue;
            }

            $fetched++;

            // ── 3. benzinpreis_cache upsert ───────────────────────────────
            $existing    = BenzinpreisCache::find($hash);
            $priceChange = $this->detectPriceChange($existing, $prices);
            $lastChanged = $priceChange ? $now : ($existing?->last_changed_at);

            if (! $dryRun) {
                BenzinpreisCache::updateOrCreate(
                    ['hash' => $hash],
                    [
                        'slug'            => $slug ?: ($existing?->slug ?? $hash),
                        'mts_uuid'        => $stationData['mts_uuid'] ?? $existing?->mts_uuid,
                        'name'            => $stationData['name'] ?: ($existing?->name),
                        'e5'              => $prices['e5']     ?? null,
                        'e10'             => $prices['e10']    ?? null,
                        'diesel'          => $prices['diesel'] ?? null,
                        'fetched_at'      => $now,
                        'last_changed_at' => $lastChanged,
                    ]
                );
            }

            $cacheEntry = new BenzinpreisCache(array_merge(['hash' => $hash], [
                'e5' => $prices['e5'] ?? null, 'e10' => $prices['e10'] ?? null, 'diesel' => $prices['diesel'] ?? null,
            ]));

            $this->writeCacheRow($rows, $hash, $cacheEntry, ($dryRun ? '[dry] ' : '') . $source);

            // ── 4. Eigene Stationen aktualisieren ─────────────────────────
            $this->syncOwnStations($info['own_station_ids'], $cacheEntry, $force || $priceChange, $dryRun, $changed, $unchanged, $now);

            usleep(400_000);
        }

        $elapsed = round(microtime(true) - $start, 1);

        $this->table(
            ['Hash', 'E5', 'E10', 'Diesel', 'Status'],
            $rows
        );

        $this->newLine();
        $this->line(sprintf(
            '✅  %d neu abgerufen, %d aus Cache, %d fehlgeschlagen | %d eigene Preise neu, %d unverändert | %ss',
            $fetched, $skipped, $failed, $changed, $unchanged, $elapsed
        ));

        if ($dryRun) {
            $this->warn('--dry-run: keine Daten geschrieben.');
        }

        return ($failed > 0 && $fetched === 0) ? Command::FAILURE : Command::SUCCESS;
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    private function syncOwnStations(
        array $stationIds,
        BenzinpreisCache $cache,
        bool $hasChanged,
        bool $dryRun,
        int &$changed,
        int &$unchanged,
        \Carbon\Carbon $now,
    ): void {
        if (empty($stationIds)) return;

        foreach ($stationIds as $stationId) {
            $station = Station::find($stationId);
            if (! $station) continue;

            if ($hasChanged) {
                if (! $dryRun) {
                    StationFuelPrice::create([
                        'station_id'  => $stationId,
                        'e5'          => $cache->e5,
                        'e10'         => $cache->e10,
                        'diesel'      => $cache->diesel,
                        'source'      => 'api',
                        'recorded_at' => $now,
                    ]);
                    $station->updateQuietly([
                        'price_super'       => $cache->e5     ?? $station->price_super,
                        'price_e10'         => $cache->e10    ?? $station->price_e10,
                        'price_diesel'      => $cache->diesel ?? $station->price_diesel,
                        'prices_updated_at' => $now,
                    ]);
                }
                $changed++;
            } else {
                if (! $dryRun) {
                    $station->updateQuietly(['prices_updated_at' => $now]);
                }
                $unchanged++;
            }
        }
    }

    private function detectPriceChange(?BenzinpreisCache $existing, array $newPrices): bool
    {
        if (! $existing) return true;

        foreach (['e5', 'e10', 'diesel'] as $fuel) {
            $oldVal = round((float) ($existing->{$fuel} ?? 0), 3);
            $newVal = round((float) ($newPrices[$fuel] ?? 0), 3);
            if ($newVal > 0 && abs($oldVal - $newVal) > 0.0001) {
                return true;
            }
        }

        return false;
    }

    private function writeCacheRow(array &$rows, string $hash, BenzinpreisCache $c, string $status): void
    {
        $fmt  = fn ($v) => $v ? number_format((float) $v, 3, ',', '.') : '—';
        $rows[] = [
            substr($hash, 0, 8) . '…',
            $fmt($c->e5),
            $fmt($c->e10),
            $fmt($c->diesel),
            $status,
        ];
    }
}
