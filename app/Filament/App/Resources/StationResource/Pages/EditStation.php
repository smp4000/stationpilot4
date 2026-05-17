<?php

namespace App\Filament\App\Resources\StationResource\Pages;

use App\Filament\App\Resources\StationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStation extends EditRecord
{
    protected static string $resource = StationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn() => auth()->user()?->can('partner.stations.delete')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
