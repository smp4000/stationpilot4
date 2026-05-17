<?php

namespace App\Console\Commands;

use App\Services\BenzinpreisService;
use Illuminate\Console\Command;

class CheckBenzinpreisService extends Command
{
    protected $signature = 'benzinpreis:check
                            {--zip=36093 : PLZ für Testsuche}';

    protected $description = 'Prüft ob benzinpreis.de erreichbar ist und liefert Testergebnisse.';

    public function handle(BenzinpreisService $service): int
    {
        $zip = $this->option('zip');

        if (! $service->isHealthy()) {
            $this->error('benzinpreis.de ist nicht erreichbar.');
            return Command::FAILURE;
        }

        $this->info('benzinpreis.de: erreichbar');

        if (! $this->option('quiet')) {
            $stations = $service->searchByZip($zip);

            if (empty($stations)) {
                $this->warn("Keine Stationen für PLZ {$zip} gefunden.");
            } else {
                $this->table(
                    ['Name', 'Slug', 'Straße', 'Stadt', 'Marke'],
                    array_map(fn($s) => [
                        $s['name'],
                        $s['slug'],
                        $s['street'],
                        $s['city'],
                        $s['brand'] ?? '—',
                    ], $stations)
                );
                $this->info(count($stations) . ' Stationen gefunden.');
            }
        }

        return Command::SUCCESS;
    }
}
