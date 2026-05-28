<?php

namespace App\Filament\App\Resources\DocumentTemplateResource\Pages;

use App\Filament\App\Resources\DocumentTemplateResource;
use Filament\Resources\Pages\EditRecord;

class EditDocumentTemplate extends EditRecord
{
    protected static string $resource = DocumentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Vorlage gespeichert';
    }
}
