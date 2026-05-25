<?php

namespace App\Filament\App\Resources\EmployeeResource\Pages;

use App\Filament\App\Resources\EmployeeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            RestoreAction::make()
                ->visible(fn (): bool => (bool) $this->record?->deleted_at),
            DeleteAction::make()
                ->hidden(fn (): bool => (bool) $this->record?->deleted_at),
        ];
    }
}
