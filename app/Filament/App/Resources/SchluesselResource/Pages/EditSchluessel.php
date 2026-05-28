<?php
namespace App\Filament\App\Resources\SchluesselResource\Pages;

use App\Filament\App\Resources\SchluesselResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchluessel extends EditRecord
{
    protected static string $resource = SchluesselResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
