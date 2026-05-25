<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationFuelPrice extends Model
{
    protected $table = 'station_fuel_prices';

    protected $fillable = [
        'station_id',
        'e5',
        'e10',
        'diesel',
        'lpg',
        'source',
        'recorded_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'e5'          => 'decimal:3',
            'e10'         => 'decimal:3',
            'diesel'      => 'decimal:3',
            'lpg'         => 'decimal:3',
            'recorded_at' => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────
    // Beziehungen
    // ─────────────────────────────────────────────

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    // ─────────────────────────────────────────────
    // Hilfsmethoden
    // ─────────────────────────────────────────────

    public static function sourceOptions(): array
    {
        return [
            'manual'  => 'Manuell',
            'scraper' => 'Scraper',
            'api'     => 'API',
            'import'  => 'Import',
        ];
    }
}
