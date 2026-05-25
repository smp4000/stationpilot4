<?php

namespace App\Filament\Admin\Resources\FuelTypeResource\Pages;

use App\Filament\Admin\Resources\FuelTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFuelTypes extends ListRecords
{
    protected static string $resource = FuelTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
