<?php

namespace App\Filament\Admin\Resources\TenantResource\Pages;

use App\Filament\Admin\Resources\TenantResource;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /**
     * Nach dem Anlegen des Mandanten automatisch alle 5 Tenant-Rollen erstellen.
     * Ohne das können sich Partner nicht einloggen.
     */
    protected function afterCreate(): void
    {
        RolesAndPermissionsSeeder::createTenantRoles($this->record->id);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
