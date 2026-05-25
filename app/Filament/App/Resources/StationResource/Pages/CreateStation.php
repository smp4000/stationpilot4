<?php

namespace App\Filament\App\Resources\StationResource\Pages;

use App\Filament\App\Resources\StationResource;
use App\Models\Station;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class CreateStation extends CreateRecord
{
    protected static string $resource = StationResource::class;

    // ─────────────────────────────────────────────
    // Wizard-Formular (2 Steps)
    // ─────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([

                Step::make('Stationssuche')
                    ->icon('heroicon-o-magnifying-glass')
                    ->description('Bestehende Tankstelle per PLZ suchen und importieren')
                    ->schema(StationResource::osmSearchSchema()),

                Step::make('Stationsdaten')
                    ->icon('heroicon-o-building-storefront')
                    ->description('Alle Daten der Tankstelle erfassen oder prüfen')
                    ->schema([
                        Tabs::make('tabs')
                            ->tabs(StationResource::stationDataTabs())
                            ->columnSpanFull(),
                    ]),

            ])->columnSpanFull(),
        ]);
    }

    // ─────────────────────────────────────────────
    // Vor dem Speichern: temp. Felder entfernen
    // ─────────────────────────────────────────────

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset(
            $data['_search_zip'],
            $data['_selected_station'],
            $data['_search_results'],
            $data['_osm_data_json'],
            $data['_backup_opening_hours'],
            $data['competitors'],
            $data['_comp_search_zip'],
            $data['_comp_osm_json'],
            $data['_comp_selected']
        );

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
