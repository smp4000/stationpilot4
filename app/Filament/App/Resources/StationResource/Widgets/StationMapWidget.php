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
        return Station::where('is_active', true)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get(['id', 'ulid', 'name', 'brand', 'street', 'house_number', 'zip', 'city', 'lat', 'lng']);
    }
}
