<?php
namespace App\Filament\App\Resources\CredentialTypeResource\Pages;

use App\Filament\App\Resources\CredentialTypeResource;
use App\Models\CredentialType;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCredentialTypes extends ListRecords
{
    protected static string $resource = CredentialTypeResource::class;

    public function mount(): void
    {
        parent::mount();
        // Standardtypen automatisch anlegen wenn noch keine vorhanden
        CredentialType::ensureDefaults(auth()->user()->tenant_id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Typ hinzufügen'),
        ];
    }
}
