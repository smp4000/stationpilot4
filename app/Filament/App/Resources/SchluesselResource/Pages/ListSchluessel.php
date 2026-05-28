<?php
namespace App\Filament\App\Resources\SchluesselResource\Pages;

use App\Filament\App\Resources\SchluesselResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchluessel extends ListRecords
{
    protected static string $resource = SchluesselResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Schlüssel anlegen')];
    }
}
