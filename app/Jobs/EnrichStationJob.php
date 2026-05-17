<?php

namespace App\Jobs;

use App\Models\Station;
use App\Services\BenzinpreisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnrichStationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;
    public int $backoff = 60;

    /**
     * Diese Felder werden niemals überschrieben — sensible oder manuell gepflegte Daten.
     */
    private const PROTECTED_FIELDS = ['iban', 'bic', 'opening_hours', 'is_active', 'station_number'];

    public function __construct(
        private readonly Station $station,
        private readonly bool $onlyIfEmpty = false,
    ) {}

    public function handle(BenzinpreisService $service): void
    {
        if ($this->onlyIfEmpty && $this->station->enriched_at !== null) {
            return;
        }

        $data = $service->enrichStation($this->station);

        if (! $data) {
            return;
        }

        // Geschützte Felder niemals überschreiben
        foreach (self::PROTECTED_FIELDS as $field) {
            unset($data[$field]);
        }

        if (empty($data)) {
            return;
        }

        $data['enriched_at'] = now();

        $this->station->updateQuietly($data);
    }
}
