<?php

namespace App\Filament\App\Resources\RollenResource\Pages;

use App\Filament\App\Resources\RollenResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\PermissionRegistrar;

class EditRolle extends EditRecord
{
    protected static string $resource = RollenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (): bool => RollenResource::isBuiltIn($this->record->name)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Beim Laden: aktuelle Permissions der Rolle als CheckboxList-Werte vorbelegen.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $tenantId = (int) session('tenant_id', 0);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        $current = $this->record->permissions->pluck('name')->toArray();

        foreach (RollenResource::permissionGroups() as $fieldName => $group) {
            $data[$fieldName] = array_values(
                array_filter($current, fn (string $p): bool => isset($group['perms'][$p])
            ));
        }

        return $data;
    }

    /**
     * Verhindert, dass die perms_*-Felder an das Eloquent-Model übergeben werden.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (RollenResource::permissionGroups() as $fieldName => $group) {
            unset($data[$fieldName]);
        }
        // guard_name schützen
        unset($data['tenant_id'], $data['guard_name']);
        return $data;
    }

    /** Nach dem Speichern: Permissions aus Formular synchronisieren. */
    protected function afterSave(): void
    {
        $tenantId = (int) session('tenant_id', 0);
        $rawState = $this->form->getRawState();

        $selected = [];
        foreach (RollenResource::permissionGroups() as $fieldName => $group) {
            $selected = array_merge($selected, $rawState[$fieldName] ?? []);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        $this->record->syncPermissions($selected);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
