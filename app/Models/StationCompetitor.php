<?php

namespace App\Models;

use App\Models\BenzinpreisCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationCompetitor extends Model
{
    protected $table = 'station_competitors';

    protected $fillable = [
        'station_id', 'tenant_id', 'comp_station_id',
        'name', 'brand', 'street', 'city', 'zip',
        'lat', 'lng', 'distance_km',
        'osm_id', 'benzinpreis_hash', 'benzinpreis_slug',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'lat'         => 'decimal:8',
            'lng'         => 'decimal:8',
            'distance_km' => 'decimal:1',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function compStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'comp_station_id');
    }

    public function benzinpreisCache(): BelongsTo
    {
        return $this->belongsTo(BenzinpreisCache::class, 'benzinpreis_hash', 'hash');
    }
}
