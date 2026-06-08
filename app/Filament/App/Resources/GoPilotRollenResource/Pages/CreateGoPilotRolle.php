<?php

namespace App\Filament\App\Resources\GoPilotRollenResource\Pages;

use App\Filament\App\Resources\GoPilotRollenResource;
use App\Support\RolePermissions;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CreateGoPilotRolle extends CreateRecord
{
    protected static string $resource = GoPilotRollenResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Vor dem Speichern: tenant_id + guard_name + scope setzen.
     * Die CheckboxList-Felder (perms_*) werden in afterCreate() verarbeitet.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenantId = (int) session('tenant_id', 0);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        return [
            'name'       => $data['name'],
            'guard_name' => 'web',
            'tenant_id'  => $tenantId,
            'scope'      => RolePermissions::SCOPE_GOPILOT,
        ];
    }

    protected function afterCreate(): void
    {
        // scope sicher setzen (falls nicht mass-assignable)
        if ($this->record->scope !== RolePermissions::SCOPE_GOPILOT) {
            $this->record->scope = RolePermissions::SCOPE_GOPILOT;
            $this->record->save();
        }

        $this->syncPermissions();
    }

    protected function syncPermissions(): void
    {
        $tenantId = (int) session('tenant_id', 0);
        $rawState = $this->form->getRawState();

        $selected = [];
        foreach (GoPilotRollenResource::permissionGroups() as $fieldName => $group) {
            $selected = array_merge($selected, $rawState[$fieldName] ?? []);
        }

        // Permissions global sicherstellen (team_id = 0)
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
