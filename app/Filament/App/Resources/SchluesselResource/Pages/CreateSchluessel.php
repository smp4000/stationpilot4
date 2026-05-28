<?php
namespace App\Filament\App\Resources\SchluesselResource\Pages;

use App\Filament\App\Resources\SchluesselResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchluessel extends CreateRecord
{
    protected static string $resource = SchluesselResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        return $data;
    }
}
