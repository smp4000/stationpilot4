<?php

namespace App\Filament\App\Resources\EmployeeResource\Pages;

use App\Filament\App\Resources\EmployeeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RestoreAction::make()
                ->visible(fn (): bool => (bool) $this->record?->deleted_at),
            DeleteAction::make()
                ->hidden(fn (): bool => (bool) $this->record?->deleted_at),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // MDE-PIN nicht überschreiben wenn leer gelassen
        if (empty($data['mde_pin'])) {
            unset($data['mde_pin']);
        }

        // user_id darf NUR über die app_zugang-Actions geändert werden
        unset($data['user_id']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
