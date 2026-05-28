<?php
namespace App\Filament\App\Resources\ZugangsdatenResource\Pages;

use App\Filament\App\Resources\ZugangsdatenResource;
use Filament\Resources\Pages\CreateRecord;

class CreateZugangsdaten extends CreateRecord
{
    protected static string $resource = ZugangsdatenResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        return $data;
    }
}
