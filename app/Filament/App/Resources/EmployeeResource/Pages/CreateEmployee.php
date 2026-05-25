<?php

namespace App\Filament\App\Resources\EmployeeResource\Pages;

use App\Filament\App\Resources\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = session('tenant_id');

        // MDE-PIN nur setzen wenn ausgefüllt
        if (empty($data['mde_pin'])) {
            unset($data['mde_pin']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
