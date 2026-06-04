<?php

namespace App\Filament\App\Resources\StationResource\Pages;

use App\Filament\App\Resources\StationResource;
use App\Filament\App\Resources\StationResource\RelationManagers\CompetitorsRelationManager;
use App\Filament\App\Resources\StationResource\RelationManagers\FuelPricesRelationManager;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStation extends EditRecord
{
    protected static string $resource = StationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // GoPilot QR-Code — Gerät mit dieser Station verbinden
            Action::make('gopilot_qr')
                ->label('GoPilot QR')
                ->icon('heroicon-o-qr-code')
                ->color('primary')
                ->modalHeading(fn () => 'GoPilot Einrichtungs-QR — ' . $this->record->name)
                ->modalDescription('Diesen QR-Code in der GoPilot-App scannen, um ein MDE-Gerät mit dieser Tankstelle zu verbinden.')
                ->modalWidth('sm')
                ->modalContent(function () {
                    $url = route('mde.station.qr', $this->record->ulid);
                    return view('filament.mde.station-qr-modal', [
                        'qrUrl'       => $url,
                        'stationUlid' => $this->record->ulid,
                        'stationName' => $this->record->name,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Schließen'),

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
