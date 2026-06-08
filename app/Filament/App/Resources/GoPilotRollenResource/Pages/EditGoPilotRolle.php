<?php

namespace App\Filament\App\Resources\GoPilotRollenResource\Pages;

use App\Filament\App\Resources\GoPilotRollenResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\PermissionRegistrar;

class EditGoPilotRolle extends EditRecord
{
    protected static string $resource = GoPilotRollenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (): bool => GoPilotRollenResource::isBuiltIn($this->record->name)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /** Beim Laden: aktuelle Permissions als CheckboxList-Werte vorbelegen. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $tenantId = (int) session('tenant_id', 0);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        $current = $this->record->permissions->pluck('name')->toArray();

        foreach (GoPilotRollenResource::permissionGroups() as $fieldName => $group) {
            $data[$fieldName] = array_values(
                array_filter($current, fn (string $p): bool => isset($group['perms'][$p]))
            );
        }

        return $data;
    }

    /** Verhindert, dass perms_*-Felder ans Model übergeben werden. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (GoPilotRollenResource::permissionGroups() as $fieldName => $group) {
            unset($data[$fieldName]);
        }
        unset($data['tenant_id'], $data['guard_name'], $data['scope']);
        return $data;
    }

    /** Nach dem Speichern: Permissions synchronisieren. */
    protected function afterSave(): void
    {
        $tenantId = (int) session('tenant_id', 0);
        $rawState = $this->form->getRawState();

        $selected = [];
        foreach (GoPilotRollenResource::permissionGroups() as $fieldName => $group) {
            $selected = array_merge($selected, $rawState[$fieldName] ?? []);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        $this->record->syncPermissions($selected);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
