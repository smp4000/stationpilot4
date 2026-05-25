<?php

namespace App\Filament\App\Resources\StationResource\Pages;

use App\Filament\App\Resources\StationResource;
use App\Filament\App\Resources\StationResource\RelationManagers\CompetitorsRelationManager;
use App\Filament\App\Resources\StationResource\RelationManagers\FuelPricesRelationManager;
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

    public function getRelationManagers(): array
    {
        return [
            CompetitorsRelationManager::class,
            FuelPricesRelationManager::class,
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove legacy JSON competitor fields — competitors are now stored in station_competitors table
        unset(
            $data['competitors'],
            $data['_comp_search_zip'],
            $data['_comp_osm_json'],
            $data['_comp_selected']
        );

        return $data;
    }
}
