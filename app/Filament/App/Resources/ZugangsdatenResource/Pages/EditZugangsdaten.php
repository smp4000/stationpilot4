<?php
namespace App\Filament\App\Resources\ZugangsdatenResource\Pages;

use App\Filament\App\Resources\ZugangsdatenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditZugangsdaten extends EditRecord
{
    protected static string $resource = ZugangsdatenResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
