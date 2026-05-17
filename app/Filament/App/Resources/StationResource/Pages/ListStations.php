<?php

namespace App\Filament\App\Resources\StationResource\Pages;

use App\Filament\App\Resources\StationResource;
use App\Filament\App\Resources\StationResource\Widgets\StationMapWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStations extends ListRecords
{
    protected static string $resource = StationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn() => auth()->user()?->can('partner.stations.create')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [StationMapWidget::class];
    }
}
