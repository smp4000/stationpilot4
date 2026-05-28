<?php

namespace App\Filament\App\Resources\RollenResource\Pages;

use App\Filament\App\Resources\RollenResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CreateRolle extends CreateRecord
{
    protected static string $resource = RollenResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Bevor gespeichert wird: tenant_id setzen + guard_name erzwingen.
     * Die CheckboxList-Felder (perms_*) werden NICHT an das Model übergeben,
     * sondern in afterCreate() verarbeitet.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenantId = (int) session('tenant_id', 0);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        return [
            'name'       => $data['name'],
            'guard_name' => 'web',
            'tenant_id'  => $tenantId,
        ];
    }

    /** Nach dem Anlegen: Permissions aus den CheckboxList-Feldern synchronisieren. */
    protected function afterCreate(): void
    {
        $this->syncPermissions();
    }

    protected function syncPermissions(): void
    {
        $tenantId = (int) session('tenant_id', 0);
        $rawState = $this->form->getRawState();

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        $selected = [];
        foreach (RollenResource::permissionGroups() as $fieldName => $group) {
            $selected = array_merge($selected, $rawState[$fieldName] ?? []);
        }

        // Permissions sicherstellen (werden global unter team_id=0 angelegt)
        app(PermissionRegistrar::class)->setPermissionsTeamId(0);
        foreach ($selected as $permName) {
            Permission::findOrCreate($permName, 'web');
        }

        // Rolle mit Permissions verknüpfen
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        $this->record->syncPermissions($selected);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
