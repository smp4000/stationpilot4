<?php

namespace App\Filament\Admin\Resources\FuelTypeResource\Pages;

use App\Filament\Admin\Resources\FuelTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFuelType extends EditRecord
{
    protected static string $resource = FuelTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
