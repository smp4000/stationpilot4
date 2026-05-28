<?php

namespace App\Filament\Admin\Resources\RoleResource\Pages;

use App\Filament\Admin\Resources\RoleResource;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(RolesAndPermissionsSeeder::GLOBAL_TEAM_ID);

        return [
            'name'       => $data['name'],
            'guard_name' => 'web',
            'tenant_id'  => RolesAndPermissionsSeeder::GLOBAL_TEAM_ID,
        ];
    }

    protected function afterCreate(): void
    {
        $rawState = $this->form->getRawState();

        $selected = [];
        foreach (RoleResource::permissionGroups() as $fieldName => $group) {
            $selected = array_merge($selected, $rawState[$fieldName] ?? []);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(RolesAndPermissionsSeeder::GLOBAL_TEAM_ID);

        foreach ($selected as $permName) {
            Permission::findOrCreate($permName, 'web');
        }

        $this->record->syncPermissions($selected);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
