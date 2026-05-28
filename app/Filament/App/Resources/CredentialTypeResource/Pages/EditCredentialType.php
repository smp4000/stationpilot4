<?php
namespace App\Filament\App\Resources\CredentialTypeResource\Pages;

use App\Filament\App\Resources\CredentialTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCredentialType extends EditRecord
{
    protected static string $resource = CredentialTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
