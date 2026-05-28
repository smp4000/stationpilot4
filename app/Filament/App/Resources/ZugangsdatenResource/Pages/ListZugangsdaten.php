<?php
namespace App\Filament\App\Resources\ZugangsdatenResource\Pages;

use App\Filament\App\Resources\ZugangsdatenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListZugangsdaten extends ListRecords
{
    protected static string $resource = ZugangsdatenResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Zugangsdaten anlegen')];
    }
}
