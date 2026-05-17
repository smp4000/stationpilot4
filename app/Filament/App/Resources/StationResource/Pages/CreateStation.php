<?php

namespace App\Filament\App\Resources\StationResource\Pages;

use App\Filament\App\Resources\StationResource;
use App\Models\Station;
use Filament\Resources\Pages\CreateRecord;

class CreateStation extends CreateRecord
{
    protected static string $resource = StationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['opening_hours'])) {
            $data['opening_hours'] = Station::defaultOpeningHours();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
