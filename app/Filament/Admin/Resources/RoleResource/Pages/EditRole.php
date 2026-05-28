<?php

namespace App\Filament\Admin\Resources\RoleResource\Pages;

use App\Filament\Admin\Resources\RoleResource;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\PermissionRegistrar;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (): bool => in_array(
                    $this->record->name,
                    ['super_admin_level_1', 'super_admin_level_2', 'super_admin_level_3']
                )),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(RolesAndPermissionsSeeder::GLOBAL_TEAM_ID);

        $current = $this->record->permissions->pluck('name')->toArray();

        foreach (RoleResource::permissionGroups() as $fieldName => $group) {
            $data[$fieldName] = array_values(
                array_filter($current, fn (string $p): bool => isset($group['perms'][$p]))
            );
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (RoleResource::permissionGroups() as $fieldName => $group) {
            unset($data[$fieldName]);
        }
        unset($data['tenant_id'], $data['guard_name']);
        return $data;
    }

    protected function afterSave(): void
    {
        $rawState = $this->form->getRawState();

        $selected = [];
        foreach (RoleResource::permissionGroups() as $fieldName => $group) {
            $selected = array_merge($selected, $rawState[$fieldName] ?? []);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(RolesAndPermissionsSeeder::GLOBAL_TEAM_ID);
        $this->record->syncPermissions($selected);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
