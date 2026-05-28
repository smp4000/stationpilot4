<?php
namespace App\Filament\App\Resources\CredentialTypeResource\Pages;

use App\Filament\App\Resources\CredentialTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCredentialType extends CreateRecord
{
    protected static string $resource = CredentialTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        return $data;
    }
}
