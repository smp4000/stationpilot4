<?php

namespace App\Filament\App\Resources\StationResource\Widgets;

use App\Models\Station;
use Filament\Widgets\Widget;

class StationMapWidget extends Widget
{
    protected string $view = 'filament.app.widgets.station-map';

    protected int|string|array $columnSpan = 'full';

    public function getStations(): \Illuminate\Support\Collection
    {
        return Station::with('brand')
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'ulid', 'brand_id', 'name', 'street', 'house_number', 'zip', 'city', 'latitude', 'longitude'])
            ->map(fn ($s) => array_merge($s->toArray(), [
                'brand' => $s->brand?->name,
                'lat'   => $s->latitude,
                'lng'   => $s->longitude,
            ]));
    }
}
