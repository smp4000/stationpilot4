<?php

namespace App\Filament\App\Resources\StationResource\Pages;

use App\Filament\App\Resources\StationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStation extends ViewRecord
{
    protected static string $resource = StationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn() => auth()->user()?->can('partner.stations.edit')),
        ];
    }
}
